/**
 * Homepage hero: particle-text title that scatters under the pointer, low-poly
 * multimedia icons (camera, film reel, gamepad, headphones, VR headset,
 * clapperboard, play button, pen) orbiting around it, a glitching keyword
 * rotator and an equalizer strip. Uses three.js (r128, loaded by index.php).
 *
 * Without WebGL / three.js the CSS fallback title stays visible; with
 * prefers-reduced-motion a single still frame is drawn.
 */
(function () {
    var hero = document.getElementById('heroFx');
    if (!hero) return;

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var isSmall = window.innerWidth < 720;

    // ---------- Keyword rotator (works without WebGL) ----------
    (function keywords() {
        var el = hero.querySelector('.hero-fx-glitch');
        var words = [];
        try { words = JSON.parse(hero.getAttribute('data-keywords') || '[]'); } catch (e) {}
        if (!el || words.length < 2 || reduceMotion) return;
        var i = 0;
        setInterval(function () {
            i = (i + 1) % words.length;
            el.classList.remove('is-glitching');
            void el.offsetWidth; // restart the CSS animation
            el.textContent = words[i];
            el.setAttribute('data-text', words[i]);
            el.classList.add('is-glitching');
        }, 2600);
    })();

    // ---------- Equalizer: give each bar its own rhythm ----------
    (function equalizer() {
        var bars = hero.querySelectorAll('.hero-fx-eq span');
        for (var i = 0; i < bars.length; i++) {
            // Taller in the middle, like a spectrum analyser.
            var centre = 1 - Math.abs(i / (bars.length - 1) - 0.5) * 1.4;
            bars[i].style.setProperty('--eq-max', (0.35 + centre * 0.65 * (0.6 + Math.random() * 0.4)).toFixed(2));
            bars[i].style.setProperty('--eq-dur', (0.55 + Math.random() * 0.9).toFixed(2) + 's');
            bars[i].style.setProperty('--eq-delay', (-Math.random() * 1.5).toFixed(2) + 's');
        }
    })();

    function start() {
        if (!window.THREE) return;
        var canvas = hero.querySelector('.hero-fx-canvas');
        var renderer;
        try {
            renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true, powerPreference: 'low-power' });
        } catch (e) {
            return;
        }
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, isSmall ? 1.5 : 1.75));

        var scene = new THREE.Scene();
        var camera = new THREE.PerspectiveCamera(35, 1, 0.1, 100);
        camera.position.set(0, 0, 20);

        var viewW = 10, viewH = 10;
        function resize() {
            var w = hero.clientWidth, h = hero.clientHeight;
            renderer.setSize(w, h, false);
            camera.aspect = w / h;
            camera.updateProjectionMatrix();
            viewH = 2 * camera.position.z * Math.tan(THREE.MathUtils.degToRad(camera.fov / 2));
            viewW = viewH * camera.aspect;
        }
        resize();

        // ---------- Particle text ----------
        var text = (hero.getAttribute('data-particle-text') || 'MULTIMEDIA').split('|');
        var particles = null;

        function buildParticles() {
            if (particles) {
                scene.remove(particles.points);
                particles.points.geometry.dispose();
            }
            var c = document.createElement('canvas');
            var cw = 1400;
            c.width = cw;
            var ctx = c.getContext('2d');
            // Fit the longest line into the canvas width, then size the canvas
            // height to the lines so the line gap scales with the font.
            var size = 240;
            ctx.font = '700 ' + size + 'px Kanit, sans-serif';
            var widest = Math.max.apply(null, text.map(function (t) { return ctx.measureText(t).width; }));
            size = Math.floor(size * Math.min(1, (cw * 0.94) / widest));
            var lineH = Math.round(size * 1.08);
            c.height = lineH * text.length + 40;
            ctx.font = '700 ' + size + 'px Kanit, sans-serif'; // resizing the canvas resets the context
            ctx.fillStyle = '#fff';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            text.forEach(function (t, i) { ctx.fillText(t, cw / 2, 20 + lineH * (i + 0.5)); });

            var img = ctx.getImageData(0, 0, c.width, c.height).data;
            var step = isSmall ? 7 : 6;
            var homes = [];
            for (var y = 0; y < c.height; y += step) {
                for (var x = 0; x < cw; x += step) {
                    if (img[(y * cw + x) * 4 + 3] > 128) homes.push(x, y);
                }
            }
            var count = homes.length / 2;
            // Text spans ~78% of the view width (less on phones), sitting in the upper part of the hero.
            var targetW = Math.min(viewW * (isSmall ? 0.9 : 0.72), 15);
            var scale = targetW / cw;
            var offsetY = viewH * (isSmall ? 0.2 : 0.16);

            var home = new Float32Array(count * 3);
            var pos = new Float32Array(count * 3);
            var vel = new Float32Array(count * 3);
            var seed = new Float32Array(count);
            for (var i = 0; i < count; i++) {
                home[i * 3] = (homes[i * 2] - cw / 2) * scale;
                home[i * 3 + 1] = -(homes[i * 2 + 1] - c.height / 2) * scale + offsetY;
                home[i * 3 + 2] = 0;
                seed[i] = Math.random();
                if (reduceMotion) {
                    pos[i * 3] = home[i * 3]; pos[i * 3 + 1] = home[i * 3 + 1]; pos[i * 3 + 2] = 0;
                } else {
                    // Intro: particles fly in from a wide random cloud.
                    pos[i * 3] = (Math.random() - 0.5) * viewW * 1.6;
                    pos[i * 3 + 1] = (Math.random() - 0.5) * viewH * 1.6;
                    pos[i * 3 + 2] = (Math.random() - 0.5) * 12;
                }
            }

            var geo = new THREE.BufferGeometry();
            geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
            geo.setAttribute('seed', new THREE.BufferAttribute(seed, 1));
            var mat = new THREE.ShaderMaterial({
                transparent: true,
                depthWrite: false,
                blending: THREE.AdditiveBlending,
                uniforms: {
                    uTime: { value: 0 },
                    uSize: { value: (isSmall ? 3.6 : 4.4) * renderer.getPixelRatio() },
                    uMinX: { value: -targetW / 2 },
                    uWidth: { value: targetW }
                },
                vertexShader: [
                    'attribute float seed;',
                    'uniform float uTime; uniform float uSize; uniform float uMinX; uniform float uWidth;',
                    'varying vec3 vColor;',
                    'void main(){',
                    '  vec4 mv = modelViewMatrix * vec4(position,1.);',
                    '  gl_Position = projectionMatrix * mv;',
                    '  float twinkle = .75 + .25 * sin(uTime * 3. + seed * 40.);',
                    '  gl_PointSize = uSize * twinkle * (20. / -mv.z);',
                    // left -> right sweep: white -> coral -> red, with a moving highlight
                    '  float t = clamp((position.x - uMinX) / uWidth, 0., 1.);',
                    '  vec3 a = vec3(1., .93, .9), b = vec3(1., .45, .32), c = vec3(.85, .12, .06);',
                    '  vColor = mix(mix(a, b, smoothstep(0., .55, t)), c, smoothstep(.5, 1., t));',
                    '  float sweep = smoothstep(.08, 0., abs(fract(uTime * .12) * 1.4 - .2 - t));',
                    '  vColor += sweep * .6;',
                    '}'
                ].join('\n'),
                fragmentShader: [
                    'varying vec3 vColor;',
                    'void main(){',
                    '  float d = length(gl_PointCoord - .5);',
                    '  float a = smoothstep(.5, .12, d);',
                    '  gl_FragColor = vec4(vColor * 1.25, a);',
                    '}'
                ].join('\n')
            });
            var points = new THREE.Points(geo, mat);
            scene.add(points);
            particles = { points: points, home: home, pos: pos, vel: vel, count: count, mat: mat };
        }

        // ---------- Low-poly multimedia icons ----------
        var accent = new THREE.Color(0xff5a3c);
        var edgeMat = new THREE.LineBasicMaterial({ color: accent, transparent: true, opacity: 0.9 });
        var faceMat = new THREE.MeshBasicMaterial({ color: 0x3a0806, transparent: true, opacity: 0.35, depthWrite: false });

        function part(geometry, x, y, z, rx, ry, rz) {
            var g = new THREE.Group();
            g.add(new THREE.Mesh(geometry, faceMat));
            g.add(new THREE.LineSegments(new THREE.EdgesGeometry(geometry, 20), edgeMat));
            g.position.set(x || 0, y || 0, z || 0);
            g.rotation.set(rx || 0, ry || 0, rz || 0);
            return g;
        }
        function icon(parts) {
            var g = new THREE.Group();
            parts.forEach(function (p) { g.add(p); });
            return g;
        }
        var H = Math.PI / 2;
        var iconBuilders = [
            function camera() {
                return icon([
                    part(new THREE.BoxGeometry(1.3, 0.85, 0.6)),
                    part(new THREE.CylinderGeometry(0.32, 0.36, 0.5, 16), 0, 0, 0.5, H),
                    part(new THREE.BoxGeometry(0.4, 0.2, 0.4), -0.35, 0.52, 0)
                ]);
            },
            function filmReel() {
                var parts = [part(new THREE.CylinderGeometry(0.75, 0.75, 0.16, 28), 0, 0, 0, H)];
                for (var i = 0; i < 5; i++) {
                    var a = i / 5 * Math.PI * 2;
                    parts.push(part(new THREE.CylinderGeometry(0.15, 0.15, 0.2, 12), Math.cos(a) * 0.42, Math.sin(a) * 0.42, 0, H));
                }
                return icon(parts);
            },
            function gamepad() {
                return icon([
                    part(new THREE.BoxGeometry(1.2, 0.55, 0.3)),
                    part(new THREE.CylinderGeometry(0.3, 0.3, 0.3, 16), -0.6, -0.08, 0, H),
                    part(new THREE.CylinderGeometry(0.3, 0.3, 0.3, 16), 0.6, -0.08, 0, H),
                    part(new THREE.BoxGeometry(0.34, 0.1, 0.1), -0.45, 0, 0.2),
                    part(new THREE.BoxGeometry(0.1, 0.34, 0.1), -0.45, 0, 0.2),
                    part(new THREE.SphereGeometry(0.07, 8, 6), 0.42, 0.08, 0.2),
                    part(new THREE.SphereGeometry(0.07, 8, 6), 0.58, -0.06, 0.2)
                ]);
            },
            function headphones() {
                return icon([
                    part(new THREE.TorusGeometry(0.62, 0.06, 8, 24, Math.PI)),
                    part(new THREE.CylinderGeometry(0.22, 0.22, 0.24, 16), -0.62, -0.08, 0, 0, 0, H),
                    part(new THREE.CylinderGeometry(0.22, 0.22, 0.24, 16), 0.62, -0.08, 0, 0, 0, H)
                ]);
            },
            function vrHeadset() {
                return icon([
                    part(new THREE.BoxGeometry(1.3, 0.62, 0.5)),
                    part(new THREE.CylinderGeometry(0.2, 0.2, 0.1, 16), -0.3, 0, 0.28, H),
                    part(new THREE.CylinderGeometry(0.2, 0.2, 0.1, 16), 0.3, 0, 0.28, H),
                    part(new THREE.TorusGeometry(0.7, 0.04, 6, 20, Math.PI), 0, 0, -0.35, H)
                ]);
            },
            function clapperboard() {
                return icon([
                    part(new THREE.BoxGeometry(1.2, 0.8, 0.08)),
                    part(new THREE.BoxGeometry(1.2, 0.2, 0.08), -0.08, 0.6, 0, 0, 0, 0.28)
                ]);
            },
            function playButton() {
                return icon([
                    part(new THREE.CylinderGeometry(0.62, 0.62, 0.22, 3), 0, 0, 0, H, 0, -H)
                ]);
            },
            function pen() {
                return icon([
                    part(new THREE.CylinderGeometry(0.12, 0.12, 1.1, 10), 0, 0.15, 0),
                    part(new THREE.ConeGeometry(0.12, 0.35, 10), 0, -0.57, 0, Math.PI)
                ]);
            }
        ];

        var orbit = new THREE.Group();
        scene.add(orbit);
        var icons = [];
        var iconCount = isSmall ? 5 : iconBuilders.length;
        for (var k = 0; k < iconCount; k++) {
            var m = iconBuilders[k]();
            m.userData = {
                angle: k / iconCount * Math.PI * 2,
                speed: 0.08 + Math.random() * 0.05,
                spin: new THREE.Vector3(Math.random() - 0.5, Math.random() - 0.5, Math.random() - 0.5).multiplyScalar(0.9),
                bob: Math.random() * Math.PI * 2
            };
            m.scale.setScalar(isSmall ? 0.75 : 1);
            orbit.add(m);
            icons.push(m);
        }

        function placeIcons(t) {
            var rx = viewW * (isSmall ? 0.42 : 0.4), ry = viewH * 0.36;
            icons.forEach(function (m) {
                var d = m.userData;
                var a = d.angle + t * d.speed;
                m.position.set(Math.cos(a) * rx, Math.sin(a) * ry * 0.8 + Math.sin(t + d.bob) * 0.25, Math.sin(a) * 3 - 1);
                m.rotation.set(t * d.spin.x, t * d.spin.y, t * d.spin.z);
                // Icons passing behind the title fade out so the text stays readable.
                var behind = Math.max(0, -Math.sin(a));
                m.scale.setScalar((isSmall ? 0.75 : 1) * (1 - behind * 0.35));
            });
        }

        // ---------- Pointer ----------
        var pointer = new THREE.Vector2(9, 9), pointerWorld = new THREE.Vector3(999, 999, 0), tilt = new THREE.Vector2();
        var raycaster = new THREE.Raycaster(), plane = new THREE.Plane(new THREE.Vector3(0, 0, 1), 0);
        hero.addEventListener('pointermove', function (e) {
            var r = hero.getBoundingClientRect();
            pointer.x = ((e.clientX - r.left) / r.width) * 2 - 1;
            pointer.y = -((e.clientY - r.top) / r.height) * 2 + 1;
            raycaster.setFromCamera(pointer, camera);
            raycaster.ray.intersectPlane(plane, pointerWorld);
        }, { passive: true });
        hero.addEventListener('pointerleave', function () { pointerWorld.set(999, 999, 0); pointer.set(0, 0); });

        function stepParticles(dt) {
            var p = particles, R = isSmall ? 1.3 : 1.6, R2 = R * R;
            for (var i = 0; i < p.count; i++) {
                var ix = i * 3, iy = ix + 1, iz = ix + 2;
                var dx = p.pos[ix] - pointerWorld.x, dy = p.pos[iy] - pointerWorld.y;
                var d2 = dx * dx + dy * dy;
                if (d2 < R2) {
                    var f = (1 - d2 / R2) * 0.9;
                    var inv = 1 / Math.sqrt(d2 + 0.0001);
                    p.vel[ix] += dx * inv * f;
                    p.vel[iy] += dy * inv * f;
                    p.vel[iz] += f * 0.6;
                }
                p.vel[ix] += (p.home[ix] - p.pos[ix]) * 0.045;
                p.vel[iy] += (p.home[iy] - p.pos[iy]) * 0.045;
                p.vel[iz] += (p.home[iz] - p.pos[iz]) * 0.045;
                p.vel[ix] *= 0.86; p.vel[iy] *= 0.86; p.vel[iz] *= 0.86;
                p.pos[ix] += p.vel[ix] * dt * 60 * 0.26;
                p.pos[iy] += p.vel[iy] * dt * 60 * 0.26;
                p.pos[iz] += p.vel[iz] * dt * 60 * 0.26;
            }
            p.points.geometry.attributes.position.needsUpdate = true;
        }

        // ---------- Loop ----------
        var clock = new THREE.Clock(), visible = true, running = false;
        function frame() {
            if (!visible || document.hidden) { running = false; return; }
            var dt = Math.min(clock.getDelta(), 0.05), t = clock.elapsedTime;
            particles.mat.uniforms.uTime.value = t;
            stepParticles(dt);
            placeIcons(t);
            tilt.x += (pointer.y * 0.18 - tilt.x) * 0.05;
            tilt.y += (pointer.x * 0.25 - tilt.y) * 0.05;
            orbit.rotation.set(tilt.x, tilt.y, 0);
            renderer.render(scene, camera);
            requestAnimationFrame(frame);
        }
        function play() {
            if (reduceMotion || running) return;
            running = true;
            clock.getDelta();
            requestAnimationFrame(frame);
        }
        function still() {
            placeIcons(4);
            renderer.render(scene, camera);
        }

        var fontReady = document.fonts && document.fonts.load ? document.fonts.load('700 100px Kanit') : Promise.resolve();
        fontReady.catch(function () {}).then(function () {
            buildParticles();
            hero.classList.add('hero-fx-ready');
            if (reduceMotion) still(); else play();
        });

        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                visible = entries[0].isIntersecting;
                if (visible && particles) play();
            }).observe(hero);
        }
        document.addEventListener('visibilitychange', function () { if (!document.hidden && particles) play(); });

        // Phones fire resize when the address bar shows/hides; only rebuild
        // the particle text when the width actually changes.
        var resizeTimer, lastWidth = hero.clientWidth;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                isSmall = window.innerWidth < 720;
                resize();
                if (particles && hero.clientWidth !== lastWidth) buildParticles();
                lastWidth = hero.clientWidth;
                if (reduceMotion) still();
            }, 200);
        });
    }

    if (document.readyState === 'complete') start();
    else window.addEventListener('load', start);
})();
