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
    document.documentElement.classList.add('tid-js');   // hide photos until re-framed (no flash)

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

    // ---------- WebGL particle photo ----------
    // The teacher photo is rebuilt from thousands of points sampled from the
    // image itself (each point keeps that pixel's colour). Points fly in to
    // form the photo when it scrolls into view, scatter away from the pointer
    // and drift back - the same feel as the particle titles in the headers.
    // A tap (touch) blows the whole photo apart and lets it reassemble.
    // The <img> stays in the page (layout, SEO, no-WebGL fallback).
    var PVERT = [
        'attribute vec2 aPos;',
        'attribute vec4 aCol;',
        'uniform vec2 uRes;',
        'uniform float uSize;',
        'varying vec4 vCol;',
        'void main(){',
        '  vec2 clip = (aPos / uRes) * 2. - 1.;',
        '  gl_Position = vec4(clip.x, -clip.y, 0., 1.);',
        '  gl_PointSize = uSize;',
        '  vCol = aCol;',
        '}'
    ].join('\n');
    var PFRAG = [
        'precision mediump float;',
        'varying vec4 vCol;',
        'void main(){',
        '  float d = length(gl_PointCoord - .5);',
        '  float a = smoothstep(.5, .32, d);',
        '  gl_FragColor = vec4(vCol.rgb * vCol.a * a, vCol.a * a);',
        '}'
    ].join('\n');

    function startPhotoGL(wrap) {
        var img = wrap.querySelector('img');
        if (!img || wrap._gl) return;
        function go() {
            if (!img.naturalWidth || !img.clientWidth) return;
            var canvas = document.createElement('canvas');
            canvas.className = 'tid-canvas';
            canvas.setAttribute('aria-hidden', 'true');
            var gl = canvas.getContext('webgl', { premultipliedAlpha: true, alpha: true, antialias: false });
            if (!gl) return;
            function sh(type, src) { var x = gl.createShader(type); gl.shaderSource(x, src); gl.compileShader(x); return gl.getShaderParameter(x, gl.COMPILE_STATUS) ? x : null; }
            var vs = sh(gl.VERTEX_SHADER, PVERT), fs = sh(gl.FRAGMENT_SHADER, PFRAG);
            if (!vs || !fs) return;
            var prog = gl.createProgram();
            gl.attachShader(prog, vs); gl.attachShader(prog, fs); gl.linkProgram(prog);
            if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) return;
            gl.useProgram(prog);
            var uRes = gl.getUniformLocation(prog, 'uRes');
            var uSize = gl.getUniformLocation(prog, 'uSize');
            var aPos = gl.getAttribLocation(prog, 'aPos');
            var aCol = gl.getAttribLocation(prog, 'aCol');
            var posBuf = gl.createBuffer(), colBuf = gl.createBuffer();
            gl.enable(gl.BLEND);
            gl.blendFunc(gl.ONE, gl.ONE_MINUS_SRC_ALPHA);

            var dpr = Math.min(window.devicePixelRatio || 1, 2);
            var W = 0, H = 0, count = 0, home, pos, vel, step;

            // Sample the photo (object-fit: cover, centred top) into points.
            function build() {
                W = img.clientWidth; H = img.clientHeight;
                canvas.width = Math.round(W * dpr); canvas.height = Math.round(H * dpr);
                gl.viewport(0, 0, canvas.width, canvas.height);
                step = W > 320 ? 3 : 2.6;   // CSS px between points
                var cw = Math.round(W / step), ch = Math.round(H / step);
                var c = document.createElement('canvas');
                c.width = cw; c.height = ch;
                var ctx = c.getContext('2d');
                var boxA = cw / ch, imgA = img.naturalWidth / img.naturalHeight;
                var dw = cw, dh = ch, dx = 0;
                if (imgA > boxA) { dw = ch * imgA; dx = (cw - dw) / 2; } else { dh = cw / imgA; }
                ctx.drawImage(img, dx, 0, dw, dh);
                var data;
                try { data = ctx.getImageData(0, 0, cw, ch).data; } catch (e) { return false; } // no CORS
                var hs = [], cs = [];
                for (var y = 0; y < ch; y++) {
                    for (var x = 0; x < cw; x++) {
                        var k = (y * cw + x) * 4;
                        if (data[k + 3] < 90) continue;   // transparent background
                        hs.push((x + .5) * step * dpr, (y + .5) * step * dpr);
                        cs.push(data[k] / 255, data[k + 1] / 255, data[k + 2] / 255, data[k + 3] / 255);
                    }
                }
                count = hs.length / 2;
                home = new Float32Array(hs);
                pos = new Float32Array(count * 2);
                vel = new Float32Array(count * 2);
                scatter(1);
                gl.bindBuffer(gl.ARRAY_BUFFER, colBuf);
                gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(cs), gl.STATIC_DRAW);
                gl.enableVertexAttribArray(aCol);
                gl.vertexAttribPointer(aCol, 4, gl.FLOAT, false, 0, 0);
                gl.uniform2f(uRes, canvas.width, canvas.height);
                gl.uniform1f(uSize, step * dpr * 1.25);
                return true;
            }
            // Throw every point to a random spot (strength 1 = whole frame and beyond).
            function scatter(strength) {
                for (var i = 0; i < count; i++) {
                    var ang = Math.random() * Math.PI * 2, r = (0.3 + Math.random()) * strength * Math.max(canvas.width, canvas.height) * 0.7;
                    pos[i * 2] = home[i * 2] + Math.cos(ang) * r;
                    pos[i * 2 + 1] = home[i * 2 + 1] + Math.sin(ang) * r;
                    vel[i * 2] = vel[i * 2 + 1] = 0;
                }
            }
            function blast() {
                for (var i = 0; i < count; i++) {
                    var dx = home[i * 2] - canvas.width / 2, dy = home[i * 2 + 1] - canvas.height / 2;
                    var len = Math.sqrt(dx * dx + dy * dy) + 1;
                    var f = 18 + Math.random() * 22;
                    vel[i * 2] += dx / len * f; vel[i * 2 + 1] += dy / len * f;
                }
            }

            if (!build()) return;
            wrap.insertBefore(canvas, img.nextSibling);
            wrap.classList.add('tid-gl');
            wrap._gl = true;
            if (reduceMotion) { pos.set(home); }

            var mx = -9999, my = -9999;
            var host = wrap.closest('.card') || wrap;
            host.addEventListener('pointermove', function (e) {
                var r = canvas.getBoundingClientRect();
                mx = (e.clientX - r.left) * dpr; my = (e.clientY - r.top) * dpr;
            });
            host.addEventListener('pointerleave', function () { mx = my = -9999; });
            host.addEventListener('pointerdown', function (e) { if (e.pointerType !== 'mouse') blast(); });

            var R = 46 * dpr, R2 = R * R;
            // Once every point is home, show the real (sharp) photo instead of
            // the dots; the first push from the pointer swaps the dots back in.
            var settled = false, calmFrames = 0;
            function setSettled(v) {
                if (v === settled) return;
                settled = v;
                wrap.classList.toggle('tid-settled', v);
            }
            function stepPhysics() {
                var maxOff = 0;
                for (var i = 0; i < count; i++) {
                    var ix = i * 2, iy = ix + 1;
                    var dx = pos[ix] - mx, dy = pos[iy] - my, d2 = dx * dx + dy * dy;
                    if (d2 < R2) {
                        var f = (1 - d2 / R2) * 3.2, inv = 1 / Math.sqrt(d2 + 1);
                        vel[ix] += dx * inv * f; vel[iy] += dy * inv * f;
                    }
                    vel[ix] += (home[ix] - pos[ix]) * 0.06;
                    vel[iy] += (home[iy] - pos[iy]) * 0.06;
                    vel[ix] *= 0.82; vel[iy] *= 0.82;
                    pos[ix] += vel[ix]; pos[iy] += vel[iy];
                    var off = Math.abs(pos[ix] - home[ix]) + Math.abs(pos[iy] - home[iy]);
                    if (off > maxOff) maxOff = off;
                }
                if (maxOff < 1.2 * dpr) {
                    if (++calmFrames > 6) setSettled(true);
                } else {
                    calmFrames = 0;
                    setSettled(false);
                }
            }
            function draw() {
                gl.bindBuffer(gl.ARRAY_BUFFER, posBuf);
                gl.bufferData(gl.ARRAY_BUFFER, pos, gl.DYNAMIC_DRAW);
                gl.enableVertexAttribArray(aPos);
                gl.vertexAttribPointer(aPos, 2, gl.FLOAT, false, 0, 0);
                gl.clearColor(0, 0, 0, 0);
                gl.clear(gl.COLOR_BUFFER_BIT);
                gl.drawArrays(gl.POINTS, 0, count);
            }

            var running = false, visible = false, firstView = true;
            function frame() {
                if (!visible || document.hidden) { running = false; return; }
                stepPhysics();
                if (!settled) draw();   // the sharp <img> is showing; skip drawing
                requestAnimationFrame(frame);
            }
            function play() { if (!running) { running = true; requestAnimationFrame(frame); } }
            if (reduceMotion) { setSettled(true); return; }
            if ('IntersectionObserver' in window) {
                new IntersectionObserver(function (en) {
                    visible = en[0].isIntersecting;
                    if (visible && firstView) { firstView = false; scatter(1); }
                    if (visible) play();
                }, { threshold: 0.15 }).observe(wrap);
            } else { visible = true; play(); }
            document.addEventListener('visibilitychange', function () { if (!document.hidden && visible) play(); });
            var rt;
            window.addEventListener('resize', function () {
                clearTimeout(rt);
                rt = setTimeout(function () { if (img.clientWidth !== W) { build(); pos.set(home); draw(); } }, 200);
            });
        }
        if (img.complete && img.naturalWidth) go();
        else img.addEventListener('load', go, { once: true });
    }

    // ---------- Uniform framing ----------
    // Cut-out photos arrive with very different margins (some heads touch the
    // top edge, others have lots of headroom), so the teachers look different
    // sizes in identical cards. For transparent PNGs we find the person's
    // bounding box and redraw it into a 3:4 frame with the same headroom and
    // the same height for everyone, then use that as the image (and as the
    // source for the particle effect).
    var FRAME_W = 900, FRAME_H = 1200, HEADROOM = 0.07;

    function normalizePhoto(img, done) {
        function go() {
            if (!img.naturalWidth || img.dataset.normalized) { done(); return; }
            var sw = img.naturalWidth, sh = img.naturalHeight;
            var k = Math.min(1, 400 / Math.max(sw, sh));          // small copy just to measure
            var mw = Math.max(1, Math.round(sw * k)), mh = Math.max(1, Math.round(sh * k));
            var m = document.createElement('canvas');
            m.width = mw; m.height = mh;
            var mctx = m.getContext('2d');
            mctx.drawImage(img, 0, 0, mw, mh);
            var data;
            try { data = mctx.getImageData(0, 0, mw, mh).data; } catch (e) { done(); return; }
            // Count clearly opaque pixels per row/column; faint haze, soft
            // shadows and stray specks shouldn't count as the person.
            var rows = new Uint16Array(mh), cols = new Uint16Array(mw);
            for (var y = 0; y < mh; y++) {
                for (var x = 0; x < mw; x++) {
                    if (data[(y * mw + x) * 4 + 3] > 140) { rows[y]++; cols[x]++; }
                }
            }
            var minRow = Math.max(2, mw * 0.02), minCol = Math.max(2, mh * 0.02);
            var x0 = 0, x1 = mw - 1, y0 = 0, y1 = mh - 1;
            while (y0 < mh && rows[y0] < minRow) y0++;
            while (y1 > y0 && rows[y1] < minRow) y1--;
            while (x0 < mw && cols[x0] < minCol) x0++;
            while (x1 > x0 && cols[x1] < minCol) x1--;
            if (y0 >= mh || x0 >= mw) { x1 = -1; }
            // No transparency (ordinary photo) or nothing found: leave as is.
            if (x1 < 0 || ((x1 - x0) > mw * 0.97 && (y1 - y0) > mh * 0.97)) { done(); return; }
            var bx = x0 / k, by = y0 / k, bw = (x1 - x0 + 1) / k, bh = (y1 - y0 + 1) / k;
            // Same height for every person; widen less if someone is very wide.
            var scale = (FRAME_H * (1 - HEADROOM)) / bh;
            if (bw * scale > FRAME_W * 1.25) scale = (FRAME_W * 1.25) / bw;
            var c = document.createElement('canvas');
            c.width = FRAME_W; c.height = FRAME_H;
            var ctx = c.getContext('2d');
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(img, FRAME_W / 2 - (bx + bw / 2) * scale, FRAME_H * HEADROOM - by * scale, sw * scale, sh * scale);
            c.toBlob(function (blob) {
                if (!blob) { done(); return; }
                img.dataset.normalized = '1';
                img.addEventListener('load', function () { done(); }, { once: true });
                img.src = URL.createObjectURL(blob);
            }, 'image/png');
        }
        if (img.complete && img.naturalWidth) go();
        else img.addEventListener('load', go, { once: true });
    }

    function init() {
        var wraps = document.querySelectorAll('.tid');
        wraps.forEach(build);
        wraps.forEach(function (w) {
            var img = w.querySelector('img');
            if (!img) return;
            normalizePhoto(img, function () {
                w.classList.add('tid-framed');
                if (w.classList.contains('tid-on')) startPhotoGL(w);
            });
        });
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
