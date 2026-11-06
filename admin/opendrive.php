<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mirror') {
    // One small batch per request (called repeatedly by the page) so no request runs long enough to time out.
    csrf_check();
    header('Content-Type: application/json; charset=utf-8');
    try {
        echo json_encode(['ok' => true] + opendrive_mirror_batch($pdo, (int) ($_POST['skip'] ?? 0)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Throwable $e) {
        error_log('OpenDrive mirror failed: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

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
$remoteLinks = opendrive_remote_links($pdo);
?>
<div class="admin-card" id="od-mirror">
    <h3 style="margin-top:0;">ดึงไฟล์จาก OpenDrive มาเก็บบนเว็บ (<?= count($remoteLinks) ?> ไฟล์)</h3>
    <?php if ($remoteLinks): ?>
    <p>รูปและไฟล์บนเว็บที่ยังเก็บไว้ใน OpenDrive จะถูกคัดลอกมาไว้บนเซิร์ฟเวอร์นี้ (<code>assets/uploads</code>) และเปลี่ยนลิงก์บนเว็บให้อัตโนมัติ
        เว็บจะแสดงรูปได้แม้ OpenDrive ใช้งานไม่ได้ ไฟล์ใน OpenDrive ไม่ถูกลบ</p>
    <p class="field-hint">ระบบลองลิงก์สาธารณะก่อน ถ้าถูกปิด (เช่น บัญชีถูกระงับ) จะล็อกอินบัญชี OpenDrive ของเว็บเพื่อดาวน์โหลดแทน · ทำครั้งละ <?= OPENDRIVE_MIRROR_BATCH ?> ไฟล์ต่อเนื่องจนครบ อย่าปิดหน้านี้ระหว่างทำงาน</p>
    <button class="btn" type="button" id="od-mirror-start">เริ่มดึงไฟล์</button>
    <div id="od-mirror-status" style="margin-top:12px;font-weight:600;"></div>
    <div style="height:10px;background:#eee;border-radius:6px;overflow:hidden;margin-top:8px;"><div id="od-mirror-bar" style="height:100%;width:0;background:#a3222c;transition:width .3s;"></div></div>
    <ul id="od-mirror-errors" style="margin-top:10px;font-size:.9em;color:#a3222c;max-height:220px;overflow:auto;"></ul>
    <script>
    (function () {
        var btn = document.getElementById('od-mirror-start');
        var statusEl = document.getElementById('od-mirror-status');
        var bar = document.getElementById('od-mirror-bar');
        var errors = document.getElementById('od-mirror-errors');
        var total = <?= count($remoteLinks) ?>;
        var copied = 0, skip = 0;
        function show(text) { statusEl.textContent = text; }
        function step() {
            var body = new FormData();
            body.append('csrf_token', <?= json_encode(csrf_token()) ?>);
            body.append('action', 'mirror');
            body.append('skip', skip);
            fetch('/admin/opendrive.php', { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d.ok) throw new Error(d.error || 'ผิดพลาด');
                    copied += d.copied;
                    skip += d.failed.length;
                    d.failed.forEach(function (f) {
                        var li = document.createElement('li');
                        li.textContent = f[0].split('/').pop() + ' — ' + f[1];
                        errors.appendChild(li);
                    });
                    bar.style.width = Math.round((copied + skip) / Math.max(total, 1) * 100) + '%';
                    show('คัดลอกแล้ว ' + copied + ' / ' + total + ' ไฟล์' + (skip ? ' · ไม่สำเร็จ ' + skip + ' ไฟล์' : ''));
                    if (d.remaining > skip && (d.copied > 0 || d.failed.length > 0)) {
                        step();
                    } else {
                        btn.disabled = false;
                        btn.textContent = skip ? 'ลองไฟล์ที่ไม่สำเร็จอีกครั้ง' : 'เสร็จแล้ว';
                        show((skip ? '⚠️ ' : '✅ ') + 'เสร็จแล้ว คัดลอก ' + copied + ' ไฟล์' + (skip ? ' · ไม่สำเร็จ ' + skip + ' ไฟล์ (ดูสาเหตุด้านล่าง)' : ' รูปบนเว็บแสดงจากเซิร์ฟเวอร์นี้แล้ว'));
                    }
                })
                .catch(function (e) {
                    btn.disabled = false;
                    btn.textContent = 'ทำต่อ';
                    show('⚠️ หยุดชั่วคราว: ' + e.message + ' (กด "ทำต่อ" ได้ ไฟล์ที่คัดลอกแล้วไม่ต้องทำซ้ำ)');
                });
        }
        btn.addEventListener('click', function () {
            btn.disabled = true;
            btn.textContent = 'กำลังดึงไฟล์...';
            errors.innerHTML = '';
            skip = 0;
            show('กำลังเริ่ม...');
            step();
        });
    })();
    </script>
    <?php else: ?>
    <p>✅ ไม่มีไฟล์ที่ต้องพึ่ง OpenDrive แล้ว รูปทั้งหมดแสดงจากเซิร์ฟเวอร์นี้</p>
    <?php endif; ?>
</div>

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
    <p>ไฟล์เหล่านี้อยู่บนเซิร์ฟเวอร์ (รวมไฟล์ที่ดึงมาจาก OpenDrive ด้านบน) ถ้าต้องการย้ายกลับไปไว้ใน OpenDrive ให้กดปุ่มนี้ — <strong>ถ้าต้องการให้รูปแสดงจากเซิร์ฟเวอร์นี้ต่อไป ไม่ต้องกด</strong>
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
