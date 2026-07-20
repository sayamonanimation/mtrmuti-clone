<?php
require_once __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$post = ['id' => null, 'title' => '', 'excerpt' => '', 'title_en' => '', 'excerpt_en' => '', 'image_path' => '', 'post_date' => '', 'link_url' => '', 'sort_order' => 0, 'is_active' => 1];

if ($id) {
    $found = get_post($id);
    if ($found) {
        $post = $found;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    reject_oversized_upload($post['id'] ? '/admin/post-form.php?id=' . $post['id'] : '/admin/post-form.php');
    csrf_check();
    $title = trim($_POST['title'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $titleEn = trim($_POST['title_en'] ?? '');
    $excerptEn = trim($_POST['excerpt_en'] ?? '');
    $postDate = trim($_POST['post_date'] ?? '') ?: null;
    $linkUrl = normalize_url($_POST['link_url'] ?? '');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $imagePath = $post['image_path'];

    $uploaded = handle_image_upload('image', 'posts');
    if ($uploaded) {
        $imagePath = $uploaded;
    }

    if ($title === '' || ($imagePath === '' && !$uploaded)) {
        set_flash('กรุณากรอกหัวข้อและเลือกไฟล์ภาพ (jpg, png, webp, gif)', 'error');
    } else {
        if ($post['id']) {
            $stmt = $pdo->prepare('UPDATE posts SET title = ?, excerpt = ?, title_en = ?, excerpt_en = ?, image_path = ?, post_date = ?, link_url = ?, sort_order = ?, is_active = ? WHERE id = ?');
            $stmt->execute([$title, $excerpt, $titleEn, $excerptEn, $imagePath, $postDate, $linkUrl, $sortOrder, $isActive, $post['id']]);
            set_flash('บันทึกโพสต์เรียบร้อยแล้ว');
        } else {
            $stmt = $pdo->prepare('INSERT INTO posts (title, excerpt, title_en, excerpt_en, image_path, post_date, link_url, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$title, $excerpt, $titleEn, $excerptEn, $imagePath, $postDate, $linkUrl, $sortOrder, $isActive]);
            set_flash('เพิ่มโพสต์เรียบร้อยแล้ว');
        }
        header('Location: /admin/posts.php');
        exit;
    }
}

$adminPageTitle = $post['id'] ? 'แก้ไขโพสต์' : 'เพิ่มโพสต์ใหม่';
require_once __DIR__ . '/includes/layout-top.php';
?>
<p><a href="/admin/posts.php">&larr; กลับไปหน้าโพสต์ประชาสัมพันธ์</a></p>

<div class="admin-card">
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <div class="form-group">
            <label>หัวข้อ</label>
            <input type="text" name="title" value="<?= h($post['title']) ?>" required>
        </div>
        <div class="form-group">
            <label>รายละเอียดย่อ</label>
            <textarea name="excerpt" rows="4"><?= h($post['excerpt']) ?></textarea>
        </div>
        <div class="form-group">
            <label>หัวข้อ (English)</label>
            <input type="text" name="title_en" value="<?= h($post['title_en']) ?>">
        </div>
        <div class="form-group">
            <label>รายละเอียดย่อ (English)</label>
            <textarea name="excerpt_en" rows="4"><?= h($post['excerpt_en']) ?></textarea>
        </div>
        <div class="form-group">
            <label>ไฟล์ภาพ<?= $post['image_path'] ? ' (อัพโหลดใหม่เพื่อแทนที่รูปเดิม)' : '' ?></label>
            <input type="file" name="image" accept="image/*" <?= $post['image_path'] ? '' : 'required' ?>>
            <?php if ($post['image_path']): ?>
            <div style="margin-top:8px;"><img class="thumb" src="<?= h($post['image_path']) ?>" alt=""></div>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label>วันที่กิจกรรม/ประกาศ</label>
            <input type="date" name="post_date" value="<?= h($post['post_date']) ?>">
        </div>
        <div class="form-group">
            <label>ลิงก์ "อ่านเพิ่มเติม" (ไม่บังคับ)</label>
            <input type="text" name="link_url" value="<?= h($post['link_url']) ?>" placeholder="https://...">
        </div>
        <div class="form-group">
            <label>ลำดับการแสดงผล</label>
            <input type="number" name="sort_order" value="<?= h($post['sort_order']) ?>">
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="is_active" value="1" <?= $post['is_active'] ? 'checked' : '' ?>> แสดงบนหน้าแรก</label>
        </div>
        <button class="btn" type="submit">บันทึก</button>
    </form>
</div>
<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
