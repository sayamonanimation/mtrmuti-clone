<?php
require_once __DIR__ . '/includes/auth.php';

ensure_classroom_photos_table();

/** Stores newly uploaded photos for a classroom, never going over CLASSROOM_MAX_PHOTOS. Returns a flash note. */
function save_classroom_uploads($classroomId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*), COALESCE(MAX(sort_order), -1) FROM classroom_photos WHERE classroom_id = ?');
    $stmt->execute([$classroomId]);
    [$existing, $maxSort] = $stmt->fetch(PDO::FETCH_NUM);
    $slots = max(0, CLASSROOM_MAX_PHOTOS - (int) $existing);

    $upload = handle_multi_image_upload('images', 'classrooms', $slots);
    $insert = $pdo->prepare('INSERT INTO classroom_photos (classroom_id, image_path, sort_order) VALUES (?, ?, ?)');
    foreach ($upload['paths'] as $i => $path) {
        $insert->execute([$classroomId, $path, (int) $maxSort + 1 + $i]);
    }
    sync_classroom_cover($classroomId);

    if ($upload['skipped'] > 0) {
        return ' (ข้ามไป ' . $upload['skipped'] . ' รูป: ห้องเรียนละไม่เกิน ' . CLASSROOM_MAX_PHOTOS . ' รูป หรือไฟล์ไม่ใช่รูปภาพ)';
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    reject_oversized_upload('/admin/classrooms.php');
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM classrooms WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('ลบข้อมูลห้องเรียนเรียบร้อยแล้ว');
    } elseif ($action === 'delete_photo') {
        $photoId = (int) $_POST['photo_id'];
        $stmt = $pdo->prepare('SELECT classroom_id FROM classroom_photos WHERE id = ?');
        $stmt->execute([$photoId]);
        $classroomId = $stmt->fetchColumn();
        if ($classroomId) {
            $pdo->prepare('DELETE FROM classroom_photos WHERE id = ?')->execute([$photoId]);
            sync_classroom_cover($classroomId);
        }
        set_flash('ลบรูปภาพเรียบร้อยแล้ว');
    } elseif ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $nameEn = trim($_POST['name_en'] ?? '');
        $descriptionEn = trim($_POST['description_en'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        if ($name !== '') {
            $stmt = $pdo->prepare('INSERT INTO classrooms (name, description, name_en, description_en, sort_order) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $description, $nameEn, $descriptionEn, $sortOrder]);
            $note = save_classroom_uploads((int) $pdo->lastInsertId());
            set_flash('เพิ่มห้องเรียนเรียบร้อยแล้ว' . $note, $note ? 'error' : 'success');
        } else {
            set_flash('กรุณากรอกชื่อห้องเรียน', 'error');
        }
    } elseif ($action === 'edit') {
        $id = (int) $_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $nameEn = trim($_POST['name_en'] ?? '');
        $descriptionEn = trim($_POST['description_en'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $stmt = $pdo->prepare('UPDATE classrooms SET name = ?, description = ?, name_en = ?, description_en = ?, sort_order = ? WHERE id = ?');
        $stmt->execute([$name, $description, $nameEn, $descriptionEn, $sortOrder, $id]);
        $note = save_classroom_uploads($id);
        set_flash('บันทึกข้อมูลห้องเรียนเรียบร้อยแล้ว' . $note, $note ? 'error' : 'success');
    }
    header('Location: /admin/classrooms.php');
    exit;
}

$adminPageTitle = 'จัดการห้องเรียน';
require_once __DIR__ . '/includes/layout-top.php';

$classrooms = get_classrooms();
$photos = get_classroom_photos();
$max = CLASSROOM_MAX_PHOTOS;
?>
<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มห้องเรียน</h3>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-group"><label>ชื่อห้องเรียน</label><input type="text" name="name" required></div>
        <div class="form-group"><label>คำอธิบาย</label><textarea name="description"></textarea></div>
        <div class="form-group"><label>ชื่อห้องเรียน (English)</label><input type="text" name="name_en"></div>
        <div class="form-group"><label>คำอธิบาย (English)</label><textarea name="description_en"></textarea></div>
        <div class="form-group">
            <label>รูปภาพ (เลือกได้สูงสุด <?= $max ?> รูป รูปแรกจะเป็นรูปหลัก)</label>
            <input type="file" name="images[]" accept="image/*" multiple data-max-files="<?= $max ?>">
            <p class="field-hint photo-limit-hint" hidden></p>
        </div>
        <div class="form-group"><label>ลำดับการแสดงผล</label><input type="number" name="sort_order" value="0"></div>
        <button class="btn" type="submit">เพิ่ม</button>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead><tr><th>รูปภาพ</th><th>ข้อมูลห้องเรียน (ไทย/English)</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($classrooms as $c): ?>
            <?php $list = $photos[$c['id']] ?? []; $remaining = $max - count($list); ?>
            <tr>
                <td style="min-width:220px;">
                    <div class="classroom-photo-grid">
                        <?php foreach ($list as $i => $photo): ?>
                        <div class="classroom-photo-item">
                            <img src="<?= h($photo['image_path']) ?>" alt="">
                            <?php if ($i === 0): ?><span class="classroom-photo-badge">รูปหลัก</span><?php endif; ?>
                            <form method="post" onsubmit="return confirm('ลบรูปนี้?');">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete_photo">
                                <input type="hidden" name="photo_id" value="<?= (int) $photo['id'] ?>">
                                <button class="classroom-photo-remove" type="submit" title="ลบรูปนี้" aria-label="ลบรูปนี้">&times;</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="field-hint"><?= count($list) ?>/<?= $max ?> รูป</p>
                </td>
                <td>
                    <form method="post" class="inline-edit-form" enctype="multipart/form-data" style="max-width:420px;">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <input type="text" name="name" value="<?= h($c['name']) ?>" placeholder="ชื่อห้องเรียน" style="margin-bottom:6px;">
                        <textarea name="description" placeholder="คำอธิบาย" style="margin-bottom:6px;"><?= h($c['description']) ?></textarea>
                        <input type="text" name="name_en" value="<?= h($c['name_en']) ?>" placeholder="Name (English)" style="margin-bottom:6px;">
                        <textarea name="description_en" placeholder="Description (English)" style="margin-bottom:6px;"><?= h($c['description_en']) ?></textarea>
                        <?php if ($remaining > 0): ?>
                        <label class="field-hint">เพิ่มรูปภาพ (เพิ่มได้อีก <?= $remaining ?> รูป)</label>
                        <input type="file" name="images[]" accept="image/*" multiple data-max-files="<?= $remaining ?>" style="margin-bottom:6px;">
                        <p class="field-hint photo-limit-hint" hidden></p>
                        <?php else: ?>
                        <p class="field-hint">ครบ <?= $max ?> รูปแล้ว ลบรูปเดิมก่อนถ้าต้องการเพิ่มรูปใหม่</p>
                        <?php endif; ?>
                        <input type="number" name="sort_order" value="<?= h($c['sort_order']) ?>" style="width:80px;" title="ลำดับการแสดงผล">
                        <button class="btn btn-sm btn-ghost" type="submit">บันทึก</button>
                    </form>
                </td>
                <td>
                    <form method="post" onsubmit="return confirm('ยืนยันการลบห้องเรียนนี้และรูปทั้งหมด?');">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$classrooms): ?>
            <tr><td colspan="3">ยังไม่มีข้อมูลห้องเรียน</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<script>
// Stop the admin from picking more files than the classroom has room for.
document.querySelectorAll('input[type=file][data-max-files]').forEach(function (input) {
    input.addEventListener('change', function () {
        var max = parseInt(input.getAttribute('data-max-files'), 10);
        var hint = input.parentNode.querySelector('.photo-limit-hint');
        if (input.files.length > max) {
            alert('เลือกได้สูงสุด ' + max + ' รูป (เลือกมา ' + input.files.length + ' รูป) กรุณาเลือกใหม่');
            input.value = '';
            if (hint) hint.hidden = true;
        } else if (hint) {
            hint.textContent = 'เลือกแล้ว ' + input.files.length + ' รูป';
            hint.hidden = input.files.length === 0;
        }
    });
});
</script>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
