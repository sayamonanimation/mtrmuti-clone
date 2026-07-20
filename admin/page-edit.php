<?php
require_once __DIR__ . '/includes/auth.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = ? LIMIT 1');
$stmt->execute([$slug]);
$page = $stmt->fetch();

if (!$page) {
    set_flash('ไม่พบหน้าที่ต้องการแก้ไข', 'error');
    header('Location: /admin/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    reject_oversized_upload('/admin/page-edit.php?slug=' . urlencode($slug));
    csrf_check();
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $content = $_POST['content'] ?? '';
    $titleEn = trim($_POST['title_en'] ?? '');
    $subtitleEn = trim($_POST['subtitle_en'] ?? '');
    $contentEn = $_POST['content_en'] ?? '';

    $stmt = $pdo->prepare('UPDATE pages SET title = ?, subtitle = ?, content = ?, title_en = ?, subtitle_en = ?, content_en = ? WHERE slug = ?');
    $stmt->execute([$title, $subtitle, $content, $titleEn, $subtitleEn, $contentEn, $slug]);

    if ($slug === 'course') {
        $pdfPath = handle_pdf_upload('curriculum_pdf');
        if ($pdfPath) {
            set_setting('curriculum_pdf_path', $pdfPath);
        } elseif (!empty($_FILES['curriculum_pdf']['name'])) {
            set_flash('บันทึกเนื้อหาแล้ว แต่ไฟล์เล่มหลักสูตรไม่ถูกต้อง (ต้องเป็น PDF เท่านั้น)', 'error');
            header('Location: /admin/page-edit.php?slug=' . urlencode($slug));
            exit;
        }
    }

    if ($slug === 'career-intro') {
        $careerImagePath = handle_image_upload('career_image');
        if ($careerImagePath) {
            set_setting('career_intro_image_path', $careerImagePath);
        } elseif (!empty($_FILES['career_image']['name'])) {
            set_flash('บันทึกชื่อหัวข้อแล้ว แต่ไฟล์รูปภาพไม่ถูกต้อง (ต้องเป็น jpg, png, webp, gif เท่านั้น)', 'error');
            header('Location: /admin/page-edit.php?slug=' . urlencode($slug));
            exit;
        }
    }

    set_flash('บันทึกเนื้อหาหน้า "' . $title . '" เรียบร้อยแล้ว');
    header('Location: /admin/page-edit.php?slug=' . urlencode($slug));
    exit;
}

$adminPageTitle = 'แก้ไขหน้า: ' . $page['title'];
require_once __DIR__ . '/includes/layout-top.php';
?>
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<form method="post" id="pageForm" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <div class="admin-card">
        <div class="form-group">
            <label>ชื่อหัวข้อหน้า (Title)</label>
            <input type="text" name="title" value="<?= h($page['title']) ?>" required>
        </div>
        <?php if ($slug !== 'career-intro'): ?>
        <div class="form-group">
            <label>คำโปรย (Subtitle) - ไม่บังคับ</label>
            <input type="text" name="subtitle" value="<?= h($page['subtitle']) ?>">
        </div>
        <div class="form-group">
            <label>เนื้อหา</label>
            <div id="editor"><?= $page['content'] ?></div>
        </div>
        <input type="hidden" name="content" id="contentInput">

        <hr style="margin:28px 0; border:none; border-top:1px solid #eee;">
        <p class="field-hint" style="margin-top:0;">เวอร์ชันภาษาอังกฤษ (ไม่บังคับ - ถ้าเว้นว่าง หน้าเว็บโหมดภาษาอังกฤษจะแสดงเนื้อหาภาษาไทยแทน)</p>
        <div class="form-group">
            <label>Title (English)</label>
            <input type="text" name="title_en" value="<?= h($page['title_en']) ?>">
        </div>
        <div class="form-group">
            <label>Subtitle (English)</label>
            <input type="text" name="subtitle_en" value="<?= h($page['subtitle_en']) ?>">
        </div>
        <div class="form-group">
            <label>Content (English)</label>
            <div id="editor_en"><?= $page['content_en'] ?></div>
        </div>
        <input type="hidden" name="content_en" id="contentEnInput">
        <?php else: ?>
        <hr style="margin:28px 0; border:none; border-top:1px solid #eee;">
        <div class="form-group">
            <label>Title (English)</label>
            <input type="text" name="title_en" value="<?= h($page['title_en']) ?>">
        </div>
        <div class="form-group">
            <label>รูปภาพแนะนำอาชีพ</label>
            <?php $careerImage = get_setting('career_intro_image_path'); ?>
            <?php if ($careerImage): ?>
                <div style="margin-bottom:10px;"><img class="current-thumb" src="<?= h($careerImage) ?>" alt=""></div>
            <?php endif; ?>
            <input type="file" name="career_image" accept="image/*">
            <p class="field-hint">อัพโหลดรูปใหม่เพื่อแทนที่รูปเดิม (jpg, png, webp, gif)</p>
        </div>
        <?php endif; ?>

        <?php if ($slug === 'course'): ?>
        <div class="form-group">
            <label>ไฟล์เล่มหลักสูตร (PDF) - แสดงเป็นปุ่มดาวน์โหลดในหน้าเว็บ</label>
            <?php $curriculumPdf = get_setting('curriculum_pdf_path'); ?>
            <?php if ($curriculumPdf): ?>
                <p class="field-hint">ไฟล์ปัจจุบัน: <a href="<?= h($curriculumPdf) ?>" target="_blank">เปิดดูไฟล์ที่แนบไว้</a></p>
            <?php endif; ?>
            <input type="file" name="curriculum_pdf" accept="application/pdf">
            <p class="field-hint">อัพโหลดไฟล์ใหม่เพื่อแทนที่ไฟล์เดิม (รองรับเฉพาะ .pdf)</p>
        </div>
        <?php endif; ?>

        <div class="form-actions">
            <button class="btn" type="submit">บันทึก</button>
            <a class="btn btn-ghost" href="/admin/dashboard.php">ยกเลิก</a>
        </div>
    </div>
</form>

<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
var quillToolbar = [
    [{ header: [2, 3, false] }],
    ['bold', 'italic', 'underline'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['link', 'image'],
    ['clean']
];
var editorEl = document.getElementById('editor');
var quill = editorEl ? new Quill('#editor', { theme: 'snow', modules: { toolbar: quillToolbar } }) : null;
var quillEn = editorEl ? new Quill('#editor_en', { theme: 'snow', modules: { toolbar: quillToolbar } }) : null;
document.getElementById('pageForm').addEventListener('submit', function () {
    if (quill) document.getElementById('contentInput').value = quill.root.innerHTML;
    if (quillEn) document.getElementById('contentEnInput').value = quillEn.root.innerHTML;
});
</script>

<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
