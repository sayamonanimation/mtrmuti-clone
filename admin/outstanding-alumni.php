<?php
require_once __DIR__ . '/includes/auth.php';

ensure_outstanding_alumni();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    reject_oversized_upload('/admin/outstanding-alumni.php');
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'save') {
        $fields = [
            'name' => trim($_POST['name'] ?? ''),
            'batch_name' => trim($_POST['batch_name'] ?? ''),
            'graduation_year' => (int) ($_POST['graduation_year'] ?? 0) ?: null,
            'position' => trim($_POST['position'] ?? ''),
            'workplace' => trim($_POST['workplace'] ?? ''),
            'achievement' => trim($_POST['achievement'] ?? ''),
            'quote' => trim($_POST['quote'] ?? ''),
            'link_url' => normalize_url($_POST['link_url'] ?? ''),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($fields['name'] === '') {
            set_flash('กรุณากรอกชื่อ', 'error');
            header('Location: /admin/outstanding-alumni.php' . ($id ? '?id=' . $id : ''));
            exit;
        }
        if ($photo = handle_image_upload('photo', 'alumni')) {
            $fields['photo_path'] = $photo;
        }
        if ($id) {
            $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($fields)));
            $stmt = $pdo->prepare("UPDATE outstanding_alumni SET $set WHERE id = ?");
            $stmt->execute([...array_values($fields), $id]);
            set_flash('บันทึกข้อมูลศิษย์เก่าดีเด่นแล้ว');
        } else {
            $cols = implode(', ', array_keys($fields));
            $marks = implode(', ', array_fill(0, count($fields), '?'));
            $stmt = $pdo->prepare("INSERT INTO outstanding_alumni ($cols) VALUES ($marks)");
            $stmt->execute(array_values($fields));
            set_flash('เพิ่มศิษย์เก่าดีเด่นแล้ว');
        }
    } elseif ($action === 'toggle' && $id) {
        $pdo->prepare('UPDATE outstanding_alumni SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
        set_flash('อัปเดตการแสดงผลแล้ว');
    } elseif ($action === 'delete' && $id) {
        $pdo->prepare('DELETE FROM outstanding_alumni WHERE id = ?')->execute([$id]);
        set_flash('ลบข้อมูลแล้ว');
    }
    header('Location: /admin/outstanding-alumni.php');
    exit;
}

$editing = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM outstanding_alumni WHERE id = ?');
    $stmt->execute([(int) $_GET['id']]);
    $editing = $stmt->fetch() ?: null;
}
$e = $editing ?: ['id' => 0, 'name' => '', 'photo_path' => '', 'batch_name' => '', 'graduation_year' => '', 'position' => '', 'workplace' => '', 'achievement' => '', 'quote' => '', 'link_url' => '', 'sort_order' => 0, 'is_active' => 1];

$adminPageTitle = 'ศิษย์เก่าดีเด่น';
require_once __DIR__ . '/includes/layout-top.php';

