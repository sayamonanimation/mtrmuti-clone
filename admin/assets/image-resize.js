/**
 * Multiple-image inputs marked data-resize: big JPEGs are scaled down to
 * 2000px in the browser as soon as they are picked, so many phone photos fit
 * in one form post. data-max caps how many files are kept.
 */
(function () {
    var MAX_SIDE = 2000;
    if (!window.DataTransfer || !window.createImageBitmap) return;

    function shrink(file) {
        var isJpeg = /jpe?g$/i.test(file.type) || /\.jpe?g$/i.test(file.name);
        if (!isJpeg) return Promise.resolve(file);
        return createImageBitmap(file, { imageOrientation: 'from-image' }).then(function (bmp) {
            var scale = Math.min(1, MAX_SIDE / Math.max(bmp.width, bmp.height));
            if (scale === 1 && file.size < 1.5 * 1024 * 1024) return file;
            var canvas = document.createElement('canvas');
            canvas.width = Math.round(bmp.width * scale);
            canvas.height = Math.round(bmp.height * scale);
            canvas.getContext('2d').drawImage(bmp, 0, 0, canvas.width, canvas.height);
            return new Promise(function (resolve) {
                canvas.toBlob(function (blob) {
                    resolve(blob ? new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' }) : file);
                }, 'image/jpeg', 0.85);
            });
        }).catch(function () { return file; });
    }

    document.querySelectorAll('input[type=file][data-resize]').forEach(function (input) {
        var hint = document.createElement('p');
        hint.className = 'field-hint';
        input.insertAdjacentElement('afterend', hint);
        var buttons = input.form ? input.form.querySelectorAll('[type=submit]') : [];
        input.addEventListener('change', function () {
            if (input.dataset.busy === '1') return;
            var files = Array.prototype.slice.call(input.files);
            var max = parseInt(input.getAttribute('data-max') || '0', 10);
            if (max && files.length > max) {
                alert('เลือกได้อีกไม่เกิน ' + max + ' รูป ระบบจะใช้ ' + max + ' รูปแรก');
                files = files.slice(0, max);
            }
            if (!files.length) { hint.textContent = ''; return; }
            input.dataset.busy = '1';
            buttons.forEach(function (b) { b.disabled = true; });
            hint.textContent = 'กำลังเตรียมรูป ' + files.length + ' รูป…';
            Promise.all(files.map(shrink)).then(function (out) {
                var dt = new DataTransfer();
                out.forEach(function (f) { dt.items.add(f); });
                input.files = dt.files;
                hint.textContent = 'เลือกแล้ว ' + out.length + ' รูป พร้อมอัปโหลด';
            }).finally(function () {
                input.dataset.busy = '0';
                buttons.forEach(function (b) { b.disabled = false; });
            });
        });
    });
})();
