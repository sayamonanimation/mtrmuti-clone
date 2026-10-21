<?php
require_once __DIR__ . '/includes/auth.php';

ensure_alumni_hub();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $filter = $_POST['filter'] ?? 'pending';
    if ($id && in_array($action, ['approve', 'reject', 'hide', 'delete', 'reset_password'], true)) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE alumni_members SET status = 'approved', admin_note = NULL, approved_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$id]);
            set_flash('อนุมัติแล้ว — โปรไฟล์แสดงบนหน้าเว็บ');
        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE alumni_members SET status = 'rejected', admin_note = ? WHERE id = ?")->execute([mb_substr(trim($_POST['note'] ?? ''), 0, 255) ?: null, $id]);
            set_flash('ตีกลับแล้ว ศิษย์เก่าจะเห็นเหตุผลในหน้าโปรไฟล์ของตัวเอง');
        } elseif ($action === 'hide') {
            $pdo->prepare("UPDATE alumni_members SET status = 'hidden' WHERE id = ?")->execute([$id]);
            set_flash('ซ่อนโปรไฟล์แล้ว');
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM alumni_works WHERE member_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM alumni_members WHERE id = ?')->execute([$id]);
            set_flash('ลบบัญชีและผลงานแล้ว');
        } elseif ($action === 'reset_password') {
            $new = $_POST['new_password'] ?? '';
            if (strlen($new) < 8) {
                set_flash('รหัสผ่านใหม่ต้องยาวอย่างน้อย 8 ตัว', 'error');
            } else {
                $pdo->prepare('UPDATE alumni_members SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $id]);
                set_flash('ตั้งรหัสผ่านใหม่ให้ศิษย์เก่าแล้ว');
            }
        }
    }
    header('Location: /admin/alumni-hub.php?filter=' . urlencode($filter));
    exit;
}

$filter = $_GET['filter'] ?? 'pending';
$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'hidden' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) c FROM alumni_members GROUP BY status') as $row) {
    $counts[$row['status']] = (int) $row['c'];
}
$stmt = $pdo->prepare('SELECT m.*, (SELECT COUNT(*) FROM alumni_works w WHERE w.member_id = m.id) AS work_count
    FROM alumni_members m WHERE m.status = ? ORDER BY COALESCE(m.updated_at, m.created_at) DESC');
$stmt->execute([$filter]);
$members = $stmt->fetchAll();

$adminPageTitle = 'ติดต่องานศิษย์เก่า (ตรวจสอบโปรไฟล์)';
require_once __DIR__ . '/includes/layout-top.php';
?>
<div class="admin-card">
    <p style="margin:0;">ศิษย์เก่าสมัครและกรอกข้อมูลเองที่ <a href="<?= h(clean_public_url('/alumni-register')) ?>" target="_blank">หน้าสมัครฝากประวัติ</a> — โปรไฟล์จะแสดงบน <a href="<?= h(clean_public_url('/alumni-hub')) ?>" target="_blank">หน้าติดต่องานศิษย์เก่า</a> หลังจากกด <strong>อนุมัติ</strong> ที่นี่</p>
</div>
<div class="alumni-hub-tabs">
    <?php foreach (['pending' => '⏳ รอตรวจสอบ', 'approved' => '✅ เผยแพร่แล้ว', 'rejected' => '↩️ ตีกลับ', 'hidden' => '🙈 ซ่อนอยู่'] as $key => $label): ?>
    <a class="btn btn-sm <?= $filter === $key ? '' : 'btn-ghost' ?>" href="/admin/alumni-hub.php?filter=<?= $key ?>"><?= $label ?> (<?= $counts[$key] ?>)</a>
    <?php endforeach; ?>
</div>

<?php foreach ($members as $m): ?>
<div class="admin-card hub-admin-card">
    <img src="<?= h($m['photo_path'] ?: '/assets/img/placeholder-avatar.svg') ?>" alt="">
    <div class="hub-admin-info">
        <strong><?= h($m['full_name']) ?></strong> <span class="field-hint"><?= h($m['email']) ?></span><br>
        <span><?= h(trim($m['batch_name'] . ($m['graduation_year'] ? ' · จบปี ' . $m['graduation_year'] : ''), ' ·')) ?></span>
        <?php if ($m['headline']): ?> · <span><?= h($m['headline']) ?></span><?php endif; ?><br>
        <span class="field-hint">ผลงาน <?= (int) $m['work_count'] ?> ชิ้น · สมัคร <?= h($m['created_at']) ?><?= $m['updated_at'] ? ' · แก้ไขล่าสุด ' . h($m['updated_at']) : '' ?></span>
        <?php if ($m['admin_note']): ?><br><span class="field-hint">เหตุผลที่ตีกลับ: <?= h($m['admin_note']) ?></span><?php endif; ?>
    </div>
    <div class="hub-admin-actions">
        <a class="btn btn-sm btn-ghost" href="<?= h(clean_public_url('/alumni-profile?id=' . (int) $m['id'])) ?>" target="_blank">ดูโปรไฟล์</a>
        <?php if ($m['status'] !== 'approved'): ?>
        <form method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="filter" value="<?= h($filter) ?>"><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><button class="btn btn-sm" type="submit">✅ อนุมัติ</button></form>
        <?php else: ?>
        <form method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="filter" value="<?= h($filter) ?>"><input type="hidden" name="action" value="hide"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><button class="btn btn-sm btn-ghost" type="submit">🙈 ซ่อน</button></form>
        <?php endif; ?>
        <?php if ($m['status'] === 'pending'): ?>
        <form method="post" class="hub-reject"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="filter" value="<?= h($filter) ?>"><input type="hidden" name="action" value="reject"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="text" name="note" placeholder="เหตุผล (ไม่บังคับ)" maxlength="255"><button class="btn btn-sm btn-ghost" type="submit">↩️ ตีกลับ</button></form>
        <?php endif; ?>
        <details class="hub-admin-more">
            <summary>เพิ่มเติม</summary>
            <form method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="filter" value="<?= h($filter) ?>"><input type="hidden" name="action" value="reset_password"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="password" name="new_password" placeholder="รหัสผ่านใหม่" minlength="8" required><button class="btn btn-sm btn-ghost" type="submit">ตั้งรหัสผ่านใหม่</button></form>
            <form method="post" onsubmit="return confirm('ลบบัญชี <?= h($m['full_name']) ?> และผลงานทั้งหมด?');"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="filter" value="<?= h($filter) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><button class="btn btn-sm btn-danger" type="submit">ลบบัญชี</button></form>
        </details>
    </div>
</div>
<?php endforeach; ?>
<?php if (!$members): ?>
<div class="admin-card"><p style="margin:0;">ไม่มีรายการในหมวดนี้</p></div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
