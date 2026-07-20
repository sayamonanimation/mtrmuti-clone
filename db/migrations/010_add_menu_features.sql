-- Run this only if you already imported db/schema.sql (or an earlier migration) before this change.
-- (A fresh import of schema.sql already includes these.)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS student_service_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(150) NOT NULL,
    label_en VARCHAR(150) DEFAULT NULL,
    url VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS calendar_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calendar_type ENUM('academic', 'activity') NOT NULL,
    title VARCHAR(255) NOT NULL,
    title_en VARCHAR(255) DEFAULT NULL,
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    note TEXT,
    note_en TEXT,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_year_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(10) NOT NULL UNIQUE,
    year1_count INT DEFAULT 0,
    year2_count INT DEFAULT 0,
    year3_count INT DEFAULT 0,
    year4_count INT DEFAULT 0,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
