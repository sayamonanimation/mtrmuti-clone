document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('navToggle');
    var nav = document.getElementById('mainNav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            nav.classList.toggle('open');
        });
    }

    var sliderEl = document.querySelector('.slider');
    if (sliderEl) {
        var slides = sliderEl.querySelectorAll('.slide');
        var dots = sliderEl.querySelectorAll('.slider-dot');
        var prevBtn = sliderEl.querySelector('.slider-arrow-prev');
        var nextBtn = sliderEl.querySelector('.slider-arrow-next');
        var current = 0;
        var timer = null;

        var goTo = function (index) {
            slides[current].classList.remove('active');
            if (dots[current]) dots[current].classList.remove('active');
            current = (index + slides.length) % slides.length;
            slides[current].classList.add('active');
            if (dots[current]) dots[current].classList.add('active');
        };

        var startAuto = function () {
            timer = setInterval(function () { goTo(current + 1); }, 4000);
        };

        var resetAuto = function () {
            clearInterval(timer);
            startAuto();
        };

        if (slides.length > 1) {
            startAuto();
            if (prevBtn) prevBtn.addEventListener('click', function () { goTo(current - 1); resetAuto(); });
            if (nextBtn) nextBtn.addEventListener('click', function () { goTo(current + 1); resetAuto(); });
            dots.forEach(function (dot, i) {
                dot.addEventListener('click', function () { goTo(i); resetAuto(); });
            });
        }
    }

    document.querySelectorAll('[data-carousel-prev], [data-carousel-next]').forEach(function (btn) {
        var targetId = btn.getAttribute('data-carousel-prev') || btn.getAttribute('data-carousel-next');
        var track = document.getElementById(targetId);
        if (!track) return;
        var direction = btn.hasAttribute('data-carousel-prev') ? -1 : 1;
        btn.addEventListener('click', function () {
            var card = track.querySelector('.post-card');
            var step = card ? card.offsetWidth + 18 : 300;
            track.scrollBy({ left: direction * step, behavior: 'smooth' });
        });
    });

    var chatToggle = document.getElementById('chatbotToggle');
    var chatPanel = document.getElementById('chatbotPanel');
    var chatClose = document.getElementById('chatbotClose');
    var chatForm = document.getElementById('chatbotForm');
    var chatInput = document.getElementById('chatbotInput');
    var chatMessages = document.getElementById('chatbotMessages');
    var chatStrings = window.__chatbotStrings || { notFound: 'Not found.', error: 'Error.', readMore: 'View details' };

    function openChat() {
        chatPanel.hidden = false;
        chatToggle.setAttribute('aria-expanded', 'true');
        chatInput.focus();
    }
    function closeChat() {
        chatPanel.hidden = true;
        chatToggle.setAttribute('aria-expanded', 'false');
    }
    function addMessage(text, kind) {
        var msg = document.createElement('div');
        msg.className = 'chatbot-msg chatbot-msg-' + kind;
        msg.textContent = text;
        chatMessages.appendChild(msg);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        return msg;
    }
    function addResults(results) {
        var wrap = document.createElement('div');
        wrap.className = 'chatbot-msg chatbot-msg-bot chatbot-results';
        results.forEach(function (r) {
            var item = document.createElement('div');
            item.className = 'chatbot-result';
            var title = document.createElement('div');
            title.className = 'chatbot-result-title';
            title.textContent = r.title;
            var excerpt = document.createElement('div');
            excerpt.className = 'chatbot-result-excerpt';
            excerpt.textContent = r.excerpt;
            var link = document.createElement('a');
            link.className = 'chatbot-result-link';
            link.href = r.url;
            link.textContent = chatStrings.readMore;
            item.appendChild(title);
            if (r.excerpt) item.appendChild(excerpt);
            item.appendChild(link);
            wrap.appendChild(item);
        });
        chatMessages.appendChild(wrap);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    if (chatToggle && chatPanel) {
        chatToggle.addEventListener('click', function () {
            if (chatPanel.hidden) openChat(); else closeChat();
        });
        if (chatClose) chatClose.addEventListener('click', closeChat);

        chatForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var q = chatInput.value.trim();
            if (!q) return;
            addMessage(q, 'user');
            chatInput.value = '';
            chatInput.disabled = true;

            fetch('/chatbot.php?q=' + encodeURIComponent(q))
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.results && data.results.length) {
                        addResults(data.results);
                    } else {
                        addMessage(chatStrings.notFound, 'bot');
                    }
                })
                .catch(function () {
                    addMessage(chatStrings.error, 'bot');
                })
                .finally(function () {
                    chatInput.disabled = false;
                    chatInput.focus();
                });
        });
    }
});
