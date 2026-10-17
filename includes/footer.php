</main>
<footer class="site-footer">
    <div class="container footer-inner">
        <div class="footer-col">
            <h3><?= h(get_site_name()) ?></h3>
            <p><?= nl2br(h(get_setting('address'))) ?></p>
        </div>
        <div class="footer-col">
            <h3><?= h(t('footer.contact')) ?></h3>
            <p><?= h(t('footer.phone')) ?>: <?= h(get_setting('phone')) ?></p>
            <p><?= h(t('footer.email')) ?>: <?= h(get_setting('email')) ?></p>
            <?php if ($fb = get_setting('facebook_url')): ?>
            <p><a href="<?= h($fb) ?>" target="_blank" rel="noopener">Facebook</a></p>
            <?php endif; ?>
        </div>
        <div class="footer-col">
            <h3><?= h(t('footer.menu')) ?></h3>
            <ul class="footer-nav">
                <?php foreach (array_slice(nav_items(), 0, 5) as $item): ?>
                <li><a href="<?= h($item['href']) ?>"><?= h($item['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            &copy; <?= date('Y') ?> <?= h(get_site_name()) ?>. <?= h(t('footer.rights')) ?>.
        </div>
    </div>
</footer>

<?php if (get_setting('chatbot_enabled', '1') === '1'): ?>
<button type="button" id="chatbotToggle" class="chatbot-toggle" aria-label="<?= h(t('chatbot.open')) ?>" aria-expanded="false">
    <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
</button>

<div id="chatbotPanel" class="chatbot-panel" hidden>
    <div class="chatbot-header">
        <span><?= h(t('chatbot.title')) ?></span>
        <button type="button" id="chatbotClose" aria-label="<?= h(t('chatbot.close')) ?>">&times;</button>
    </div>
    <div id="chatbotMessages" class="chatbot-messages">
        <div class="chatbot-msg chatbot-msg-bot"><?= h(t('chatbot.greeting')) ?></div>
    </div>
    <form id="chatbotForm" class="chatbot-form">
        <input type="text" id="chatbotInput" placeholder="<?= h(t('chatbot.placeholder')) ?>" autocomplete="off" maxlength="200">
        <button type="submit"><?= h(t('chatbot.send')) ?></button>
    </form>
</div>
<script>
window.__chatbotStrings = {
    notFound: <?= json_encode(t('chatbot.not_found')) ?>,
    error: <?= json_encode(t('chatbot.error')) ?>,
    readMore: <?= json_encode(t('chatbot.read_more')) ?>
};
</script>
<?php endif; ?>
<script src="/assets/js/main.js?v=<?= (int) @filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>
<script src="/assets/js/webgl-fx.js?v=<?= (int) @filemtime(__DIR__ . '/../assets/js/webgl-fx.js') ?>"></script>
</body>
</html>
