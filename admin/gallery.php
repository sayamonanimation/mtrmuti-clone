<?php
require_once __DIR__ . '/includes/auth.php';

$action = $_POST['action'] ?? '';

// One image per request, sent by the bulk uploader below (any number of
// files, a few at a time). Answers JSON instead of redirecting.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_one') {
    header('Content-Type: application/json; charset=utf-8');
    csrf_check();
    // Release the session lock so the uploader's parallel requests really
    // run in parallel instead of queuing behind each other.
    session_write_close();

    $error = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        echo json_encode(['ok' => false, 'error' => 'ไฟล์ใหญ่เกินที่เซิร์ฟเวอร์อนุญาต']);
        exit;
    }
    unset($_SESSION['upload_notice']);
    $imagePath = handle_image_upload('image', 'gallery');
    if (!$imagePath) {
        echo json_encode(['ok' => false, 'error' => 'ไม่ใช่ไฟล์ภาพที่รองรับ (jpg, png, webp, gif)']);
        exit;
    }
    $caption = mb_substr(trim($_POST['caption'] ?? ''), 0, 255);
    $category = mb_substr(trim($_POST['category'] ?? ''), 0, 100);
    $stmt = $pdo->prepare('INSERT INTO gallery (image_path, caption, category) VALUES (?, ?, ?)');
    $stmt->execute([$imagePath, $caption, $category]);
    echo json_encode([
        'ok' => true,
        'id' => (int) $pdo->lastInsertId(),
        'path' => $imagePath,
        'notice' => $_SESSION['upload_notice'] ?? '',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    reject_oversized_upload('/admin/gallery.php');
    csrf_check();

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM gallery WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('ลบภาพเรียบร้อยแล้ว');
    } elseif ($action === 'delete_selected') {
        $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? []))));
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM gallery WHERE id IN ($placeholders)");
            $stmt->execute($ids);
        }
        set_flash('ลบภาพที่เลือกแล้ว ' . count($ids) . ' ภาพ');
    } elseif ($action === 'add') {
        // Fallback when JavaScript is off: a plain multi-file form post
        // (limited by the host's max_file_uploads, usually 20).
        $caption = trim($_POST['caption'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $upload = handle_multi_image_upload('images', 'gallery', 500);
        $stmt = $pdo->prepare('INSERT INTO gallery (image_path, caption, category) VALUES (?, ?, ?)');
        foreach ($upload['paths'] as $path) {
            $stmt->execute([$path, $caption, $category]);
        }
        if ($upload['paths']) {
            set_flash('เพิ่มภาพแล้ว ' . count($upload['paths']) . ' ภาพ' . ($upload['skipped'] ? ' (ข้าม ' . $upload['skipped'] . ' ไฟล์ที่ไม่ใช่รูปภาพ)' : ''));
        } else {
            set_flash('กรุณาเลือกไฟล์ภาพ (jpg, png, webp, gif)', 'error');
        }
    }
    header('Location: /admin/gallery.php');
    exit;
}

$adminPageTitle = 'จัดการแกลอรี่ภาพ';
require_once __DIR__ . '/includes/layout-top.php';

$images = $pdo->query('SELECT * FROM gallery ORDER BY sort_order ASC, created_at DESC, id DESC')->fetchAll();
?>
<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มภาพใหม่ (เลือกได้หลายภาพพร้อมกัน ไม่จำกัดจำนวน)</h3>
    <form method="post" enctype="multipart/form-data" id="galleryUploadForm">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <label class="bulk-drop" id="bulkDrop">
            <input type="file" name="images[]" accept="image/*" multiple id="bulkInput">
            <strong>ลากรูปมาวางที่นี่</strong>
            <span>หรือคลิกเพื่อเลือกไฟล์ (กด ⌘/Ctrl หรือ Shift เพื่อเลือกหลายรูป)</span>
        </label>
        <div class="form-group">
            <label>คำบรรยายภาพ (ใช้กับทุกภาพในชุดนี้ ไม่บังคับ)</label>
            <input type="text" name="caption" maxlength="255" placeholder="เช่น กิจกรรมปฐมนิเทศ 2569">
        </div>
        <div class="form-group">
            <label>หมวดหมู่ (ไม่บังคับ)</label>
            <input type="text" name="category" maxlength="100">
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
            <a class="btn btn-sm" href="/admin/gallery.php" id="bulkDone" hidden>เสร็จแล้ว — โหลดหน้าใหม่</a>
        </div>
        <ul class="bulk-list" id="bulkList"></ul>
    </div>
</div>

<div class="admin-card">
    <form method="post" id="galleryDeleteForm" onsubmit="return confirm('ยืนยันการลบภาพที่เลือก?');">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="delete_selected">
        <div class="gallery-admin-toolbar">
            <strong>ภาพทั้งหมด <?= count($images) ?> ภาพ</strong>
            <?php if ($images): ?>
            <label><input type="checkbox" id="gallerySelectAll"> เลือกทั้งหมด</label>
            <button class="btn btn-sm btn-danger" type="submit" id="galleryDeleteSelected" disabled>ลบที่เลือก (0)</button>
            <?php endif; ?>
        </div>
        <?php if ($images): ?>
        <div class="gallery-admin-grid">
            <?php foreach ($images as $img): ?>
            <label class="gallery-admin-item" title="<?= h($img['caption']) ?>">
                <input type="checkbox" name="ids[]" value="<?= (int) $img['id'] ?>">
                <img src="<?= h($img['image_path']) ?>" alt="" loading="lazy">
                <?php if ($img['caption'] || $img['category']): ?>
                <span class="gallery-admin-caption"><?= h($img['caption'] ?: $img['category']) ?></span>
                <?php endif; ?>
            </label>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p>ยังไม่มีภาพในแกลอรี่</p>
        <?php endif; ?>
    </form>
</div>
<script src="/admin/assets/gallery-upload.js?v=<?= (int) @filemtime(__DIR__ . '/assets/gallery-upload.js') ?>"></script>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
