<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/service-links.php';

ensure_student_service_sections();
$self = '/admin/student-service-links.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $anchor = '';

    if ($action === 'add_section' || $action === 'edit_section') {
        $title = mb_substr(trim($_POST['title'] ?? ''), 0, 255);
        $titleEn = mb_substr(trim($_POST['title_en'] ?? ''), 0, 255);
        $sort = (int) ($_POST['sort_order'] ?? 0);
        if ($title === '') {
            set_flash('กรุณากรอกชื่อหัวข้อ', 'error');
        } elseif ($action === 'add_section') {
            $pdo->prepare('INSERT INTO student_service_sections (title, title_en, sort_order) VALUES (?, ?, ?)')->execute([$title, $titleEn, $sort]);
            $anchor = '#section-' . $pdo->lastInsertId();
            set_flash('เพิ่มหัวข้อแล้ว — เพิ่มลิงก์ในหัวข้อนี้ได้เลย');
        } else {
            $pdo->prepare('UPDATE student_service_sections SET title = ?, title_en = ?, sort_order = ? WHERE id = ?')->execute([$title, $titleEn, $sort, $id]);
            $anchor = '#section-' . $id;
            set_flash('บันทึกหัวข้อแล้ว');
        }
    } elseif ($action === 'toggle_section') {
        $pdo->prepare('UPDATE student_service_sections SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
        $anchor = '#section-' . $id;
        set_flash('อัปเดตการแสดงผลแล้ว');
    } elseif ($action === 'delete_section') {
        $pdo->prepare('DELETE FROM student_service_items WHERE section_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM student_service_sections WHERE id = ?')->execute([$id]);
        set_flash('ลบหัวข้อและลิงก์ในหัวข้อแล้ว');
    } elseif ($action === 'add_link' || $action === 'edit_link') {
        $sectionId = (int) ($_POST['section_id'] ?? 0);
        $fields = [
            mb_substr(trim($_POST['label'] ?? ''), 0, 255),
            mb_substr(trim($_POST['label_en'] ?? ''), 0, 255),
            mb_substr(normalize_url($_POST['url'] ?? ''), 0, 500),
            mb_substr(trim($_POST['description'] ?? ''), 0, 500),
            (int) ($_POST['sort_order'] ?? 0),
        ];
        $anchor = '#section-' . $sectionId;
        if ($fields[0] === '' || $fields[2] === '') {
            set_flash('กรุณากรอกชื่อลิงก์และ URL', 'error');
        } elseif ($action === 'add_link') {
            $pdo->prepare('INSERT INTO student_service_items (section_id, label, label_en, url, description, sort_order) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute(array_merge([$sectionId], $fields));
            set_flash('เพิ่มลิงก์แล้ว');
        } else {
            $pdo->prepare('UPDATE student_service_items SET label = ?, label_en = ?, url = ?, description = ?, sort_order = ? WHERE id = ? AND section_id = ?')
                ->execute(array_merge($fields, [$id, $sectionId]));
            set_flash('บันทึกลิงก์แล้ว');
        }
    } elseif ($action === 'delete_link') {
        $anchor = '#section-' . (int) ($_POST['section_id'] ?? 0);
        $pdo->prepare('DELETE FROM student_service_items WHERE id = ?')->execute([$id]);
        set_flash('ลบลิงก์แล้ว');
    }
    header('Location: ' . $self . $anchor);
    exit;
}

$adminPageTitle = 'ข้อมูลทั่วไป: หัวข้อและลิงก์';
require_once __DIR__ . '/includes/layout-top.php';

$sections = get_student_service_sections(false);
?>
<div class="admin-card">
    <p style="margin:0;">หัวข้อและลิงก์ด้านล่างแสดงบนหน้า <a href="<?= h(clean_public_url('/student-services')) ?>" target="_blank" rel="noopener">บริการนักศึกษา › ข้อมูลทั่วไป ↗</a>
        ต่อจากข้อความแนะนำ (<a href="/admin/page-edit.php?slug=student-services">แก้ไขข้อความแนะนำ</a>) · เพิ่มหัวข้อและลิงก์ได้ไม่จำกัด</p>
</div>

<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มหัวข้อ</h3>
    <form method="post" class="svc-row">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add_section">
        <input type="text" name="title" required maxlength="255" placeholder="ชื่อหัวข้อ เช่น งานทะเบียน, ทุนการศึกษา">
        <input type="text" name="title_en" maxlength="255" placeholder="English (ไม่บังคับ)">
        <input type="number" name="sort_order" value="<?= count($sections) ?>" title="ลำดับ (น้อยขึ้นก่อน)">
        <button class="btn" type="submit">+ เพิ่มหัวข้อ</button>
    </form>
</div>

<?php foreach ($sections as $s): ?>
<div class="admin-card svc-section<?= $s['is_active'] ? '' : ' is-hidden' ?>" id="section-<?= (int) $s['id'] ?>">
    <div class="svc-section-head">
        <form method="post" class="svc-row">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="edit_section">
            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
            <input type="text" name="title" required maxlength="255" value="<?= h($s['title']) ?>" class="svc-title" aria-label="ชื่อหัวข้อ">
            <input type="text" name="title_en" maxlength="255" value="<?= h($s['title_en']) ?>" placeholder="English" aria-label="ชื่อหัวข้อ English">
            <input type="number" name="sort_order" value="<?= (int) $s['sort_order'] ?>" title="ลำดับ">
            <button class="btn btn-sm btn-ghost" type="submit">บันทึกหัวข้อ</button>
        </form>
        <div class="actions-row">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="toggle_section">
                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                <button type="submit" class="phone-pill<?= $s['is_active'] ? ' is-on' : '' ?>"><?= $s['is_active'] ? '👁 แสดง' : '🙈 ซ่อน' ?></button>
            </form>
            <form method="post" data-confirm="ลบหัวข้อ &quot;<?= h($s['title']) ?>&quot; และลิงก์ทั้งหมด <?= count($s['items']) ?> ลิงก์?">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete_section">
                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                <button class="btn btn-sm btn-danger" type="submit">ลบหัวข้อ</button>
            </form>
        </div>
    </div>

    <div class="svc-links">
        <?php foreach ($s['items'] as $l): ?>
        <div class="svc-link">
            <form method="post" class="svc-link-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="edit_link">
                <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                <input type="hidden" name="section_id" value="<?= (int) $s['id'] ?>">
                <input type="text" name="label" required maxlength="255" value="<?= h($l['label']) ?>" placeholder="ชื่อลิงก์" aria-label="ชื่อลิงก์">
                <input type="text" name="url" required maxlength="500" value="<?= h($l['url']) ?>" placeholder="https://..." aria-label="URL">
                <input type="text" name="description" maxlength="500" value="<?= h($l['description']) ?>" placeholder="คำอธิบายสั้น ๆ (ไม่บังคับ)" aria-label="คำอธิบาย">
                <input type="text" name="label_en" maxlength="255" value="<?= h($l['label_en']) ?>" placeholder="ชื่อลิงก์ English" aria-label="ชื่อลิงก์ English">
                <input type="number" name="sort_order" value="<?= (int) $l['sort_order'] ?>" title="ลำดับ">
                <button class="btn btn-sm btn-ghost" type="submit">บันทึก</button>
            </form>
            <a class="btn btn-sm btn-ghost" href="<?= h($l['url']) ?>" target="_blank" rel="noopener" title="เปิดลิงก์">↗</a>
            <form method="post" data-confirm="ลบลิงก์ &quot;<?= h($l['label']) ?>&quot;?">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete_link">
                <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                <input type="hidden" name="section_id" value="<?= (int) $s['id'] ?>">
                <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
            </form>
        </div>
        <?php endforeach; ?>
        <?php if (!$s['items']): ?><p class="field-hint" style="margin:0;">ยังไม่มีลิงก์ในหัวข้อนี้</p><?php endif; ?>
    </div>

    <form method="post" class="svc-link-form svc-add">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add_link">
        <input type="hidden" name="section_id" value="<?= (int) $s['id'] ?>">
        <input type="text" name="label" required maxlength="255" placeholder="ชื่อลิงก์ใหม่">
        <input type="text" name="url" required maxlength="500" placeholder="https://...">
        <input type="text" name="description" maxlength="500" placeholder="คำอธิบายสั้น ๆ (ไม่บังคับ)">
        <input type="text" name="label_en" maxlength="255" placeholder="ชื่อลิงก์ English (ไม่บังคับ)">
        <input type="number" name="sort_order" value="<?= count($s['items']) ?>" title="ลำดับ">
        <button class="btn btn-sm" type="submit">+ เพิ่มลิงก์</button>
    </form>
</div>
<?php endforeach; ?>
<?php if (!$sections): ?>
<div class="admin-card"><p style="margin:0;">ยังไม่มีหัวข้อ เริ่มจากเพิ่มหัวข้อด้านบน</p></div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
