-- Run this only if you already imported db/schema.sql (or an earlier migration) before this change.
-- (A fresh import of schema.sql already includes this.)
--
-- The academic/activity calendar pages changed from date-based events to a
-- simple admin-managed list of external links, so calendar_events (added in
-- migration 010) is replaced with calendar_links here.
SET NAMES utf8mb4;

DROP TABLE IF EXISTS calendar_events;

CREATE TABLE IF NOT EXISTS calendar_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calendar_type ENUM('academic', 'activity') NOT NULL,
    label VARCHAR(150) NOT NULL,
    label_en VARCHAR(150) DEFAULT NULL,
    url VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
