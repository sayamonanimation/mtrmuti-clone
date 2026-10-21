/**
 * Form marked data-photo-upload: the photos picked in images[] are sent one
 * per request (3 at a time) to action=upload_photo before the form itself is
 * saved, with a progress bar. Each request stays short, so many photos going
 * to OpenDrive never hit the server's time limit (504). Without JS the form
 * still posts the files directly.
 */
(function () {
    var form = document.querySelector('form[data-photo-upload]');
    if (!form || !window.fetch || !window.FormData) return;
    var input = form.querySelector('input[type=file][name="images[]"]');
    if (!input) return;

    var CONCURRENCY = 3;
    var status = document.createElement('div');
    status.className = 'bulk-status';
    status.hidden = true;
    status.innerHTML = '<div class="bulk-progress"><div class="bulk-progress-bar"></div></div><p class="bulk-summary"></p>';
    form.querySelector('.form-actions').insertAdjacentElement('beforebegin', status);
    var bar = status.querySelector('.bulk-progress-bar'), summary = status.querySelector('.bulk-summary');
    var buttons = form.querySelectorAll('[type=submit]');

    form.addEventListener('submit', function (e) {
        if (!input.files.length) return;          // nothing to send first: save normally
        e.preventDefault();
        e.stopImmediatePropagation();

        var files = Array.prototype.slice.call(input.files);
        var results = new Array(files.length), failed = [], done = 0, next = 0;
        var csrf = form.querySelector('[name=csrf_token]').value;
        status.hidden = false;
        buttons.forEach(function (b) { b.disabled = true; });

        function render() {
            bar.style.width = (done / files.length * 100) + '%';
            summary.textContent = 'กำลังอัปโหลดรูป ' + done + '/' + files.length + (failed.length ? ' — ไม่สำเร็จ ' + failed.length + ' รูป' : '');
        }
        function worker() {
            if (next >= files.length) return Promise.resolve();
            var i = next++;
            var fd = new FormData();
            fd.append('csrf_token', csrf);
            fd.append('action', 'upload_photo');
            fd.append('image', files[i], files[i].name);
            return fetch(location.href, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (res) {
                    return res.json().catch(function () { return { ok: false, error: 'HTTP ' + res.status }; });
                })
                .then(function (d) {
                    if (d.ok) results[i] = d;
                    else failed.push(files[i].name + ' (' + (d.error || 'ไม่สำเร็จ') + ')');
                })
                .catch(function () { failed.push(files[i].name + ' (เชื่อมต่อไม่ได้)'); })
                .then(function () { done++; render(); return worker(); });
        }

        render();
        var workers = [];
        for (var w = 0; w < CONCURRENCY; w++) workers.push(worker());
        Promise.all(workers).then(function () {
            results.forEach(function (r) {
                if (!r) return;
                [['uploaded_photos[]', r.path], ['uploaded_sigs[]', r.sig]].forEach(function (pair) {
                    var h = document.createElement('input');
                    h.type = 'hidden';
                    h.name = pair[0];
                    h.value = pair[1];
                    form.appendChild(h);
                });
            });
            if (failed.length) {
                alert('อัปโหลดไม่สำเร็จ ' + failed.length + ' รูป:\n' + failed.join('\n') + '\n\nระบบจะบันทึกรูปที่สำเร็จไว้ก่อน แล้วเพิ่มรูปที่เหลือได้ภายหลัง');
            }
            summary.textContent = 'อัปโหลดรูปเสร็จ กำลังบันทึก…';
            input.value = '';
            input.removeAttribute('required');
            buttons.forEach(function (b) { b.disabled = false; });
            form._cfmOk = true;                   // already confirmed in the popup
            if (form.requestSubmit) form.requestSubmit();
            else HTMLFormElement.prototype.submit.call(form);
        });
    });
})();
