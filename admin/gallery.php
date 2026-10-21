<?php
require_once __DIR__ . '/includes/auth.php';

ensure_gallery_albums();
$action = $_POST['action'] ?? '';
$max = GALLERY_ALBUM_MAX_PHOTOS;

function album_photo_count($albumId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM gallery WHERE album_id = ?');
    $stmt->execute([(int) $albumId]);
    return (int) $stmt->fetchColumn();
}

// One image per request from the bulk uploader (admin/assets/gallery-upload.js).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_one') {
    header('Content-Type: application/json; charset=utf-8');
    csrf_check();
    session_write_close();

    $albumId = (int) ($_POST['album_id'] ?? 0);
    if (!get_gallery_album($albumId)) {
        echo json_encode(['ok' => false, 'error' => 'ไม่พบอัลบั้ม']);
        exit;
    }
    // Checked per request: several uploads run in parallel, so the album
    // can fill up while a batch is still going.
    if (album_photo_count($albumId) >= $max) {
        echo json_encode(['ok' => false, 'full' => true, 'error' => "อัลบั้มเต็มแล้ว (สูงสุด $max รูป)"]);
        exit;
    }
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
    $stmt = $pdo->prepare('INSERT INTO gallery (image_path, caption, album_id) VALUES (?, ?, ?)');
    $stmt->execute([$imagePath, $caption, $albumId]);
    echo json_encode([
        'ok' => true,
        'id' => (int) $pdo->lastInsertId(),
        'notice' => $_SESSION['upload_notice'] ?? '',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    reject_oversized_upload('/admin/gallery.php');
    csrf_check();
    $albumId = (int) ($_POST['album_id'] ?? 0);
    $back = $albumId ? '/admin/gallery.php?album=' . $albumId : '/admin/gallery.php';

    if ($action === 'create_album') {
        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            set_flash('กรุณากรอกชื่ออัลบั้ม', 'error');
        } else {
            $stmt = $pdo->prepare('INSERT INTO gallery_albums (title, title_en, description, event_date) VALUES (?, ?, ?, ?)');
            $stmt->execute([$title, trim($_POST['title_en'] ?? ''), trim($_POST['description'] ?? ''), ($_POST['event_date'] ?? '') ?: null]);
            set_flash('สร้างอัลบั้มแล้ว — เพิ่มรูปได้เลย (สูงสุด ' . $max . ' รูป)');
            $back = '/admin/gallery.php?album=' . (int) $pdo->lastInsertId();
        }
    } elseif ($action === 'update_album' && $albumId) {
        $stmt = $pdo->prepare('UPDATE gallery_albums SET title = ?, title_en = ?, description = ?, event_date = ?, sort_order = ? WHERE id = ?');
        $stmt->execute([trim($_POST['title'] ?? ''), trim($_POST['title_en'] ?? ''), trim($_POST['description'] ?? ''), ($_POST['event_date'] ?? '') ?: null, (int) ($_POST['sort_order'] ?? 0), $albumId]);
        set_flash('บันทึกข้อมูลอัลบั้มแล้ว');
    } elseif ($action === 'delete_album' && $albumId) {
        $pdo->prepare('DELETE FROM gallery WHERE album_id = ?')->execute([$albumId]);
        $pdo->prepare('DELETE FROM gallery_albums WHERE id = ?')->execute([$albumId]);
        set_flash('ลบอัลบั้มและรูปทั้งหมดในอัลบั้มแล้ว');
        $back = '/admin/gallery.php';
    } elseif ($action === 'set_cover' && $albumId) {
        $stmt = $pdo->prepare('SELECT image_path FROM gallery WHERE id = ? AND album_id = ?');
        $stmt->execute([(int) $_POST['photo_id'], $albumId]);
        if ($path = $stmt->fetchColumn()) {
            $pdo->prepare('UPDATE gallery_albums SET cover_path = ? WHERE id = ?')->execute([$path, $albumId]);
            set_flash('ตั้งเป็นภาพปกอัลบั้มแล้ว');
        }
    } elseif ($action === 'delete_selected' && $albumId) {
        $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? []))));
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM gallery WHERE album_id = ? AND id IN ($placeholders)");
            $stmt->execute(array_merge([$albumId], $ids));
            // Drop a cover that pointed at a deleted photo.
            $pdo->prepare('UPDATE gallery_albums SET cover_path = NULL WHERE id = ? AND cover_path IS NOT NULL
                AND cover_path NOT IN (SELECT image_path FROM gallery WHERE album_id = ?)')->execute([$albumId, $albumId]);
        }
        set_flash('ลบภาพที่เลือกแล้ว ' . count($ids) . ' ภาพ');
    }
    header('Location: ' . $back);
    exit;
}

