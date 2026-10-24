<?php
/**
 * Admin navigation built from the public site's menu (nav_menu_items), so
 * the back office always has the same sections, names and order visitors
 * see - change the site menu and this follows.
 *
 * admin_nav_registry() says which admin page(s) manage each public page.
 * 'match' lists extra admin scripts (sub-pages) that belong to an item,
 * 'slug' marks page-edit.php items by the page they edit, 'page' adds a
 * "ข้อความหน้า" button for the page's intro text on the dashboard.
 */
function admin_nav_registry() {
    return [
        '/' => [
            ['href' => '/admin/page-edit.php?slug=home', 'label' => 'ข้อความหน้าหลัก', 'slug' => 'home'],
            ['href' => '/admin/sliders.php', 'label' => 'สไลด์หน้าแรก'],
            ['href' => '/admin/posts.php', 'label' => 'ข่าวประชาสัมพันธ์/กิจกรรม', 'match' => ['post-form.php']],
            ['href' => '/admin/page-edit.php?slug=career-intro', 'label' => 'แนะนำอาชีพ (หน้าแรก)', 'slug' => 'career-intro'],
        ],
        '/gallery' => [['href' => '/admin/gallery.php', 'label' => 'อัลบั้มภาพกิจกรรม', 'home' => true]],
        '/subjects' => [['href' => '/admin/fields.php', 'label' => 'รายวิชาที่เปิดสอน', 'page' => 'subjects', 'home' => true]],
        '/course' => [['href' => '/admin/page-edit.php?slug=course', 'label' => 'หลักสูตรปริญญาตรี', 'slug' => 'course']],
        '/history' => [['href' => '/admin/page-edit.php?slug=history', 'label' => 'ประวัติ', 'slug' => 'history']],
        '/teachers' => [['href' => '/admin/teachers.php', 'label' => 'อาจารย์', 'match' => ['teacher-form.php', 'teacher-portfolio.php'], 'page' => 'teachers']],
        '/classroom' => [['href' => '/admin/classrooms.php', 'label' => 'ห้องเรียน', 'page' => 'classroom']],
        '/students' => [['href' => '/admin/students.php', 'label' => 'นักศึกษาปัจจุบัน', 'match' => ['student-photos.php'], 'page' => 'students']],
        '/alumni' => [['href' => '/admin/alumni.php', 'label' => 'ศิษย์เก่า', 'match' => ['alumni-photos.php'], 'page' => 'alumni']],
        '/outstanding-alumni' => [['href' => '/admin/outstanding-alumni.php', 'label' => 'ศิษย์เก่าดีเด่น', 'page' => 'outstanding-alumni']],
        '/alumni-hub' => [['href' => '/admin/alumni-hub.php', 'label' => 'Portfolio', 'page' => 'alumni-hub', 'badge' => 'alumni_hub_pending']],
        '/statistics' => [
            ['href' => '/admin/stats.php', 'label' => 'สถิติการรับนักศึกษา', 'page' => 'statistics'],
            ['href' => '/admin/student-year-stats.php', 'label' => 'สถิตินักศึกษาแยกชั้นปี'],
        ],
        '/student-services' => [
            ['href' => '/admin/student-service-links.php', 'label' => 'ข้อมูลทั่วไป', 'page' => 'student-services'],
            ['href' => '/admin/page-edit.php?slug=student-services', 'label' => 'ข้อความแนะนำ (ข้อมูลทั่วไป)', 'slug' => 'student-services', 'sub' => true],
        ],
        '/academic-calendar' => [['href' => '/admin/academic-calendar.php', 'label' => 'ปฏิทินการศึกษา']],
        '/activity-calendar' => [['href' => '/admin/activity-calendar.php', 'label' => 'ปฏิทินกิจกรรม']],
        '/student-works' => [['href' => '/admin/student-work-batches.php', 'label' => 'ผลงานนักศึกษา', 'match' => ['student-work-items.php']]],
        '/classroom-online' => [['href' => '/admin/page-edit.php?slug=classroom-online', 'label' => 'Classroom Online', 'slug' => 'classroom-online']],
        '/contact' => [['href' => '/admin/page-edit.php?slug=contact', 'label' => 'หน้าติดต่อเรา', 'slug' => 'contact']],
    ];
}

/** Public path of a menu URL ('/teachers.php' -> '/teachers'), or null for '#' and external links. */
function admin_nav_public_path($url) {
    $slug = nav_slug_from_url($url);
    if ($slug === null) {
        return null;
    }
    return $slug === 'home' ? '/' : '/' . $slug;
}

function admin_nav_icon($label, $path) {
    $icons = [
        '/' => '🏠', '/student-works' => '🎬', '/contact' => '✉️',
        'บริการ' => '🧭', 'ผลงาน' => '🎬', 'อาจารย์' => '🧑‍🏫', 'หลักสูตร' => '🎓', 'นักศึกษา' => '👩‍🎓',
        'ตาราง' => '📅', 'ปฏิทิน' => '📅', 'ข่าว' => '📰', 'ติดต่อ' => '✉️',
    ];
    if ($path !== null && isset($icons[$path])) {
        return $icons[$path];
    }
    foreach ($icons as $key => $icon) {
        if ($key[0] !== '/' && mb_strpos($label, $key) !== false) {
            return $icon;
        }
    }
    return '📁';
}

