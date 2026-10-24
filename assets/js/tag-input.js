/**
 * Keyword fields: <input data-tag-input [data-tag-max="6"]> becomes a box of
 * keyword chips. Type a word and press Enter (or comma / Tab) to add it,
 * click × or press Backspace in an empty box to remove, paste "a, b, c" to
 * add several. The original input stays in the form as a hidden field
 * holding "a, b, c", so the server side is unchanged; it fires "input"
 * whenever the list changes (for live previews).
 */
(function () {
    var css = ''
        + '.tagbox{display:flex;flex-wrap:wrap;align-items:center;gap:6px;min-height:46px;padding:6px 8px;border-radius:10px;cursor:text;'
        + 'border:1px solid var(--tag-border,#ccc);background:var(--tag-bg,#fff);transition:border-color .2s,box-shadow .2s}'
        + '.tagbox:focus-within{border-color:var(--tag-focus,#a3222c);box-shadow:0 0 0 3px var(--tag-ring,rgba(163,34,44,.15))}'
        + '.tagbox-chip{display:inline-flex;align-items:center;gap:4px;padding:3px 6px 3px 11px;border-radius:999px;font-size:.88em;font-weight:600;line-height:1.3;'
        + 'color:var(--tag-chip-ink,#7a1b22);background:var(--tag-chip-bg,#fdeceb);border:1px solid var(--tag-chip-border,#f3c9c5);animation:tagbox-in .18s ease-out}'
        + '.tagbox-chip button{display:inline-grid;place-items:center;width:20px;height:20px;padding:0;border:0;border-radius:50%;cursor:pointer;font-size:14px;line-height:1;'
        + 'color:inherit;background:transparent;opacity:.65}'
        + '.tagbox-chip button:hover,.tagbox-chip button:focus-visible{opacity:1;background:rgba(0,0,0,.12)}'
        + '.tagbox-input{flex:1 1 140px;min-width:120px;border:0 !important;outline:0 !important;box-shadow:none !important;background:transparent !important;'
        + 'padding:6px 4px !important;margin:0 !important;font:inherit;color:inherit}'
        + '.tagbox-hint{margin:4px 2px 0;font-size:.8em;opacity:.7}'
        + '.tagbox.is-full .tagbox-input{display:none}'
        + '@keyframes tagbox-in{from{transform:scale(.8);opacity:0}to{transform:none;opacity:1}}'
        // Dark public pages (webgl theme)
        + '.page-content .tagbox{--tag-border:var(--fx-border-hi,rgba(255,120,95,.45));--tag-bg:rgba(255,255,255,.04);--tag-focus:var(--fx-accent,#ff5a3c);'
        + '--tag-ring:rgba(255,90,60,.2);--tag-chip-ink:#fff;--tag-chip-bg:rgba(255,90,60,.2);--tag-chip-border:rgba(255,120,95,.5)}';
    var style = document.createElement('style');
    style.textContent = css;
    document.head.appendChild(style);

    function split(text) {
        return String(text).split(/[,\n،]+/).map(function (s) { return s.trim(); }).filter(Boolean);
    }

    document.querySelectorAll('input[data-tag-input]').forEach(function (field) {
        var max = parseInt(field.getAttribute('data-tag-max') || '0', 10) || 0;
        var maxLen = parseInt(field.getAttribute('maxlength') || '0', 10) || 0;
        var tags = [];
        split(field.value).forEach(function (t) { if (tags.indexOf(t) === -1) tags.push(t); });

        var box = document.createElement('div');
        box.className = 'tagbox';
        var typer = document.createElement('input');
        typer.type = 'text';
        typer.className = 'tagbox-input';
        typer.placeholder = field.getAttribute('placeholder') ? 'พิมพ์แล้วกด Enter เช่น ' + split(field.getAttribute('placeholder'))[0] : 'พิมพ์แล้วกด Enter';
        typer.setAttribute('aria-label', (field.labels && field.labels[0] ? field.labels[0].textContent.trim() + ': ' : '') + 'พิมพ์คำแล้วกด Enter');
        if (field.id) {
            // Clicking the field's <label> should focus the typing box.
            var label = document.querySelector('label[for="' + field.id + '"]');
            if (label) label.htmlFor = field.id + '__typer';
            typer.id = field.id + '__typer';
        }
        var hint = document.createElement('p');
        hint.className = 'tagbox-hint';

        field.type = 'hidden';
        field.insertAdjacentElement('afterend', box);
        box.insertAdjacentElement('afterend', hint);
        // A hidden input inside a <label> no longer takes the label's click.
        var wrapLabel = field.closest('label');
        if (wrapLabel) wrapLabel.addEventListener('click', function (e) { if (!box.contains(e.target)) { e.preventDefault(); typer.focus(); } });

        function joined(list) { return list.join(', '); }
        function sync() {
            field.value = joined(tags);
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        }
        function render() {
            box.querySelectorAll('.tagbox-chip').forEach(function (c) { c.remove(); });
            tags.forEach(function (t, i) {
                var chip = document.createElement('span');
                chip.className = 'tagbox-chip';
                chip.appendChild(document.createTextNode(t));
                var x = document.createElement('button');
                x.type = 'button';
                x.textContent = '×';
                x.setAttribute('aria-label', 'ลบ ' + t);
                x.addEventListener('click', function (e) { e.stopPropagation(); tags.splice(i, 1); render(); sync(); typer.focus(); });
                chip.appendChild(x);
                box.insertBefore(chip, typer);
            });
            var full = max && tags.length >= max;
            box.classList.toggle('is-full', !!full);
            hint.textContent = full
                ? 'ครบ ' + max + ' คำแล้ว (ลบคำเดิมก่อนถ้าต้องการเพิ่ม)'
                : 'กด Enter เพื่อเพิ่มคำ · คลิก × เพื่อลบ' + (max ? ' · เพิ่มได้อีก ' + (max - tags.length) + ' คำ' : '');
        }
        function add(text) {
            var added = false;
            split(text).forEach(function (t) {
                if (max && tags.length >= max) return;
                if (tags.some(function (x) { return x.toLowerCase() === t.toLowerCase(); })) return;
                if (maxLen && joined(tags.concat([t])).length > maxLen) return;
                tags.push(t);
                added = true;
            });
            if (added) { render(); sync(); }
            typer.value = '';
        }

        box.appendChild(typer);
        box.addEventListener('click', function (e) { if (e.target === box) typer.focus(); });
        typer.addEventListener('keydown', function (e) {
            if ((e.key === 'Enter' || e.key === ',' || (e.key === 'Tab' && typer.value.trim())) && !e.isComposing) {
                if (typer.value.trim()) { e.preventDefault(); add(typer.value); }
                else if (e.key !== 'Tab') e.preventDefault();   // Enter on an empty box must not submit
            } else if (e.key === 'Backspace' && !typer.value && tags.length) {
                tags.pop();
                render();
                sync();
            }
        });
        typer.addEventListener('paste', function (e) {
            var text = (e.clipboardData || window.clipboardData).getData('text');
            if (/[,\n]/.test(text)) { e.preventDefault(); add(text); }
        });
        // Whatever is still typed counts when the form is saved.
        typer.addEventListener('blur', function () { if (typer.value.trim()) add(typer.value); });
        if (field.form) field.form.addEventListener('submit', function () { if (typer.value.trim()) add(typer.value); }, true);

        render();
    });
})();
