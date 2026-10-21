<?php
require_once __DIR__ . '/includes/auth.php';

ensure_teacher_show_phone_column();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['phones_hide_all', 'phones_show_all', 'phone_toggle'], true)) {
    csrf_check();
    $action = $_POST['action'];
    if ($action === 'phone_toggle') {
        $stmt = $pdo->prepare('UPDATE teachers SET show_phone = 1 - show_phone WHERE id = ?');
        $stmt->execute([(int) $_POST['id']]);
        set_flash('อัปเดตการแสดงเบอร์โทรแล้ว');
    } else {
        $pdo->exec('UPDATE teachers SET show_phone = ' . ($action === 'phones_show_all' ? 1 : 0));
        set_flash($action === 'phones_show_all' ? 'แสดงเบอร์โทรอาจารย์ทุกท่านแล้ว' : 'ซ่อนเบอร์โทรอาจารย์ทุกท่านแล้ว');
    }
    header('Location: /admin/teachers.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $id = (int) $_POST['id'];
    $stmt = $pdo->prepare('DELETE FROM teachers WHERE id = ?');
    $stmt->execute([$id]);
    set_flash('ลบข้อมูลอาจารย์เรียบร้อยแล้ว');
    header('Location: /admin/teachers.php');
    exit;
}

$adminPageTitle = 'จัดการข้อมูลอาจารย์';
require_once __DIR__ . '/includes/layout-top.php';

$teachers = $pdo->query('SELECT * FROM teachers ORDER BY sort_order ASC, name ASC')->fetchAll();
?>
<?php $shownPhones = count(array_filter($teachers, fn($t) => (int) $t['show_phone'] === 1)); ?>
<div class="admin-card" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
    <p style="margin:0;">รายชื่ออาจารย์ทั้งหมด (<?= count($teachers) ?> คน) · เบอร์โทรที่แสดงบนเว็บ <strong><?= $shownPhones ?></strong> คน</p>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <form method="post" onsubmit="return confirm('ซ่อนเบอร์โทรของอาจารย์ทุกท่านบนหน้าเว็บ?');">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="phones_hide_all">
            <button class="btn btn-ghost" type="submit">🔒 ซ่อนเบอร์โทรทั้งหมด</button>
        </form>
        <form method="post" onsubmit="return confirm('แสดงเบอร์โทรของอาจารย์ทุกท่านบนหน้าเว็บ?');">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="phones_show_all">
            <button class="btn btn-ghost" type="submit">📞 แสดงทั้งหมด</button>
        </form>
        <a class="btn" href="/admin/teacher-form.php">+ เพิ่มอาจารย์</a>
    </div>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr><th></th><th>ชื่อ</th><th>ตำแหน่ง</th><th>เบอร์โทรบนเว็บ</th><th>ลำดับ</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($teachers as $t): ?>
            <tr>
                <td><img class="thumb" src="<?= h($t['photo_path'] ?: '/assets/img/placeholder-avatar.svg') ?>" alt=""></td>
                <td><?= h($t['name']) ?></td>
                <td><?= h($t['position']) ?></td>
                <td>
                    <?php if ($t['phone']): ?>
                    <form method="post" class="phone-toggle-form">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="phone_toggle">
                        <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                        <button type="submit" class="phone-pill<?= (int) $t['show_phone'] === 1 ? ' is-on' : '' ?>" title="คลิกเพื่อสลับ">
                            <?= (int) $t['show_phone'] === 1 ? '📞 แสดง' : '🔒 ซ่อน' ?>
                        </button>
                    </form>
                    <?php else: ?><span class="field-hint">ไม่มีเบอร์</span><?php endif; ?>
                </td>
                <td><?= h($t['sort_order']) ?></td>
                <td class="actions-row">
                    <a class="btn btn-sm btn-ghost" href="/teacher-profile.php?id=<?= $t['id'] ?>" target="_blank">ดูหน้าเว็บ</a>
                    <a class="btn btn-sm btn-ghost" href="/admin/teacher-form.php?id=<?= $t['id'] ?>">แก้ไข</a>
                    <a class="btn btn-sm btn-ghost" href="/admin/teacher-portfolio.php?teacher_id=<?= $t['id'] ?>">ผลงาน</a>
                    <form method="post" onsubmit="return confirm('ยืนยันการลบ?');">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$teachers): ?>
            <tr><td colspan="5">ยังไม่มีข้อมูลอาจารย์</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
