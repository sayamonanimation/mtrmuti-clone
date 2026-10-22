-- Hub member type (alumni / student / general) and free-form portfolio
-- sections. Created automatically on first use (ensure_alumni_portfolio()
-- in includes/hub-portfolio.php).
ALTER TABLE alumni_members ADD COLUMN member_type VARCHAR(20) NOT NULL DEFAULT 'alumni';
CREATE TABLE IF NOT EXISTS alumni_portfolio_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    heading VARCHAR(255) NOT NULL,
    body TEXT,
    image_path VARCHAR(255) DEFAULT NULL,
    link_url VARCHAR(500) DEFAULT NULL,
    link_label VARCHAR(150) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    INDEX (member_id),
    FOREIGN KEY (member_id) REFERENCES alumni_members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
