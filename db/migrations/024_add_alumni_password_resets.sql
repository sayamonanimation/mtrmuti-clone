-- One-time password reset links for alumni hub accounts. Created
-- automatically on first use (ensure_alumni_password_resets() in
-- includes/alumni-hub.php). Only a SHA-256 hash of each token is stored.
CREATE TABLE IF NOT EXISTS alumni_password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    INDEX (token_hash),
    INDEX (member_id),
    FOREIGN KEY (member_id) REFERENCES alumni_members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
