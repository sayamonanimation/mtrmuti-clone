<?php
/**
 * Admin navigation grouped the same way as the public site's main menu,
 * so each admin section sits under the menu name visitors see.
 *
 * 'match' lists extra admin scripts (sub-pages) that belong to an item,
 * and 'slug' marks page-edit.php items by the page they edit.
 */
function admin_nav_groups() {
    return [
        [
            'label' => 'หน้าหลัก', 'icon' => '🏠', 'public' => '/',
            'items' => [
                ['href' => '/admin/page-edit.php?slug=home', 'label' => 'ข้อความหน้าหลัก', 'slug' => 'home'],
                ['href' => '/admin/sliders.php', 'label' => 'สไลด์หน้าแรก'],
                ['href' => '/admin/posts.php', 'label' => 'ข่าวประชาสัมพันธ์/กิจกรรม', 'match' => ['post-form.php']],
                ['href' => '/admin/gallery.php', 'label' => 'อัลบั้มภาพกิจกรรม', 'public' => '/gallery'],
                ['href' => '/admin/page-edit.php?slug=career-intro', 'label' => 'แนะนำอาชีพ', 'slug' => 'career-intro'],
            ],
        ],
        [
            'label' => 'เกี่ยวกับหลักสูตร', 'icon' => '🎓',
            'items' => [
                ['href' => '/admin/page-edit.php?slug=course', 'label' => 'หลักสูตรปริญญาตรี', 'slug' => 'course', 'public' => '/course'],
                ['href' => '/admin/page-edit.php?slug=history', 'label' => 'ประวัติ', 'slug' => 'history', 'public' => '/history'],
                ['href' => '/admin/fields.php', 'label' => 'รายวิชาที่เปิดสอน', 'public' => '/subjects', 'page' => 'subjects'],
                ['href' => '/admin/teachers.php', 'label' => 'อาจารย์', 'match' => ['teacher-form.php', 'teacher-portfolio.php'], 'public' => '/teachers', 'page' => 'teachers'],
                ['href' => '/admin/classrooms.php', 'label' => 'ห้องเรียน', 'public' => '/classroom', 'page' => 'classroom'],
            ],
        ],
        [
            'label' => 'ฐานข้อมูลนักศึกษา', 'icon' => '👩‍🎓',
            'items' => [
                ['href' => '/admin/students.php', 'label' => 'นักศึกษาปัจจุบัน', 'match' => ['student-photos.php'], 'public' => '/students', 'page' => 'students'],
                ['href' => '/admin/alumni.php', 'label' => 'ศิษย์เก่า', 'match' => ['alumni-photos.php'], 'public' => '/alumni', 'page' => 'alumni'],
                ['href' => '/admin/outstanding-alumni.php', 'label' => 'ศิษย์เก่าดีเด่น', 'sub' => true, 'public' => '/outstanding-alumni', 'page' => 'outstanding-alumni'],
                ['href' => '/admin/stats.php', 'label' => 'สถิติการรับนักศึกษา', 'public' => '/statistics', 'page' => 'statistics'],
                ['href' => '/admin/student-year-stats.php', 'label' => 'สถิตินักศึกษาแยกชั้นปี', 'public' => '/statistics'],
            ],
        ],
        [
            'label' => 'ตารางเวลา', 'icon' => '📅',
            'items' => [
                ['href' => '/admin/academic-calendar.php', 'label' => 'ปฏิทินการศึกษา', 'public' => '/academic-calendar'],
                ['href' => '/admin/activity-calendar.php', 'label' => 'ปฏิทินกิจกรรม', 'public' => '/activity-calendar'],
            ],
        ],
        [
            'label' => 'บริการนักศึกษา', 'icon' => '🧭',
            'items' => [
                ['href' => '/admin/page-edit.php?slug=student-services', 'label' => 'ข้อมูลทั่วไป', 'slug' => 'student-services', 'public' => '/student-services'],
                ['href' => '/admin/student-service-links.php', 'label' => 'ลิงก์ระบบบริการนักศึกษา'],
            ],
        ],
        [
            'label' => 'ผลงานนักศึกษา', 'icon' => '🎬', 'public' => '/student-works',
            'items' => [
                ['href' => '/admin/student-work-batches.php', 'label' => 'ผลงานนักศึกษา', 'match' => ['student-work-items.php'], 'public' => '/student-works'],
            ],
        ],
        [
            'label' => 'สำหรับอาจารย์', 'icon' => '🧑‍🏫',
            'items' => [
                ['href' => '/admin/teacher-service-links.php', 'label' => 'ลิงก์ระบบสำหรับอาจารย์'],
                ['href' => '/admin/page-edit.php?slug=classroom-online', 'label' => 'Classroom Online', 'slug' => 'classroom-online', 'public' => '/classroom-online'],
            ],
        ],
        [
            'label' => 'ติดต่อเรา', 'icon' => '✉️', 'public' => '/contact',
            'items' => [
                ['href' => '/admin/page-edit.php?slug=contact', 'label' => 'หน้าติดต่อเรา', 'slug' => 'contact', 'public' => '/contact'],
            ],
        ],
        [
            'label' => 'ตั้งค่าระบบ', 'icon' => '⚙️', 'system' => true,
            'items' => [
                ['href' => '/admin/settings.php', 'label' => 'ตั้งค่าเว็บไซต์'],
                ['href' => '/admin/nav-menu.php', 'label' => 'จัดการเมนูเว็บไซต์'],
                ['href' => '/admin/opendrive.php', 'label' => 'ที่เก็บไฟล์ OpenDrive'],
                ['href' => '/admin/admins.php', 'label' => 'ผู้ดูแลระบบ'],
            ],
        ],
    ];
}

/** True when $item is the page currently open (incl. its sub-pages / page-edit slug). */
function admin_nav_is_active(array $item) {
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $path = parse_url($item['href'], PHP_URL_PATH);
    if (isset($item['slug'])) {
        return $script === 'page-edit.php' && ($_GET['slug'] ?? '') === $item['slug'];
    }
    return $script === basename($path) || in_array($script, $item['match'] ?? [], true);
}
