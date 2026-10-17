/**
 * Homepage statistics as WebGL 3D bar charts (three.js r128, loaded by the
 * homepage hero). Every .stats3d block on the page becomes a chart: glowing
 * bars grow when scrolled into view, sit on a neon grid floor, tilt with the
 * pointer, and show a tooltip on hover. Values and year labels are HTML
 * overlays so Thai text stays crisp.
 *
 * data-stats: [{year, counts: [...], label?}] - one group per year, one bar
 * per series (4 year levels, or a single admissions count).
 * Without WebGL the plain data table inside .stats3d-stage stays visible.
 */
(function () {
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function initChart(root) {
    var data = JSON.parse(root.getAttribute('data-stats') || '[]');
    var colors = JSON.parse(root.getAttribute('data-colors') || '[]');
    var seriesLabels = JSON.parse(root.getAttribute('data-series-labels') || '[]');
    var yearLabel = root.getAttribute('data-year-label') || '';
    var unit = root.getAttribute('data-unit') || '';

    // ---------- Count-up chips ----------
    function countUp() {
        root.querySelectorAll('.stats3d-chip-num[data-count]').forEach(function (el) {
            var target = parseFloat(el.getAttribute('data-count')) || 0;
            var suffix = el.getAttribute('data-suffix') || '';
            var signed = el.hasAttribute('data-signed');
            function show(n) {
                n = Math.round(n);
                el.textContent = (signed && n > 0 ? '+' : '') + n + suffix;
            }
            if (reduceMotion) { show(target); return; }
            var start = performance.now();
            (function tick(now) {
                var p = Math.min(1, (now - start) / 1400);
                show(target * (1 - Math.pow(1 - p, 3)));
                if (p < 1) requestAnimationFrame(tick);
            })(start);
        });
    }

    function start() {
        if (!window.THREE || !data.length) { countUp(); return; }
        var stage = root.querySelector('.stats3d-stage');
        var canvas = root.querySelector('.stats3d-canvas');
        var labelLayer = root.querySelector('.stats3d-labels');
        var tip = root.querySelector('.stats3d-tip');
        var renderer;
        try {
            renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true, powerPreference: 'low-power' });
        } catch (e) {
            countUp();
            return;
        }
        root.classList.add('stats3d-ready');
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));

        var scene = new THREE.Scene();
        var camera = new THREE.PerspectiveCamera(32, 1, 0.1, 200);

        // ---------- Layout ----------
        var levels = Math.max.apply(null, data.map(function (d) { return d.counts.length; }));
        var single = levels === 1;
        var BAR = single ? 1.1 : 0.8, GAP = 0.18, GROUP_GAP = single ? 0.6 : 1.1, MAX_H = 4.2;
        var groupW = levels * BAR + (levels - 1) * GAP;
        var totalW = data.length * groupW + (data.length - 1) * GROUP_GAP;
        var maxVal = Math.max(1, Math.max.apply(null, data.map(function (d) { return Math.max.apply(null, d.counts); })));

        // ---------- Floor: neon grid + soft glow ----------
        var floorSize = Math.max(totalW + 4, 10);
        var grid = new THREE.GridHelper(floorSize, Math.round(floorSize * 2), 0xff5a3c, 0x5a1610);
        grid.material.transparent = true;
        grid.material.opacity = 0.35;
        scene.add(grid);
        var glow = new THREE.Mesh(
            new THREE.PlaneGeometry(floorSize, floorSize * 0.6),
            new THREE.MeshBasicMaterial({ color: 0xff3a1c, transparent: true, opacity: 0.06, depthWrite: false })
        );
        glow.rotation.x = -Math.PI / 2;
        glow.position.y = 0.01;
        scene.add(glow);

        scene.add(new THREE.AmbientLight(0xffffff, 0.45));
        var key = new THREE.DirectionalLight(0xffffff, 0.9);
        key.position.set(4, 8, 6);
        scene.add(key);
        var rim = new THREE.PointLight(0xff5a3c, 1.2, 30);
        rim.position.set(-4, 5, -3);
        scene.add(rim);

        // ---------- Bars ----------
        var bars = [];
        var unitBox = new THREE.BoxGeometry(BAR, 1, BAR);
        unitBox.translate(0, 0.5, 0); // grow upwards from the floor
        var edgeGeo = new THREE.EdgesGeometry(unitBox);
        data.forEach(function (d, gi) {
            var gx = -totalW / 2 + gi * (groupW + GROUP_GAP);
            d.counts.forEach(function (count, li) {
                var color = new THREE.Color(colors[li] || '#ff5a3c');
                var mat = new THREE.MeshStandardMaterial({
                    color: color, emissive: color, emissiveIntensity: 0.35,
                    roughness: 0.35, metalness: 0.2, transparent: true, opacity: 0.92
                });
                var mesh = new THREE.Mesh(unitBox, mat);
                var edges = new THREE.LineSegments(edgeGeo, new THREE.LineBasicMaterial({ color: 0xffd2c4, transparent: true, opacity: 0.55 }));
                mesh.add(edges);
                mesh.position.set(gx + li * (BAR + GAP) + BAR / 2, 0, 0);
                mesh.scale.y = 0.001;
                mesh.userData = { year: d.year, series: d.label || seriesLabels[li] || '', count: count, target: Math.max(0.02, count / maxVal * MAX_H), delay: gi * 0.12 + li * 0.08 };
                scene.add(mesh);

                var label = document.createElement('span');
                label.className = 'stats3d-value';
                label.textContent = count;
                labelLayer.appendChild(label);
                mesh.userData.label = label;
                bars.push(mesh);
            });
            var yl = document.createElement('span');
            yl.className = 'stats3d-year';
            yl.textContent = yearLabel + ' ' + d.year;
            labelLayer.appendChild(yl);
            d.anchor = new THREE.Vector3(gx + groupW / 2, 0, BAR * 0.9);
            d.labelEl = yl;
        });

        // ---------- Camera fit ----------
        var baseAngle = 0;
        function resize() {
            var w = stage.clientWidth, h = stage.clientHeight;
            renderer.setSize(w, h, false);
            camera.aspect = w / h;
            // Distance that fits the chart width (plus margin) at this aspect.
            var halfW = (totalW / 2 + 1.05);
            var fovH = 2 * Math.atan(Math.tan(THREE.MathUtils.degToRad(camera.fov / 2)) * camera.aspect);
            var dist = Math.max(halfW / Math.tan(fovH / 2), (MAX_H + 1.5) / Math.tan(THREE.MathUtils.degToRad(camera.fov / 2)) * 0.6);
            camera.userData.dist = dist;
            camera.updateProjectionMatrix();
        }
        resize();

        // ---------- Pointer: tilt + hover ----------
        var pointer = new THREE.Vector2(), tilt = new THREE.Vector2(), hovered = null, pointerIn = false;
        var raycaster = new THREE.Raycaster();
        stage.addEventListener('pointermove', function (e) {
            var r = stage.getBoundingClientRect();
            pointer.x = ((e.clientX - r.left) / r.width) * 2 - 1;
            pointer.y = -((e.clientY - r.top) / r.height) * 2 + 1;
            pointerIn = true;
            tip.style.left = (e.clientX - r.left) + 'px';
            tip.style.top = (e.clientY - r.top) + 'px';
        }, { passive: true });
        stage.addEventListener('pointerleave', function () { pointerIn = false; pointer.set(0, 0); });

        function updateHover() {
            var hit = null;
            if (pointerIn) {
                raycaster.setFromCamera(pointer, camera);
                var hits = raycaster.intersectObjects(bars, false);
                hit = hits.length ? hits[0].object : null;
            }
            if (hit !== hovered) {
                if (hovered) hovered.material.emissiveIntensity = 0.35;
                hovered = hit;
                if (hovered) {
                    hovered.material.emissiveIntensity = 0.9;
                    var u = hovered.userData;
                    tip.textContent = yearLabel + ' ' + u.year + (u.series ? ' · ' + u.series : '') + ' · ' + u.count + ' ' + unit;
                }
                tip.hidden = !hovered;
                stage.style.cursor = hovered ? 'pointer' : '';
            }
        }

        // ---------- Overlay labels ----------
        var v = new THREE.Vector3();
        function placeLabels() {
            var w = stage.clientWidth, h = stage.clientHeight;
            bars.forEach(function (m) {
                v.set(m.position.x, m.scale.y + 0.25, m.position.z).project(camera);
                var el = m.userData.label;
                el.style.transform = 'translate(-50%, -100%) translate(' + ((v.x + 1) / 2 * w) + 'px,' + ((1 - v.y) / 2 * h) + 'px)';
                el.style.opacity = m.scale.y > 0.1 ? 1 : 0;
                el.classList.toggle('is-hover', m === hovered);
            });
            data.forEach(function (d) {
                v.copy(d.anchor).project(camera);
                d.labelEl.style.transform = 'translate(-50%, 0) translate(' + ((v.x + 1) / 2 * w) + 'px,' + ((1 - v.y) / 2 * h + 6) + 'px)';
            });
        }

        // ---------- Loop ----------
        var clock = new THREE.Clock(false), growStart = null, visible = false, running = false;
        function ease(t) { return 1 - Math.pow(1 - t, 3); }

        function render() {
            var t = clock.getElapsedTime();
            // Bars grow in a cascade once the chart is on screen.
            bars.forEach(function (m) {
                var u = m.userData;
                var p = growStart === null ? 0 : (reduceMotion ? 1 : Math.min(1, Math.max(0, (t - growStart - u.delay) / 1.1)));
                var s = u.target * ease(p);
                m.scale.y = Math.max(0.001, s * (m === hovered ? 1.04 : 1));
            });
            tilt.x += (pointer.y * 0.08 - tilt.x) * 0.06;
            tilt.y += (pointer.x * 0.35 - tilt.y) * 0.06;
            var sway = reduceMotion ? 0 : Math.sin(t * 0.25) * 0.12;
            var angle = baseAngle + tilt.y + sway;
            var dist = camera.userData.dist;
            camera.position.set(Math.sin(angle) * dist, dist * (0.42 + tilt.x), Math.cos(angle) * dist);
            camera.lookAt(0, MAX_H * 0.36, 0);
            rim.intensity = 1 + Math.sin(t * 1.5) * 0.25;
            updateHover();
            renderer.render(scene, camera);
            placeLabels();
        }
        function frame() {
            if (!visible || document.hidden) { running = false; return; }
            render();
            requestAnimationFrame(frame);
        }
        function play() {
            if (running) return;
            running = true;
            if (!clock.running) clock.start();
            requestAnimationFrame(frame);
        }

        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                visible = entries[0].isIntersecting;
                if (visible) {
                    if (growStart === null) { growStart = clock.running ? clock.getElapsedTime() : 0; countUp(); }
                    play();
                }
            }, { threshold: 0.25 }).observe(stage);
        } else {
            visible = true; growStart = 0; countUp(); play();
        }
        document.addEventListener('visibilitychange', function () { if (!document.hidden && visible) play(); });
        window.addEventListener('resize', function () { resize(); if (!running) render(); });
    }

    start();
    }

    function boot() {
        document.querySelectorAll('.stats3d').forEach(initChart);
    }
    if (document.readyState === 'complete') boot();
    else window.addEventListener('load', boot);
})();
