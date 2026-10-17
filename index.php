<?php
$currentSlug = 'home';
require_once __DIR__ . '/includes/header.php';

$page = get_page('home');
$sliders = get_active_sliders();
$heroBanner = get_setting('hero_banner_path');
$homepageVideoId = youtube_video_id(get_setting('homepage_video_url'));
$posts = get_active_posts();
$fields = get_fields_of_study();
$stats = get_stats();
$gallery = get_gallery(8);
$studentYearStats = get_student_year_stats();
$facebookUrl = get_setting('facebook_url');
// Meta's Page Plugin expects the www.facebook.com domain even if the saved
// link uses the web.facebook.com (mobile-web) host.
$facebookEmbedUrl = $facebookUrl ? preg_replace('#^https?://(web|m)\.facebook\.com#', 'https://www.facebook.com', $facebookUrl) : '';
?>

<?php if ($sliders): ?>
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
<?php else: ?>
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
        <h1><?= h(lang_field($page, 'title')) ?></h1>
        <?php if (lang_field($page, 'subtitle')): ?><p><?= h(lang_field($page, 'subtitle')) ?></p><?php endif; ?>
        <?php if ($heroKeywords): ?>
        <div class="hero-fx-keywords" aria-live="off">
            <span class="hero-fx-bracket">&lt;</span><span class="hero-fx-glitch" data-text="<?= h($heroKeywords[0]) ?>"><?= h($heroKeywords[0]) ?></span><span class="hero-fx-bracket">/&gt;</span>
        </div>
        <?php endif; ?>
        <div class="hero-fx-eq" aria-hidden="true"><?= str_repeat('<span></span>', 48) ?></div>
        <div class="hero-fx-cta">
            <?php if ($enrollUrl = get_setting('enroll_url')): ?>
            <a class="btn hero-fx-btn" href="<?= h($enrollUrl) ?>" target="_blank" rel="noopener"><?= h(t('topbar.enroll')) ?></a>
            <?php endif; ?>
            <a class="btn hero-fx-btn hero-fx-btn-ghost" href="/student-works.php"><?= h(t('home.hero_works')) ?></a>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js" defer></script>
<script src="/assets/js/hero-fx.js?v=<?= (int) @filemtime(__DIR__ . '/assets/js/hero-fx.js') ?>" defer></script>
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
<section class="alt">
    <div class="container">
        <div class="two-col-section">
            <?php if ($homepageVideoId): ?>
            <div class="two-col-item">
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
            </div>
            <?php endif; ?>

            <?php if ($facebookEmbedUrl): ?>
            <div class="two-col-item">
                <div class="section-title">
                    <h2><?= h(t('home.facebook_heading')) ?></h2>
                    <div class="bar"></div>
                </div>
                <div class="fb-embed-wrap">
                    <div id="fb-root"></div>
                    <div class="fb-page"
                         data-href="<?= h($facebookEmbedUrl) ?>"
                         data-tabs="timeline"
                         data-width=""
                         data-height="500"
                         data-small-header="false"
                         data-adapt-container-width="true"
                         data-hide-cover="false"
                         data-show-facepile="true">
                        <blockquote cite="<?= h($facebookEmbedUrl) ?>" class="fb-xfbml-parse-ignore">
                            <a href="<?= h($facebookUrl) ?>" target="_blank" rel="noopener"><?= h(t('home.facebook_link')) ?></a>
                        </blockquote>
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
// The Facebook Page Plugin measures its container's width once when it first
// parses, which can happen before our CSS grid layout has settled (fonts,
// image reflow, etc.) and leaves the widget stuck at a too-narrow width.
// Re-parsing once everything has finished loading forces it to re-measure.
window.addEventListener('load', function () {
    if (window.FB && FB.XFBML) {
        FB.XFBML.parse();
    }
});
</script>
<?php endif; ?>
<?php endif; ?>

<?php if ($stats): ?>
<section>
    <div class="container">
        <div class="section-title">
            <h2><?= h(t('home.stats_heading')) ?></h2>
            <div class="bar"></div>
        </div>
        <div class="stats-grid">
            <?php foreach ($stats as $s): ?>
            <div class="stat-box">
                <div class="num"><?= h($s['count']) ?></div>
                <div class="label"><?= h(t('home.stats_year_prefix')) ?> <?= h($s['year']) ?> &middot; <?= h($s['label']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($studentYearStats): ?>
<section>
    <div class="container">
        <div class="section-title">
            <h2><?= h(t('home.student_stats_heading')) ?></h2>
            <div class="bar"></div>
        </div>
        <div class="chart-wrap">
            <canvas id="studentYearChart" height="320"></canvas>
        </div>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    var labels = <?= json_encode(array_column($studentYearStats, 'academic_year')) ?>;
    var yearLabel = <?= json_encode(t('home.student_stats_year')) ?>;
    var ctx = document.getElementById('studentYearChart');
    if (ctx && window.Chart) {
        Chart.defaults.color = '#c9b2ae';
        Chart.defaults.borderColor = 'rgba(255, 140, 120, 0.14)';
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: yearLabel + ' 1', data: <?= json_encode(array_map('intval', array_column($studentYearStats, 'year1_count'))) ?>, backgroundColor: '#ff5a3c' },
                    { label: yearLabel + ' 2', data: <?= json_encode(array_map('intval', array_column($studentYearStats, 'year2_count'))) ?>, backgroundColor: '#b51200' },
                    { label: yearLabel + ' 3', data: <?= json_encode(array_map('intval', array_column($studentYearStats, 'year3_count'))) ?>, backgroundColor: '#ff9b7a' },
                    { label: yearLabel + ' 4', data: <?= json_encode(array_map('intval', array_column($studentYearStats, 'year4_count'))) ?>, backgroundColor: '#7a6a68' }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }
})();
</script>
<?php endif; ?>

<?php if ($gallery): ?>
<section class="alt">
    <div class="container">
        <div class="section-title">
            <h2><?= h(t('home.gallery_heading')) ?></h2>
            <div class="bar"></div>
        </div>
        <div class="gallery-grid">
            <?php foreach ($gallery as $g): ?>
            <img src="<?= h($g['image_path']) ?>" alt="<?= h($g['caption']) ?>">
            <?php endforeach; ?>
        </div>
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