$people = get_outstanding_alumni(false);
?>
<div class="admin-card">
    <h3 style="margin-top:0;"><?= $editing ? 'แก้ไข: ' . h($editing['name']) : 'เพิ่มศิษย์เก่าดีเด่น' ?></h3>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $e['id'] ?>">
        <div class="form-group"><label>ชื่อ - นามสกุล</label><input type="text" name="name" required maxlength="150" value="<?= h($e['name']) ?>"></div>
        <div class="form-group">
            <label>รูปภาพ</label>
            <?php if ($e['photo_path']): ?><img class="current-thumb" src="<?= h($e['photo_path']) ?>" alt=""><?php endif; ?>
            <input type="file" name="photo" accept="image/*">
        </div>
        <div class="form-group"><label>รุ่น</label><input type="text" name="batch_name" maxlength="100" placeholder="เช่น MT05" value="<?= h($e['batch_name']) ?>"></div>
        <div class="form-group"><label>จบการศึกษาปีการศึกษา</label><input type="number" name="graduation_year" min="2500" max="2700" style="max-width:160px;" value="<?= h($e['graduation_year']) ?>"></div>
        <div class="form-group"><label>ตำแหน่ง / อาชีพปัจจุบัน</label><input type="text" name="position" maxlength="255" placeholder="เช่น Senior Game Developer" value="<?= h($e['position']) ?>"></div>
        <div class="form-group"><label>สถานที่ทำงาน / บริษัท</label><input type="text" name="workplace" maxlength="255" value="<?= h($e['workplace']) ?>"></div>
        <div class="form-group">
            <label>ผลงาน / รางวัล / ความสำเร็จ</label>
            <textarea name="achievement" rows="4" placeholder="บรรทัดละ 1 รายการ"><?= h($e['achievement']) ?></textarea>
            <p class="field-hint">ขึ้นบรรทัดใหม่ต่อ 1 รายการ ระบบจะแสดงเป็นข้อ ๆ ให้อัตโนมัติ</p>
        </div>
        <div class="form-group"><label>คำคม / ข้อความถึงรุ่นน้อง (ไม่บังคับ)</label><textarea name="quote" rows="2"><?= h($e['quote']) ?></textarea></div>
        <div class="form-group"><label>ลิงก์ผลงาน / โปรไฟล์ (ไม่บังคับ)</label><input type="text" name="link_url" maxlength="255" placeholder="https://..." value="<?= h($e['link_url']) ?>"></div>
        <div class="form-group"><label>ลำดับการแสดงผล (น้อยขึ้นก่อน)</label><input type="number" name="sort_order" style="max-width:120px;" value="<?= (int) $e['sort_order'] ?>"></div>
        <label class="toggle-switch">
            <input type="checkbox" name="is_active" value="1" <?= (int) $e['is_active'] === 1 ? 'checked' : '' ?>>
            <span class="toggle-switch-track" aria-hidden="true"></span>
            <span class="toggle-switch-label">แสดงบนหน้าเว็บไซต์</span>
        </label>
        <div class="form-actions" style="margin-top:16px;">
            <button class="btn" type="submit"><?= $editing ? 'บันทึก' : 'เพิ่ม' ?></button>
            <?php if ($editing): ?><a class="btn btn-ghost" href="/admin/outstanding-alumni.php">ยกเลิก</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="admin-card">
    <h3 style="margin-top:0;">รายชื่อศิษย์เก่าดีเด่น (<?= count($people) ?>)</h3>
    <table class="admin-table">
        <thead><tr><th></th><th>ชื่อ</th><th>รุ่น / ปีที่จบ</th><th>ตำแหน่ง</th><th>หน้าเว็บ</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($people as $p): ?>
            <tr>
                <td><img class="thumb" src="<?= h($p['photo_path'] ?: '/assets/img/placeholder-avatar.svg') ?>" alt=""></td>
                <td><?= h($p['name']) ?></td>
                <td><?= h(trim($p['batch_name'] . ($p['graduation_year'] ? ' / ' . $p['graduation_year'] : ''), ' /')) ?></td>
                <td><?= h($p['position']) ?><?= $p['workplace'] ? '<br><span class="field-hint">' . h($p['workplace']) . '</span>' : '' ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <button type="submit" class="phone-pill<?= (int) $p['is_active'] === 1 ? ' is-on' : '' ?>"><?= (int) $p['is_active'] === 1 ? '👁 แสดง' : '🙈 ซ่อน' ?></button>
                    </form>
                </td>
                <td class="actions-row">
                    <a class="btn btn-sm btn-ghost" href="/admin/outstanding-alumni.php?id=<?= (int) $p['id'] ?>">แก้ไข</a>
                    <form method="post" onsubmit="return confirm('ลบ <?= h($p['name']) ?>?');">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$people): ?><tr><td colspan="6">ยังไม่มีข้อมูล</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
