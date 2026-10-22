<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
ensure_alumni_hub();
ensure_alumni_consent_columns();

$error = '';
$old = ['full_name' => '', 'email' => '', 'batch_name' => '', 'graduation_year' => '', 'member_type' => 'alumni'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old = array_map('trim', array_intersect_key($_POST, $old)) + $old;
    $password = $_POST['password'] ?? '';
    if (alumni_form_is_bot()) {
        $error = 'กรุณาลองใหม่อีกครั้ง';
    } elseif ($old['full_name'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'กรุณากรอกชื่อ-นามสกุล และอีเมลให้ถูกต้อง';
    } elseif (strlen($password) < 8) {
        $error = 'รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร';
    } elseif ($password !== ($_POST['password_confirm'] ?? '')) {
        $error = 'ยืนยันรหัสผ่านไม่ตรงกัน';
    } elseif (($_POST['consent'] ?? '') !== '1') {
        $error = 'กรุณาอ่านและกดยินยอมการเผยแพร่ข้อมูลส่วนบุคคลก่อนสมัคร';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM alumni_members WHERE email = ?');
        $stmt->execute([mb_strtolower($old['email'])]);
        if ($stmt->fetchColumn()) {
            $error = 'อีเมลนี้สมัครไว้แล้ว — เข้าสู่ระบบได้เลย';
        } else {
            $stmt = $pdo->prepare('INSERT INTO alumni_members (email, password_hash, full_name, batch_name, graduation_year, contact_email, status, consent_at, consent_version, member_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([mb_strtolower($old['email']), password_hash($password, PASSWORD_DEFAULT), $old['full_name'], $old['batch_name'], (int) $old['graduation_year'] ?: null, mb_strtolower($old['email']), 'pending', date('Y-m-d H:i:s'), ALUMNI_CONSENT_VERSION, alumni_member_type_from_post($old['member_type'])]);
            session_regenerate_id(true);
            $_SESSION['alumni_member_id'] = (int) $pdo->lastInsertId();
            $_SESSION['alumni_flash'] = 'สมัครสำเร็จ! กรอกโปรไฟล์และเพิ่มผลงานได้เลย แล้วกด "ส่งให้ตรวจสอบ" เพื่อแสดงบนหน้าเว็บ';
            header('Location: ' . clean_public_url('/alumni-dashboard'));
            exit;
        }
    }
}

$currentSlug = 'alumni-hub';
$pageTitleOverride = 'สมัครฝากประวัติศิษย์เก่า';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-header"><div class="container"><h1>สมัครฝากประวัติศิษย์เก่า</h1><p>สร้างบัญชีเพื่อฝากประวัติและผลงาน แก้ไขข้อมูลได้ตลอด</p></div></div>
<div class="page-content">
    <div class="container hub-auth">
        <form method="post" class="hub-form content-card">
            <?php if ($error): ?><div class="hub-error"><?= h($error) ?></div><?php endif; ?>
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="form_started" value="<?= time() ?>">
            <input type="text" name="website_confirm" value="" tabindex="-1" autocomplete="off" class="hub-hp" aria-hidden="true">
            <fieldset class="hub-type">
                <legend>สถานะของคุณ</legend>
                <?php foreach (alumni_member_types() as $key => $label): ?>
                <label><input type="radio" name="member_type" value="<?= $key ?>"<?= $old['member_type'] === $key ? ' checked' : '' ?>><span><?= h($label) ?></span></label>
                <?php endforeach; ?>
            </fieldset>
            <label>ชื่อ - นามสกุล<input type="text" name="full_name" required maxlength="150" value="<?= h($old['full_name']) ?>"></label>
            <label>อีเมล (ใช้เข้าสู่ระบบ)<input type="email" name="email" required maxlength="190" value="<?= h($old['email']) ?>" autocomplete="email"></label>
            <div class="hub-form-row hub-class-fields">
                <label>รุ่น<input type="text" name="batch_name" maxlength="100" placeholder="เช่น MT10" value="<?= h($old['batch_name']) ?>"></label>
                <label><span class="hub-year-label">จบปีการศึกษา</span><input type="number" name="graduation_year" min="2500" max="2700" placeholder="เช่น 2565" value="<?= h($old['graduation_year']) ?>"></label>
            </div>
            <label>รหัสผ่าน (อย่างน้อย 8 ตัว)<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
            <label>ยืนยันรหัสผ่าน<input type="password" name="password_confirm" required minlength="8" autocomplete="new-password"></label>
            <label class="hub-consent"><input type="checkbox" name="consent" value="1" id="consentBox"<?= ($_POST['consent'] ?? '') === '1' ? ' checked' : '' ?>>
                <span>ข้าพเจ้ายินยอมให้เผยแพร่ข้อมูลส่วนบุคคลตาม <button type="button" class="hub-consent-link" id="consentOpen">เงื่อนไขการเผยแพร่ข้อมูล</button></span></label>
            <button class="btn" type="submit">สมัคร</button>
            <p class="hub-muted">มีบัญชีแล้ว? <a href="<?= h(clean_public_url('/alumni-login')) ?>">เข้าสู่ระบบ</a></p>
            <p class="hub-muted" style="font-size:.8rem;">ข้อมูลจะแสดงบนหน้าเว็บหลังได้รับการตรวจสอบจากเจ้าหน้าที่สาขา</p>
        </form>
    </div>
</div>

<dialog class="consent-dialog" id="consentDialog" aria-labelledby="consentTitle">
    <form method="dialog">
        <h2 id="consentTitle">การยินยอมเผยแพร่ข้อมูลส่วนบุคคล</h2>
        <div class="consent-body">
            <p>สาขาวิชาเทคโนโลยีมัลติมีเดีย คณะสถาปัตยกรรมศาสตร์และศิลปกรรมสร้างสรรค์ มหาวิทยาลัยเทคโนโลยีราชมงคลอีสาน ขอความยินยอมจากท่านในการเก็บรวบรวมและเผยแพร่ข้อมูลส่วนบุคคล ตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 ดังนี้</p>
            <h3>ข้อมูลที่จะเผยแพร่บนหน้าเว็บ "ติดต่องานศิษย์เก่า"</h3>
            <ul>
                <li>ชื่อ - นามสกุล รุ่น และปีที่จบการศึกษา</li>
                <li>รูปโปรไฟล์ ตำแหน่ง/ความถนัด ทักษะ และประวัติโดยย่อ</li>
                <li>ผลงาน รูปผลงาน และลิงก์ผลงาน</li>
                <li>ช่องทางติดต่อที่ท่านกรอกไว้ (อีเมล โทรศัพท์ LINE เว็บไซต์)</li>
            </ul>
            <h3>วัตถุประสงค์</h3>
            <p>เพื่อแนะนำศิษย์เก่าและผลงานต่อสาธารณะ ให้ผู้ประกอบการและบุคคลทั่วไปติดต่อเรื่องงานได้ และเพื่อประชาสัมพันธ์สาขาวิชา</p>
            <h3>สิทธิของท่าน</h3>
            <ul>
                <li>ข้อมูลจะแสดงบนหน้าเว็บหลังเจ้าหน้าที่ตรวจสอบแล้วเท่านั้น</li>
                <li>ท่านเลือกได้ว่าจะกรอกช่องทางติดต่อใดบ้าง และแก้ไขหรือลบข้อมูลได้ตลอดเวลาที่หน้าโปรไฟล์ของท่าน</li>
                <li>ท่านถอนความยินยอมได้ทุกเมื่อ โดยแจ้งเจ้าหน้าที่สาขาเพื่อซ่อนหรือลบบัญชีและข้อมูลทั้งหมด</li>
                <li>อีเมลที่ใช้เข้าสู่ระบบและรหัสผ่านจะไม่ถูกเผยแพร่</li>
            </ul>
            <p class="consent-note">ข้อมูลที่เผยแพร่บนอินเทอร์เน็ตอาจถูกบุคคลภายนอกคัดลอกหรือเก็บสำเนาไว้ได้ กรุณาเปิดเผยเฉพาะข้อมูลที่ท่านประสงค์</p>
        </div>
        <div class="consent-actions">
            <button type="submit" value="decline" class="btn btn-ghost">ไม่ยินยอม</button>
            <button type="submit" value="accept" class="btn" id="consentAccept">ยินยอมและสมัคร</button>
        </div>
    </form>
</dialog>
<script src="/assets/js/hub-type.js?v=<?= (int) @filemtime(__DIR__ . '/assets/js/hub-type.js') ?>"></script>
<script>
(function () {
    var dialog = document.getElementById('consentDialog');
    var box = document.getElementById('consentBox');
    var form = box && box.form;
    if (!dialog || !form || typeof dialog.showModal !== 'function') return; // plain checkbox still works
    var submitAfter = false;
    function open(thenSubmit) { submitAfter = thenSubmit; dialog.returnValue = ''; dialog.showModal(); }
    document.getElementById('consentOpen').addEventListener('click', function () { open(false); });
    form.addEventListener('submit', function (e) {
        if (box.checked) return;
        e.preventDefault();
        open(true);
    });
    dialog.addEventListener('close', function () {
        if (dialog.returnValue === 'accept') {
            box.checked = true;
            if (submitAfter) form.requestSubmit ? form.requestSubmit() : form.submit();
        } else if (dialog.returnValue === 'decline') {
            box.checked = false;
        }
    });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
