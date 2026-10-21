<?php
$currentSlug = 'home';
require_once __DIR__ . '/includes/header.php';

$page = get_page('home');
$sliders = get_active_sliders();
$heroBanner = get_setting('hero_banner_path');
$homepageVideoId = youtube_video_id(get_setting('homepage_video_url'));
$posts = get_active_posts();
$fields = get_fields_of_study();
$galleryAlbums = get_gallery_albums(6);
$facebookUrl = get_setting('facebook_url');
// Meta's Page Plugin expects the www.facebook.com domain even if the saved
// link uses the web.facebook.com (mobile-web) host.
$facebookEmbedUrl = $facebookUrl ? preg_replace('#^https?://(web|m)\.facebook\.com#', 'https://www.facebook.com', $facebookUrl) : '';
?>

<?php
$heroParticleText = trim(get_setting('hero_particle_text')) ?: 'MULTIMEDIA|TECHNOLOGY';
$heroKeywords = array_values(array_filter(array_map('trim', explode(',', get_setting('hero_keywords') ?: 'ANIMATION, GAME, FILM, GRAPHIC DESIGN, VR / AR, SOUND'))));
$heroVideoUrl = get_setting('hero_video_url');
$heroVideoId = youtube_video_id($heroVideoUrl);
$heroVideoFile = !$heroVideoId && preg_match('#^https?://\S+\.(mp4|webm)(\?\S*)?$#i', (string) $heroVideoUrl) ? $heroVideoUrl : '';
?>
<div class="hero hero-fx<?= $heroBanner ? ' hero-has-banner' : '' ?>" id="heroFx"
     data-particle-text="<?= h($heroParticleText) ?>"
     data-keywords="<?= h(json_encode($heroKeywords, JSON_UNESCAPED_UNICODE)) ?>"<?php if ($heroBanner): ?>
     style="background-image: linear-gradient(135deg, rgba(11,2,3,.78), rgba(71,6,0,.7)), url('<?= h($heroBanner) ?>');"<?php endif; ?>>
    <?php if ($heroVideoId): ?>
    <div class="hero-fx-video" aria-hidden="true">
        <iframe src="https://www.youtube.com/embed/<?= h($heroVideoId) ?>?autoplay=1&mute=1&loop=1&playlist=<?= h($heroVideoId) ?>&controls=0&modestbranding=1&playsinline=1&rel=0&disablekb=1" title="" tabindex="-1" frameborder="0" allow="autoplay; encrypted-media"></iframe>
    </div>
    <?php elseif ($heroVideoFile): ?>
    <div class="hero-fx-video" aria-hidden="true">
        <video src="<?= h($heroVideoFile) ?>" autoplay muted loop playsinline preload="metadata"></video>
    </div>
    <?php endif; ?>
    <canvas class="hero-fx-canvas" aria-hidden="true"></canvas>
    <div class="hero-fx-fallback" aria-hidden="true"><?= str_replace('|', '<br>', h($heroParticleText)) ?></div>
    <div class="container hero-fx-content">
        <h1 class="visually-hidden"><?= h(lang_field($page, 'title')) ?></h1>
        <?php if ($heroKeywords): ?>
        <div class="hero-fx-keywords" aria-live="off">
            <span class="hero-fx-glitch" data-text="<?= h($heroKeywords[0]) ?>"><?= h($heroKeywords[0]) ?></span>
        </div>
        <?php endif; ?>
        <div class="hero-fx-cta">
            <?php if ($enrollUrl = get_setting('enroll_url')): ?>
            <a class="btn hero-fx-btn" href="<?= h($enrollUrl) ?>" target="_blank" rel="noopener"><?= h(t('topbar.enroll')) ?></a>
            <?php endif; ?>
            <a class="btn hero-fx-btn hero-fx-btn-ghost" href="<?= h(clean_public_url('/student-works')) ?>"><?= h(t('home.hero_works')) ?></a>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js" defer></script>
<script src="/assets/js/hero-fx.js?v=<?= (int) @filemtime(__DIR__ . '/assets/js/hero-fx.js') ?>" defer></script>

