<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!opendrive_enabled()) {
        set_flash('ยังไม่ได้ตั้งค่าบัญชี OpenDrive ใน config/config.local.php', 'error');
    } elseif (($_POST['action'] ?? '') === 'migrate') {
        try {
            $result = opendrive_migrate_local_files($pdo);
            $message = 'ย้ายไฟล์ไป OpenDrive แล้ว ' . $result['moved'] . ' ไฟล์';
            if ($result['remaining'] > 0) {
                $message .= ' — ยังเหลือ ' . $result['remaining'] . ' ไฟล์ กดปุ่มอีกครั้งเพื่อย้ายต่อ';
            }
            if ($result['failed']) {
                $message .= ' — ไม่สำเร็จ: ' . implode(', ', array_slice($result['failed'], 0, 3));
            }
            set_flash($message, $result['failed'] ? 'error' : 'success');
        } catch (Throwable $e) {
            error_log('OpenDrive migration failed: ' . $e->getMessage());
            set_flash('เชื่อมต่อ OpenDrive ไม่สำเร็จ: ' . $e->getMessage(), 'error');
        }
    } else {
        try {
            $ids = opendrive_prepare_all_folders();
            set_flash('สร้าง/ตรวจสอบโฟลเดอร์บน OpenDrive เรียบร้อยแล้ว (' . count($ids) . ' หมวด)');
        } catch (Throwable $e) {
            error_log('OpenDrive prepare folders failed: ' . $e->getMessage());
            set_flash('เชื่อมต่อ OpenDrive ไม่สำเร็จ: ' . $e->getMessage(), 'error');
        }
    }
    header('Location: /admin/opendrive.php');
    exit;
}

$adminPageTitle = 'ที่เก็บไฟล์ OpenDrive';
require_once __DIR__ . '/includes/layout-top.php';

$enabled = opendrive_enabled();
$cached = opendrive_cached_folders();
$localFiles = opendrive_local_files($pdo);
?>
<div class="admin-card">
    <h3 style="margin-top:0;">สถานะ</h3>
    <?php if ($enabled): ?>
    <p>✅ เปิดใช้งาน: ไฟล์ที่อัปโหลดผ่านหลังบ้านจะถูกเก็บใน OpenDrive บัญชี <strong><?= h(OD_USERNAME) ?></strong>
        ในโฟลเดอร์ <strong><?= h(OPENDRIVE_ROOT_FOLDER_NAME) ?></strong> แยกตามหมวด</p>
    <?php else: ?>
    <p>⚠️ ยังไม่เปิดใช้งาน: ไฟล์จะถูกเก็บบนเซิร์ฟเวอร์ (<code>assets/uploads</code>)
        เพิ่ม <code>OD_USERNAME</code> และ <code>OD_PASSWORD</code> ใน <code>config/config.local.php</code> เพื่อเปิดใช้งาน</p>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <button class="btn" type="submit"<?= $enabled ? '' : ' disabled' ?>>สร้าง/ตรวจสอบโฟลเดอร์บน OpenDrive</button>
    </form>
    <p class="field-hint">กดซ้ำได้ ระบบจะใช้โฟลเดอร์เดิมที่มีอยู่แล้ว ไม่สร้างซ้ำ (ใช้ทดสอบการเชื่อมต่อได้ด้วย)</p>
</div>

<div class="admin-card">
    <h3 style="margin-top:0;">ไฟล์ที่ยังเก็บบนเซิร์ฟเวอร์ (<?= count($localFiles) ?> ไฟล์)</h3>
    <?php if ($localFiles): ?>
    <p>ไฟล์เหล่านี้อัปโหลดก่อนเปิดใช้ OpenDrive (หรือตอนที่ OpenDrive เชื่อมต่อไม่ได้) กดปุ่มเพื่อย้ายไปไว้ใน OpenDrive
        ระบบจะเปลี่ยนลิงก์บนเว็บให้อัตโนมัติ และเก็บไฟล์เดิมบนเซิร์ฟเวอร์ไว้เป็นสำรอง (ครั้งละไม่เกิน 15 ไฟล์)</p>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="migrate">
        <button class="btn" type="submit"<?= $enabled ? '' : ' disabled' ?>>ย้ายไฟล์เดิมไป OpenDrive</button>
    </form>
    <?php else: ?>
    <p>✅ ไม่มีไฟล์ค้างบนเซิร์ฟเวอร์ ทุกไฟล์อยู่ใน OpenDrive แล้ว</p>
    <?php endif; ?>
</div>

<div class="admin-card">
    <h3 style="margin-top:0;">โฟลเดอร์บน OpenDrive</h3>
    <table class="admin-table">
        <thead><tr><th>โฟลเดอร์</th><th>ใช้เก็บ</th><th>สถานะ</th></tr></thead>
        <tbody>
            <tr>
                <td><strong><?= h(OPENDRIVE_ROOT_FOLDER_NAME) ?></strong></td>
                <td>โฟลเดอร์หลักของเว็บนี้</td>
                <td><?= !empty($cached['_root']) ? '✅ พร้อมใช้งาน' : '— ยังไม่ได้สร้าง' ?></td>
            </tr>
            <?php foreach (OPENDRIVE_SECTION_FOLDERS as $section => $label): ?>
            <tr>
                <td><?= h(OPENDRIVE_ROOT_FOLDER_NAME . '/' . $section) ?></td>
                <td><?= h($label) ?></td>
                <td><?= !empty($cached[$section]) ? '✅ พร้อมใช้งาน' : '— ยังไม่ได้สร้าง' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
