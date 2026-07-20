<?php
require_once __DIR__ . '/includes/auth.php';

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
<div class="admin-card" style="display:flex; justify-content:space-between; align-items:center;">
    <p style="margin:0;">รายชื่ออาจารย์ทั้งหมด (<?= count($teachers) ?> คน)</p>
    <a class="btn" href="/admin/teacher-form.php">+ เพิ่มอาจารย์</a>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr><th></th><th>ชื่อ</th><th>ตำแหน่ง</th><th>ลำดับ</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($teachers as $t): ?>
            <tr>
                <td><img class="thumb" src="<?= h($t['photo_path'] ?: '/assets/img/placeholder-avatar.svg') ?>" alt=""></td>
                <td><?= h($t['name']) ?></td>
                <td><?= h($t['position']) ?></td>
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
