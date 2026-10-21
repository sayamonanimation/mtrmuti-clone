-- Activity photo albums (max 80 photos each, enforced in admin/gallery.php).
-- The site creates this automatically on first use (ensure_gallery_albums()).
CREATE TABLE IF NOT EXISTS gallery_albums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    title_en VARCHAR(255) DEFAULT NULL,
    description TEXT,
    event_date DATE DEFAULT NULL,
    cover_path VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE gallery ADD COLUMN album_id INT DEFAULT NULL;
