<?php
/**
 * A hub member's portfolio as an EPUB 3 e-book (opens in Apple Books,
 * Google Play Books, Kindle via Send-to-Kindle, Thorium, etc.).
 * Same content and visibility as alumni-portfolio.php. Images are embedded
 * in the file: local uploads are read from disk, OpenDrive links are
 * fetched (with a time and size limit); an image that can't be fetched is
 * left out rather than breaking the book.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
ensure_alumni_hub();

$member = alumni_viewable_member($_GET['id'] ?? 0);
if (!$member) {
    http_response_code(404);
    exit('ไม่พบ Portfolio นี้');
}
if (!class_exists('ZipArchive')) {
    http_response_code(500);
    exit('เซิร์ฟเวอร์ไม่รองรับการสร้างไฟล์ E-book (ต้องเปิด PHP zip extension)');
}
@set_time_limit(120);

$items = get_alumni_portfolio_items($member['id']);
$works = get_alumni_works($member['id']);
$skills = alumni_skill_list($member['skills']);

function epub_x($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function epub_paragraphs($text) {
    $out = '';
    foreach (preg_split('/\R{2,}/u', trim((string) $text)) as $para) {
        if ($para !== '') {
            $out .= '<p>' . implode('<br/>', array_map('epub_x', preg_split('/\R/u', $para))) . "</p>\n";
        }
    }
    return $out;
}

/** Image bytes for a stored path (local upload or remote link), or null. */
function epub_fetch_image($path) {
    $max = 6 * 1024 * 1024;
    $data = null;
    if (strpos($path, '/') === 0 && strpos($path, '//') !== 0) {
        $file = realpath(SITE_ROOT . parse_url($path, PHP_URL_PATH));
        if ($file && strpos($file, realpath(SITE_ROOT)) === 0 && is_file($file) && filesize($file) <= $max) {
            $data = file_get_contents($file);
        }
    } elseif (preg_match('#^https?://#i', $path) && function_exists('curl_init')) {
        $ch = curl_init($path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 4,
            CURLOPT_CONNECTTIMEOUT => 6, CURLOPT_TIMEOUT => 15, CURLOPT_USERAGENT => 'Mozilla/5.0 (portfolio e-book)',
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);
        $body = curl_exec($ch);
        if ($body !== false && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200 && strlen($body) <= $max) {
            $data = $body;
        }
        curl_close($ch);
    }
    if ($data === null) {
        return null;
    }
    $info = @getimagesizefromstring($data);
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    if (!$info || !isset($types[$info['mime']])) {
        return null;
    }
    return ['data' => $data, 'mime' => $info['mime'], 'ext' => $types[$info['mime']]];
}

// Collect images once each (photo, portfolio sections, works); at most 40.
$images = [];
$imageRef = function ($path) use (&$images) {
    if (!$path) {
        return null;
    }
    if (isset($images[$path])) {
        return $images[$path] ? $images[$path]['file'] : null;
    }
    if (count(array_filter($images)) >= 40) {
        return null;
    }
    $img = epub_fetch_image($path);
    if (!$img) {
        $images[$path] = false;
        return null;
    }
    $img['file'] = 'images/img' . (count($images) + 1) . '.' . $img['ext'];
    $images[$path] = $img;
    return $img['file'];
};

$name = $member['full_name'];
$typeLine = alumni_member_type_label($member['member_type'] ?? 'alumni') . (alumni_class_line($member, false) !== '' ? ' · ' . alumni_class_line($member, false) : '');
$siteName = get_site_name();
$profileUrl = alumni_absolute_url('/alumni-profile?id=' . (int) $member['id']);

$page = function ($title, $body) {
    return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops" xml:lang="th" lang="th">'
        . '<head><meta charset="UTF-8"/><title>' . epub_x($title) . '</title><link rel="stylesheet" type="text/css" href="style.css"/></head>'
        . '<body>' . $body . '</body></html>';
};

