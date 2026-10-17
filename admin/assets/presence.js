/**
 * Admin presence: warns when another admin has the same page open, shows who
 * is online, and asks before saving over a change someone else saved after
 * this page was loaded. Server side: admin/presence.php.
 */
(function () {
    var body = document.body;
    var csrf = body.getAttribute('data-csrf');
    var me = body.getAttribute('data-admin-user');
    var loadedAt = parseInt(body.getAttribute('data-loaded-at'), 10) || 0;
    if (!csrf || !me) return;

    var pageKey = location.pathname + location.search;
    var banner = document.getElementById('presenceBanner');
    var onlineEl = document.getElementById('presenceOnline');
    var HEARTBEAT_MS = 15000;

    function send(event) {
        var fd = new FormData();
        fd.append('csrf_token', csrf);
        fd.append('page', pageKey);
        fd.append('event', event);
        return fetch('/admin/presence.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .catch(function () { return null; });
    }

    function fmtTime(ts) {
        var d = new Date(ts * 1000);
        return ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
    }

    function render(data) {
        if (!data) return;
        if (banner) {
            var msgs = [];
            if (data.editors && data.editors.length) {
                msgs.push('⚠ <strong>' + escapeHtml(data.editors.join(', ')) + '</strong> กำลังเปิดหน้านี้อยู่ ' +
                    'ระวังการแก้ไขรายการเดียวกันพร้อมกัน เพราะคนที่บันทึกทีหลังจะทับข้อมูลของอีกคน');
            }
            if (data.saved && data.saved.by !== me && data.saved.at > loadedAt) {
                msgs.push('↻ <strong>' + escapeHtml(data.saved.by) + '</strong> บันทึกการเปลี่ยนแปลงในหน้านี้เมื่อ ' +
                    fmtTime(data.saved.at) + ' หลังจากที่คุณเปิดหน้า ' +
                    '<a href="' + escapeHtml(pageKey) + '">โหลดข้อมูลล่าสุด</a>');
            }
            banner.innerHTML = msgs.join('<br>');
            banner.hidden = msgs.length === 0;
        }
        if (onlineEl && data.online) {
            onlineEl.textContent = 'ออนไลน์ ' + data.online.length + ' คน';
            onlineEl.title = data.online.join(', ');
            onlineEl.hidden = false;
        }
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function ping() { send('ping').then(render); }
    ping();
    setInterval(function () {
        if (!document.hidden) ping();
    }, HEARTBEAT_MS);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) ping();
    });

    window.addEventListener('pagehide', function () {
        var fd = new FormData();
        fd.append('csrf_token', csrf);
        fd.append('page', pageKey);
        fd.append('event', 'leave');
        if (navigator.sendBeacon) navigator.sendBeacon('/admin/presence.php', fd);
    });

    // Before any POST form on the page is submitted: if someone else saved
    // this page after we loaded it, ask first. Then record our own save.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (e.defaultPrevented || form.dataset.presenceChecked === '1') return;
        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post') return;
        if (form.getAttribute('action') && form.getAttribute('action').indexOf('/admin/logout') === 0) return;
        e.preventDefault();

        // Keep the clicked button's name/value - form.submit() would drop it.
        var submitter = e.submitter;
        send('check').then(function (data) {
            var saved = data && data.saved;
            if (saved && saved.by !== me && saved.at > loadedAt) {
                var ok = window.confirm(
                    saved.by + ' บันทึกการเปลี่ยนแปลงในหน้านี้เมื่อ ' + fmtTime(saved.at) +
                    ' หลังจากที่คุณเปิดหน้านี้\n\n' +
                    'ถ้ากด "ตกลง" ข้อมูลของคุณอาจทับสิ่งที่เขาเพิ่งบันทึก\n' +
                    'กด "ยกเลิก" เพื่อกลับไปตรวจสอบก่อน (แนะนำให้คัดลอกสิ่งที่แก้ไว้ แล้วโหลดหน้าใหม่)'
                );
                if (!ok) return;
            }
            return send('save').then(function () {
                if (submitter && submitter.name) {
                    var hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = submitter.name;
                    hidden.value = submitter.value;
                    form.appendChild(hidden);
                }
                form.dataset.presenceChecked = '1';
                HTMLFormElement.prototype.submit.call(form);
            });
        });
    });
})();
