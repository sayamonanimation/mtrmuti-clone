/**
 * WebGL backdrop + motion effects for the public site.
 *
 * - A full-viewport fragment shader (domain-warped noise in the site's red
 *   palette) sits behind every page and reacts to the pointer and scroll.
 * - Cards tilt in 3D under the pointer, and content fades up as it scrolls in.
 *
 * Everything degrades quietly: no WebGL -> the CSS gradient on <body> shows;
 * prefers-reduced-motion -> one still frame, no tilt, no reveal animation.
 */
(function () {
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

    // ---------- Shader backdrop ----------
    var VERT = 'attribute vec2 p;void main(){gl_Position=vec4(p,0.,1.);}';

    var FRAG = [
        'precision mediump float;',
        'uniform vec2 u_res;',
        'uniform float u_time;',
        'uniform vec2 u_mouse;',
        'uniform float u_scroll;',
        'float hash(vec2 p){p=fract(p*vec2(123.34,456.21));p+=dot(p,p+45.32);return fract(p.x*p.y);}',
        'float noise(vec2 p){vec2 i=floor(p),f=fract(p);vec2 u=f*f*(3.-2.*f);',
        '  return mix(mix(hash(i),hash(i+vec2(1,0)),u.x),mix(hash(i+vec2(0,1)),hash(i+vec2(1,1)),u.x),u.y);}',
        'float fbm(vec2 p){float v=0.,a=.5;mat2 m=mat2(1.6,1.2,-1.2,1.6);',
        '  for(int i=0;i<5;i++){v+=a*noise(p);p=m*p;a*=.5;}return v;}',
        'void main(){',
        '  vec2 uv=gl_FragCoord.xy/u_res;',
        '  vec2 p=(gl_FragCoord.xy-.5*u_res)/min(u_res.x,u_res.y);',
        '  float t=u_time*.05;',
        '  p.y-=u_scroll*.00035;',
        '  vec2 m=(u_mouse-.5)*vec2(u_res.x/min(u_res.x,u_res.y),u_res.y/min(u_res.x,u_res.y));',
        '  vec2 q=vec2(fbm(p*1.4+t),fbm(p*1.4+vec2(5.2,1.3)-t));',
        '  vec2 r=vec2(fbm(p*1.4+3.*q+vec2(1.7,9.2)+t*1.5+m*.35),fbm(p*1.4+3.*q+vec2(8.3,2.8)-t*1.2));',
        '  float f=fbm(p*1.4+3.*r);',
        '  vec3 c0=vec3(.035,.008,.012);',   // near-black
        '  vec3 c1=vec3(.28,.024,.0);',      // #470600
        '  vec3 c2=vec3(.64,.05,.0);',       // #A30D00
        '  vec3 c3=vec3(1.,.42,.28);',       // hot highlight
        '  vec3 col=mix(c0,c1,smoothstep(.2,.65,f));',
        '  col=mix(col,c2,smoothstep(.55,.9,f*length(q)));',
        '  col=mix(col,c3,smoothstep(.82,1.15,f*r.x*1.6)*.55);',
        // soft light that follows the pointer
        '  float glow=exp(-3.5*length(p-m));',
        '  col+=vec3(.55,.12,.06)*glow*.35;',
        // thin flowing light lines for a "tech" sheen
        '  float lines=smoothstep(.985,1.,sin((p.x+p.y*.6+f*1.8)*38.+u_time*.6));',
        '  col+=vec3(1.,.35,.25)*lines*.05*f;',
        // vignette + grain
        '  col*=1.-.55*pow(length(uv-.5)*1.25,2.);',
        '  col+=(hash(gl_FragCoord.xy+u_time)-.5)*.025;',
        '  gl_FragColor=vec4(col,1.);',
        '}'
    ].join('\n');

    function startBackdrop() {
        var canvas = document.createElement('canvas');
        canvas.className = 'fx-canvas';
        canvas.setAttribute('aria-hidden', 'true');
        var gl = canvas.getContext('webgl', { antialias: false, alpha: false, powerPreference: 'low-power' });
        if (!gl) return;

        function compile(type, src) {
            var s = gl.createShader(type);
            gl.shaderSource(s, src);
            gl.compileShader(s);
            return gl.getShaderParameter(s, gl.COMPILE_STATUS) ? s : null;
        }
        var vs = compile(gl.VERTEX_SHADER, VERT);
        var fs = compile(gl.FRAGMENT_SHADER, FRAG);
        if (!vs || !fs) return;
        var prog = gl.createProgram();
        gl.attachShader(prog, vs);
        gl.attachShader(prog, fs);
        gl.linkProgram(prog);
        if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) return;
        gl.useProgram(prog);

        var buf = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, buf);
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 3, -1, -1, 3]), gl.STATIC_DRAW);
        var loc = gl.getAttribLocation(prog, 'p');
        gl.enableVertexAttribArray(loc);
        gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);

        var uRes = gl.getUniformLocation(prog, 'u_res');
        var uTime = gl.getUniformLocation(prog, 'u_time');
        var uMouse = gl.getUniformLocation(prog, 'u_mouse');
        var uScroll = gl.getUniformLocation(prog, 'u_scroll');

        document.body.insertBefore(canvas, document.body.firstChild);
        document.documentElement.classList.add('fx-webgl');

        // The noise is soft, so rendering below native resolution is
        // invisible but saves a lot of GPU time (especially on phones).
        var scale = Math.min(window.devicePixelRatio || 1, 1.5) * (window.innerWidth < 720 ? 0.5 : 0.7);
        function resize() {
            canvas.width = Math.round(window.innerWidth * scale);
            canvas.height = Math.round(window.innerHeight * scale);
            gl.viewport(0, 0, canvas.width, canvas.height);
            gl.uniform2f(uRes, canvas.width, canvas.height);
        }
        resize();
        window.addEventListener('resize', resize);

        var mouse = { x: 0.5, y: 0.6 }, target = { x: 0.5, y: 0.6 };
        window.addEventListener('pointermove', function (e) {
            target.x = e.clientX / window.innerWidth;
            target.y = 1 - e.clientY / window.innerHeight;
        }, { passive: true });

        var start = performance.now();
        var running = true;
        function frame(now) {
            mouse.x += (target.x - mouse.x) * 0.05;
            mouse.y += (target.y - mouse.y) * 0.05;
            gl.uniform1f(uTime, (now - start) / 1000 + 20);
            gl.uniform2f(uMouse, mouse.x, mouse.y);
            gl.uniform1f(uScroll, window.scrollY);
            gl.drawArrays(gl.TRIANGLES, 0, 3);
            if (running && !reduceMotion) requestAnimationFrame(frame);
        }
        requestAnimationFrame(frame);

        if (reduceMotion) {
            // Still frame only; redraw when the canvas size changes.
            window.addEventListener('resize', function () { requestAnimationFrame(frame); });
        } else {
            document.addEventListener('visibilitychange', function () {
                running = !document.hidden;
                if (running) requestAnimationFrame(frame);
            });
        }
    }

    // ---------- Card tilt ----------
    function startTilt() {
        if (reduceMotion || !finePointer) return;
        var selector = '.card, .post-card, .stat-box, .link-list-item, .profile-section, .gallery-grid img';
        document.addEventListener('pointermove', function (e) {
            var el = e.target.closest && e.target.closest(selector);
            if (!el) return;
            var r = el.getBoundingClientRect();
            var x = (e.clientX - r.left) / r.width;
            var y = (e.clientY - r.top) / r.height;
            // Big blocks (profile sections) tilt less so text stays readable.
            var max = r.width > 420 ? 2 : 7;
            el.style.setProperty('--rx', ((0.5 - y) * max).toFixed(2) + 'deg');
            el.style.setProperty('--ry', ((x - 0.5) * max).toFixed(2) + 'deg');
            el.style.setProperty('--mx', (x * 100).toFixed(1) + '%');
            el.style.setProperty('--my', (y * 100).toFixed(1) + '%');
            el.classList.add('is-tilting');
        }, { passive: true });
        document.addEventListener('pointerout', function (e) {
            var el = e.target.closest && e.target.closest(selector);
            if (!el || el.contains(e.relatedTarget)) return;
            el.classList.remove('is-tilting');
            el.style.removeProperty('--rx');
            el.style.removeProperty('--ry');
        });
    }

    // ---------- Scroll reveal ----------
    function startReveal() {
        if (reduceMotion || !('IntersectionObserver' in window)) return;
        var items = document.querySelectorAll(
            'main .section-title, main .card, main .post-card, main .stat-box, main .gallery-grid img,' +
            'main .content-card, main .profile-section, main .link-list-item, main .chart-wrap,' +
            'main .two-col-item, main .batch-section, main .teacher-profile-photo'
        );
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (!en.isIntersecting) return;
                var el = en.target;
                el.classList.add('is-visible');
                io.unobserve(el);
                // Drop the stagger once the entrance is done so hover/tilt
                // respond instantly afterwards.
                setTimeout(function () { el.style.removeProperty('--reveal-delay'); }, 1400);
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
        items.forEach(function (el) {
            // Stagger siblings in the same grid for a cascading entrance.
            var idx = Array.prototype.indexOf.call(el.parentNode.children, el);
            el.style.setProperty('--reveal-delay', Math.min(idx, 8) * 60 + 'ms');
            el.classList.add('reveal');
            io.observe(el);
        });
    }

    // ---------- Scroll progress bar + back-to-top button ----------
    function startScrollNav() {
        var bar = document.createElement('div');
        bar.className = 'scroll-progress';
        bar.setAttribute('aria-hidden', 'true');
        document.body.appendChild(bar);

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'back-to-top';
        btn.setAttribute('aria-label', document.documentElement.lang === 'en' ? 'Back to top' : 'กลับขึ้นด้านบน');
        // Ring circumference for r=22: 2 * PI * 22 = 138.2
        btn.innerHTML = '<svg viewBox="0 0 52 52" aria-hidden="true"><circle class="back-to-top-track" cx="26" cy="26" r="22"/>' +
            '<circle class="back-to-top-ring" cx="26" cy="26" r="22"/></svg>' +
            '<span class="back-to-top-arrow" aria-hidden="true">&uarr;</span>';
        document.body.appendChild(btn);
        var ring = btn.querySelector('.back-to-top-ring');
        var RING = 138.2;

        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
        });

        var ticking = false;
        function update() {
            ticking = false;
            var max = document.documentElement.scrollHeight - window.innerHeight;
            var p = max > 0 ? Math.min(1, Math.max(0, window.scrollY / max)) : 0;
            bar.style.transform = 'scaleX(' + p + ')';
            ring.style.strokeDashoffset = (RING * (1 - p)).toFixed(1);
            btn.classList.toggle('is-visible', window.scrollY > 400);
        }
        window.addEventListener('scroll', function () {
            if (!ticking) { ticking = true; requestAnimationFrame(update); }
        }, { passive: true });
        window.addEventListener('resize', update);
        update();
    }

    function init() {
        startBackdrop();
        startTilt();
        startReveal();
        startScrollNav();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
