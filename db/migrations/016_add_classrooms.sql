-- Run this only if you already imported db/schema.sql (or an earlier migration) before this change.
-- (A fresh import of schema.sql already includes this.)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS classrooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    name_en VARCHAR(150) DEFAULT NULL,
    description TEXT,
    description_en TEXT,
    image_path VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