// ---------- Chapters ----------
$chapters = [];
$photo = $imageRef($member['photo_path']);
$contact = '';
foreach ([
    ['✉', $member['contact_email']], ['☎', $member['contact_phone']],
    ['LINE', $member['line_id']], ['Web', $member['website_url']],
] as [$label, $value]) {
    if ($value) {
        $contact .= '<li><b>' . epub_x($label) . '</b> ' . epub_x($value) . '</li>';
    }
}
$chapters[] = ['id' => 'cover', 'title' => 'หน้าปก', 'html' => $page($name, '<section class="cover" epub:type="cover">'
    . ($photo ? '<img class="photo" src="' . $photo . '" alt=""/>' : '')
    . '<p class="kicker">PORTFOLIO</p><h1>' . epub_x($name) . '</h1>'
    . ($member['headline'] ? '<p class="headline">' . epub_x($member['headline']) . '</p>' : '')
    . '<p class="meta">' . epub_x($typeLine) . '</p>'
    . ($contact ? '<ul class="contact">' . $contact . '</ul>' : '')
    . '<p class="site">' . epub_x($siteName) . '</p></section>')];

$about = '';
if ($member['bio']) {
    $about .= '<h2>เกี่ยวกับฉัน</h2>' . epub_paragraphs($member['bio']);
}
if ($skills) {
    $about .= '<h2>ทักษะ</h2><p class="skills">' . implode(' ', array_map(fn($s) => '<span>' . epub_x($s) . '</span>', $skills)) . '</p>';
}
if ($about !== '') {
    $chapters[] = ['id' => 'about', 'title' => 'เกี่ยวกับฉัน', 'html' => $page('เกี่ยวกับฉัน', $about)];
}

foreach ($items as $i => $it) {
    $img = $imageRef($it['image_path']);
    $html = '<h2>' . epub_x($it['heading']) . '</h2>'
        . ($img ? '<figure><img src="' . $img . '" alt=""/></figure>' : '')
        . epub_paragraphs($it['body'])
        . ($it['link_url'] ? '<p class="link"><a href="' . epub_x($it['link_url']) . '">' . epub_x($it['link_label'] ?: $it['link_url']) . '</a></p>' : '');
    $chapters[] = ['id' => 'item' . ($i + 1), 'title' => $it['heading'], 'html' => $page($it['heading'], $html)];
}

if ($works) {
    $html = '<h2>ผลงาน</h2>';
    foreach ($works as $w) {
        $img = $imageRef($w['image_path']);
        $html .= '<div class="work">' . ($img ? '<figure><img src="' . $img . '" alt=""/></figure>' : '')
            . '<h3>' . epub_x($w['title']) . '</h3>' . epub_paragraphs($w['description'])
            . ($w['link_url'] ? '<p class="link"><a href="' . epub_x($w['link_url']) . '">' . epub_x($w['link_url']) . '</a></p>' : '')
            . '</div>';
    }
    $chapters[] = ['id' => 'works', 'title' => 'ผลงาน', 'html' => $page('ผลงาน', $html)];
}
$chapters[] = ['id' => 'contact', 'title' => 'ติดต่อ', 'html' => $page('ติดต่อ', '<h2>ติดต่อ</h2>'
    . ($contact ? '<ul class="contact">' . $contact . '</ul>' : '')
    . '<p>ดูโปรไฟล์ออนไลน์: <a href="' . epub_x($profileUrl) . '">' . epub_x($profileUrl) . '</a></p>'
    . '<p class="site">' . epub_x($siteName) . ' · ติดต่องานศิษย์เก่า</p>')];

// ---------- Package ----------
$uuid = sprintf('%08x-%04x-4%03x-%04x-%012x', crc32('mt-portfolio-' . $member['id']), mt_rand(0, 0xffff), mt_rand(0, 0xfff), mt_rand(0x8000, 0xbfff), mt_rand(0, 0xffffffffffff));
$manifest = '<item id="nav" href="nav.xhtml" media-type="application/xhtml+xml" properties="nav"/>'
    . '<item id="css" href="style.css" media-type="text/css"/>';
