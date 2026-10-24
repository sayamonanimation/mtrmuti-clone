-- Teacher research / creative works by level, and several Classroom Online
-- links per teacher. Applied automatically by ensure_teacher_works_levels().
ALTER TABLE teachers ADD COLUMN research_works_intl TEXT;
ALTER TABLE teachers ADD COLUMN creative_works_intl TEXT;
CREATE TABLE IF NOT EXISTS teacher_classroom_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    label VARCHAR(150) DEFAULT NULL,
    url VARCHAR(500) NOT NULL,
    sort_order INT DEFAULT 0,
    INDEX (teacher_id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO teacher_classroom_links (teacher_id, label, url, sort_order)
    SELECT t.id, NULL, t.classroom_online_url, 0 FROM teachers t
    WHERE t.classroom_online_url IS NOT NULL AND t.classroom_online_url <> ''
      AND NOT EXISTS (SELECT 1 FROM teacher_classroom_links c WHERE c.teacher_id = t.id);
