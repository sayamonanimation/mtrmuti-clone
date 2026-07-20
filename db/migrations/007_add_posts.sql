-- Run this only if you already imported db/schema.sql (or an earlier migration) before this change.
-- (A fresh import of schema.sql already includes this.)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    excerpt TEXT,
    image_path VARCHAR(255) NOT NULL,
    post_date DATE DEFAULT NULL,
    link_url VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