$spine = '';
$navList = '';
foreach ($chapters as $c) {
    $manifest .= '<item id="' . $c['id'] . '" href="' . $c['id'] . '.xhtml" media-type="application/xhtml+xml"/>';
    $spine .= '<itemref idref="' . $c['id'] . '"/>';
    $navList .= '<li><a href="' . $c['id'] . '.xhtml">' . epub_x($c['title']) . '</a></li>';
}
$coverImageId = '';
foreach (array_filter($images) as $img) {
    $id = 'img' . md5($img['file']);
    $isCover = $photo && $img['file'] === $photo;
    if ($isCover) {
        $coverImageId = $id;
    }
    $manifest .= '<item id="' . $id . '" href="' . $img['file'] . '" media-type="' . $img['mime'] . '"' . ($isCover ? ' properties="cover-image"' : '') . '/>';
}

$opf = '<?xml version="1.0" encoding="UTF-8"?>'
    . '<package xmlns="http://www.idpf.org/2007/opf" version="3.0" unique-identifier="bookid" xml:lang="th">'
    . '<metadata xmlns:dc="http://purl.org/dc/elements/1.1/">'
    . '<dc:identifier id="bookid">urn:uuid:' . $uuid . '</dc:identifier>'
    . '<dc:title>Portfolio - ' . epub_x($name) . '</dc:title>'
    . '<dc:creator>' . epub_x($name) . '</dc:creator>'
    . '<dc:publisher>' . epub_x($siteName) . '</dc:publisher>'
    . '<dc:language>th</dc:language>'
    . '<meta property="dcterms:modified">' . gmdate('Y-m-d\TH:i:s\Z') . '</meta>'
    . ($coverImageId ? '<meta name="cover" content="' . $coverImageId . '"/>' : '')
    . '</metadata><manifest>' . $manifest . '</manifest><spine>' . $spine . '</spine></package>';

$nav = $page('สารบัญ', '<nav epub:type="toc" id="toc"><h1>สารบัญ</h1><ol>' . $navList . '</ol></nav>');

$css = 'body{font-family:serif;line-height:1.6;margin:0 4%;color:#1d1416}'
    . 'h1,h2,h3{font-family:sans-serif;line-height:1.3}h2{color:#b3261e;border-bottom:1px solid #ead9d6;padding-bottom:.2em}'
    . '.cover{text-align:center;margin-top:12%}.cover .photo{width:45%;max-width:220px;border-radius:50%}'
    . '.kicker{letter-spacing:.3em;color:#b3261e;font-family:sans-serif;font-size:.8em;margin:1.2em 0 0}'
    . '.cover h1{font-size:2em;margin:.2em 0}.headline{color:#b3261e;font-weight:bold}.meta,.site{color:#6b5d5f}'
    . '.contact{list-style:none;padding:0}.contact li{margin:.2em 0}'
    . '.skills span{display:inline-block;border:1px solid #ead9d6;border-radius:1em;padding:0 .7em;margin:0 .2em .3em 0}'
    . 'figure{margin:.8em 0;text-align:center}figure img{max-width:100%}'
    . '.work{margin-bottom:1.6em;page-break-inside:avoid}.link{word-break:break-all;font-size:.9em}a{color:#b3261e}';

$tmp = tempnam(sys_get_temp_dir(), 'epub');
$zip = new ZipArchive();
$zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addFromString('mimetype', 'application/epub+zip');
$zip->setCompressionName('mimetype', ZipArchive::CM_STORE);   // must be first and uncompressed
$zip->addFromString('META-INF/container.xml', '<?xml version="1.0" encoding="UTF-8"?><container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container"><rootfiles><rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/></rootfiles></container>');
$zip->addFromString('OEBPS/content.opf', $opf);
$zip->addFromString('OEBPS/nav.xhtml', $nav);
$zip->addFromString('OEBPS/style.css', $css);
foreach ($chapters as $c) {
    $zip->addFromString('OEBPS/' . $c['id'] . '.xhtml', $c['html']);
}
foreach (array_filter($images) as $img) {
    $zip->addFromString('OEBPS/' . $img['file'], $img['data']);
}
$zip->close();

$fileName = 'portfolio-' . preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '-', $name) . '.epub';
header('Content-Type: application/epub+zip');
header("Content-Disposition: attachment; filename=\"portfolio.epub\"; filename*=UTF-8''" . rawurlencode($fileName));
header('Content-Length: ' . filesize($tmp));
header('Cache-Control: private, no-store');
readfile($tmp);
@unlink($tmp);
