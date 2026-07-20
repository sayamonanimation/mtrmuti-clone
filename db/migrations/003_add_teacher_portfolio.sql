-- Run this only if you already imported db/schema.sql (or migration 002) before this change.
-- (A fresh import of schema.sql already includes these.)
SET NAMES utf8mb4;

ALTER TABLE teachers
    ADD COLUMN innovation_works TEXT AFTER creative_works,
    ADD COLUMN competition_works TEXT AFTER innovation_works;

CREATE TABLE IF NOT EXISTS teacher_portfolio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    caption VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    INDEX idx_teacher_category (teacher_id, category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
