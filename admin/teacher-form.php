<?php
require_once __DIR__ . '/includes/auth.php';

ensure_teacher_works_levels();

// label => db column, for the long-text profile sections
$profileFields = [
    'education' => 'วุฒิการศึกษา',
    'subjects_taught' => 'วิชาที่สอน',
    'research_works' => 'ผลงานวิจัย',
    'creative_works' => 'ผลงานสร้างสรรค์',
    'innovation_works' => 'ผลงานนวัตกรรม',
    'competition_works' => 'ผลงานประกวด',
    'intellectual_property' => 'ทรัพย์สินทางปัญญา',
    'copyrights_patents' => 'ลิขสิทธิ์/สิทธิบัตร',
    'awards' => 'รางวัลที่ได้รับ',
];

// Works sections are Thai only (requested); English stays for these two.
$englishFields = ['education', 'subjects_taught'];
$profileFieldsEn = array_combine(
    array_map(fn($c) => $c . '_en', $englishFields),
    array_map(fn($c) => $profileFields[$c], $englishFields)
);
// Research / creative works: the main column is the national list, *_intl the international one.
$levelFields = array_combine(
    array_map(fn($c) => $c . '_intl', TEACHER_LEVELLED_WORKS),
    TEACHER_LEVELLED_WORKS
);

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$teacher = array_merge(
    ['id' => null, 'name' => '', 'position' => '', 'position_en' => '', 'phone' => '', 'email' => '', 'photo_path' => '', 'bio' => '', 'bio_en' => '', 'classroom_online_url' => '', 'sort_order' => 0, 'identity_tags' => '', 'show_phone' => 0],
    array_fill_keys(array_keys($profileFields), ''),
    array_fill_keys(array_keys($profileFieldsEn), ''),
    array_fill_keys(array_keys($levelFields), '')
);

