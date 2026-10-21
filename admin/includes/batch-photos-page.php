<?php
/**
 * Shared photo manager for a student or alumni cohort, with the bulk
 * uploader (any number of photos at once). Included by
 * admin/student-photos.php and admin/alumni-photos.php, which set:
 *   $batchKind = 'student' | 'alumni'
 */
$cfg = $batchKind === 'alumni'
    ? ['table' => 'alumni_photos', 'list' => '/admin/alumni.php', 'self' => '/admin/alumni-photos.php', 'subdir' => 'alumni',
       'label' => 'ศิษย์เก่า', 'get_batch' => 'get_alumni_batch', 'get_photos' => 'get_alumni_photos', 'hint' => 'เช่น ภาพหมู่วันรับปริญญา']
    : ['table' => 'student_photos', 'list' => '/admin/students.php', 'self' => '/admin/student-photos.php', 'subdir' => 'students',
       'label' => 'นักศึกษา', 'get_batch' => 'get_student_batch', 'get_photos' => 'get_student_photos', 'hint' => 'เช่น ภาพกิจกรรมปฐมนิเทศ'];

$batchId = isset($_GET['batch_id']) ? (int) $_GET['batch_id'] : 0;
$batch = $batchId ? $cfg['get_batch']($batchId) : null;
$selfUrl = $cfg['self'] . '?batch_id=' . $batchId;
$action = $_POST['action'] ?? '';

// One photo per request from the bulk uploader (JSON response).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_one') {
    header('Content-Type: application/json; charset=utf-8');
    csrf_check();
    session_write_close();
    if (!$batch) {
        echo json_encode(['ok' => false, 'error' => 'ไม่พบรุ่นนี้']);
        exit;
    }
    $error = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        echo json_encode(['ok' => false, 'error' => 'ไฟล์ใหญ่เกินที่เซิร์ฟเวอร์อนุญาต']);
        exit;
    }
    unset($_SESSION['upload_notice']);
    $imagePath = handle_image_upload('image', $cfg['subdir']);
    if (!$imagePath) {
        echo json_encode(['ok' => false, 'error' => 'ไม่ใช่ไฟล์ภาพที่รองรับ (jpg, png, webp, gif)']);
        exit;
    }
    $caption = mb_substr(trim($_POST['caption'] ?? ''), 0, 255);
    $stmt = $pdo->prepare("INSERT INTO {$cfg['table']} (batch_id, image_path, caption) VALUES (?, ?, ?)");
    $stmt->execute([$batchId, $imagePath, $caption]);
    echo json_encode(['ok' => true, 'id' => (int) $pdo->lastInsertId(), 'notice' => $_SESSION['upload_notice'] ?? ''], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$batch) {
    set_flash('ไม่พบรุ่นที่ต้องการ', 'error');
    header('Location: ' . $cfg['list']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    reject_oversized_upload($selfUrl);
    csrf_check();
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM {$cfg['table']} WHERE id = ? AND batch_id = ?");
        $stmt->execute([(int) $_POST['id'], $batchId]);
        set_flash('ลบรูปภาพเรียบร้อยแล้ว');
    } elseif ($action === 'delete_selected') {
        $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? []))));
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM {$cfg['table']} WHERE batch_id = ? AND id IN ($placeholders)");
            $stmt->execute(array_merge([$batchId], $ids));
        }
        set_flash('ลบรูปที่เลือกแล้ว ' . count($ids) . ' รูป');
    }
    header('Location: ' . $selfUrl);
    exit;
}

$adminPageTitle = 'รูปภาพรุ่น: ' . $batch['name'];
require_once __DIR__ . '/layout-top.php';

$photos = $cfg['get_photos']($batchId);
?>
<p><a href="<?= h($cfg['list']) ?>">&larr; กลับไปหน้ารุ่น<?= h($cfg['label']) ?></a></p>

<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มรูปภาพ (เลือกได้หลายรูปพร้อมกัน ไม่จำกัดจำนวน)</h3>
    <form method="post" enctype="multipart/form-data" id="galleryUploadForm" data-endpoint="<?= h($selfUrl) ?>">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <label class="bulk-drop" id="bulkDrop">
            <input type="file" name="images[]" accept="image/*" multiple id="bulkInput">
            <strong>ลากรูปมาวางที่นี่</strong>
            <span>หรือคลิกเพื่อเลือกไฟล์ (กด ⌘/Ctrl+A ในโฟลเดอร์เพื่อเลือกทั้งหมด) — อัปโหลดได้ครั้งละ 70 รูปขึ้นไป</span>
        </label>
        <div class="form-group">
            <label>คำบรรยายภาพ (ใช้กับทุกภาพในชุดนี้ ไม่บังคับ)</label>
            <input type="text" name="caption" maxlength="255" placeholder="<?= h($cfg['hint']) ?>">
        </div>
        <div class="form-group">
            <label><input type="checkbox" id="bulkResize" checked> ย่อขนาดรูปก่อนอัปโหลด (กว้าง/สูงไม่เกิน 2000px) — อัปโหลดเร็วขึ้นมาก แนะนำให้เปิดไว้</label>
        </div>
        <button class="btn" type="submit" id="bulkStart">อัพโหลด</button>
    </form>
    <div class="bulk-status" id="bulkStatus" hidden>
        <div class="bulk-progress"><div class="bulk-progress-bar" id="bulkBar"></div></div>
        <p class="bulk-summary" id="bulkSummary"></p>
        <div class="bulk-actions">
            <button type="button" class="btn btn-sm btn-ghost" id="bulkRetry" hidden>ลองอัปโหลดรูปที่ไม่สำเร็จอีกครั้ง</button>
            <a class="btn btn-sm" href="<?= h($selfUrl) ?>" id="bulkDone" hidden>เสร็จแล้ว — โหลดหน้าใหม่</a>
        </div>
        <ul class="bulk-list" id="bulkList"></ul>
    </div>
</div>

<div class="admin-card">
    <form method="post" onsubmit="return confirm('ยืนยันการลบรูปที่เลือก?');">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="delete_selected">
        <div class="gallery-admin-toolbar">
            <strong>รูปในรุ่นนี้ <?= count($photos) ?> รูป</strong>
            <?php if ($photos): ?>
            <label><input type="checkbox" id="gallerySelectAll"> เลือกทั้งหมด</label>
            <button class="btn btn-sm btn-danger" type="submit" id="galleryDeleteSelected" disabled>ลบที่เลือก (0)</button>
            <?php endif; ?>
        </div>
        <?php if ($photos): ?>
        <div class="gallery-admin-grid">
            <?php foreach ($photos as $photo): ?>
            <label class="gallery-admin-item" title="<?= h($photo['caption']) ?>">
                <input type="checkbox" name="ids[]" value="<?= (int) $photo['id'] ?>">
                <img src="<?= h($photo['image_path']) ?>" alt="" loading="lazy">
                <?php if ($photo['caption']): ?><span class="gallery-admin-caption"><?= h($photo['caption']) ?></span><?php endif; ?>
            </label>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="field-hint" style="margin:0;">ยังไม่มีรูปภาพในรุ่นนี้</p>
        <?php endif; ?>
    </form>
</div>
<script src="/admin/assets/gallery-upload.js?v=<?= (int) @filemtime(__DIR__ . '/../assets/gallery-upload.js') ?>"></script>
<?php require_once __DIR__ . '/layout-bottom.php'; ?>