$album = isset($_GET['album']) ? get_gallery_album($_GET['album']) : null;
$adminPageTitle = $album ? 'อัลบั้ม: ' . $album['title'] : 'แกลอรี่ภาพกิจกรรม (อัลบั้ม)';
require_once __DIR__ . '/includes/layout-top.php';

if (!$album):
    $albums = get_gallery_albums();
?>
<div class="admin-card">
    <h3 style="margin-top:0;">สร้างอัลบั้มใหม่</h3>
    <form method="post" class="album-create-form">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="create_album">
        <div class="form-group"><label>ชื่ออัลบั้ม / ชื่อกิจกรรม</label><input type="text" name="title" required maxlength="255" placeholder="เช่น ปฐมนิเทศนักศึกษาใหม่ 2569"></div>
        <div class="form-group"><label>ชื่ออัลบั้ม (English)</label><input type="text" name="title_en" maxlength="255"></div>
        <div class="form-group"><label>วันที่จัดกิจกรรม</label><input type="date" name="event_date"></div>
        <div class="form-group"><label>คำอธิบาย (ไม่บังคับ)</label><textarea name="description" rows="2"></textarea></div>
        <button class="btn" type="submit">สร้างอัลบั้ม แล้วเพิ่มรูป</button>
    </form>
</div>

<div class="admin-card">
    <h3 style="margin-top:0;">อัลบั้มทั้งหมด (<?= count($albums) ?>)</h3>
    <?php if ($albums): ?>
    <div class="album-admin-grid">
        <?php foreach ($albums as $a): ?>
        <a class="album-admin-card" href="/admin/gallery.php?album=<?= (int) $a['id'] ?>">
            <span class="album-admin-cover"<?php if ($a['cover']): ?> style="background-image:url('<?= h($a['cover']) ?>')"<?php endif; ?>>
                <span class="album-admin-count"><?= (int) $a['photo_count'] ?>/<?= $max ?></span>
            </span>
            <strong><?= h($a['title']) ?></strong>
            <small><?= $a['event_date'] ? h(thai_date($a['event_date'])) : 'ไม่ระบุวันที่' ?></small>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p>ยังไม่มีอัลบั้ม สร้างอัลบั้มแรกด้านบนได้เลย</p>
    <?php endif; ?>
</div>
<?php else:
    $photos = get_album_photos($album['id']);
    $count = count($photos);
    $remaining = max(0, $max - $count);
?>
<p><a href="/admin/gallery.php">&larr; กลับไปหน้าอัลบั้มทั้งหมด</a></p>

<div class="admin-card">
    <h3 style="margin-top:0;">ข้อมูลอัลบั้ม</h3>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="update_album">
        <input type="hidden" name="album_id" value="<?= (int) $album['id'] ?>">
        <div class="form-group"><label>ชื่ออัลบั้ม</label><input type="text" name="title" required maxlength="255" value="<?= h($album['title']) ?>"></div>
        <div class="form-group"><label>ชื่ออัลบั้ม (English)</label><input type="text" name="title_en" maxlength="255" value="<?= h($album['title_en']) ?>"></div>
        <div class="form-group"><label>วันที่จัดกิจกรรม</label><input type="date" name="event_date" value="<?= h($album['event_date']) ?>"></div>
        <div class="form-group"><label>คำอธิบาย</label><textarea name="description" rows="2"><?= h($album['description']) ?></textarea></div>
        <div class="form-group"><label>ลำดับการแสดงผล (น้อยขึ้นก่อน)</label><input type="number" name="sort_order" value="<?= (int) $album['sort_order'] ?>" style="max-width:120px;"></div>
        <button class="btn" type="submit">บันทึก</button>
    </form>
    <form method="post" onsubmit="return confirm('ลบอัลบั้มนี้และรูปทั้งหมด <?= $count ?> รูป?');" style="margin-top:12px;">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="delete_album">
        <input type="hidden" name="album_id" value="<?= (int) $album['id'] ?>">
        <button class="btn btn-sm btn-danger" type="submit">ลบอัลบั้มนี้</button>
    </form>
