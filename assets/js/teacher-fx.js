/**
 * Teacher identity effects (teachers.php, teacher-profile.php).
 *
 * Each .tid wrapper carries the themes detected from the teacher's
 * "identity" words (data-themes) and the words themselves (data-tags).
 * We add a themed glow and glyphs drifting up *behind* the photo (seen
 * through transparent PNG backgrounds) - nothing is drawn over the teacher.
 * Glyphs animate only while the card is on screen; reduced motion = static.
 */
(function () {
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var THEMES = {
        code:      { color: '#5cf2a6', glyphs: ['</>', '{ }', '0101', '1010', '01101', '110', '#include', '#!/bin', 'int main()', '// code', 'print()', 'def', 'return', '0x1F', '=>', ';', 'if ()', '[ ]', '&&', '#'], icon: '💻', binary: true },
        algorithm: { color: '#7cc4ff', glyphs: ['O(n)', '∑', 'log n', '→', '⇄', 'π', '∞', 'f(x)', '≤', 'Δ'], icon: '🧠' },
        game:      { color: '#ffd166', glyphs: ['▲', '●', '■', '✕', '1UP', '★', 'HP', '♥', 'LV', 'GG'], icon: '🎮' },
        web:       { color: '#8be9fd', glyphs: ['<div>', 'CSS', '#id', 'UI', 'UX', 'www', '.app', '⌘', '☰', '↗'], icon: '🌐' },
        animation: { color: '#ff9bd2', glyphs: ['◆', '24fps', '◇', '⟳', 'key', '◐', '✦', '∿', 'ease', '▶'], icon: '🎞️' },
        threed:    { color: '#b69cff', glyphs: ['⬡', 'XYZ', '◈', '△', 'VR', 'AR', '⟁', '3D', '⬢', '⊞'], icon: '🧊' },
        film:      { color: '#ff6b6b', glyphs: ['● REC', '4K', '🎬', '▶', '⏸', '24p', 'CUT', '⏺', 'TAKE', '⧉'], icon: '🎬' },
        photo:     { color: '#ffe08a', glyphs: ['f/1.8', 'ISO', '◎', '⊙', '1/250', 'RAW', '✺', '◉', 'AF', '⌖'], icon: '📷' },
        design:    { color: '#ff8c69', glyphs: ['Aa', '✒', '◐', '#FF5A3C', '⬤', '✎', '▦', '◭', 'Rgb', '✂'], icon: '🎨' },
        sound:     { color: '#9dffb0', glyphs: ['♪', '♫', '♬', '∿', 'dB', '🎧', '♩', 'Hz', '▮▯▮', '≋'], icon: '🎧' },
        general:   { color: '#ff9b7a', glyphs: ['✦', '✧', '◆', '•', '✺', '★'], icon: '✨' }
    };

    function el(tag, cls, text) {
        var e = document.createElement(tag);
        if (cls) e.className = cls;
        if (text != null) e.textContent = text;
        return e;
    }

    function build(wrap) {
        var themeNames = (wrap.getAttribute('data-themes') || '').split(',').filter(function (t) { return THEMES[t]; });
        var tags = [];
        try { tags = JSON.parse(wrap.getAttribute('data-tags') || '[]'); } catch (e) {}
        if (!themeNames.length && !tags.length) return; // nothing filled in: leave the photo plain
        if (!themeNames.length) themeNames = ['general'];
        var large = wrap.classList.contains('tid-large');
        var primary = THEMES[themeNames[0]];
        wrap.style.setProperty('--tid-color', primary.color);
        wrap.classList.add('tid-on', 'tid-' + themeNames[0]);

        // Soft themed glow behind the photo
        wrap.insertBefore(el('span', 'tid-glow'), wrap.firstChild);

        // Floating glyphs behind the photo (visible through transparent PNG
        // backgrounds), mixed from all detected themes.
        var layer = el('span', 'tid-glyphs');
        layer.setAttribute('aria-hidden', 'true');
        var count = large ? 22 : 14;
        for (var i = 0; i < count; i++) {
            var th = THEMES[themeNames[i % themeNames.length]];
            // About a third are the teacher's own words (what they typed in the
            // admin), the rest are theme symbols.
            var isTerm = tags.length && Math.random() < 0.35;
            var text = isTerm ? tags[Math.floor(Math.random() * tags.length)] : th.glyphs[Math.floor(Math.random() * th.glyphs.length)];
            var g = el('span', 'tid-glyph' + (isTerm ? ' tid-term' : ''), text);
            g.style.left = (4 + Math.random() * 86).toFixed(1) + '%';
            g.style.setProperty('--d', (6 + Math.random() * 6).toFixed(1) + 's');
            g.style.setProperty('--delay', (-Math.random() * 10).toFixed(1) + 's');
            g.style.setProperty('--size', (large ? 0.85 : 0.7) + Math.random() * 0.45 + 'rem');
            g.style.setProperty('--drift', ((Math.random() - 0.5) * 30).toFixed(0) + 'px');
            g.style.color = th.color;
            layer.appendChild(g);
        }
        // Code theme: a few columns of 0/1 "binary rain" falling behind the photo
        if (themeNames.some(function (t) { return THEMES[t].binary; })) {
            var cols = large ? 7 : 5;
            for (var c = 0; c < cols; c++) {
                var digits = '';
                for (var d = 0; d < 18; d++) digits += Math.random() < 0.5 ? '0' : '1';
                var col = el('span', 'tid-binary', digits);
                col.style.left = (6 + c * (88 / (cols - 1)) + (Math.random() - 0.5) * 6).toFixed(1) + '%';
                col.style.setProperty('--d', (7 + Math.random() * 6).toFixed(1) + 's');
                col.style.setProperty('--delay', (-Math.random() * 12).toFixed(1) + 's');
                col.style.color = THEMES.code.color;
                layer.appendChild(col);
            }
        }
        wrap.insertBefore(layer, wrap.querySelector('img'));

    }

    function init() {
        var wraps = document.querySelectorAll('.tid');
        wraps.forEach(build);
        if (reduceMotion) {
            document.documentElement.classList.add('tid-static');
            return;
        }
        // Animate only cards that are on screen.
        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (en) { en.target.classList.toggle('tid-playing', en.isIntersecting); });
            }, { rootMargin: '80px' });
            wraps.forEach(function (w) { io.observe(w); });
        } else {
            wraps.forEach(function (w) { w.classList.add('tid-playing'); });
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
