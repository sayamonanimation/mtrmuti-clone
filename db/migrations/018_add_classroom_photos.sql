-- Up to 6 photos per classroom (admin/classrooms.php). The site also creates
-- this table automatically on first use (ensure_classroom_photos_table()),
-- so running this file by hand is optional.
CREATE TABLE IF NOT EXISTS classroom_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    classroom_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (classroom_id),
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO classroom_photos (classroom_id, image_path, sort_order)
SELECT c.id, c.image_path, 0 FROM classrooms c
WHERE c.image_path IS NOT NULL AND c.image_path <> ''
  AND NOT EXISTS (SELECT 1 FROM classroom_photos p WHERE p.classroom_id = c.id);