<?php if ($sliders): ?>
<section class="home-slider-section">
<div class="container">
<div class="slider">
    <?php foreach ($sliders as $i => $slide): ?>
    <div class="slide<?= $i === 0 ? ' active' : '' ?>">
        <img src="<?= h($slide['image_path']) ?>" alt="<?= h(lang_field($slide, 'caption')) ?>">
        <?php if (lang_field($slide, 'caption') || lang_field($slide, 'subtitle')): ?>
        <div class="slider-caption">
            <?php if (lang_field($slide, 'caption')): ?><h2><?= h(lang_field($slide, 'caption')) ?></h2><?php endif; ?>
            <?php if (lang_field($slide, 'subtitle')): ?><p><?= h(lang_field($slide, 'subtitle')) ?></p><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php if (count($sliders) > 1): ?>
    <button type="button" class="slider-arrow slider-arrow-prev" aria-label="สไลด์ก่อนหน้า">&lsaquo;</button>
    <button type="button" class="slider-arrow slider-arrow-next" aria-label="สไลด์ถัดไป">&rsaquo;</button>
    <div class="slider-dots">
        <?php foreach ($sliders as $i => $slide): ?>
        <button type="button" class="slider-dot<?= $i === 0 ? ' active' : '' ?>" data-slide-index="<?= $i ?>" aria-label="ไปที่สไลด์ <?= $i + 1 ?>"></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</div>
</section>
<?php endif; ?>

<?php if ($posts): ?>
<section class="alt">
    <div class="container">
        <div class="section-title">
            <h2><?= h(t('home.posts_heading')) ?></h2>
            <div class="bar"></div>
        </div>
        <div class="post-carousel">
            <div class="post-carousel-track" id="postCarousel">
                <?php foreach ($posts as $p): ?>
                <div class="post-card">
                    <img src="<?= h($p['image_path']) ?>" alt="<?= h(lang_field($p, 'title')) ?>">
                    <div class="post-card-body">
                        <?php if ($p['post_date']): ?><div class="post-card-date"><?= h(thai_date($p['post_date'])) ?></div><?php endif; ?>
                        <h3><?= h(lang_field($p, 'title')) ?></h3>
                        <?php if (lang_field($p, 'excerpt')): ?><p><?= h(lang_field($p, 'excerpt')) ?></p><?php endif; ?>
                        <?php if ($p['link_url']): ?><a class="post-link" href="<?= h($p['link_url']) ?>" target="_blank" rel="noopener"><?= h(t('home.read_more')) ?> &rarr;</a><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($posts) > 1): ?>
            <div class="post-carousel-nav">
                <button type="button" class="post-carousel-btn" data-carousel-prev="postCarousel" aria-label="ก่อนหน้า">&larr;</button>
                <button type="button" class="post-carousel-btn" data-carousel-next="postCarousel" aria-label="ถัดไป">&rarr;</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($homepageVideoId || $facebookEmbedUrl): ?>
