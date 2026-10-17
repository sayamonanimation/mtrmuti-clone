<?php
require_once __DIR__ . '/includes/auth.php';

const ADMIN_MIN_PASSWORD_LENGTH = 8;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $myId = (int) $_SESSION['admin_id'];

    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            set_flash('ชื่อผู้ใช้ต้องยาว 3-50 ตัว ใช้ได้เฉพาะ a-z, 0-9, จุด, ขีดกลาง, ขีดล่าง', 'error');
        } elseif (strlen($password) < ADMIN_MIN_PASSWORD_LENGTH) {
            set_flash('รหัสผ่านต้องยาวอย่างน้อย ' . ADMIN_MIN_PASSWORD_LENGTH . ' ตัวอักษร', 'error');
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM admin_users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                set_flash('มีชื่อผู้ใช้ "' . $username . '" อยู่แล้ว', 'error');
            } else {
                $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
                set_flash('เพิ่มผู้ดูแลระบบ "' . $username . '" เรียบร้อยแล้ว');
            }
        }
    } elseif ($action === 'reset_password') {
        $id = (int) $_POST['id'];
        $password = $_POST['password'] ?? '';
        if (strlen($password) < ADMIN_MIN_PASSWORD_LENGTH) {
            set_flash('รหัสผ่านต้องยาวอย่างน้อย ' . ADMIN_MIN_PASSWORD_LENGTH . ' ตัวอักษร', 'error');
        } else {
            $stmt = $pdo->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?');
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            set_flash('ตั้งรหัสผ่านใหม่เรียบร้อยแล้ว');
        }
    } elseif ($action === 'change_own_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $stmt = $pdo->prepare('SELECT password_hash FROM admin_users WHERE id = ?');
        $stmt->execute([$myId]);
        if (!password_verify($current, (string) $stmt->fetchColumn())) {
            set_flash('รหัสผ่านปัจจุบันไม่ถูกต้อง', 'error');
        } elseif (strlen($new) < ADMIN_MIN_PASSWORD_LENGTH) {
            set_flash('รหัสผ่านใหม่ต้องยาวอย่างน้อย ' . ADMIN_MIN_PASSWORD_LENGTH . ' ตัวอักษร', 'error');
        } else {
            $stmt = $pdo->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?');
            $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $myId]);
            set_flash('เปลี่ยนรหัสผ่านของคุณเรียบร้อยแล้ว');
        }
    } elseif ($action === 'delete') {
        $id = (int) $_POST['id'];
        $total = (int) $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
        if ($id === $myId) {
            set_flash('ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่ได้', 'error');
        } elseif ($total <= 1) {
            set_flash('ต้องมีผู้ดูแลระบบอย่างน้อย 1 บัญชี', 'error');
        } else {
            $stmt = $pdo->prepare('DELETE FROM admin_users WHERE id = ?');
            $stmt->execute([$id]);
            set_flash('ลบผู้ดูแลระบบเรียบร้อยแล้ว');
        }
    }
    header('Location: /admin/admins.php');
    exit;
}

$adminPageTitle = 'จัดการผู้ดูแลระบบ';
require_once __DIR__ . '/includes/layout-top.php';

$admins = $pdo->query('SELECT id, username, created_at FROM admin_users ORDER BY username ASC')->fetchAll();
?>
<div class="admin-card">
    <h3 style="margin-top:0;">เพิ่มผู้ดูแลระบบ</h3>
    <p class="field-hint">ทุกบัญชีเข้าใช้งานหลังบ้านพร้อมกันได้ไม่จำกัดจำนวน และมีสิทธิ์เท่ากันทุกบัญชี</p>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <div class="form-group"><label>ชื่อผู้ใช้ (a-z, 0-9, . _ -)</label><input type="text" name="username" required pattern="[A-Za-z0-9._\-]{3,50}" autocomplete="off"></div>
        <div class="form-group"><label>รหัสผ่าน (อย่างน้อย <?= ADMIN_MIN_PASSWORD_LENGTH ?> ตัว)</label><input type="password" name="password" required minlength="<?= ADMIN_MIN_PASSWORD_LENGTH ?>" autocomplete="new-password"></div>
        <button class="btn" type="submit">เพิ่ม</button>
    </form>
</div>

<div class="admin-card">
    <h3 style="margin-top:0;">ผู้ดูแลระบบทั้งหมด (<?= count($admins) ?> บัญชี)</h3>
    <table class="admin-table">
        <thead><tr><th>ชื่อผู้ใช้</th><th>สร้างเมื่อ</th><th>ตั้งรหัสผ่านใหม่</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($admins as $a): ?>
            <?php $isMe = (int) $a['id'] === (int) $_SESSION['admin_id']; ?>
            <tr>
                <td><strong><?= h($a['username']) ?></strong><?= $isMe ? ' <span class="field-hint">(คุณ)</span>' : '' ?></td>
                <td><?= h($a['created_at']) ?></td>
                <td>
                    <?php if (!$isMe): ?>
                    <form method="post" class="inline-edit-form inline-edit-form-row">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                        <input type="password" name="password" placeholder="รหัสผ่านใหม่" required minlength="<?= ADMIN_MIN_PASSWORD_LENGTH ?>" autocomplete="new-password" style="width:160px;">
                        <button class="btn btn-sm btn-ghost" type="submit">บันทึก</button>
                    </form>
                    <?php else: ?>
                    <span class="field-hint">ใช้ฟอร์มด้านล่าง</span>
                    <?php endif; ?>
                </td>
                <td class="actions-row">
                    <?php if (!$isMe): ?>
                    <form method="post" onsubmit="return confirm('ยืนยันการลบผู้ดูแลระบบ <?= h($a['username']) ?>?');">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit">ลบ</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="admin-card">
    <h3 style="margin-top:0;">เปลี่ยนรหัสผ่านของฉัน (<?= h($_SESSION['admin_username']) ?>)</h3>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="change_own_password">
        <div class="form-group"><label>รหัสผ่านปัจจุบัน</label><input type="password" name="current_password" required autocomplete="current-password"></div>
        <div class="form-group"><label>รหัสผ่านใหม่ (อย่างน้อย <?= ADMIN_MIN_PASSWORD_LENGTH ?> ตัว)</label><input type="password" name="new_password" required minlength="<?= ADMIN_MIN_PASSWORD_LENGTH ?>" autocomplete="new-password"></div>
        <button class="btn" type="submit">เปลี่ยนรหัสผ่าน</button>
    </form>
</div>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
