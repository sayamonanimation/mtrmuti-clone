-- Outstanding alumni (outstanding-alumni.php). Created automatically on
-- first use together with its page and a menu item under ศิษย์เก่า
-- (ensure_outstanding_alumni()).
CREATE TABLE IF NOT EXISTS outstanding_alumni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    photo_path VARCHAR(255) DEFAULT NULL,
    batch_name VARCHAR(100) DEFAULT NULL,
    graduation_year INT DEFAULT NULL,
    position VARCHAR(255) DEFAULT NULL,
    workplace VARCHAR(255) DEFAULT NULL,
    achievement TEXT,
    quote TEXT,
    link_url VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