<section class="alt media-section">
    <div class="container">
        <div class="section-title">
            <h2><?= h(t('home.media_heading')) ?></h2>
            <div class="bar"></div>
        </div>
        <div class="media-grid<?= ($homepageVideoId && $facebookEmbedUrl) ? '' : ' media-grid-single' ?>">
            <?php if ($homepageVideoId): ?>
            <div class="media-col media-col-video">
                <div class="media-label"><span class="media-label-dot"></span><?= h(t('home.media_video_label')) ?></div>
                <div class="video-banner">
                    <div class="video-banner-inner">
                        <iframe
                            src="https://www.youtube.com/embed/<?= h($homepageVideoId) ?>?autoplay=1&mute=1&loop=1&playlist=<?= h($homepageVideoId) ?>&controls=1&rel=0&modestbranding=1&playsinline=1"
                            title="<?= h(get_site_name()) ?>"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                            loading="eager"
                        ></iframe>
                    </div>
                </div>
                <div class="media-quick">
                    <a class="media-quick-card" href="<?= h(clean_public_url('/course')) ?>"><span class="media-quick-icon">🎓</span><span><strong><?= h(t('home.quick_course')) ?></strong><small><?= h(t('home.quick_course_sub')) ?></small></span></a>
                    <a class="media-quick-card" href="<?= h(clean_public_url('/student-works')) ?>"><span class="media-quick-icon">🎬</span><span><strong><?= h(t('home.quick_works')) ?></strong><small><?= h(t('home.quick_works_sub')) ?></small></span></a>
                    <a class="media-quick-card" href="<?= h(clean_public_url('/contact')) ?>"><span class="media-quick-icon">✉️</span><span><strong><?= h(t('home.quick_contact')) ?></strong><small><?= h(t('home.quick_contact_sub')) ?></small></span></a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($facebookEmbedUrl): ?>
            <div class="media-col media-col-facebook">
                <div class="media-label"><span class="media-label-dot media-label-dot-fb"></span><?= h(t('home.media_facebook_label')) ?></div>
                <?php
                require_once __DIR__ . '/includes/facebook.php';
                $fbGraph = facebook_graph_enabled() ? facebook_feed() : null;
                $fbSlug = trim((string) parse_url($facebookEmbedUrl, PHP_URL_PATH), '/');
                $fbSlug = explode('/', $fbSlug)[0];
                $fbName = get_setting('facebook_page_name') ?: $fbSlug;
                $fbFollowers = get_setting('facebook_followers');
                $fbCover = get_setting('facebook_cover_path') ?: $heroBanner;
                $fbAvatar = get_setting('logo_path');
                if ($fbGraph && !empty($fbGraph['page']['name'])) {
                    $fbName = get_setting('facebook_page_name') ?: $fbGraph['page']['name'];
                    $fbFollowers = $fbFollowers ?: ($fbGraph['page']['followers'] !== null ? number_format($fbGraph['page']['followers']) : '');
                    $fbAvatar = $fbGraph['page']['picture'] ?: $fbAvatar;
                }
                $fbPosts = array_slice(array_values(array_filter(array_map('trim', preg_split('/\R/', (string) get_setting('facebook_post_urls'))), function ($u) {
                    return preg_match('#^https://(www\.|web\.|m\.)?facebook\.com/#i', $u);
                })), 0, 10);
                ?>
                <div class="fb-look">
                    <div id="fb-root"></div>
                    <div class="fb-look-cover"<?php if ($fbCover): ?> style="background-image:url('<?= h($fbCover) ?>')"<?php endif; ?>></div>
                    <div class="fb-look-head">
                        <div class="fb-look-avatar">
                            <?php if ($fbAvatar): ?><img src="<?= h($fbAvatar) ?>" alt="<?= h($fbName) ?>"><?php else: ?><span>MT</span><?php endif; ?>
                        </div>
                        <div class="fb-look-meta">
                            <a class="fb-look-name" href="<?= h($facebookUrl) ?>" target="_blank" rel="noopener"><?= h($fbName) ?></a>
                            <?php if ($fbFollowers): ?><div class="fb-look-followers"><?= h($fbFollowers) ?> <?= h(t('home.facebook_followers')) ?></div><?php endif; ?>
                        </div>
                    </div>
                    <div class="fb-look-actions">
                        <a class="fb-look-btn fb-look-btn-primary" href="<?= h($facebookUrl) ?>" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M13.5 21v-7.5h2.5l.4-3h-2.9V8.6c0-.9.3-1.5 1.5-1.5H16.6V4.4c-.3 0-1.2-.1-2.3-.1-2.3 0-3.8 1.4-3.8 3.9v2.3H8v3h2.5V21h3z"/></svg>
                            <?= h(t('home.facebook_follow')) ?>
                        </a>
                        <?php if ($fbSlug): ?>
                        <a class="fb-look-btn" href="https://m.me/<?= h(rawurlencode($fbSlug)) ?>" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M12 2C6.4 2 2 6.1 2 11.3c0 2.9 1.4 5.5 3.7 7.2V22l3.4-1.9c.9.3 1.9.4 2.9.4 5.6 0 10-4.1 10-9.3S17.6 2 12 2zm1 12.5-2.5-2.7-4.9 2.7 5.4-5.8 2.6 2.7 4.8-2.7-5.4 5.8z"/></svg>
                            <?= h(t('home.facebook_message')) ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="fb-look-feed"<?php if ($fbGraph && $fbGraph['posts']): ?> id="fbGraphFeed" data-refresh="/facebook-feed.php"<?php endif; ?>>
                        <?php if ($fbGraph && $fbGraph['posts']): ?>
                            <?= facebook_render_posts($fbGraph) ?>
                        <?php elseif ($fbPosts): ?>
                            <?php foreach ($fbPosts as $postUrl): ?>
                            <div class="fb-look-post">
                                <div class="fb-post" data-href="<?= h($postUrl) ?>" data-width="500" data-show-text="true">
                                    <blockquote cite="<?= h($postUrl) ?>" class="fb-xfbml-parse-ignore"><a href="<?= h($postUrl) ?>" target="_blank" rel="noopener"><?= h(t('home.facebook_link')) ?></a></blockquote>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- No post links set in Settings yet: fall back to Facebook's page plugin -->
                            <div class="fb-page"
                                 data-href="<?= h($facebookEmbedUrl) ?>"
                                 data-tabs="timeline"
                                 data-width="500"
                                 data-height="560"
                                 data-small-header="true"
                                 data-adapt-container-width="true"
                                 data-hide-cover="true"
                                 data-show-facepile="false">
                                <blockquote cite="<?= h($facebookEmbedUrl) ?>" class="fb-xfbml-parse-ignore">
                                    <a href="<?= h($facebookUrl) ?>" target="_blank" rel="noopener"><?= h(t('home.facebook_link')) ?></a>
                                </blockquote>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php if ($facebookEmbedUrl): ?>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/th_TH/sdk.js#xfbml=1&version=v19.0"></script>
