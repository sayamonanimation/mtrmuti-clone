/**
 * Teacher identity effects (teachers.php, teacher-profile.php).
 *
 * Each .tid wrapper carries the themes detected from the teacher's
 * "identity" words (data-themes) and the words themselves (data-tags).
 * We add: themed glyphs that drift up around the photo, a themed "scanner"
 * accent (code lines, sort bars, waveform...), and glowing tag chips.
 * Glyphs animate only while the card is on screen; reduced motion = static.
 */
(function () {
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var THEMES = {
        code:      { color: '#5cf2a6', glyphs: ['</>', '{ }', '01', '=>', 'fn()', ';', 'if', '[ ]', '#', '&&'], icon: '💻' },
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

        // Floating glyphs, mixed from all detected themes
        var layer = el('span', 'tid-glyphs');
        layer.setAttribute('aria-hidden', 'true');
        var count = large ? 16 : 10;
        for (var i = 0; i < count; i++) {
            var th = THEMES[themeNames[i % themeNames.length]];
            var g = el('span', 'tid-glyph', th.glyphs[Math.floor(Math.random() * th.glyphs.length)]);
            // Keep glyphs mostly at the sides so the face stays clear.
            var side = i % 2 === 0 ? Math.random() * 26 : 74 + Math.random() * 22;
            g.style.left = side + '%';
            g.style.setProperty('--d', (6 + Math.random() * 6).toFixed(1) + 's');
            g.style.setProperty('--delay', (-Math.random() * 10).toFixed(1) + 's');
            g.style.setProperty('--size', (large ? 0.85 : 0.7) + Math.random() * 0.45 + 'rem');
            g.style.setProperty('--drift', ((Math.random() - 0.5) * 30).toFixed(0) + 'px');
            g.style.color = th.color;
            layer.appendChild(g);
        }
        wrap.appendChild(layer);

        // Themed accent strip along the bottom of the photo
        var accent = el('span', 'tid-accent tid-accent-' + themeNames[0]);
        accent.setAttribute('aria-hidden', 'true');
        if (themeNames[0] === 'code' || themeNames[0] === 'web') {
            var lines = themeNames[0] === 'code'
                ? ['for (i = 0; i < n; i++)', '  learn(skill[i]);', 'return passion;']
                : ['<section class="hero">', '  <h1>Hello</h1>', '</section>'];
            lines.forEach(function (l) { accent.appendChild(el('span', 'tid-code-line', l)); });
        } else if (themeNames[0] === 'algorithm' || themeNames[0] === 'sound') {
            for (var b = 0; b < 14; b++) {
                var bar = el('span', 'tid-bar');
                bar.style.setProperty('--h', (20 + Math.random() * 80).toFixed(0) + '%');
                bar.style.setProperty('--delay', (-Math.random() * 2).toFixed(2) + 's');
                accent.appendChild(bar);
            }
        } else if (themeNames[0] === 'game') {
            accent.appendChild(el('span', 'tid-hp', 'HP'));
            var hp = el('span', 'tid-hp-bar');
            hp.appendChild(el('span', 'tid-hp-fill'));
            accent.appendChild(hp);
            accent.appendChild(el('span', 'tid-hp', 'LV 99'));
        } else if (themeNames[0] === 'film' || themeNames[0] === 'photo') {
            accent.appendChild(el('span', 'tid-rec', themeNames[0] === 'film' ? '● REC' : '◉ f/1.8 · ISO 100'));
        } else {
            accent.appendChild(el('span', 'tid-spark', primary.icon));
        }
        wrap.appendChild(accent);

        // Tag chips (what the teacher typed)
        if (tags.length) {
            var chips = el('span', 'tid-chips');
            tags.slice(0, large ? 6 : 3).forEach(function (t, i) {
                var c = el('span', 'tid-chip', t);
                c.style.setProperty('--delay', (i * 0.12) + 's');
                chips.appendChild(c);
            });
            wrap.appendChild(chips);
        }
        // Theme badge in the corner
        var badge = el('span', 'tid-badge', themeNames.map(function (t) { return THEMES[t].icon; }).slice(0, 3).join(''));
        badge.setAttribute('aria-hidden', 'true');
        wrap.appendChild(badge);
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
