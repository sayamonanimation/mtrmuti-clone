<?php
require_once __DIR__ . '/includes/auth.php';

ensure_nav_new_tab_column();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM nav_menu_items WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('ลบเมนูเรียบร้อยแล้ว (เมนูย่อยภายใต้เมนูนี้ถูกลบไปด้วย)');
    } elseif ($action === 'toggle') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('UPDATE nav_menu_items SET is_active = 1 - is_active WHERE id = ?');
        $stmt->execute([$id]);
        set_flash('อัพเดทสถานะเรียบร้อยแล้ว');
    } elseif ($action === 'add') {
        $parentId = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
        $label = trim($_POST['label'] ?? '');
        $labelEn = trim($_POST['label_en'] ?? '');
        $url = normalize_nav_url($_POST['url'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        if ($label !== '') {
            $stmt = $pdo->prepare('INSERT INTO nav_menu_items (parent_id, label, label_en, url, sort_order, new_tab) VALUES (?, ?, ?, ?, ?, ?)');
            // Links to other websites open in a new tab unless switched off later.
            $newTab = isset($_POST['new_tab']) || preg_match('#^https?://#i', $url);
            $stmt->execute([$parentId, $label, $labelEn, $url, $sortOrder, $newTab ? 1 : 0]);
            set_flash('เพิ่มเมนูเรียบร้อยแล้ว');
        } else {
            set_flash('กรุณากรอกชื่อเมนู', 'error');
        }
    } elseif ($action === 'edit') {
        $id = (int) $_POST['id'];
        $label = trim($_POST['label'] ?? '');
        $labelEn = trim($_POST['label_en'] ?? '');
        $url = normalize_nav_url($_POST['url'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $stmt = $pdo->prepare('UPDATE nav_menu_items SET label = ?, label_en = ?, url = ?, sort_order = ?, new_tab = ? WHERE id = ?');
        $stmt->execute([$label, $labelEn, $url, $sortOrder, isset($_POST['new_tab']) ? 1 : 0, $id]);
        set_flash('บันทึกเมนูเรียบร้อยแล้ว');
    }
    header('Location: /admin/nav-menu.php');
    exit;
}

$adminPageTitle = 'จัดการเมนูเว็บไซต์';
require_once __DIR__ . '/includes/layout-top.php';

$allItems = get_all_nav_menu_items();
$topLevel = array_values(array_filter($allItems, fn($i) => $i['parent_id'] === null));
$childrenByParent = [];
foreach ($allItems as $i) {
    if ($i['parent_id'] !== null) {
        $childrenByParent[$i['parent_id']][] = $i;
    }
}
?>
<p class="field-hint">จัดการเมนูหลักและเมนูย่อยของเว็บไซต์ทั้งหมด ใส่ URL เป็น <code>#</code> ถ้าต้องการให้เมนูนี้เป็นแค่ตัวเปิดเมนูย่อย (ไม่ต้องมีลิงก์ของตัวเอง) เว้นว่าง URL ไว้ได้เช่นกัน</p>

<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มเมนูหลัก</h3>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-group"><label>ชื่อเมนู</label><input type="text" name="label" required></div>
        <div class="form-group"><label>ชื่อเมนู (English)</label><input type="text" name="label_en"></div>
        <div class="form-group"><label>URL</label><input type="text" name="url" placeholder="/example.php หรือ https://... หรือ # (ถ้าเป็นแค่เมนูเปิดเมนูย่อย)"></div>
        <div class="form-group"><label>ลำดับการแสดงผล</label><input type="number" name="sort_order" value="0"></div>
        <div class="form-group"><label><input type="checkbox" name="new_tab" value="1"> เปิดลิงก์ในแท็บใหม่</label></div>
        <button class="btn" type="submit">เพิ่มเมนูหลัก</button>
    </form>
</div>

<?php foreach ($topLevel as $top): ?>
<div class="admin-card" id="nav-item-<?= (int) $top['id'] ?>">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
        <form method="post" class="inline-edit-form inline-edit-form-row">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= $top['id'] ?>">
            <label class="field-hint">ชื่อเมนู</label>
            <input type="text" name="label" value="<?= h($top['label']) ?>" style="width:160px;">
            <label class="field-hint">English</label>
            <input type="text" name="label_en" value="<?= h($top['label_en']) ?>" style="width:160px;">
            <label class="field-hint">URL</label>
            <input type="text" name="url" value="<?= h($top['url']) ?>" style="width:200px;">
            <label class="field-hint">ลำดับ</label>
            <input type="number" name="sort_order" value="<?= h($top['sort_order']) ?>" style="width:70px;">
            <label class="field-hint nav-newtab"><input type="checkbox" name="new_tab" value="1" <?= !empty($top['new_tab']) ? 'checked' : '' ?>> เปิดแท็บใหม่</label>
            <button class="btn btn-sm btn-ghost" type="submit">บันทึก</button>
        </form>
        <div class="actions-row">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= $top['id'] ?>">
                <button class="btn btn-sm btn-ghost" type="submit"><?= $top['is_active'] ? 'ซ่อน' : 'แสดง' ?></button>
            </form>
            <form method="post" onsubmit="return confirm('ยืนยันการลบเมนูนี้? เมนูย่อยภายใต้เมนูนี้จะถูกลบไปด้วย');">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $top['id'] ?>">
                <button class="btn btn-sm btn-danger" type="submit">ลบเมนูนี้</button>
            </form>
        </div>
    </div>
    <p class="field-hint" style="margin:6px 0 14px;">สถานะ: <?= $top['is_active'] ? 'แสดงอยู่' : 'ซ่อนอยู่' ?></p>

    <table class="admin-table">
        <thead><tr><th>เมนูย่อย</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($childrenByParent[$top['id']] ?? [] as $child): ?>
            <tr id="nav-item-<?= (int) $child['id'] ?>">
                <td>
                    <form method="post" class="inline-edit-form inline-edit-form-row">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $child['id'] ?>">
                        <input type="text" name="label" value="<?= h($child['label']) ?>" placeholder="ชื่อเมนูย่อย" style="width:150px;">
                        <input type="text" name="label_en" value="<?= h($child['label_en']) ?>" placeholder="English" style="width:150px;">
                        <input type="text" name="url" value="<?= h($child['url']) ?>" placeholder="URL" style="width:200px;">
                        <input type="number" name="sort_order" value="<?= h($child['sort_order']) ?>" style="width:70px;" title="ลำดับ">
                        <label class="field-hint nav-newtab"><input type="checkbox" name="new_tab" value="1" <?= !empty($child['new_tab']) ? 'checked' : '' ?>> เปิดแท็บใหม่ ↗</label>
                        <button class="btn btn-sm btn-ghost" type="submit">บันทึก</button>
                    </form>
                </td>
                <td class="actions-row">
                    <span class="field-hint"><?= $child['is_active'] ? 'แสดงอยู่' : 'ซ่อนอยู่' ?></span>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $child['id'] ?>">
                        <button class="btn btn-sm btn-ghost" type="submit"><?= $child['is_active'] ? 'ซ่อน' : 'แสดง' ?></button>
                    </form>
                    <form method="post" onsubmit="return confirm('ยืนยันการลบ?');">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $child['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($childrenByParent[$top['id']])): ?>
            <tr><td colspan="2">ยังไม่มีเมนูย่อย</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <form method="post" class="inline-edit-form-row" style="margin-top:10px;">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="parent_id" value="<?= $top['id'] ?>">
        <input type="text" name="label" placeholder="ชื่อเมนูย่อยใหม่" required style="width:150px;">
        <input type="text" name="label_en" placeholder="English" style="width:150px;">
        <input type="text" name="url" placeholder="URL" style="width:200px;">
        <input type="number" name="sort_order" value="0" style="width:70px;" title="ลำดับ">
        <label class="field-hint nav-newtab"><input type="checkbox" name="new_tab" value="1"> เปิดแท็บใหม่</label>
        <button class="btn btn-sm" type="submit">+ เพิ่มเมนูย่อย</button>
    </form>
</div>
<?php endforeach; ?>

<?php if (!$topLevel): ?>
<div class="admin-card">ยังไม่มีเมนูในระบบ</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
