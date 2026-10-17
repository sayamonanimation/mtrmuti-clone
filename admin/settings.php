<?php
require_once __DIR__ . '/includes/auth.php';

$keys = ['site_name', 'site_name_en', 'address', 'phone', 'email', 'facebook_url', 'enroll_url', 'homepage_video_url', 'logo_path', 'hero_banner_path',
    'hero_particle_text', 'hero_keywords', 'hero_video_url'];
$imageKeys = ['logo_path', 'hero_banner_path'];
$urlKeys = ['facebook_url', 'enroll_url', 'homepage_video_url', 'hero_video_url'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    reject_oversized_upload('/admin/settings.php');
    csrf_check();
    $stmt = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    foreach ($keys as $key) {
        if (in_array($key, $imageKeys, true)) {
            continue;
        }
        $value = in_array($key, $urlKeys, true) ? normalize_url($_POST[$key] ?? '') : trim($_POST[$key] ?? '');
        $stmt->execute([$key, $value]);
    }

    // A newly uploaded image wins over the "remove" checkbox.
    $logoPath = handle_image_upload('logo', 'branding');
    if ($logoPath) {
        $stmt->execute(['logo_path', $logoPath]);
    } elseif (!empty($_POST['remove_logo'])) {
        $stmt->execute(['logo_path', '']);
    }

    $heroBannerPath = handle_image_upload('hero_banner', 'branding');
    if ($heroBannerPath) {
        $stmt->execute(['hero_banner_path', $heroBannerPath]);
    } elseif (!empty($_POST['remove_hero_banner'])) {
        $stmt->execute(['hero_banner_path', '']);
    }

    $stmt->execute(['chatbot_enabled', isset($_POST['chatbot_enabled']) ? '1' : '0']);

    set_flash('บันทึกการตั้งค่าเรียบร้อยแล้ว');
    header('Location: /admin/settings.php');
    exit;
}

$adminPageTitle = 'ตั้งค่าเว็บไซต์';
require_once __DIR__ . '/includes/layout-top.php';

