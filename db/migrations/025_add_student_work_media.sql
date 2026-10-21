-- ผลงานนักศึกษา: several photos and links per work. Created automatically
-- on first use (ensure_student_work_media() in includes/student-work-media.php),
-- which also copies each work's old single image_path / link_url in.
CREATE TABLE IF NOT EXISTS student_work_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (work_id),
    FOREIGN KEY (work_id) REFERENCES student_works(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS student_work_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_id INT NOT NULL,
    label VARCHAR(150) DEFAULT NULL,
    url VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    INDEX (work_id),
    FOREIGN KEY (work_id) REFERENCES student_works(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO student_work_photos (work_id, image_path, sort_order)
    SELECT w.id, w.image_path, 0 FROM student_works w
    WHERE w.image_path IS NOT NULL AND w.image_path <> ''
      AND NOT EXISTS (SELECT 1 FROM student_work_photos p WHERE p.work_id = w.id);
INSERT INTO student_work_links (work_id, label, url, sort_order)
    SELECT w.id, NULL, w.link_url, 0 FROM student_works w
    WHERE w.link_url IS NOT NULL AND w.link_url <> ''
      AND NOT EXISTS (SELECT 1 FROM student_work_links l WHERE l.work_id = w.id);
