-- Alumni self-service portfolio hub (ติดต่องานศิษย์เก่า). Created
-- automatically on first use with its page and a menu item under ศิษย์เก่า
-- (ensure_alumni_hub() in includes/alumni-hub.php).
CREATE TABLE IF NOT EXISTS alumni_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    batch_name VARCHAR(100) DEFAULT NULL,
    graduation_year INT DEFAULT NULL,
    photo_path VARCHAR(255) DEFAULT NULL,
    headline VARCHAR(255) DEFAULT NULL,
    skills VARCHAR(500) DEFAULT NULL,
    bio TEXT,
    contact_email VARCHAR(190) DEFAULT NULL,
    contact_phone VARCHAR(50) DEFAULT NULL,
    line_id VARCHAR(100) DEFAULT NULL,
    website_url VARCHAR(255) DEFAULT NULL,
    available_for_work TINYINT(1) NOT NULL DEFAULT 1,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    admin_note VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS alumni_works (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_path VARCHAR(255) DEFAULT NULL,
    link_url VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (member_id),
    FOREIGN KEY (member_id) REFERENCES alumni_members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