$settings = [];
foreach ($pdo->query('SELECT setting_key, setting_value FROM site_settings') as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <div class="admin-card">
        <div class="form-group">
            <label>ชื่อเว็บไซต์ / สาขาวิชา</label>
            <input type="text" name="site_name" value="<?= h($settings['site_name'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>ชื่อเว็บไซต์ (English)</label>
            <input type="text" name="site_name_en" value="<?= h($settings['site_name_en'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>ที่อยู่</label>
            <textarea name="address"><?= h($settings['address'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>เบอร์โทรศัพท์</label>
            <input type="text" name="phone" value="<?= h($settings['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>อีเมล</label>
            <input type="email" name="email" value="<?= h($settings['email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>ลิงก์ Facebook</label>
            <input type="text" name="facebook_url" value="<?= h($settings['facebook_url'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>ลิงก์ปุ่ม "สมัครเรียน" (แถบบนสุดของเว็บไซต์)</label>
            <p class="field-hint" style="margin-top:0;">ใส่ลิงก์ระบบรับสมัคร หรือฟอร์ม Google Form เว้นว่างไว้ถ้ายังไม่ต้องการแสดงปุ่มนี้ (ไม่ต้องมี https:// นำหน้าก็ได้)</p>
            <input type="text" name="enroll_url" value="<?= h($settings['enroll_url'] ?? '') ?>" placeholder="https://...">
        </div>
        <div class="form-group">
            <label>วิดีโอหน้าแรก (YouTube)</label>
            <p class="field-hint" style="margin-top:0;">แสดงเป็นแบนเนอร์วิดีโอเหนือสไลด์หน้าแรก เล่นอัตโนมัติ (ปิดเสียงไว้ตามข้อกำหนดของเบราว์เซอร์ ผู้ชมกดเปิดเสียงเองได้) วางลิงก์ YouTube เช่น https://www.youtube.com/watch?v=... เว้นว่างไว้ถ้าไม่ต้องการแสดง</p>
            <input type="text" name="homepage_video_url" value="<?= h($settings['homepage_video_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=...">
        </div>
        <div class="form-group">
            <label>โลโก้เว็บไซต์</label>
            <?php if (!empty($settings['logo_path'])): ?>
                <img class="current-thumb" src="<?= h($settings['logo_path']) ?>" alt="">
                <label class="remove-image-option"><input type="checkbox" name="remove_logo" value="1"> ลบโลโก้นี้ (จะแสดงตัวอักษร MT แทน)</label>
            <?php endif; ?>
            <input type="file" name="logo" accept="image/*">
        </div>
        <div class="form-group">
            <label>แบนเนอร์หน้าแรก (แนะนำขนาดกว้าง เช่น 1920x480px)</label>
            <p class="field-hint" style="margin-top:0;">แสดงจาง ๆ เป็นพื้นหลังของหัวข้อหน้าแรกแบบ WebGL</p>
            <?php if (!empty($settings['hero_banner_path'])): ?>
                <img class="current-thumb" src="<?= h($settings['hero_banner_path']) ?>" alt="">
                <label class="remove-image-option"><input type="checkbox" name="remove_hero_banner" value="1"> ลบแบนเนอร์นี้</label>
            <?php endif; ?>
            <input type="file" name="hero_banner" accept="image/*">
        </div>
        <h3>หัวข้อหน้าแรกแบบ WebGL</h3>
        <p class="field-hint" style="margin-top:0;">แสดงด้านบนสุดของหน้าแรกเสมอ ส่วน "สไลด์หน้าแรก" (ถ้ามี) จะแสดงต่อจากหัวข้อนี้</p>
        <div class="form-group">
            <label>ข้อความอนุภาคแสง (ตัวอักษรที่ประกอบจากจุดแสง เล่นกับเมาส์ได้)</label>
            <p class="field-hint" style="margin-top:0;">แนะนำภาษาอังกฤษสั้น ๆ 1-2 คำ เช่น MULTIMEDIA หรือ MT RMUTI ขึ้นบรรทัดใหม่ด้วยเครื่องหมาย | เช่น MULTIMEDIA|TECHNOLOGY</p>
            <input type="text" name="hero_particle_text" value="<?= h($settings['hero_particle_text'] ?? '') ?>" placeholder="MULTIMEDIA|TECHNOLOGY">
        </div>
        <div class="form-group">
            <label>คำสาขาย่อยที่หมุนเปลี่ยน (คั่นด้วยเครื่องหมายจุลภาค ,)</label>
            <input type="text" name="hero_keywords" value="<?= h($settings['hero_keywords'] ?? '') ?>" placeholder="ANIMATION, GAME, FILM, GRAPHIC DESIGN, VR / AR, SOUND">
        </div>
        <div class="form-group">
            <label>วิดีโอ Showreel พื้นหลังหัวข้อ (YouTube หรือไฟล์ .mp4)</label>
            <p class="field-hint" style="margin-top:0;">เล่นวนแบบไม่มีเสียงอยู่ด้านหลังหัวข้อ วางลิงก์ YouTube หรือลิงก์ไฟล์ .mp4 เว้นว่างไว้ถ้าไม่ต้องการ</p>
            <input type="text" name="hero_video_url" value="<?= h($settings['hero_video_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=...">
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" name="chatbot_enabled" value="1" <?= ($settings['chatbot_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                เปิดใช้งานผู้ช่วยตอบคำถาม (chatbot) บนหน้าเว็บไซต์
            </label>
            <p class="field-hint" style="margin-top:0;">ถ้าปิด ปุ่มแชทจะหายไปจากทุกหน้าของเว็บไซต์ทันที</p>
        </div>
        <div class="form-actions">
            <button class="btn" type="submit">บันทึก</button>
        </div>
    </div>
</form>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
