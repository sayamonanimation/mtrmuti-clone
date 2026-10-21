<?php
require_once __DIR__ . '/includes/auth.php';

ensure_student_work_media();

$batchId = isset($_GET['batch_id']) ? (int) $_GET['batch_id'] : 0;
$batch = $batchId ? get_student_work_batch($batchId) : null;

if (!$batch) {
    set_flash('ไม่พบปีการศึกษาที่ต้องการ', 'error');
    header('Location: /admin/student-work-batches.php');
    exit;
}
$listUrl = '/admin/student-work-items.php?batch_id=' . $batchId;

$editing = null;
if (isset($_GET['id'])) {
    $editing = get_student_work((int) $_GET['id']);
    if (!$editing || (int) $editing['batch_id'] !== $batchId) {
        $editing = null;
    }
}

/** Signature proving a photo path came from this admin's upload_photo request. */
function student_work_upload_sig($path) {
    return hash_hmac('sha256', (string) $path, csrf_token());
}

// One photo per request (admin/assets/work-photo-upload.js), so a form with
// many photos never runs into the server's time limit while they are sent
// to OpenDrive. Returns the stored path; the form then saves the paths.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_photo') {
    header('Content-Type: application/json; charset=utf-8');
    csrf_check();
    session_write_close();
    $error = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        echo json_encode(['ok' => false, 'error' => 'ไฟล์ใหญ่เกินที่เซิร์ฟเวอร์อนุญาต'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $path = handle_image_upload('image', 'student-works');
    if (!$path) {
        echo json_encode(['ok' => false, 'error' => 'ไม่ใช่ไฟล์ภาพที่รองรับ (jpg, png, webp, gif)'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode(['ok' => true, 'path' => $path, 'sig' => student_work_upload_sig($path)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    reject_oversized_upload($listUrl);
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM student_works WHERE id = ? AND batch_id = ?');
        $stmt->execute([$id, $batchId]);
        set_flash('ลบผลงานเรียบร้อยแล้ว');
        header('Location: ' . $listUrl);
        exit;
    }

    if ($action === 'add' || $action === 'edit') {
        $fields = [
            'title' => trim($_POST['title'] ?? ''),
            'title_en' => trim($_POST['title_en'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'description_en' => trim($_POST['description_en'] ?? ''),
            'student_name' => trim($_POST['student_name'] ?? ''),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
        $existing = [];
        if ($action === 'edit') {
            $work = get_student_work($id);
            if (!$work || (int) $work['batch_id'] !== $batchId) {
                set_flash('ไม่พบผลงานนี้', 'error');
                header('Location: ' . $listUrl);
                exit;
            }
            $existing = get_student_work_photos($id);
        }
        $backUrl = $listUrl . ($action === 'edit' ? '&id=' . $id : '');
        $removeIds = array_map('intval', (array) ($_POST['remove_photos'] ?? []));
        $keep = array_values(array_filter($existing, fn($p) => !in_array((int) $p['id'], $removeIds, true)));
        $room = STUDENT_WORK_MAX_PHOTOS - count($keep);

        if ($fields['title'] === '') {
            set_flash('กรุณากรอกชื่อผลงาน', 'error');
            header('Location: ' . $backUrl);
            exit;
        }
        $preUploaded = [];
        foreach ((array) ($_POST['uploaded_photos'] ?? []) as $i => $path) {
            $sig = (string) ($_POST['uploaded_sigs'][$i] ?? '');
            if (is_string($path) && $path !== '' && hash_equals(student_work_upload_sig($path), $sig)) {
                $preUploaded[] = $path;
            }
        }
        $skippedPre = max(0, count($preUploaded) - max(0, $room));
        $preUploaded = array_slice($preUploaded, 0, max(0, $room));
        $upload = handle_multi_image_upload('images', 'student-works', max(0, $room - count($preUploaded)));
        $upload['paths'] = array_merge($preUploaded, $upload['paths']);
        $upload['skipped'] += $skippedPre;
        if (!$keep && !$upload['paths']) {
            set_flash('ผลงานต้องมีรูปอย่างน้อย 1 รูป (jpg, png, webp, gif)', 'error');
            header('Location: ' . $backUrl);
            exit;
        }

        if ($action === 'add') {
            $stmt = $pdo->prepare('INSERT INTO student_works (batch_id, title, title_en, description, description_en, student_name, image_path, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$batchId, $fields['title'], $fields['title_en'], $fields['description'], $fields['description_en'], $fields['student_name'], $upload['paths'][0], $fields['sort_order']]);
            $id = (int) $pdo->lastInsertId();
        } else {
            $stmt = $pdo->prepare('UPDATE student_works SET title = ?, title_en = ?, description = ?, description_en = ?, student_name = ?, sort_order = ? WHERE id = ? AND batch_id = ?');
            $stmt->execute([$fields['title'], $fields['title_en'], $fields['description'], $fields['description_en'], $fields['student_name'], $fields['sort_order'], $id, $batchId]);
            if ($removeIds) {
                $marks = implode(',', array_fill(0, count($removeIds), '?'));
                $pdo->prepare("DELETE FROM student_work_photos WHERE work_id = ? AND id IN ($marks)")->execute(array_merge([$id], $removeIds));
            }
            // Chosen cover goes first, the rest keep their order.
            $cover = (int) ($_POST['cover_photo'] ?? 0);
            $order = 1;
            $setOrder = $pdo->prepare('UPDATE student_work_photos SET sort_order = ? WHERE id = ? AND work_id = ?');
            foreach ($keep as $p) {
                $setOrder->execute([(int) $p['id'] === $cover ? 0 : $order++, $p['id'], $id]);
            }
        }
        add_student_work_photos($id, $upload['paths']);
        save_student_work_links($id, student_work_links_from_post());
        sync_student_work_cover($id);

        $msg = $action === 'add' ? 'เพิ่มผลงานเรียบร้อยแล้ว' : 'บันทึกผลงานเรียบร้อยแล้ว';
        if ($upload['skipped']) {
            $msg .= ' (ข้ามไป ' . $upload['skipped'] . ' ไฟล์ เพราะเกิน ' . STUDENT_WORK_MAX_PHOTOS . ' รูป หรือไม่ใช่ไฟล์ภาพ)';
        }
        set_flash($msg);
        header('Location: ' . $listUrl . '&id=' . $id);
        exit;
    }
    header('Location: ' . $listUrl);
    exit;
}

$adminPageTitle = 'ผลงานนักศึกษา: ' . $batch['name'];
require_once __DIR__ . '/includes/layout-top.php';

$works = get_student_works($batchId);
$media = get_all_student_work_media();
$e = $editing ?: ['id' => 0, 'title' => '', 'title_en' => '', 'description' => '', 'description_en' => '', 'student_name' => '', 'sort_order' => 0];
$photos = $editing ? ($media['photos'][$editing['id']] ?? []) : [];
$links = $editing ? ($media['links'][$editing['id']] ?? []) : [];
$room = STUDENT_WORK_MAX_PHOTOS - count($photos);
?>
<p><a href="/admin/student-work-batches.php">&larr; กลับไปหน้าปีการศึกษา</a></p>

<div class="admin-card" id="workForm">
    <h3 style="margin-top:0;"><?= $editing ? 'แก้ไขผลงาน: ' . h($editing['title']) : 'เพิ่มผลงาน' ?></h3>
    <form method="post" enctype="multipart/form-data" data-photo-upload>
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="<?= $editing ? 'edit' : 'add' ?>">
        <input type="hidden" name="id" value="<?= (int) $e['id'] ?>">
        <div class="form-group"><label>ชื่อผลงาน</label><input type="text" name="title" required maxlength="255" value="<?= h($e['title']) ?>"></div>
        <div class="form-group"><label>ชื่อผลงาน (English)</label><input type="text" name="title_en" maxlength="255" value="<?= h($e['title_en']) ?>"></div>
        <div class="form-group"><label>รายละเอียด</label><textarea name="description"><?= h($e['description']) ?></textarea></div>
        <div class="form-group"><label>รายละเอียด (English)</label><textarea name="description_en"><?= h($e['description_en']) ?></textarea></div>
        <div class="form-group"><label>ชื่อนักศึกษาเจ้าของผลงาน (ไม่บังคับ)</label><input type="text" name="student_name" maxlength="150" value="<?= h($e['student_name']) ?>"></div>

        <div class="form-group">
            <label>รูปผลงาน (สูงสุด <?= STUDENT_WORK_MAX_PHOTOS ?> รูปต่อผลงาน)</label>
            <?php if ($photos): ?>
            <div class="work-photo-grid">
                <?php foreach ($photos as $i => $p): ?>
                <div class="work-photo-item">
                    <img src="<?= h($p['image_path']) ?>" alt="">
                    <label><input type="radio" name="cover_photo" value="<?= (int) $p['id'] ?>" <?= $i === 0 ? 'checked' : '' ?>> รูปปก</label>
                    <label class="work-photo-remove"><input type="checkbox" name="remove_photos[]" value="<?= (int) $p['id'] ?>"> ลบรูปนี้</label>
                </div>
                <?php endforeach; ?>
            </div>
            <p class="field-hint">มี <?= count($photos) ?> รูป · ติ๊ก "ลบรูปนี้" แล้วกดบันทึกเพื่อลบ · เลือก "รูปปก" เพื่อใช้เป็นรูปหน้าปกบนเว็บ</p>
            <?php endif; ?>
            <?php if ($room > 0): ?>
            <input type="file" name="images[]" accept="image/*" multiple data-resize data-max="<?= $room ?>" <?= $editing ? '' : 'required' ?>>
            <p class="field-hint"><?= $editing ? 'เพิ่มรูปได้อีก ' . $room . ' รูป' : 'เลือกได้หลายรูปพร้อมกัน (กด ⌘/Ctrl ค้างไว้แล้วคลิก) รูปแรกจะเป็นรูปปก' ?> · รูปใหญ่จะถูกย่อให้อัตโนมัติก่อนอัปโหลด</p>
            <?php else: ?>
            <p class="field-hint">ครบ <?= STUDENT_WORK_MAX_PHOTOS ?> รูปแล้ว ลบรูปเดิมก่อนถ้าต้องการเพิ่มรูปใหม่</p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>ลิงก์ผลงาน (ไม่บังคับ สูงสุด <?= STUDENT_WORK_MAX_LINKS ?> ลิงก์)</label>
            <div class="work-links" id="workLinks" data-max="<?= STUDENT_WORK_MAX_LINKS ?>">
                <?php foreach ($links ?: [['label' => '', 'url' => '']] as $l): ?>
                <div class="work-link-row">
                    <input type="text" name="link_label[]" maxlength="150" placeholder="ชื่อลิงก์ เช่น YouTube, Behance" value="<?= h($l['label']) ?>">
                    <input type="text" name="link_url[]" maxlength="255" placeholder="https://..." value="<?= h($l['url']) ?>">
                    <button type="button" class="btn btn-sm btn-ghost work-link-remove" title="เอาลิงก์นี้ออก">✕</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-sm btn-ghost" id="workLinkAdd">+ เพิ่มลิงก์</button>
            <p class="field-hint">เช่น วิดีโอ, Google Drive, พอร์ตโฟลิโอ ไม่ต้องมี https:// นำหน้าก็ได้ · ลิงก์ที่เอาออกจะหายไปเมื่อกดบันทึก</p>
        </div>

        <div class="form-group"><label>ลำดับการแสดงผล</label><input type="number" name="sort_order" style="max-width:120px;" value="<?= (int) $e['sort_order'] ?>"></div>
        <div class="form-actions">
            <button class="btn" type="submit"><?= $editing ? 'บันทึกการแก้ไข' : 'เพิ่มผลงาน' ?></button>
            <?php if ($editing): ?><a class="btn btn-ghost" href="<?= h($listUrl) ?>">ยกเลิก / เพิ่มผลงานใหม่</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="admin-card">
    <h3 style="margin-top:0;">ผลงานในปีการศึกษานี้ (<?= count($works) ?>)</h3>
    <?php if ($works): ?>
    <div class="portfolio-thumb-grid">
        <?php foreach ($works as $w): ?>
        <div class="portfolio-thumb-item<?= $editing && (int) $editing['id'] === (int) $w['id'] ? ' is-editing' : '' ?>">
            <img src="<?= h($w['image_path']) ?>" alt="<?= h($w['title']) ?>">
            <strong><?= h($w['title']) ?></strong>
            <span class="field-hint">📷 <?= count($media['photos'][$w['id']] ?? []) ?> รูป · 🔗 <?= count($media['links'][$w['id']] ?? []) ?> ลิงก์<?= $w['student_name'] ? ' · ' . h($w['student_name']) : '' ?></span>
            <div class="actions-row" style="margin-top:6px;">
                <a class="btn btn-sm btn-ghost" href="<?= h($listUrl . '&id=' . (int) $w['id']) ?>#workForm">แก้ไข</a>
                <form method="post" data-confirm="ยืนยันการลบผลงาน &quot;<?= h($w['title']) ?>&quot; และรูปทั้งหมด?">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $w['id'] ?>">
                    <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="field-hint" style="margin:0;">ยังไม่มีผลงานในปีการศึกษานี้</p>
    <?php endif; ?>
</div>
<script>
(function () {
    var box = document.getElementById('workLinks'), add = document.getElementById('workLinkAdd');
    var max = parseInt(box.getAttribute('data-max'), 10);
    function rows() { return box.querySelectorAll('.work-link-row'); }
    function refresh() { add.disabled = rows().length >= max; }
    add.addEventListener('click', function () {
        if (rows().length >= max) return;
        var row = rows()[0].cloneNode(true);
        row.querySelectorAll('input').forEach(function (i) { i.value = ''; });
        box.appendChild(row);
        row.querySelector('input').focus();
        refresh();
    });
    box.addEventListener('click', function (e) {
        var btn = e.target.closest('.work-link-remove');
        if (!btn) return;
        var row = btn.closest('.work-link-row');
        if (rows().length > 1) row.remove();
        else row.querySelectorAll('input').forEach(function (i) { i.value = ''; });
        refresh();
    });
    refresh();
})();
</script>
<script src="/admin/assets/image-resize.js?v=<?= (int) @filemtime(__DIR__ . '/assets/image-resize.js') ?>"></script>
<script src="/admin/assets/work-photo-upload.js?v=<?= (int) @filemtime(__DIR__ . '/assets/work-photo-upload.js') ?>"></script>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
