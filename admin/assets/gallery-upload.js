/**
 * Bulk gallery uploader (admin/gallery.php).
 *
 * Any number of images are queued in the browser and sent one per request,
 * a few at a time, to action=upload_one - so neither PHP's max_file_uploads
 * nor a single long request (OpenDrive transfer) limits how many can be
 * added at once. JPEG photos can be scaled down first to upload faster.
 */
(function () {
    var form = document.getElementById('galleryUploadForm');
    if (!form || !window.fetch || !window.FormData) return; // plain form post still works

    var input = document.getElementById('bulkInput');
    var drop = document.getElementById('bulkDrop');
    var startBtn = document.getElementById('bulkStart');
    var statusBox = document.getElementById('bulkStatus');
    var bar = document.getElementById('bulkBar');
    var summary = document.getElementById('bulkSummary');
    var list = document.getElementById('bulkList');
    var retryBtn = document.getElementById('bulkRetry');
    var doneLink = document.getElementById('bulkDone');
    var resizeBox = document.getElementById('bulkResize');

    var CONCURRENCY = 3;
    var albumId = form.getAttribute('data-album');
    var remaining = parseInt(form.getAttribute('data-remaining') || '0', 10) || Infinity;
    var MAX_SIDE = 2000;
    var queue = [];     // {file, li, state: 'waiting'|'uploading'|'done'|'failed'}
    var picked = [];
    var running = 0;

    function setPicked(files) {
        picked = Array.prototype.filter.call(files, function (f) { return /^image\//.test(f.type) || /\.(jpe?g|png|gif|webp)$/i.test(f.name); });
        var room = remaining - queue.length;
        if (picked.length > room) {
            alert('อัลบั้มนี้เพิ่มได้อีก ' + Math.max(0, room) + ' รูป (อัลบั้มละไม่เกิน 80 รูป)\nเลือกมา ' + picked.length + ' รูป ระบบจะใช้ ' + Math.max(0, room) + ' รูปแรก');
            picked = picked.slice(0, Math.max(0, room));
        }
        drop.classList.toggle('has-files', picked.length > 0);
        drop.querySelector('strong').textContent = picked.length ? 'เลือกแล้ว ' + picked.length + ' รูป' : 'ลากรูปมาวางที่นี่';
        startBtn.textContent = picked.length ? 'อัพโหลด ' + picked.length + ' รูป' : 'อัพโหลด';
    }

    input.addEventListener('change', function () { setPicked(input.files); });
    ['dragenter', 'dragover'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-over'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('is-over'); });
    });
    drop.addEventListener('drop', function (e) { setPicked(e.dataTransfer.files); });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!picked.length) {
            alert('กรุณาเลือกไฟล์ภาพก่อน');
            return;
        }
        statusBox.hidden = false;
        startBtn.disabled = true;
        picked.forEach(function (file) {
            var li = document.createElement('li');
            li.innerHTML = '<span class="bulk-name"></span><span class="bulk-state">รอคิว</span>';
            li.querySelector('.bulk-name').textContent = file.name;
            list.appendChild(li);
            queue.push({ file: file, li: li, state: 'waiting' });
        });
        picked = [];
        setPicked([]);
        input.value = '';
        pump();
    });

    retryBtn.addEventListener('click', function () {
        queue.forEach(function (item) {
            if (item.state === 'failed') setState(item, 'waiting', 'รอคิว');
        });
        retryBtn.hidden = true;
        pump();
    });

    window.addEventListener('beforeunload', function (e) {
        if (queue.some(function (i) { return i.state === 'waiting' || i.state === 'uploading'; })) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    function setState(item, state, text) {
        item.state = state;
        item.li.className = 'is-' + state;
        item.li.querySelector('.bulk-state').textContent = text;
    }

    function pump() {
        while (running < CONCURRENCY) {
            var next = queue.find(function (i) { return i.state === 'waiting'; });
            if (!next) break;
            running++;
            upload(next).then(function () { running--; render(); pump(); });
        }
        render();
    }

    function render() {
        var total = queue.length;
        var done = queue.filter(function (i) { return i.state === 'done'; }).length;
        var failed = queue.filter(function (i) { return i.state === 'failed'; }).length;
        var finished = done + failed;
        bar.style.width = (total ? finished / total * 100 : 0) + '%';
        summary.textContent = 'อัปโหลดแล้ว ' + done + '/' + total + ' รูป' + (failed ? ' — ไม่สำเร็จ ' + failed + ' รูป' : '');
        var allFinished = total > 0 && finished === total && running === 0;
        retryBtn.hidden = !(allFinished && failed);
        doneLink.hidden = !allFinished;
        if (allFinished) {
            startBtn.disabled = false;
            summary.textContent += failed ? '' : ' ✓ เสร็จเรียบร้อย';
        }
    }

    function upload(item) {
        setState(item, 'uploading', resizeBox.checked ? 'กำลังย่อ/อัปโหลด…' : 'กำลังอัปโหลด…');
        return prepare(item.file).then(function (blob) {
            var fd = new FormData();
            fd.append('csrf_token', form.querySelector('[name=csrf_token]').value);
            fd.append('action', 'upload_one');
            if (albumId) fd.append('album_id', albumId);
            fd.append('caption', form.querySelector('[name=caption]').value);
            var cat = form.querySelector('[name=category]');
            if (cat) fd.append('category', cat.value);
            fd.append('image', blob, blob.name || item.file.name);
            return fetch('/admin/gallery.php', { method: 'POST', body: fd, credentials: 'same-origin' });
        }).then(function (res) {
            return res.json().catch(function () { return { ok: false, error: 'เซิร์ฟเวอร์ตอบกลับผิดพลาด (HTTP ' + res.status + ')' }; });
        }).then(function (data) {
            if (data.ok) {
                setState(item, 'done', data.notice ? '✓ (เก็บบนเซิร์ฟเวอร์)' : '✓');
            } else {
                setState(item, 'failed', '✗ ' + (data.error || 'ไม่สำเร็จ'));
                if (data.full) {
                    // Album reached its limit: don't keep sending the rest.
                    queue.forEach(function (q) { if (q.state === 'waiting') setState(q, 'failed', '✗ อัลบั้มเต็ม'); });
                }
            }
        }).catch(function () {
            setState(item, 'failed', '✗ เชื่อมต่อไม่ได้');
        });
    }

    /** Scales big JPEGs down to MAX_SIDE; everything else is sent as-is. */
    function prepare(file) {
        var isJpeg = /jpe?g$/i.test(file.type) || /\.jpe?g$/i.test(file.name);
        if (!resizeBox.checked || !isJpeg || !window.createImageBitmap) {
            return Promise.resolve(file);
        }
        return createImageBitmap(file, { imageOrientation: 'from-image' }).then(function (bmp) {
            var scale = Math.min(1, MAX_SIDE / Math.max(bmp.width, bmp.height));
            if (scale === 1 && file.size < 1.5 * 1024 * 1024) {
                return file;
            }
            var canvas = document.createElement('canvas');
            canvas.width = Math.round(bmp.width * scale);
            canvas.height = Math.round(bmp.height * scale);
            canvas.getContext('2d').drawImage(bmp, 0, 0, canvas.width, canvas.height);
            return new Promise(function (resolve) {
                canvas.toBlob(function (blob) {
                    if (!blob) { resolve(file); return; }
                    blob.name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                    resolve(blob);
                }, 'image/jpeg', 0.85);
            });
        }).catch(function () { return file; });
    }

    // ---------- Bulk delete ----------
    var selectAll = document.getElementById('gallerySelectAll');
    var deleteBtn = document.getElementById('galleryDeleteSelected');
    var boxes = document.querySelectorAll('.gallery-admin-item input[type=checkbox]');
    function updateDelete() {
        var n = Array.prototype.filter.call(boxes, function (b) { return b.checked; }).length;
        if (deleteBtn) {
            deleteBtn.disabled = n === 0;
            deleteBtn.textContent = 'ลบที่เลือก (' + n + ')';
        }
    }
    boxes.forEach(function (b) { b.addEventListener('change', updateDelete); });
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            boxes.forEach(function (b) { b.checked = selectAll.checked; });
            updateDelete();
        });
    }
})();