</div>

<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มรูปในอัลบั้ม <span class="album-quota<?= $remaining === 0 ? ' is-full' : '' ?>"><?= $count ?>/<?= $max ?> รูป</span></h3>
    <?php if ($remaining > 0): ?>
    <form method="post" enctype="multipart/form-data" id="galleryUploadForm" data-album="<?= (int) $album['id'] ?>" data-remaining="<?= $remaining ?>">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <label class="bulk-drop" id="bulkDrop">
            <input type="file" name="images[]" accept="image/*" multiple id="bulkInput">
            <strong>ลากรูปมาวางที่นี่</strong>
            <span>เพิ่มได้อีก <?= $remaining ?> รูป (อัลบั้มละไม่เกิน <?= $max ?> รูป)</span>
        </label>
        <div class="form-group">
            <label>คำบรรยายภาพ (ใช้กับทุกภาพในชุดนี้ ไม่บังคับ)</label>
            <input type="text" name="caption" maxlength="255">
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
            <a class="btn btn-sm" href="/admin/gallery.php?album=<?= (int) $album['id'] ?>" id="bulkDone" hidden>เสร็จแล้ว — โหลดหน้าใหม่</a>
        </div>
        <ul class="bulk-list" id="bulkList"></ul>
    </div>
    <?php else: ?>
    <p>อัลบั้มนี้มีครบ <?= $max ?> รูปแล้ว ลบรูปเดิมก่อนถ้าต้องการเพิ่มรูปใหม่ หรือสร้างอัลบั้มใหม่</p>
    <?php endif; ?>
</div>

<div class="admin-card">
    <form method="post" id="galleryDeleteForm" onsubmit="return confirm('ยืนยันการลบภาพที่เลือก?');">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="delete_selected">
        <input type="hidden" name="album_id" value="<?= (int) $album['id'] ?>">
        <div class="gallery-admin-toolbar">
            <strong>รูปในอัลบั้ม <?= $count ?> รูป</strong>
            <?php if ($photos): ?>
            <label><input type="checkbox" id="gallerySelectAll"> เลือกทั้งหมด</label>
            <button class="btn btn-sm btn-danger" type="submit" id="galleryDeleteSelected" disabled>ลบที่เลือก (0)</button>
            <?php endif; ?>
        </div>
        <?php if ($photos): ?>
        <div class="gallery-admin-grid">
            <?php foreach ($photos as $img): ?>
            <label class="gallery-admin-item<?= $album['cover_path'] === $img['image_path'] ? ' is-cover' : '' ?>" title="<?= h($img['caption']) ?>">
                <input type="checkbox" name="ids[]" value="<?= (int) $img['id'] ?>">
                <img src="<?= h($img['image_path']) ?>" alt="" loading="lazy">
                <button type="submit" class="gallery-cover-btn" form="coverForm" name="photo_id" value="<?= (int) $img['id'] ?>" title="ตั้งเป็นภาพปก"><?= $album['cover_path'] === $img['image_path'] ? '★ ปก' : '☆ ตั้งเป็นปก' ?></button>
            </label>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p>ยังไม่มีรูปในอัลบั้มนี้</p>
        <?php endif; ?>
    </form>
    <form method="post" id="coverForm">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="set_cover">
        <input type="hidden" name="album_id" value="<?= (int) $album['id'] ?>">
    </form>
</div>
<script src="/admin/assets/gallery-upload.js?v=<?= (int) @filemtime(__DIR__ . '/assets/gallery-upload.js') ?>"></script>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
