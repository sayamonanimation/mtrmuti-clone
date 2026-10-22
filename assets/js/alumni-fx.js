/**
 * Interactive WebGL for "ติดต่องานศิษย์เก่า" (alumni-hub.php, alumni-profile.php).
 *
 * - .hub-galaxy: a 3D network - alumni (photos) on an inner sphere, their
 *   skills on an outer sphere, linked by glowing lines, inside a field of
 *   drifting particles. Drag to spin, hover to light up connections, click a
 *   person to open the profile, click a skill to filter the list below. It
 *   follows the page's search / skill / "พร้อมรับงาน" filters.
 * - .hub-sphere: the same engine as a rotating 3D cloud of one person's skills.
 * - Cards (.hub-card, .hub-work) tilt with a moving glare, buttons and chips
 *   lean toward the pointer, counters count up, items fade in on scroll.
 *
 * Photos and words are HTML placed at projected 3D positions (sharp text,
 * and photos stored on other domains need no CORS); lines and particles are
 * drawn with three.js r128. No WebGL -> the 3D block is hidden, the page
 * works as before. prefers-reduced-motion -> still scenes, no tilt.
 */
(function () {
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

    function fibonacciSphere(n, radius, offset) {
        var pts = [], golden = Math.PI * (3 - Math.sqrt(5));
        for (var i = 0; i < n; i++) {
            var y = n === 1 ? 0 : 1 - (i / (n - 1)) * 2;
            var r = Math.sqrt(1 - y * y);
            var theta = golden * i + (offset || 0);
            pts.push(new THREE.Vector3(Math.cos(theta) * r * radius, y * radius * 0.82, Math.sin(theta) * r * radius));
        }
        return pts;
    }

    function webglOk() {
        try {
            var c = document.createElement('canvas');
            return !!(window.WebGLRenderingContext && (c.getContext('webgl') || c.getContext('experimental-webgl')));
        } catch (e) {
            return false;
        }
    }

    /**
     * Shared 3D engine. nodes: [{el, pos: Vector3, size}] (HTML elements already
     * in the label layer); links: [[i, j]] index pairs into nodes.
     */
    function createScene(root, nodes, links, opts) {
        var stage = root.querySelector('.fx3d-stage');
        var canvas = root.querySelector('.fx3d-canvas');
        var renderer;
        try {
            renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true, powerPreference: 'low-power' });
        } catch (e) {
            return null;
        }
        // Shown only now (CSS hides it until ready), so it has a size to measure.
        root.classList.add('fx3d-ready');
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        var scene = new THREE.Scene();
        var camera = new THREE.PerspectiveCamera(opts.fov || 42, 1, 0.1, 100);
        var group = new THREE.Group();
        scene.add(group);

        // Particles: a soft field of embers around the network.
        var pCount = opts.particles || 500, pPos = new Float32Array(pCount * 3);
        for (var i = 0; i < pCount; i++) {
            var v = new THREE.Vector3().randomDirection ? new THREE.Vector3().randomDirection() : new THREE.Vector3(Math.random() - 0.5, Math.random() - 0.5, Math.random() - 0.5).normalize();
            v.multiplyScalar(opts.radius * (0.6 + Math.random() * 1.4));
            pPos.set([v.x, v.y, v.z], i * 3);
        }
        var pGeo = new THREE.BufferGeometry();
        pGeo.setAttribute('position', new THREE.BufferAttribute(pPos, 3));
        var dot = document.createElement('canvas');
        dot.width = dot.height = 64;
        var g = dot.getContext('2d'), grad = g.createRadialGradient(32, 32, 0, 32, 32, 32);
        grad.addColorStop(0, 'rgba(255,220,200,1)');
        grad.addColorStop(0.3, 'rgba(255,110,70,.8)');
        grad.addColorStop(1, 'rgba(255,60,30,0)');
        g.fillStyle = grad;
        g.fillRect(0, 0, 64, 64);
        var points = new THREE.Points(pGeo, new THREE.PointsMaterial({
            size: opts.radius * 0.035, map: new THREE.CanvasTexture(dot), transparent: true,
            depthWrite: false, blending: THREE.AdditiveBlending, opacity: 0.75
        }));
        scene.add(points);

        // Links: one line per pair, colour per vertex so hovering can light them up.
        var lPos = new Float32Array(links.length * 6), lCol = new Float32Array(links.length * 6);
        links.forEach(function (l, k) {
            var a = nodes[l[0]].pos, b = nodes[l[1]].pos;
            lPos.set([a.x, a.y, a.z, b.x, b.y, b.z], k * 6);
        });
        var lGeo = new THREE.BufferGeometry();
        lGeo.setAttribute('position', new THREE.BufferAttribute(lPos, 3));
        lGeo.setAttribute('color', new THREE.BufferAttribute(lCol, 3));
        var lines = new THREE.LineSegments(lGeo, new THREE.LineBasicMaterial({
            vertexColors: true, transparent: true, opacity: 0.9, depthWrite: false, blending: THREE.AdditiveBlending
        }));
        group.add(lines);

        // Faint wireframe shells give the sphere a shape.
        [opts.radius * 0.62, opts.radius].forEach(function (r, si) {
            var shell = new THREE.Mesh(new THREE.IcosahedronGeometry(r, 2), new THREE.MeshBasicMaterial({
                color: si ? 0xff5a3c : 0xffb199, wireframe: true, transparent: true, opacity: si ? 0.05 : 0.035, depthWrite: false
            }));
            group.add(shell);
        });

        var state = { focus: -1, dim: null, rotY: 0, rotX: -0.18, velY: reduceMotion ? 0 : 0.0018, dragging: false };
        var BASE = new THREE.Color(opts.lineColor || 0xff5a3c), HOT = new THREE.Color(0xffd2b0), OFF = new THREE.Color(0x2a0805);

        function paintLinks() {
            links.forEach(function (l, k) {
                var hot = state.focus !== -1 && (l[0] === state.focus || l[1] === state.focus);
                var dimmed = state.dim && (state.dim[l[0]] || state.dim[l[1]]);
                var c = hot ? HOT : dimmed ? OFF : BASE;
                var f = hot ? 1 : dimmed ? 0.4 : 0.42;
                for (var e = 0; e < 2; e++) lCol.set([c.r * f, c.g * f, c.b * f], k * 6 + e * 3);
            });
            lGeo.attributes.color.needsUpdate = true;
        }
        paintLinks();

        function resize() {
            var w = stage.clientWidth || 1, h = stage.clientHeight || 1;
            renderer.setSize(w, h, false);
            camera.aspect = w / h;
            var fit = opts.radius * 1.08 / Math.tan(THREE.MathUtils.degToRad(camera.fov / 2));
            camera.position.set(0, 0, Math.max(fit, fit / Math.min(1, camera.aspect)));
            camera.updateProjectionMatrix();
        }
        resize();
        window.addEventListener('resize', resize);

        // Drag to spin (mouse or touch); a light parallax follows the pointer.
        var last = null, parallax = new THREE.Vector2(), moved = 0;
        stage.addEventListener('pointerdown', function (e) {
            last = { x: e.clientX, y: e.clientY };
            moved = 0;
            state.dragging = true;
        });
        window.addEventListener('pointerup', function () { state.dragging = false; last = null; });
        stage.addEventListener('pointermove', function (e) {
            var r = stage.getBoundingClientRect();
            parallax.set(((e.clientX - r.left) / r.width - 0.5) * 2, ((e.clientY - r.top) / r.height - 0.5) * 2);
            if (!state.dragging || !last) return;
            var dx = e.clientX - last.x, dy = e.clientY - last.y;
            moved += Math.abs(dx) + Math.abs(dy);
            state.velY = dx * 0.0009;
            state.rotY += dx * 0.006;
            state.rotX = Math.max(-0.9, Math.min(0.9, state.rotX + dy * 0.004));
            last = { x: e.clientX, y: e.clientY };
        });
        // A drag should not count as a click on the node under the pointer.
        stage.addEventListener('click', function (e) {
            if (moved > 8) { e.preventDefault(); e.stopPropagation(); }
        }, true);

        var tmp = new THREE.Vector3(), camDist = 0, running = false, visible = true;
        function frame() {
            if (!state.dragging) {
                state.rotY += state.velY;
                state.velY += ((reduceMotion ? 0 : 0.0018) - state.velY) * 0.02;
            }
            group.rotation.y = state.rotY + parallax.x * 0.12;
            group.rotation.x = state.rotX + parallax.y * 0.08;
            points.rotation.y = state.rotY * 0.35;
            group.updateMatrixWorld();
            camDist = camera.position.z;

            var w = stage.clientWidth, h = stage.clientHeight;
            nodes.forEach(function (n, i) {
                tmp.copy(n.pos).applyMatrix4(group.matrixWorld);
                var depth = tmp.z;                         // + toward the camera
                var scale = camDist / (camDist - depth);
                tmp.project(camera);
                var front = (depth / opts.radius + 1) / 2; // 0 back .. 1 front
                var dimmed = state.dim && state.dim[i];
                var isFocus = i === state.focus;
                n.el.style.transform = 'translate(-50%, -50%) translate(' + ((tmp.x + 1) / 2 * w).toFixed(1) + 'px,' +
                    ((1 - tmp.y) / 2 * h).toFixed(1) + 'px) scale(' + (scale * (isFocus ? 1.25 : 1)).toFixed(3) + ')';
                n.el.style.zIndex = isFocus ? 2000 : Math.round(front * 1000);
                n.el.style.opacity = dimmed ? 0.12 : (0.35 + front * 0.65).toFixed(2);
                n.el.classList.toggle('is-back', front < 0.35);
            });
            renderer.render(scene, camera);
        }
        function loop() {
            if (!visible) { running = false; return; }
            frame();
            requestAnimationFrame(loop);
        }
        function start() {
            if (running) return;
            running = true;
            requestAnimationFrame(loop);
        }
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (en) {
                visible = en[0].isIntersecting;
                if (visible && !reduceMotion) start();
            }).observe(root);
        }
        frame();
        if (!reduceMotion) start();

        return {
            focus: function (i) { state.focus = i; paintLinks(); if (reduceMotion) frame(); },
            dim: function (map) { state.dim = map; paintLinks(); if (reduceMotion) frame(); }
        };
    }

    function stageMarkup(root) {
        var stage = document.createElement('div');
        stage.className = 'fx3d-stage';
        stage.innerHTML = '<canvas class="fx3d-canvas" aria-hidden="true"></canvas><div class="fx3d-layer"></div>';
        root.appendChild(stage);
        return stage.querySelector('.fx3d-layer');
    }

    // ---------- Alumni ↔ skills network (alumni-hub.php) ----------
    function initGalaxy(root) {
        var people = JSON.parse(root.getAttribute('data-people') || '[]');
        if (!people.length) return;
        var layer = stageMarkup(root);
        var tip = document.createElement('div');
        tip.className = 'fx3d-tip';
        tip.hidden = true;
        root.querySelector('.fx3d-stage').appendChild(tip);

        // Unique skills, most shared first, at most 28 on the outer sphere.
        var count = {};
        people.forEach(function (p) { p.skills.forEach(function (s) { var k = s.toLowerCase(); count[k] = count[k] || { name: s, n: 0 }; count[k].n++; }); });
        var skills = Object.keys(count).sort(function (a, b) { return count[b].n - count[a].n; }).slice(0, 28);

        var R = 4.2;
        var nodes = [], links = [];
        var inner = fibonacciSphere(people.length, R * 0.62, 0.4), outer = fibonacciSphere(skills.length, R, 1.3);
        people.forEach(function (p, i) {
            var a = document.createElement('a');
            a.className = 'fx3d-person' + (p.available ? ' is-available' : '');
            a.href = p.url;
            a.setAttribute('aria-label', p.name);
            a.innerHTML = '<img alt="" loading="lazy"><span></span>';
            a.querySelector('img').src = p.photo;
            a.querySelector('span').textContent = p.name.split(' ')[0];
            layer.appendChild(a);
            nodes.push({ el: a, pos: inner[i], kind: 'person', data: p });
        });
        var skillIndex = {};
        skills.forEach(function (k, j) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'fx3d-skill';
            b.textContent = count[k].name;
            b.style.fontSize = (0.72 + Math.min(count[k].n, 6) * 0.05) + 'rem';
            layer.appendChild(b);
            skillIndex[k] = nodes.length;
            nodes.push({ el: b, pos: outer[j], kind: 'skill', key: k });
        });
        people.forEach(function (p, i) {
            p.skills.forEach(function (s) {
                var j = skillIndex[s.toLowerCase()];
                if (j !== undefined) links.push([i, j]);
            });
        });

        var api = createScene(root, nodes, links, { radius: R, particles: 420 });
        if (!api) { root.hidden = true; return; }

        function neighbours(i) {
            var set = {};
            links.forEach(function (l) { if (l[0] === i) set[l[1]] = true; if (l[1] === i) set[l[0]] = true; });
            return set;
        }
        var filterDim = null;
        function showFocus(i) {
            api.focus(i);
            if (i === -1) { api.dim(filterDim); tip.hidden = true; return; }
            var keep = neighbours(i), dim = {};
            keep[i] = true;
            nodes.forEach(function (n, k) { if (!keep[k]) dim[k] = true; });
            api.dim(dim);
            var n = nodes[i];
            if (n.kind === 'person') {
                tip.innerHTML = '<strong></strong><em></em><small></small>';
                tip.querySelector('strong').textContent = n.data.name;
                tip.querySelector('em').textContent = n.data.headline || '';
                tip.querySelector('small').textContent = (n.data.type ? n.data.type + ' · ' : '') + (n.data.available ? '● พร้อมรับงาน · ' : '') + 'คลิกเพื่อดูโปรไฟล์';
            } else {
                var who = Object.keys(keep).filter(function (k) { return nodes[k].kind === 'person'; }).length;
                tip.innerHTML = '<strong></strong><small></small>';
                tip.querySelector('strong').textContent = n.el.textContent;
                tip.querySelector('small').textContent = who + ' คน · คลิกเพื่อกรองรายชื่อด้านล่าง';
            }
            tip.hidden = false;
        }
        nodes.forEach(function (n, i) {
            n.el.addEventListener('pointerenter', function () { showFocus(i); });
            n.el.addEventListener('pointerleave', function () { showFocus(-1); });
            n.el.addEventListener('focus', function () { showFocus(i); });
            n.el.addEventListener('blur', function () { showFocus(-1); });
            if (n.kind === 'person' && !finePointer) {
                // Touch: first tap previews, second tap opens.
                n.el.addEventListener('click', function (e) {
                    if (!n.el.classList.contains('is-armed')) {
                        e.preventDefault();
                        nodes.forEach(function (m) { m.el.classList.remove('is-armed'); });
                        n.el.classList.add('is-armed');
                        showFocus(i);
                    }
                });
            }
            if (n.kind === 'skill') {
                n.el.addEventListener('click', function () {
                    var chip = document.querySelector('.hub-chip[data-skill="' + n.key.replace(/"/g, '\\"') + '"]');
                    if (chip) chip.click();
                    else {
                        var s = document.getElementById('hubSearch');
                        if (s) { s.value = n.el.textContent; s.dispatchEvent(new Event('input')); }
                    }
                    var grid = document.getElementById('hubGrid');
                    if (grid) grid.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
                });
            }
        });
        tip.parentNode.addEventListener('pointermove', function (e) {
            var r = tip.parentNode.getBoundingClientRect();
            tip.style.left = (e.clientX - r.left) + 'px';
            tip.style.top = (e.clientY - r.top) + 'px';
        });

        // Follow the list filters: people not shown below fade out in 3D too.
        document.addEventListener('hub:filter', function (e) {
            var shown = e.detail.ids, dim = {}, any = false;
            people.forEach(function (p, i) { if (!shown[p.id]) { dim[i] = true; any = true; } });
            if (any) {
                // Skills nobody visible has fade as well.
                nodes.forEach(function (n, k) {
                    if (n.kind !== 'skill') return;
                    var used = links.some(function (l) { return l[1] === k && !dim[l[0]]; });
                    if (!used) dim[k] = true;
                });
            }
            filterDim = any ? dim : null;
            api.dim(filterDim);
        });
    }

    // ---------- One person's skills as a 3D word sphere (alumni-profile.php) ----------
    function initSkillSphere(root) {
        var words = JSON.parse(root.getAttribute('data-skills') || '[]');
        if (!words.length) return;
        var layer = stageMarkup(root);
        var R = 3;
        var pts = fibonacciSphere(Math.max(words.length, 1), R, 0.7);
        var nodes = words.map(function (w, i) {
            var s = document.createElement('span');
            s.className = 'fx3d-word';
            s.textContent = w;
            layer.appendChild(s);
            return { el: s, pos: pts[i] };
        });
        // Each word links to its two nearest neighbours for a constellation look.
        var links = [];
        nodes.forEach(function (n, i) {
            nodes.map(function (m, j) { return { j: j, d: n.pos.distanceTo(m.pos) }; })
                .filter(function (o) { return o.j > i; })
                .sort(function (a, b) { return a.d - b.d; })
                .slice(0, 2)
                .forEach(function (o) { links.push([i, o.j]); });
        });
        var api = createScene(root, nodes, links, { radius: R, particles: 260, fov: 40 });
        if (!api) { root.hidden = true; return; }
        nodes.forEach(function (n, i) {
            n.el.addEventListener('pointerenter', function () { api.focus(i); n.el.classList.add('is-hot'); });
            n.el.addEventListener('pointerleave', function () { api.focus(-1); n.el.classList.remove('is-hot'); });
        });
    }

    // ---------- Card tilt + glare ----------
    function initTilt() {
        if (reduceMotion || !finePointer) return;
        var selector = '.hub-card, .hub-work';
        document.addEventListener('pointermove', function (e) {
            var el = e.target.closest && e.target.closest(selector);
            if (!el) return;
            var r = el.getBoundingClientRect();
            var x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height;
            el.style.setProperty('--hrx', ((0.5 - y) * 9).toFixed(2) + 'deg');
            el.style.setProperty('--hry', ((x - 0.5) * 11).toFixed(2) + 'deg');
            el.style.setProperty('--hmx', (x * 100).toFixed(1) + '%');
            el.style.setProperty('--hmy', (y * 100).toFixed(1) + '%');
            el.classList.add('is-hub-tilt');
        }, { passive: true });
        document.addEventListener('pointerout', function (e) {
            var el = e.target.closest && e.target.closest(selector);
            if (!el || el.contains(e.relatedTarget)) return;
            el.classList.remove('is-hub-tilt');
            el.style.removeProperty('--hrx');
            el.style.removeProperty('--hry');
        });
    }

    // ---------- Buttons / chips lean toward the pointer ----------
    function initMagnetic() {
        if (reduceMotion || !finePointer) return;
        document.querySelectorAll('.hub-chip, .hub-cta .btn, .hub-contact .btn, .hub-badge').forEach(function (el) {
            el.classList.add('hub-magnet');
            el.addEventListener('pointermove', function (e) {
                var r = el.getBoundingClientRect();
                var dx = (e.clientX - r.left - r.width / 2) / r.width, dy = (e.clientY - r.top - r.height / 2) / r.height;
                el.style.transform = 'translate(' + (dx * 8).toFixed(1) + 'px,' + (dy * 6).toFixed(1) + 'px)';
            });
            el.addEventListener('pointerleave', function () { el.style.transform = ''; });
        });
    }

    // ---------- Count-up numbers ----------
    function initCounters() {
        var els = document.querySelectorAll('.hub-stat-num[data-count]');
        if (!els.length) return;
        function run(el) {
            var target = parseInt(el.getAttribute('data-count'), 10) || 0;
            if (reduceMotion) { el.textContent = target; return; }
            var t0 = performance.now();
            (function tick(now) {
                var p = Math.min(1, (now - t0) / 1200);
                el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3)));
                if (p < 1) requestAnimationFrame(tick);
            })(t0);
        }
        if (!('IntersectionObserver' in window)) { els.forEach(run); return; }
        var io = new IntersectionObserver(function (en) {
            en.forEach(function (x) { if (x.isIntersecting) { run(x.target); io.unobserve(x.target); } });
        });
        els.forEach(function (el) { io.observe(el); });
    }

    // ---------- Fade in on scroll ----------
    function initReveal() {
        if (reduceMotion || !('IntersectionObserver' in window)) return;
        var items = document.querySelectorAll('.hub-card, .hub-work, .hub-stat, .hub-profile-side, .hub-galaxy, .hub-sphere');
        var io = new IntersectionObserver(function (en) {
            en.forEach(function (x) {
                if (!x.isIntersecting) return;
                var el = x.target;
                el.classList.add('hub-in');
                io.unobserve(el);
                // Hand transform back to hover/tilt once the entrance is done.
                setTimeout(function () { el.classList.remove('hub-reveal', 'hub-in'); }, 1400);
            });
        }, { rootMargin: '0px 0px -6% 0px', threshold: 0.06 });
        items.forEach(function (el) {
            var idx = Array.prototype.indexOf.call(el.parentNode.children, el);
            el.style.setProperty('--hub-delay', Math.min(idx, 10) * 70 + 'ms');
            el.classList.add('hub-reveal');
            io.observe(el);
        });
    }

    function boot() {
        initTilt();
        initMagnetic();
        initCounters();
        initReveal();
        var galaxies = document.querySelectorAll('.hub-galaxy'), spheres = document.querySelectorAll('.hub-sphere');
        if (!galaxies.length && !spheres.length) return;
        if (!window.THREE || !webglOk()) {
            galaxies.forEach(function (g) { g.hidden = true; });
            spheres.forEach(function (s) { s.hidden = true; });
            return;
        }
        galaxies.forEach(initGalaxy);
        spheres.forEach(initSkillSphere);
    }
    // three.js is a deferred script in the footer, which may run after this
    // one; DOMContentLoaded fires only once every deferred script has run.
    if (document.readyState === 'complete') boot();
    else document.addEventListener('DOMContentLoaded', boot);
})();
