<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
ensure_alumni_hub();
require_alumni_login();

$me = current_alumni_member();
$self = clean_public_url('/alumni-dashboard');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    reject_oversized_upload($self);
    csrf_check();
    $action = $_POST['action'] ?? '';
    $flash = '';

    if ($action === 'profile') {
        $f = [
            'full_name' => trim($_POST['full_name'] ?? '') ?: $me['full_name'],
            'batch_name' => trim($_POST['batch_name'] ?? ''),
            'graduation_year' => (int) ($_POST['graduation_year'] ?? 0) ?: null,
            'headline' => mb_substr(trim($_POST['headline'] ?? ''), 0, 255),
            'skills' => mb_substr(trim($_POST['skills'] ?? ''), 0, 500),
            'bio' => mb_substr(trim($_POST['bio'] ?? ''), 0, 4000),
            'contact_email' => filter_var(trim($_POST['contact_email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: null,
            'contact_phone' => mb_substr(trim($_POST['contact_phone'] ?? ''), 0, 50),
            'line_id' => mb_substr(trim($_POST['line_id'] ?? ''), 0, 100),
            'website_url' => normalize_url($_POST['website_url'] ?? '') ?: null,
            'available_for_work' => isset($_POST['available_for_work']) ? 1 : 0,
        ];
        if ($photo = handle_image_upload('photo', 'alumni-hub')) {
            $f['photo_path'] = $photo;
        }
        $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($f)));
        $pdo->prepare("UPDATE alumni_members SET $set, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([...array_values($f), $me['id']]);
        $flash = 'บันทึกโปรไฟล์แล้ว';
    } elseif ($action === 'submit_review' && in_array($me['status'], ['pending', 'rejected'], true)) {
        $pdo->prepare("UPDATE alumni_members SET status = 'pending', admin_note = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$me['id']]);
        $_SESSION['alumni_submitted'] = 1;
        $flash = 'ส่งให้เจ้าหน้าที่ตรวจสอบแล้ว จะแสดงบนหน้าเว็บเมื่อได้รับการอนุมัติ';
    } elseif ($action === 'add_work') {
        $count = count(get_alumni_works($me['id']));
        $title = mb_substr(trim($_POST['title'] ?? ''), 0, 255);
        if ($count >= ALUMNI_HUB_MAX_WORKS) {
            $flash = 'เพิ่มผลงานได้สูงสุด ' . ALUMNI_HUB_MAX_WORKS . ' ชิ้น';
        } elseif ($title === '') {
            $flash = 'กรุณาใส่ชื่อผลงาน';
        } else {
            $image = handle_image_upload('image', 'alumni-hub');
            $pdo->prepare('INSERT INTO alumni_works (member_id, title, description, image_path, link_url, sort_order) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$me['id'], $title, mb_substr(trim($_POST['description'] ?? ''), 0, 2000), $image, normalize_url($_POST['link_url'] ?? '') ?: null, $count]);
            $pdo->prepare('UPDATE alumni_members SET updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$me['id']]);
            $flash = 'เพิ่มผลงานแล้ว';
        }
    } elseif ($action === 'delete_work') {
        $pdo->prepare('DELETE FROM alumni_works WHERE id = ? AND member_id = ?')->execute([(int) $_POST['work_id'], $me['id']]);
        $flash = 'ลบผลงานแล้ว';
    } elseif ($action === 'password') {
        $new = $_POST['new_password'] ?? '';
        if (!password_verify($_POST['current_password'] ?? '', $me['password_hash'])) {
            $flash = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
        } elseif (strlen($new) < 8) {
            $flash = 'รหัสผ่านใหม่ต้องยาวอย่างน้อย 8 ตัว';
        } else {
            alumni_set_password($me, $new);
            $flash = 'เปลี่ยนรหัสผ่านแล้ว';
        }
    }
    $_SESSION['alumni_flash'] = $flash;
    header('Location: ' . $self);
    exit;
}

$works = get_alumni_works($me['id']);
$flash = $_SESSION['alumni_flash'] ?? '';
unset($_SESSION['alumni_flash']);

$currentSlug = 'alumni-hub';
$pageTitleOverride = 'โปรไฟล์ศิษย์เก่าของฉัน';
require_once __DIR__ . '/includes/header.php';
$statusClass = ['approved' => 'ok', 'pending' => 'wait', 'rejected' => 'bad', 'hidden' => 'bad'][$me['status']] ?? 'wait';
?>
<div class="page-header"><div class="container"><h1>โปรไฟล์ของฉัน</h1><p><?= h($me['full_name']) ?></p></div></div>
<div class="page-content">
    <div class="container hub-dash">
        <?php if ($flash): ?><div class="hub-notice"><?= h($flash) ?></div><?php endif; ?>

        <div class="hub-status hub-status-<?= $statusClass ?>">
            <div>
                <strong>สถานะ: <?= h(alumni_status_label($me['status'])) ?></strong>
                <?php if ($me['status'] === 'approved'): ?><span>โปรไฟล์ของคุณแสดงบนหน้า "ติดต่องานศิษย์เก่า" แล้ว แก้ไขข้อมูลได้ตลอด</span>
                <?php elseif ($me['status'] === 'rejected'): ?><span>ยังไม่ผ่านการตรวจสอบ<?= $me['admin_note'] ? ': ' . h($me['admin_note']) : '' ?> — แก้ไขแล้วกดส่งตรวจสอบอีกครั้ง</span>
                <?php elseif ($me['status'] === 'hidden'): ?><span>เจ้าหน้าที่ซ่อนโปรไฟล์นี้ไว้ชั่วคราว ติดต่อสาขาหากมีข้อสงสัย</span>
                <?php else: ?><span>กรอกโปรไฟล์และเพิ่มผลงาน แล้วกด "ส่งให้ตรวจสอบ" — เจ้าหน้าที่จะอนุมัติก่อนแสดงบนหน้าเว็บ</span><?php endif; ?>
            </div>
            <div class="hub-status-actions">
                <a class="btn hero-fx-btn-ghost" href="<?= h(clean_public_url('/alumni-profile?id=' . (int) $me['id'])) ?>" target="_blank">ดูตัวอย่างโปรไฟล์</a>
                <?php if (in_array($me['status'], ['pending', 'rejected'], true)): ?>
                <form method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="submit_review"><button class="btn" type="submit"><?= !empty($_SESSION['alumni_submitted']) && $me['status'] === 'pending' ? 'ส่งตรวจสอบแล้ว ✓ (ส่งอีกครั้ง)' : 'ส่งให้ตรวจสอบ' ?></button></form>
                <?php endif; ?>
                <a class="btn hero-fx-btn-ghost" href="<?= h(clean_public_url('/alumni-logout')) ?>">ออกจากระบบ</a>
            </div>
        </div>

        <form method="post" enctype="multipart/form-data" class="hub-form content-card">
            <h2>ข้อมูลส่วนตัว</h2>
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="profile">
            <div class="hub-photo-row">
                <img src="<?= h($me['photo_path'] ?: '/assets/img/placeholder-avatar.svg') ?>" alt="" class="hub-dash-photo">
                <label>รูปโปรไฟล์<input type="file" name="photo" accept="image/*"></label>
            </div>
            <label>ชื่อ - นามสกุล<input type="text" name="full_name" required maxlength="150" value="<?= h($me['full_name']) ?>"></label>
            <div class="hub-form-row">
                <label>รุ่น<input type="text" name="batch_name" maxlength="100" value="<?= h($me['batch_name']) ?>"></label>
                <label>จบปีการศึกษา<input type="number" name="graduation_year" min="2500" max="2700" value="<?= h($me['graduation_year']) ?>"></label>
            </div>
            <label>ตำแหน่ง / ความถนัดหลัก<input type="text" name="headline" maxlength="255" placeholder="เช่น 3D Animator · Freelance" value="<?= h($me['headline']) ?>"></label>
            <label>ทักษะ (คั่นด้วยจุลภาค)<input type="text" name="skills" maxlength="500" placeholder="Animation, Unity, Blender, ตัดต่อวิดีโอ" value="<?= h($me['skills']) ?>"></label>
            <label>แนะนำตัว / ประสบการณ์<textarea name="bio" rows="5" maxlength="4000"><?= h($me['bio']) ?></textarea></label>
            <h2>ช่องทางติดต่อ (แสดงบนหน้าเว็บ — กรอกเฉพาะที่ต้องการให้เห็น)</h2>
            <div class="hub-form-row">
                <label>อีเมลติดต่องาน<input type="email" name="contact_email" maxlength="190" value="<?= h($me['contact_email']) ?>"></label>
                <label>เบอร์โทร<input type="text" name="contact_phone" maxlength="50" value="<?= h($me['contact_phone']) ?>"></label>
            </div>
            <div class="hub-form-row">
                <label>LINE ID<input type="text" name="line_id" maxlength="100" value="<?= h($me['line_id']) ?>"></label>
                <label>Portfolio / เว็บไซต์<input type="text" name="website_url" maxlength="255" placeholder="https://..." value="<?= h($me['website_url']) ?>"></label>
            </div>
            <label class="hub-check"><input type="checkbox" name="available_for_work" value="1" <?= $me['available_for_work'] ? 'checked' : '' ?>> พร้อมรับงาน / เปิดรับข้อเสนองาน</label>
            <button class="btn" type="submit">บันทึกโปรไฟล์</button>
        </form>

        <div class="content-card hub-form">
            <h2>ผลงานของฉัน (<?= count($works) ?>/<?= ALUMNI_HUB_MAX_WORKS ?>)</h2>
            <?php if ($works): ?>
            <div class="hub-works-grid hub-works-edit">
                <?php foreach ($works as $w): ?>
                <figure class="hub-work">
                    <?php if ($w['image_path']): ?><img src="<?= h($w['image_path']) ?>" alt="" loading="lazy"><?php endif; ?>
                    <figcaption>
                        <strong><?= h($w['title']) ?></strong>
                        <form method="post" data-confirm="ลบผลงานนี้?">
                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete_work">
                            <input type="hidden" name="work_id" value="<?= (int) $w['id'] ?>">
                            <button type="submit" class="hub-link-btn">ลบ</button>
                        </form>
                    </figcaption>
                </figure>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (count($works) < ALUMNI_HUB_MAX_WORKS): ?>
            <form method="post" enctype="multipart/form-data" class="hub-add-work">
                <h3>เพิ่มผลงาน</h3>
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="add_work">
                <label>ชื่อผลงาน<input type="text" name="title" required maxlength="255"></label>
                <label>รูปผลงาน<input type="file" name="image" accept="image/*"></label>
                <label>รายละเอียด (ไม่บังคับ)<textarea name="description" rows="2" maxlength="2000"></textarea></label>
                <label>ลิงก์ผลงาน เช่น YouTube, Behance, ArtStation (ไม่บังคับ)<input type="text" name="link_url" maxlength="255" placeholder="https://..."></label>
                <button class="btn" type="submit">เพิ่มผลงาน</button>
            </form>
            <?php endif; ?>
        </div>

        <form method="post" class="content-card hub-form">
            <h2>เปลี่ยนรหัสผ่าน</h2>
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="password">
            <div class="hub-form-row">
                <label>รหัสผ่านปัจจุบัน<input type="password" name="current_password" required autocomplete="current-password"></label>
                <label>รหัสผ่านใหม่ (อย่างน้อย 8 ตัว)<input type="password" name="new_password" required minlength="8" autocomplete="new-password"></label>
            </div>
            <button class="btn hero-fx-btn-ghost" type="submit">เปลี่ยนรหัสผ่าน</button>
        </form>
    </div>
</div>
<script src="/assets/js/confirm-popup.js?v=<?= (int) @filemtime(__DIR__ . '/assets/js/confirm-popup.js') ?>"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
