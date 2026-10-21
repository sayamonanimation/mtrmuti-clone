/**
 * Asks before every POST form is sent (add / edit / delete) with a popup.
 * The text comes from the form's data-confirm attribute, or is worked out
 * from its "action" field. Forms marked data-no-confirm are sent directly.
 * Runs before other submit handlers (presence check, bulk uploader).
 */
(function () {
    var css = '.cfm-back{position:fixed;inset:0;z-index:10000;background:rgba(10,12,20,.55);display:flex;align-items:center;justify-content:center;padding:16px;animation:cfm-in .15s ease-out}'
        + '.cfm-box{background:#fff;color:#1d1d1f;border-radius:14px;max-width:420px;width:100%;padding:22px 22px 18px;box-shadow:0 20px 60px rgba(0,0,0,.35);font:inherit;text-align:left}'
        + '.cfm-icon{font-size:1.9rem;line-height:1;margin-bottom:8px}'
        + '.cfm-title{font-size:1.08rem;font-weight:700;margin:0 0 6px}'
        + '.cfm-text{margin:0 0 18px;color:#555;font-size:.93rem;line-height:1.5;white-space:pre-line}'
        + '.cfm-actions{display:flex;gap:10px;justify-content:flex-end}'
        + '.cfm-actions button{font:inherit;border:0;border-radius:8px;padding:9px 18px;cursor:pointer;font-weight:600}'
        + '.cfm-cancel{background:#eceef1;color:#333}.cfm-ok{background:#1f6feb;color:#fff}.cfm-danger .cfm-ok{background:#c62f3a}'
        + '.cfm-actions button:focus-visible{outline:3px solid rgba(31,111,235,.4);outline-offset:2px}'
        + '@keyframes cfm-in{from{opacity:0}to{opacity:1}}';
    var style = document.createElement('style');
    style.textContent = css;
    document.head.appendChild(style);

    var KINDS = {
        del: { icon: '🗑️', title: 'ยืนยันการลบ', text: 'ข้อมูลที่ลบแล้วจะกู้คืนไม่ได้', ok: 'ลบ', danger: true },
        add: { icon: '➕', title: 'ยืนยันการเพิ่มข้อมูล', text: 'ต้องการเพิ่มข้อมูลนี้ใช่หรือไม่?', ok: 'เพิ่ม' },
        edit: { icon: '✏️', title: 'ยืนยันการบันทึกการแก้ไข', text: 'ต้องการบันทึกการเปลี่ยนแปลงนี้ใช่หรือไม่?', ok: 'บันทึก' }
    };

    function kindOf(form, submitter) {
        var forced = form.getAttribute('data-confirm-kind');
        if (KINDS[forced]) return forced;
        var act = (submitter && submitter.name === 'action' && submitter.value) || '';
        if (!act) {
            var field = form.querySelector('[name=action]');
            act = field ? field.value : '';
        }
        if (/delete|remove|clear/i.test(act)) return 'del';
        if (/^(add|create|new|upload|insert)/i.test(act)) return 'add';
        if (!act) {
            var id = form.querySelector('input[type=hidden][name=id]');
            if (id ? (!id.value || id.value === '0') : form.querySelector('input[type=file]')) return 'add';
        }
        return 'edit';
    }

    function ask(form, submitter) {
        var kind = KINDS[kindOf(form, submitter)];
        var custom = form.getAttribute('data-confirm');
        if (custom) {
            kind = Object.assign({}, kind, { text: custom });
            if (/ลบ/.test(custom) && !form.hasAttribute('data-confirm-kind')) kind = Object.assign({}, KINDS.del, { text: custom });
        }
        return new Promise(function (resolve) {
            var back = document.createElement('div');
            back.className = 'cfm-back' + (kind.danger ? ' cfm-danger' : '');
            back.innerHTML = '<div class="cfm-box" role="alertdialog" aria-modal="true" aria-labelledby="cfmTitle">'
                + '<div class="cfm-icon"></div><p class="cfm-title" id="cfmTitle"></p><p class="cfm-text"></p>'
                + '<div class="cfm-actions"><button type="button" class="cfm-cancel">ยกเลิก</button><button type="button" class="cfm-ok"></button></div></div>';
            back.querySelector('.cfm-icon').textContent = kind.icon;
            back.querySelector('.cfm-title').textContent = kind.title;
            back.querySelector('.cfm-text').textContent = kind.text;
            back.querySelector('.cfm-ok').textContent = kind.ok;
            var last = document.activeElement;
            function done(ok) {
                document.removeEventListener('keydown', onKey, true);
                back.remove();
                if (last && last.focus) last.focus();
                resolve(ok);
            }
            function onKey(e) {
                if (e.key === 'Escape') { e.preventDefault(); done(false); }
            }
            back.addEventListener('click', function (e) { if (e.target === back) done(false); });
            back.querySelector('.cfm-cancel').addEventListener('click', function () { done(false); });
            back.querySelector('.cfm-ok').addEventListener('click', function () { done(true); });
            document.addEventListener('keydown', onKey, true);
            document.body.appendChild(back);
            back.querySelector(kind.danger ? '.cfm-cancel' : '.cfm-ok').focus();
        });
    }

    // Older forms ask with onsubmit="return confirm('...')": take over their
    // text so the question is asked once, in this popup.
    document.querySelectorAll('form[onsubmit]').forEach(function (form) {
        var m = /confirm\((['"])([\s\S]*)\1\)/.exec(form.getAttribute('onsubmit'));
        if (!m) return;
        if (!form.hasAttribute('data-confirm')) form.setAttribute('data-confirm', m[2]);
        form.removeAttribute('onsubmit');
        form.onsubmit = null;
    });

    window.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post') return;
        if (form.hasAttribute('data-no-confirm')) return;
        if (form._cfmOk) { form._cfmOk = false; return; }   // confirmed: let other handlers run
        e.preventDefault();
        e.stopImmediatePropagation();
        var submitter = e.submitter || null;
        ask(form, submitter).then(function (ok) {
            if (!ok) return;
            form._cfmOk = true;
            if (form.requestSubmit) {
                form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);
            } else {
                if (submitter && submitter.name) {
                    var hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = submitter.name;
                    hidden.value = submitter.value;
                    form.appendChild(hidden);
                }
                form._cfmOk = false;
                HTMLFormElement.prototype.submit.call(form);
            }
        });
    }, true);
})();