if ($id) {
    $found = get_teacher($id);
    if ($found) {
        $teacher = $found + array_fill_keys(array_keys($levelFields), '');
    }
}
$classroomLinks = $teacher['id'] ? get_teacher_classroom_links($teacher['id']) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    reject_oversized_upload($teacher['id'] ? '/admin/teacher-form.php?id=' . $teacher['id'] : '/admin/teacher-form.php');
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $positionEn = trim($_POST['position_en'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $bioEn = trim($_POST['bio_en'] ?? '');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $identityTags = mb_substr(trim($_POST['identity_tags'] ?? ''), 0, 255);
    $showPhone = isset($_POST['show_phone']) ? 1 : 0;
    $photoPath = $teacher['photo_path'];

    $profileValues = [];
    foreach ($profileFields as $column => $label) {
        $profileValues[$column] = trim($_POST[$column] ?? '');
    }
    foreach ($profileFieldsEn as $column => $label) {
        $profileValues[$column] = trim($_POST[$column] ?? '');
    }
    foreach ($levelFields as $column => $base) {
        $profileValues[$column] = trim($_POST[$column] ?? '');
    }

    $uploaded = handle_image_upload('photo', 'teachers');
    if ($uploaded) {
        $photoPath = $uploaded;
    }

    $columns = array_merge(['name', 'position', 'position_en', 'phone', 'email', 'bio', 'bio_en', 'sort_order', 'photo_path', 'identity_tags', 'show_phone'], array_keys($profileValues));
    $values = array_merge([$name, $position, $positionEn, $phone, $email, $bio, $bioEn, $sortOrder, $photoPath, $identityTags, $showPhone], array_values($profileValues));

    if ($teacher['id']) {
        $setClause = implode(', ', array_map(fn($c) => "$c = ?", $columns));
        $stmt = $pdo->prepare("UPDATE teachers SET $setClause WHERE id = ?");
        $stmt->execute([...$values, $teacher['id']]);
    } else {
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $stmt = $pdo->prepare('INSERT INTO teachers (' . implode(', ', $columns) . ") VALUES ($placeholders)");
        $stmt->execute($values);
        $teacher['id'] = (int) $pdo->lastInsertId();
    }
    save_teacher_classroom_links($teacher['id'], (array) ($_POST['classroom_label'] ?? []), (array) ($_POST['classroom_url'] ?? []));

    set_flash('บันทึกข้อมูลอาจารย์เรียบร้อยแล้ว');
    header('Location: /admin/teachers.php');
    exit;
}

$adminPageTitle = $teacher['id'] ? 'แก้ไขข้อมูลอาจารย์' : 'เพิ่มอาจารย์ใหม่';
$identityThemeLabels = [
    'code' => '💻 โปรแกรม', 'algorithm' => '🧠 อัลกอริทึม/ข้อมูล', 'game' => '🎮 เกม', 'web' => '🌐 เว็บ/แอป',
    'animation' => '🎞️ แอนิเมชัน', 'threed' => '🧊 3D/VR', 'film' => '🎬 ภาพยนตร์/วิดีโอ', 'photo' => '📷 ถ่ายภาพ',
    'design' => '🎨 ออกแบบ', 'sound' => '🎧 เสียง/ดนตรี',
];
require_once __DIR__ . '/includes/layout-top.php';
?>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

    <div class="admin-card">
        <h3 style="margin-top:0;">ข้อมูลพื้นฐาน</h3>
        <div class="form-group">
            <label>ชื่อ - นามสกุล</label>
            <input type="text" name="name" value="<?= h($teacher['name']) ?>" required>
        </div>
        <div class="form-group">
            <label>ตำแหน่ง</label>
            <input type="text" name="position" value="<?= h($teacher['position']) ?>">
        </div>
        <div class="form-group">
            <label>Position (English)</label>
            <input type="text" name="position_en" value="<?= h($teacher['position_en']) ?>">
        </div>
        <div class="form-group">
            <label>เบอร์โทร</label>
            <input type="text" name="phone" value="<?= h($teacher['phone']) ?>" placeholder="044-233-000 ต่อ 1234">
            <label class="toggle-switch">
                <input type="checkbox" name="show_phone" value="1" <?= (int) ($teacher['show_phone'] ?? 0) === 1 ? 'checked' : '' ?>>
                <span class="toggle-switch-track" aria-hidden="true"></span>
                <span class="toggle-switch-label">แสดงเบอร์โทรบนหน้าเว็บไซต์</span>
            </label>
            <p class="field-hint" style="margin-top:2px;">ปิดไว้ถ้าไม่ต้องการให้เบอร์โทรแสดงต่อสาธารณะ (เบอร์ยังเก็บไว้ในระบบ เปิดกลับได้ทุกเมื่อ)</p>
        </div>
        <div class="form-group">
            <label>อีเมล</label>
            <input type="email" name="email" value="<?= h($teacher['email']) ?>" placeholder="name@example.ac.th">
        </div>
        <div class="form-group">
            <label>ประวัติ</label>
            <textarea name="bio"><?= h($teacher['bio']) ?></textarea>
            <p class="field-hint">หากมีหลายรายการ ให้ขึ้นบรรทัดใหม่ต่อ 1 รายการ ระบบจะแสดงเป็นข้อย่อยให้อัตโนมัติ</p>
        </div>
        <div class="form-group">
            <label>Biography (English)</label>
            <textarea name="bio_en"><?= h($teacher['bio_en']) ?></textarea>
        </div>
        <div class="form-group">
            <label>ลำดับการแสดงผล</label>
            <input type="number" name="sort_order" value="<?= h($teacher['sort_order']) ?>">
        </div>
        <div class="form-group">
            <label>รูปภาพ</label>
            <?php if ($teacher['photo_path']): ?>
                <img class="current-thumb" src="<?= h($teacher['photo_path']) ?>" alt="">
            <?php endif; ?>
            <input type="file" name="photo" accept="image/*">
            <p class="field-hint">แนะนำรูปพื้นหลังสีเข้มหรือโปร่งใส (PNG) จะเข้ากับลูกเล่นรอบรูปได้ดีที่สุด</p>
        </div>
        <div class="form-group">
            <label>ตัวตน / ความเชี่ยวชาญ (แสดงเป็นลูกเล่นรอบรูปอาจารย์)</label>
            <p class="field-hint" style="margin-top:0;">พิมพ์คำที่บอกตัวตน คั่นด้วยเครื่องหมายจุลภาค ( , ) ไม่เกิน 6 คำ เช่น <em>เขียนโปรแกรม, อัลกอริทึม, พัฒนาเกม</em> ระบบจะเลือกลูกเล่นให้ตามคำที่กรอก</p>
            <input type="text" name="identity_tags" id="identityTags" maxlength="255" value="<?= h($teacher['identity_tags'] ?? '') ?>" placeholder="เขียนโปรแกรม, อัลกอริทึม, พัฒนาเกม">
            <div class="identity-preview" id="identityPreview"></div>
            <p class="field-hint">ธีมที่รองรับ: โปรแกรม · อัลกอริทึม/AI/ข้อมูล · เกม · เว็บ/UI/แอป · แอนิเมชัน · 3D/VR/AR · ภาพยนตร์/วิดีโอ · ถ่ายภาพ · ออกแบบ/กราฟิก · เสียง/ดนตรี</p>
        </div>
    </div>

    <div class="admin-card">
        <h3 style="margin-top:0;">ข้อมูลผลงานและวิชาการ</h3>
        <p class="field-hint" style="margin-top:0;">หากมีหลายรายการ ให้ขึ้นบรรทัดใหม่ต่อ 1 รายการ ระบบจะแสดงเป็นข้อย่อยให้อัตโนมัติ</p>
        <?php foreach ($profileFields as $column => $label): ?>
        <?php if (in_array($column, TEACHER_LEVELLED_WORKS, true)): ?>
        <fieldset class="level-fieldset">
            <legend><?= h($label) ?></legend>
            <div class="level-grid">
                <div class="form-group">
                    <label><span class="level-tag level-tag-th">ระดับชาติ</span></label>
                    <textarea name="<?= h($column) ?>"><?= h($teacher[$column]) ?></textarea>
                </div>
                <div class="form-group">
                    <label><span class="level-tag level-tag-intl">ระดับนานาชาติ</span></label>
                    <textarea name="<?= h($column) ?>_intl"><?= h($teacher[$column . '_intl'] ?? '') ?></textarea>
                </div>
            </div>
        </fieldset>
        <?php else: ?>
        <div class="form-group">
            <label><?= h($label) ?></label>
            <textarea name="<?= h($column) ?>"><?= h($teacher[$column]) ?></textarea>
        </div>
        <?php if (isset($profileFieldsEn[$column . '_en'])): ?>
        <div class="form-group">
            <label><?= h($label) ?> (English)</label>
            <textarea name="<?= h($column) ?>_en"><?= h($teacher[$column . '_en']) ?></textarea>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        <?php endforeach; ?>
        <div class="form-group">
            <label>Classroom Online (ลิงก์ห้องเรียนออนไลน์ของอาจารย์ท่านนี้ — เพิ่มได้ไม่จำกัด)</label>
            <div class="classroom-links" id="classroomLinks">
                <?php foreach ($classroomLinks ?: [['label' => '', 'url' => '']] as $cl): ?>
                <div class="classroom-link-row">
                    <input type="text" name="classroom_label[]" maxlength="150" placeholder="ชื่อห้อง/วิชา เช่น การเขียนโปรแกรม 1 (ไม่บังคับ)" value="<?= h($cl['label']) ?>">
                    <input type="text" name="classroom_url[]" maxlength="500" placeholder="https://classroom.google.com/..." value="<?= h($cl['url']) ?>">
                    <button type="button" class="btn btn-sm btn-ghost classroom-remove" title="เอาลิงก์นี้ออก">✕</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm btn-ghost" id="classroomAdd">+ เพิ่มห้องเรียน</button>
            <p class="field-hint">ลิงก์ที่เอาออกจะหายไปเมื่อกดบันทึก · ถ้าไม่ใส่ชื่อ ปุ่มบนหน้าเว็บจะขึ้นว่า "Classroom Online"</p>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn" type="submit">บันทึก</button>
        <a class="btn btn-ghost" href="/admin/teachers.php">ยกเลิก</a>
    </div>
</form>

<?php if ($teacher['id']): ?>
<div class="admin-card">
    <h3 style="margin-top:0;">รูปภาพประกอบผลงาน</h3>
    <p class="field-hint" style="margin:0 0 14px;">อัพโหลดรูปภาพประกอบสำหรับผลงานวิจัย, ผลงานสร้างสรรค์, ผลงานนวัตกรรม, ผลงานประกวด และทรัพย์สินทางปัญญา</p>
    <a class="btn" href="/admin/teacher-portfolio.php?teacher_id=<?= $teacher['id'] ?>">จัดการรูปภาพผลงาน</a>
</div>
<?php else: ?>
<div class="admin-card">
    <p class="field-hint" style="margin:0;">บันทึกข้อมูลอาจารย์ก่อน จึงจะสามารถอัพโหลดรูปภาพประกอบผลงานได้</p>
</div>
<?php endif; ?>

<script>
// Live preview of which effect themes the typed identity words will trigger.
(function () {
    var input = document.getElementById('identityTags');
    var out = document.getElementById('identityPreview');
    if (!input || !out) return;
    var themes = <?= json_encode(TEACHER_IDENTITY_THEMES, JSON_UNESCAPED_UNICODE) ?>;
    var labels = <?= json_encode($identityThemeLabels, JSON_UNESCAPED_UNICODE) ?>;
    function detect() {
        var tags = input.value.split(/[,\n]+/).map(function (t) { return t.trim(); }).filter(Boolean).slice(0, 6);
        var found = {};
        tags.forEach(function (tag) {
            var lower = tag.toLowerCase();
            Object.keys(themes).forEach(function (th) {
                themes[th].some(function (kw) {
                    var hit = (kw.length <= 3 && /^[a-z0-9+]+$/.test(kw))
                        ? new RegExp('(^|[^a-z])' + kw.replace(/[+]/g, '\\+') + '([^a-z]|$)').test(lower)
                        : lower.indexOf(kw) !== -1;
                    if (hit) found[th] = true;
                    return hit;
                });
            });
        });
        var keys = Object.keys(found);
        out.innerHTML = tags.length
            ? '<span class="identity-preview-label">ลูกเล่นที่จะแสดง:</span> ' + (keys.length ? keys.map(function (k) { return '<span class="identity-chip">' + labels[k] + '</span>'; }).join(' ') : '<span class="identity-chip identity-chip-none">✨ ทั่วไป (ไม่พบคำที่ตรงกับธีม)</span>')
            : '';
    }
    input.addEventListener('input', detect);
    detect();
})();
</script>
<script>
(function () {
    var box = document.getElementById('classroomLinks'), add = document.getElementById('classroomAdd');
    if (!box) return;
    add.addEventListener('click', function () {
        var row = box.querySelector('.classroom-link-row').cloneNode(true);
        row.querySelectorAll('input').forEach(function (i) { i.value = ''; });
        box.appendChild(row);
        row.querySelector('input').focus();
    });
    box.addEventListener('click', function (e) {
        var btn = e.target.closest('.classroom-remove');
        if (!btn) return;
        var rows = box.querySelectorAll('.classroom-link-row'), row = btn.closest('.classroom-link-row');
        if (rows.length > 1) row.remove();
        else row.querySelectorAll('input').forEach(function (i) { i.value = ''; });
    });
})();
</script>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
