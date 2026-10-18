<?php
/**
 * Latest posts from the department's Facebook Page via the Graph API,
 * rendered to look like Facebook's own feed (avatar, name, time, text,
 * photo grid with "+N").
 *
 * Enabled when config/config.local.php defines FB_PAGE_ID and FB_PAGE_TOKEN
 * (a long-lived Page Access Token with pages_read_engagement). Responses are
 * cached in storage/facebook_cache.json for FACEBOOK_CACHE_TTL seconds; if
 * Facebook is unreachable the last good copy keeps being served.
 */

const FACEBOOK_GRAPH_VERSION = 'v21.0';
const FACEBOOK_CACHE_TTL = 300;   // new posts show up within 5 minutes
const FACEBOOK_POST_LIMIT = 10;

function facebook_graph_enabled() {
    return defined('FB_PAGE_ID') && defined('FB_PAGE_TOKEN') && FB_PAGE_ID !== '' && FB_PAGE_TOKEN !== '';
}

/** ['page' => [...], 'posts' => [...], 'fetched_at' => int, 'error' => string] */
function facebook_feed() {
    $cacheFile = SITE_ROOT . '/storage/facebook_cache.json';
    $cached = is_file($cacheFile) ? (json_decode((string) file_get_contents($cacheFile), true) ?: null) : null;
    if ($cached && time() - (int) $cached['fetched_at'] < FACEBOOK_CACHE_TTL) {
        return $cached;
    }

    try {
        try {
            $page = facebook_graph_get(FB_PAGE_ID, ['fields' => 'name,link,followers_count,fan_count,picture.width(200).height(200){url}']);
        } catch (RuntimeException $e) {
            $page = facebook_graph_get(FB_PAGE_ID, ['fields' => 'name,link,picture.width(200).height(200){url}']);
        }
        // Richest request first; some fields (comments, reactions) need extra
        // permissions on some Pages, so fall back to fewer fields.
        $base = 'id,message,created_time,permalink_url,full_picture,status_type,'
            . 'attachments{media_type,type,title,description,url,media{image{src,width,height}},'
            . 'subattachments.limit(100){media{image{src,width,height}},type}}';
        $attempts = [
            $base . ',reactions.summary(total_count).limit(0),comments.summary(total_count).limit(0),shares',
            $base . ',reactions.summary(total_count).limit(0),shares',
            $base . ',shares',
            'id,message,created_time,permalink_url,full_picture,status_type',
        ];
        $posts = null;
        $lastError = null;
        foreach ($attempts as $fields) {
            try {
                $posts = facebook_graph_get(FB_PAGE_ID . '/posts', ['limit' => FACEBOOK_POST_LIMIT, 'fields' => $fields]);
                break;
            } catch (RuntimeException $e) {
                $lastError = $e;
            }
        }
        if ($posts === null) {
            throw $lastError;
        }
        $feed = [
            'page' => [
                'name' => $page['name'] ?? '',
                'link' => $page['link'] ?? '',
                'followers' => $page['followers_count'] ?? ($page['fan_count'] ?? null),
                // Public redirect to the Page's profile picture works without a token.
                'picture' => ($page['picture']['data']['url'] ?? '') ?: 'https://graph.facebook.com/' . rawurlencode(FB_PAGE_ID) . '/picture?type=large',
            ],
            'posts' => array_map('facebook_normalize_post', $posts['data'] ?? []),
            'fetched_at' => time(),
            'error' => '',
        ];
        @file_put_contents($cacheFile, json_encode($feed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
        return $feed;
    } catch (Throwable $e) {
        error_log('Facebook feed failed: ' . $e->getMessage());
        if ($cached) {
            $cached['error'] = $e->getMessage();
            return $cached; // stale but better than nothing
        }
        return ['page' => [], 'posts' => [], 'fetched_at' => 0, 'error' => $e->getMessage()];
    }
}

function facebook_graph_get($path, array $params) {
    $params['access_token'] = FB_PAGE_TOKEN;
    $url = 'https://graph.facebook.com/' . FACEBOOK_GRAPH_VERSION . '/' . $path . '?' . http_build_query($params);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12, CURLOPT_CONNECTTIMEOUT => 6]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    if ($body === false) {
        throw new RuntimeException('Graph API: ' . $err);
    }
    $data = json_decode((string) $body, true) ?: [];
    if ($code !== 200 || isset($data['error'])) {
        throw new RuntimeException('Graph API ' . $code . ': ' . ($data['error']['message'] ?? substr((string) $body, 0, 200)));
    }
    return $data;
}

/** Flattens a Graph post into what the template needs. */
function facebook_normalize_post(array $p) {
    $images = [];
    $link = null;
    foreach ($p['attachments']['data'] ?? [] as $att) {
        foreach ($att['subattachments']['data'] ?? [] as $sub) {
            if (!empty($sub['media']['image']['src'])) {
                $images[] = $sub['media']['image']['src'];
            }
        }
        if (!$images && !empty($att['media']['image']['src'])) {
            $images[] = $att['media']['image']['src'];
        }
        // Link shares: show a link preview card under the photo.
        if (($att['type'] ?? '') === 'share' && !empty($att['url'])) {
            $link = ['url' => $att['url'], 'title' => $att['title'] ?? '', 'description' => $att['description'] ?? ''];
        }
    }
    if (!$images && !empty($p['full_picture'])) {
        $images[] = $p['full_picture'];
    }
    return [
        'id' => $p['id'] ?? '',
        'message' => $p['message'] ?? '',
        'time' => strtotime($p['created_time'] ?? 'now'),
        'url' => $p['permalink_url'] ?? '',
        'images' => $images,
        'link' => $link,
        // Posts with no readable photos (typically shares of another Page's
        // post, whose media our token can't read) are embedded by the template.
        'shared' => ($p['status_type'] ?? '') === 'shared_story',
        'reactions' => (int) ($p['reactions']['summary']['total_count'] ?? 0),
        'comments' => (int) ($p['comments']['summary']['total_count'] ?? 0),
        'shares' => (int) ($p['shares']['count'] ?? 0),
    ];
}

/** "15 นาที", "3 ชม.", "8 ตุลาคม เวลา 16:42 น." (year added when not this year) */
function facebook_time_label($ts) {
    $diff = time() - $ts;
    if ($diff < 60) {
        return 'เมื่อสักครู่';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . ' นาที';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . ' ชม.';
    }
    $months = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    $dt = (new DateTime('@' . $ts))->setTimezone(new DateTimeZone('Asia/Bangkok'));
    $label = (int) $dt->format('j') . ' ' . $months[(int) $dt->format('n')];
    if ($dt->format('Y') !== (new DateTime('now', new DateTimeZone('Asia/Bangkok')))->format('Y')) {
        $label .= ' ' . ((int) $dt->format('Y') + 543);
    }
    return $label . ' เวลา ' . $dt->format('H:i') . ' น.';
}

function facebook_compact_number($n) {
    if ($n >= 1000000) return round($n / 1000000, 1) . ' ล้าน';
    if ($n >= 10000) return round($n / 1000, 1) . ' พัน';
    return number_format($n);
}

/** Facebook-style HTML for a list of posts. */
function facebook_render_posts(array $feed) {
    $page = $feed['page'] ?? [];
    $avatar = $page['picture'] ?? '';
    $name = $page['name'] ?? '';
    ob_start();
    foreach ($feed['posts'] ?? [] as $post): ?>
    <?php if (!$post['images'] && $post['url']): ?>
    <!-- No readable photos (e.g. a share of another Page's post): Facebook's
         own embed shows the whole post, header and caption included. -->
    <article class="fbp fbp-embedded" data-post-id="<?= h($post['id']) ?>">
        <div class="fb-post" data-href="<?= h($post['url']) ?>" data-width="476" data-show-text="true">
            <blockquote cite="<?= h($post['url']) ?>" class="fb-xfbml-parse-ignore"><a href="<?= h($post['url']) ?>" target="_blank" rel="noopener"><?= h(mb_substr($post['message'], 0, 120)) ?></a></blockquote>
        </div>
    </article>
    <?php continue; endif; ?>
    <article class="fbp" data-post-id="<?= h($post['id']) ?>">
        <header class="fbp-head">
            <?php if ($avatar): ?><img class="fbp-avatar" src="<?= h($avatar) ?>" alt="" loading="lazy"><?php endif; ?>
            <div>
                <a class="fbp-name" href="<?= h($page['link'] ?? $post['url']) ?>" target="_blank" rel="noopener"><?= h($name) ?></a>
                <a class="fbp-time" href="<?= h($post['url']) ?>" target="_blank" rel="noopener"><?= h(facebook_time_label($post['time'])) ?> ·
                    <svg viewBox="0 0 16 16" width="12" height="12" fill="currentColor" aria-label="สาธารณะ"><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm5.9 7H11a12 12 0 0 0-1.1-4.7A6 6 0 0 1 13.9 7zM8 2.1c.7.9 1.4 2.6 1.5 4.9h-3C6.6 4.7 7.3 3 8 2.1zM6.1 2.3A12 12 0 0 0 5 7H2.1a6 6 0 0 1 4-4.7zM2.1 9H5c.1 1.8.5 3.4 1.1 4.7A6 6 0 0 1 2.1 9zM8 13.9c-.7-.9-1.4-2.6-1.5-4.9h3c-.1 2.3-.8 4-1.5 4.9zm1.9-.2c.6-1.3 1-2.9 1.1-4.7h2.9a6 6 0 0 1-4 4.7z"/></svg>
                </a>
            </div>
        </header>
        <?php if ($post['message'] !== ''): ?>
        <div class="fbp-text" data-collapsible><?= nl2br(h($post['message'])) ?></div>
        <?php endif; ?>
        <?php $imgs = $post['images']; $count = count($imgs); ?>
        <?php if ($count): ?>
        <a class="fbp-photos fbp-photos-<?= min($count, 4) ?>" href="<?= h($post['url']) ?>" target="_blank" rel="noopener">
            <?php foreach (array_slice($imgs, 0, 4) as $i => $src): ?>
            <span class="fbp-photo">
                <img src="<?= h($src) ?>" alt="" loading="lazy">
                <?php if ($i === 3 && $count > 4): ?><span class="fbp-more">+<?= $count - 4 ?></span><?php endif; ?>
            </span>
            <?php endforeach; ?>
        </a>
        <?php endif; ?>
        <?php if ($post['link']): ?>
        <a class="fbp-link" href="<?= h($post['link']['url']) ?>" target="_blank" rel="noopener">
            <span class="fbp-link-host"><?= h(strtoupper((string) parse_url($post['link']['url'], PHP_URL_HOST))) ?></span>
            <?php if ($post['link']['title']): ?><strong><?= h($post['link']['title']) ?></strong><?php endif; ?>
        </a>
        <?php endif; ?>
        <footer class="fbp-stats">
            <?php if ($post['reactions']): ?><span class="fbp-reacts"><i class="fbp-like-icon">👍</i><?= facebook_compact_number($post['reactions']) ?></span><?php else: ?><span></span><?php endif; ?>
            <span><?php if ($post['comments']): ?><?= facebook_compact_number($post['comments']) ?> ความคิดเห็น<?php endif; ?><?php if ($post['shares']): ?> &nbsp;<?= facebook_compact_number($post['shares']) ?> แชร์<?php endif; ?></span>
        </footer>
        <div class="fbp-actions">
            <a href="<?= h($post['url']) ?>" target="_blank" rel="noopener">ถูกใจ</a>
            <a href="<?= h($post['url']) ?>" target="_blank" rel="noopener">แสดงความคิดเห็น</a>
            <a href="<?= h($post['url']) ?>" target="_blank" rel="noopener">แชร์</a>
        </div>
    </article>
    <?php endforeach;
    return ob_get_clean();
}
