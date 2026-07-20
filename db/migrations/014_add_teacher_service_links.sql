-- Run this only if you already imported db/schema.sql (or an earlier migration) before this change.
-- (A fresh import of schema.sql already includes these.)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS teacher_service_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(150) NOT NULL,
    label_en VARCHAR(150) DEFAULT NULL,
    url VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO teacher_service_links (label, label_en, url, sort_order) VALUES
('OpenDrive', 'OpenDrive', '#', 0);