function admin_nav_groups() {
    global $pdo;
    static $groups = null;
    if ($groups !== null) {
        return $groups;
    }
    $registry = admin_nav_registry();
    $used = [];
    $pageSlugs = array_flip($pdo->query('SELECT slug FROM pages')->fetchAll(PDO::FETCH_COLUMN));
    $menuRows = [];
    foreach ($pdo->query('SELECT id, url FROM nav_menu_items')->fetchAll() as $row) {
        $menuRows[$row['id']] = $row['url'];
    }

    // Admin items for one menu entry. The first item takes the menu's own name.
    $itemsFor = function (array $menu, $depth) use (&$registry, &$used, $pageSlugs, $menuRows) {
        $url = $menuRows[$menu['id']] ?? $menu['href'];
        $path = admin_nav_public_path($url);
        $out = [];
        if ($path !== null && isset($registry[$path]) && !isset($used[$path])) {
            $used[$path] = true;
            foreach ($registry[$path] as $i => $item) {
                $item['public'] = $path;
                if ($i === 0 && $depth > 0) {
                    $item['label'] = $menu['label'];
                }
                $out[] = $item;
            }
        } elseif ($path !== null && !isset($registry[$path]) && isset($pageSlugs[ltrim($path, '/')])) {
            $out[] = ['href' => '/admin/page-edit.php?slug=' . urlencode(ltrim($path, '/')), 'label' => $menu['label'], 'slug' => ltrim($path, '/'), 'public' => $path];
        } elseif ($path === null && $url !== '' && $url !== '#') {
            // External link in the site menu: edited in จัดการเมนูเว็บไซต์.
            $out[] = ['href' => '/admin/nav-menu.php#nav-item-' . (int) $menu['id'], 'label' => $menu['label'], 'external' => $url, 'nolink_active' => true];
        }
        return $out;
    };

    $groups = [];
    foreach (get_nav_menu_tree() as $top) {
        $topPath = admin_nav_public_path($menuRows[$top['id']] ?? '');
        $items = $itemsFor($top, 0);
        foreach ($top['children'] ?? [] as $child) {
            $items = array_merge($items, $itemsFor($child, 1));
            foreach ($child['children'] ?? [] as $grand) {
                $items = array_merge($items, array_map(fn($it) => $it + ['sub' => true], $itemsFor($grand, 2)));
            }
        }
        $groups[] = ['label' => $top['label'], 'icon' => admin_nav_icon($top['label'], $topPath), 'public' => $topPath, 'items' => $items];
    }

    // Admin pages whose public page is not in the site menu: home-page
    // sections go under หน้าหลัก, the rest are collected so nothing is lost.
    $homeIndex = null;
    foreach ($groups as $i => $g) {
        if ($g['public'] === '/') {
            $homeIndex = $i;
        }
    }
    $other = [];
    foreach ($registry as $path => $list) {
        if (isset($used[$path])) {
            continue;
        }
        foreach ($list as $item) {
            $item['public'] = $path;
            if ($homeIndex !== null && (!empty($item['home']) || $path === '/')) {
                $groups[$homeIndex]['items'][] = $item;
            } else {
                $other[] = $item;
            }
        }
    }
    $groups = array_values(array_filter($groups, fn($g) => $g['items']));
    if ($other) {
        $groups[] = ['label' => 'หน้าอื่น ๆ (ไม่อยู่ในเมนู)', 'icon' => '🗂️', 'public' => null, 'items' => $other, 'hidden_menu' => true];
    }
    $groups[] = [
        'label' => 'ตั้งค่าระบบ', 'icon' => '⚙️', 'system' => true, 'public' => null,
        'items' => [
            ['href' => '/admin/settings.php', 'label' => 'ตั้งค่าเว็บไซต์'],
            ['href' => '/admin/nav-menu.php', 'label' => 'จัดการเมนูเว็บไซต์'],
            ['href' => '/admin/opendrive.php', 'label' => 'ที่เก็บไฟล์ OpenDrive'],
            ['href' => '/admin/admins.php', 'label' => 'ผู้ดูแลระบบ'],
            ['href' => '/admin/edit-code.php', 'label' => '🔒 รหัสแก้ไขหลังบ้าน'],
        ],
    ];
    return $groups;
}

/** [group label, item label] of the admin page currently open, for the page heading. */
function admin_nav_current() {
    foreach (admin_nav_groups() as $group) {
        foreach ($group['items'] as $item) {
            if (admin_nav_is_active($item)) {
                return [$group['label'], $item['label']];
            }
        }
    }
    return null;
}

/** Small counters shown next to menu items (e.g. profiles waiting for review). */
function admin_nav_badge($key) {
    global $pdo;
    if ($key === 'alumni_hub_pending') {
        try {
            return (int) $pdo->query("SELECT COUNT(*) FROM alumni_members WHERE status = 'pending'")->fetchColumn();
        } catch (PDOException $e) {
            return 0; // table not created yet
        }
    }
    return 0;
}

/** True when $item is the page currently open (incl. its sub-pages / page-edit slug). */
function admin_nav_is_active(array $item) {
    if (!empty($item['nolink_active'])) {
        return false;
    }
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $path = parse_url($item['href'], PHP_URL_PATH);
    if (isset($item['slug'])) {
        return $script === 'page-edit.php' && ($_GET['slug'] ?? '') === $item['slug'];
    }
    return $script === basename($path) || in_array($script, $item['match'] ?? [], true);
}