<script>
// Facebook plugins measure their container's width once when they first
// parse, which can happen before our CSS grid layout has settled (fonts,
// image reflow, etc.) and leaves them stuck at a too-narrow width.
// Re-parsing once everything has finished loading forces a re-measure.
// Graph feed: collapse long captions, and poll for new Page posts every
// 5 minutes while the page is open (server caches Facebook for 5 minutes).
(function () {
    var feed = document.getElementById('fbGraphFeed');
    if (!feed) return;
    function collapse(root) {
        root.querySelectorAll('[data-collapsible]').forEach(function (el) {
            if (el.dataset.ready) return;
            el.dataset.ready = '1';
            if (el.scrollHeight > 140) {
                el.classList.add('is-collapsed');
                var more = document.createElement('button');
                more.type = 'button';
                more.className = 'fbp-more-btn';
                more.textContent = 'ดูเพิ่มเติม';
                more.addEventListener('click', function () { el.classList.remove('is-collapsed'); more.remove(); });
                el.after(more);
            }
        });
    }
    collapse(feed);
    setInterval(function () {
        if (document.hidden) return;
        fetch(feed.getAttribute('data-refresh'), { credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.text() : ''; })
            .then(function (html) {
                if (!html.trim()) return;
                var firstOld = feed.querySelector('.fbp');
                var tmp = document.createElement('div');
                tmp.innerHTML = html;
                var firstNew = tmp.querySelector('.fbp');
                if (firstNew && firstOld && firstNew.dataset.postId === firstOld.dataset.postId) return;
                feed.innerHTML = html;
                collapse(feed);
                if (window.FB && FB.XFBML) FB.XFBML.parse(feed); // embedded shared posts
                var top = feed.querySelector('.fbp');
                if (top) top.classList.add('fbp-new');
            });
    }, 300000);
})();

window.addEventListener('load', function () {
    if (window.FB && FB.XFBML) {
        FB.XFBML.parse();
    }
});
</script>
<?php endif; ?>
<?php endif; ?>

<?php if ($galleryAlbums): ?>
<section class="alt">
    <div class="container">
        <div class="section-title">
            <h2><?= h(t('home.gallery_heading')) ?></h2>
            <div class="bar"></div>
        </div>
        <?php $albums = $galleryAlbums; require __DIR__ . '/includes/album-grid.php'; ?>
        <div class="album-more"><a class="btn" href="<?= h(clean_public_url('/gallery')) ?>"><?= h(t('gallery.all_albums')) ?> &rarr;</a></div>
    </div>
</section>
<?php endif; ?>

<?php if ($fields): ?>
<section class="alt">
    <div class="container">
        <div class="section-title">
            <h2><?= h(t('home.fields_heading')) ?></h2>
            <div class="bar"></div>
        </div>
        <div class="card-grid">
            <?php foreach ($fields as $f): ?>
            <div class="card">
                <?php if ($f['icon_path']): ?><img src="<?= h($f['icon_path']) ?>" alt="<?= h(lang_field($f, 'name')) ?>"><?php endif; ?>
                <div class="card-body">
                    <h3><?= h(lang_field($f, 'name')) ?></h3>
                    <p><?= h(lang_field($f, 'description')) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
