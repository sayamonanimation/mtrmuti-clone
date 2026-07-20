<?php
require_once __DIR__ . '/includes/auth.php';

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

$profileFieldsEn = array_combine(
    array_map(fn($c) => $c . '_en', array_keys($profileFields)),
    array_values($profileFields)
);

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$teacher = array_merge(
    ['id' => null, 'name' => '', 'position' => '', 'position_en' => '', 'phone' => '', 'email' => '', 'photo_path' => '', 'bio' => '', 'bio_en' => '', 'classroom_online_url' => '', 'sort_order' => 0],
    array_fill_keys(array_keys($profileFields), ''),
    array_fill_keys(array_keys($profileFieldsEn), '')
);

if ($id) {
    $found = get_teacher($id);
    if ($found) {
        $teacher = $found;
    }
}

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
    $classroomUrl = normalize_url($_POST['classroom_online_url'] ?? '');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $photoPath = $teacher['photo_path'];

    $profileValues = [];
    foreach ($profileFields as $column => $label) {
        $profileValues[$column] = trim($_POST[$column] ?? '');
    }
    foreach ($profileFieldsEn as $column => $label) {
        $profileValues[$column] = trim($_POST[$column] ?? '');
    }

    $uploaded = handle_image_upload('photo', 'teachers');
    if ($uploaded) {
        $photoPath = $uploaded;
    }

    $columns = array_merge(['name', 'position', 'position_en', 'phone', 'email', 'bio', 'bio_en', 'classroom_online_url', 'sort_order', 'photo_path'], array_keys($profileFields), array_keys($profileFieldsEn));
    $values = array_merge([$name, $position, $positionEn, $phone, $email, $bio, $bioEn, $classroomUrl, $sortOrder, $photoPath], array_values($profileValues));

    if ($teacher['id']) {
        $setClause = implode(', ', array_map(fn($c) => "$c = ?", $columns));
        $stmt = $pdo->prepare("UPDATE teachers SET $setClause WHERE id = ?");
        $stmt->execute([...$values, $teacher['id']]);
    } else {
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $stmt = $pdo->prepare('INSERT INTO teachers (' . implode(', ', $columns) . ") VALUES ($placeholders)");
        $stmt->execute($values);
    }

    set_flash('บันทึกข้อมูลอาจารย์เรียบร้อยแล้ว');
    header('Location: /admin/teachers.php');
    exit;
}

$adminPageTitle = $teacher['id'] ? 'แก้ไขข้อมูลอาจารย์' : 'เพิ่มอาจารย์ใหม่';
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
        </div>
    </div>

    <div class="admin-card">
        <h3 style="margin-top:0;">ข้อมูลผลงานและวิชาการ</h3>
        <?php foreach ($profileFields as $column => $label): ?>
        <div class="form-group">
            <label><?= h($label) ?></label>
            <textarea name="<?= h($column) ?>"><?= h($teacher[$column]) ?></textarea>
            <p class="field-hint">หากมีหลายรายการ ให้ขึ้นบรรทัดใหม่ต่อ 1 รายการ ระบบจะแสดงเป็นข้อย่อยให้อัตโนมัติ</p>
        </div>
        <div class="form-group">
            <label><?= h($label) ?> (English)</label>
            <textarea name="<?= h($column) ?>_en"><?= h($teacher[$column . '_en']) ?></textarea>
        </div>
        <?php endforeach; ?>
        <div class="form-group">
            <label>Classroom Online (ลิงก์ห้องเรียนออนไลน์ของอาจารย์ท่านนี้)</label>
            <input type="text" name="classroom_online_url" placeholder="https://classroom.google.com/..." value="<?= h($teacher['classroom_online_url']) ?>">
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

<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
