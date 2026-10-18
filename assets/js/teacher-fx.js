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

    // ---------- WebGL photo ----------
    // The teacher photo itself is drawn by a small shader: digital dissolve-in,
    // theme-coloured rim light along the cut-out edge, a hologram band and
    // scanlines, pointer parallax with a light RGB split on hover. The <img>
    // stays in the page (layout, SEO, fallback) and is hidden once WebGL draws.
    var VERT = 'attribute vec2 p;varying vec2 vUv;void main(){vUv=p*.5+.5;gl_Position=vec4(p,0.,1.);}';
    var FRAG = [
        'precision mediump float;',
        'varying vec2 vUv;',
        'uniform sampler2D uTex;',
        'uniform vec2 uScale, uOffset;',   // object-fit: cover mapping
        'uniform float uTime, uHover, uReveal;',
        'uniform vec2 uMouse;',
        'uniform vec3 uColor;',
        'float hash(vec2 p){return fract(sin(dot(p,vec2(127.1,311.7)))*43758.5453);}',
        'vec4 tex(vec2 uv){ if(uv.x<0.||uv.x>1.||uv.y<0.||uv.y>1.) return vec4(0.); return texture2D(uTex,uv); }',
        'void main(){',
        '  vec2 uv = vec2(vUv.x, 1.-vUv.y) * uScale + uOffset;',
        '  uv += uMouse * .012 * uHover;',                         // parallax
        '  uv.x += sin(uv.y*28. + uTime*2.2) * .0012;',            // living shimmer
        '  float split = .0015 + .004 * uHover;',
        '  vec4 c = tex(uv);',
        '  float r = tex(uv + vec2(split,0.)).r, b = tex(uv - vec2(split,0.)).b;',
        '  vec3 col = vec3(mix(c.r, r, .8), c.g, mix(c.b, b, .8));',
        '  float a = c.a;',
        // rim light: alpha drops nearby -> we are on the silhouette edge
        '  float e = .006;',
        '  float n = min(min(tex(uv+vec2(e,0.)).a, tex(uv-vec2(e,0.)).a), min(tex(uv+vec2(0.,e)).a, tex(uv-vec2(0.,e)).a));',
        '  float rim = clamp(a - n, 0., 1.);',
        '  col += uColor * rim * (1.2 + .8*uHover);',
        // hologram band sweeping down + faint scanlines
        '  float band = smoothstep(.06, 0., abs(fract(vUv.y*.9 + uTime*.12) - .5));',
        '  col += uColor * band * .22 * a;',
        '  col *= .96 + .04 * sin(gl_FragCoord.y * 1.6);',
        // digital dissolve-in on first view
        '  float cell = hash(floor(vUv * vec2(42., 56.)));',
        '  if (cell > uReveal) discard;',
        '  float edgeGlow = smoothstep(uReveal - .08, uReveal, cell) * step(uReveal, 1.);',  // only while dissolving
        '  col += uColor * edgeGlow * 1.5 * a;',
        '  gl_FragColor = vec4(col * a, a);',
        '}'
    ].join('\n');

    function startPhotoGL(wrap) {
        var img = wrap.querySelector('img');
        if (!img || wrap._gl) return;
        function go() {
            if (!img.naturalWidth) return;
            var canvas = document.createElement('canvas');
            canvas.className = 'tid-canvas';
            canvas.setAttribute('aria-hidden', 'true');
            var gl = canvas.getContext('webgl', { premultipliedAlpha: true, alpha: true, antialias: false });
            if (!gl) return;
            function sh(type, src) { var x = gl.createShader(type); gl.shaderSource(x, src); gl.compileShader(x); return gl.getShaderParameter(x, gl.COMPILE_STATUS) ? x : null; }
            var vs = sh(gl.VERTEX_SHADER, VERT), fs = sh(gl.FRAGMENT_SHADER, FRAG);
            if (!vs || !fs) return;
            var prog = gl.createProgram();
            gl.attachShader(prog, vs); gl.attachShader(prog, fs); gl.linkProgram(prog);
            if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) return;
            gl.useProgram(prog);
            var buf = gl.createBuffer();
            gl.bindBuffer(gl.ARRAY_BUFFER, buf);
            gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
            var loc = gl.getAttribLocation(prog, 'p');
            gl.enableVertexAttribArray(loc);
            gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);

            var tex = gl.createTexture();
            gl.bindTexture(gl.TEXTURE_2D, tex);
            gl.pixelStorei(gl.UNPACK_PREMULTIPLY_ALPHA_WEBGL, false);
            try {
                gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, img);
            } catch (e) {
                return; // cross-origin image without CORS: keep the plain <img>
            }
            gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
            gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
            gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
            gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
            gl.enable(gl.BLEND);
            gl.blendFunc(gl.ONE, gl.ONE_MINUS_SRC_ALPHA);

            var U = {};
            ['uScale', 'uOffset', 'uTime', 'uHover', 'uReveal', 'uMouse', 'uColor'].forEach(function (n) { U[n] = gl.getUniformLocation(prog, n); });
            var hex = getComputedStyle(wrap).getPropertyValue('--tid-color').trim() || '#ff9b7a';
            var rgb = [1, 3, 5].map(function (i) { return parseInt(hex.substr(i, 2), 16) / 255; });
            gl.uniform3f(U.uColor, rgb[0], rgb[1], rgb[2]);

            wrap.insertBefore(canvas, img.nextSibling);
            wrap.classList.add('tid-gl');
            wrap._gl = true;

            var w = 0, h = 0;
            function resize() {
                var dpr = Math.min(window.devicePixelRatio || 1, 2);
                w = img.clientWidth; h = img.clientHeight;
                canvas.width = Math.max(1, Math.round(w * dpr));
                canvas.height = Math.max(1, Math.round(h * dpr));
                gl.viewport(0, 0, canvas.width, canvas.height);
                // object-fit: cover, positioned centre-top like the <img>
                var boxA = w / h, imgA = img.naturalWidth / img.naturalHeight;
                var sx = 1, sy = 1;
                if (imgA > boxA) sx = boxA / imgA; else sy = imgA / boxA;
                gl.uniform2f(U.uScale, sx, sy);
                gl.uniform2f(U.uOffset, (1 - sx) / 2, 0);
            }
            resize();
            window.addEventListener('resize', resize);

            var hover = 0, hoverTarget = 0, mouse = [0, 0], reveal = reduceMotion ? 1.01 : 0;
            var host = wrap.closest('.card') || wrap;
            host.addEventListener('pointermove', function (e) {
                var r = wrap.getBoundingClientRect();
                mouse = [((e.clientX - r.left) / r.width - .5) * 2, ((e.clientY - r.top) / r.height - .5) * 2];
                hoverTarget = 1;
            });
            host.addEventListener('pointerleave', function () { hoverTarget = 0; mouse = [0, 0]; });

            var start = performance.now(), running = false, visible = false;
            function frame(now) {
                if (!visible || document.hidden) { running = false; return; }
                var t = (now - start) / 1000;
                hover += (hoverTarget - hover) * .08;
                if (reveal < 1.01) reveal = Math.min(1.01, reveal + .018);
                gl.uniform1f(U.uTime, t);
                gl.uniform1f(U.uHover, hover);
                gl.uniform1f(U.uReveal, reveal);
                gl.uniform2f(U.uMouse, mouse[0], mouse[1]);
                gl.clearColor(0, 0, 0, 0);
                gl.clear(gl.COLOR_BUFFER_BIT);
                gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
                if (reduceMotion) { running = false; return; }
                requestAnimationFrame(frame);
            }
            function play() { if (!running) { running = true; requestAnimationFrame(frame); } }
            if ('IntersectionObserver' in window) {
                new IntersectionObserver(function (en) { visible = en[0].isIntersecting; if (visible) play(); }, { rootMargin: '60px' }).observe(wrap);
            } else { visible = true; play(); }
            document.addEventListener('visibilitychange', function () { if (!document.hidden && visible) play(); });
        }
        if (img.complete && img.naturalWidth) go();
        else img.addEventListener('load', go, { once: true });
    }

    function init() {
        var wraps = document.querySelectorAll('.tid');
        wraps.forEach(build);
        wraps.forEach(function (w) { if (w.classList.contains('tid-on')) startPhotoGL(w); });
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
