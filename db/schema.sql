-- MTRMUTI Clone - Database schema
-- Import this file into MySQL (e.g. via phpMyAdmin on CloudRambo cPanel)

-- Force a UTF-8 client connection for this import, regardless of the
-- server/CLI's default charset (a latin1 default is common and silently
-- mangles Thai text into mojibake on import otherwise).
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per site page. `content` holds the rich-text HTML body editable in admin.
CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) DEFAULT NULL,
    content LONGTEXT,
    title_en VARCHAR(255) DEFAULT NULL,
    subtitle_en VARCHAR(255) DEFAULT NULL,
    content_en LONGTEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Homepage hero slider
CREATE TABLE IF NOT EXISTS sliders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_path VARCHAR(255) NOT NULL,
    caption VARCHAR(255) DEFAULT NULL,
    subtitle VARCHAR(255) DEFAULT NULL,
    caption_en VARCHAR(255) DEFAULT NULL,
    subtitle_en VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PR / activity announcement posts shown as a carousel on the homepage
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    excerpt TEXT,
    image_path VARCHAR(255) NOT NULL,
    post_date DATE DEFAULT NULL,
    link_url VARCHAR(255) DEFAULT NULL,
    title_en VARCHAR(255) DEFAULT NULL,
    excerpt_en TEXT,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Yearly admissions statistics shown on homepage
CREATE TABLE IF NOT EXISTS stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year VARCHAR(10) NOT NULL,
    label VARCHAR(100) DEFAULT 'นักศึกษาใหม่',
    count INT NOT NULL DEFAULT 0,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6 fields of study shown on homepage
CREATE TABLE IF NOT EXISTS fields_of_study (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    icon_path VARCHAR(255) DEFAULT NULL,
    name_en VARCHAR(150) DEFAULT NULL,
    description_en TEXT,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    position VARCHAR(150) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    photo_path VARCHAR(255) DEFAULT NULL,
    bio TEXT,                         -- ประวัติ
    education TEXT,                   -- วุฒิการศึกษา
    subjects_taught TEXT,             -- วิชาที่สอน
    research_works TEXT,              -- ผลงานวิจัย
    creative_works TEXT,              -- ผลงานสร้างสรรค์
    innovation_works TEXT,            -- ผลงานนวัตกรรม
    competition_works TEXT,           -- ผลงานประกวด
    intellectual_property TEXT,       -- ทรัพย์สินทางปัญญา
    copyrights_patents TEXT,          -- ลิขสิทธิ์/สิทธิบัตร
    awards TEXT,                      -- รางวัลที่ได้รับ
    position_en VARCHAR(150) DEFAULT NULL,
    bio_en TEXT,
    education_en TEXT,
    subjects_taught_en TEXT,
    research_works_en TEXT,
    creative_works_en TEXT,
    innovation_works_en TEXT,
    competition_works_en TEXT,
    intellectual_property_en TEXT,
    copyrights_patents_en TEXT,
    awards_en TEXT,
    classroom_online_url VARCHAR(255),-- ลิงก์ Classroom Online ของอาจารย์ท่านนี้
    sort_order INT DEFAULT 0,
    identity_tags VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Photo evidence attached to a teacher's work items (research, creative,
-- innovation, competition works, and intellectual property). A teacher can
-- have any number of images per category.
CREATE TABLE IF NOT EXISTS teacher_portfolio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    caption VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    INDEX idx_teacher_category (teacher_id, category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_path VARCHAR(255) NOT NULL,
    caption VARCHAR(255) DEFAULT NULL,
    category VARCHAR(100) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Alumni class/generation (รุ่น), e.g. "รุ่นที่ 1 (ปีการศึกษา 2560)"
CREATE TABLE IF NOT EXISTS alumni_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Group photos attached to an alumni batch. A batch can have any number of photos.
CREATE TABLE IF NOT EXISTS alumni_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    caption VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES alumni_batches(id) ON DELETE CASCADE,
    INDEX idx_batch (batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Current-student class/batch (ชั้นปี/รุ่น), e.g. "ชั้นปีที่ 1 (รหัส 69)"
CREATE TABLE IF NOT EXISTS student_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Group photos attached to a current-student batch. A batch can have any number of photos.
CREATE TABLE IF NOT EXISTS student_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    caption VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES student_batches(id) ON DELETE CASCADE,
    INDEX idx_batch (batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- External/internal links shown in the "บริการนักศึกษา" nav dropdown (e.g. equipment loan system)
CREATE TABLE IF NOT EXISTS student_service_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(150) NOT NULL,
    label_en VARCHAR(150) DEFAULT NULL,
    url VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Academic / activity calendar entries shown under the "ตารางเวลา" menu
-- Links to external calendar pages/sites shown under "ตารางเวลา" (academic / activity calendar)
CREATE TABLE IF NOT EXISTS calendar_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calendar_type ENUM('academic', 'activity') NOT NULL,
    label VARCHAR(150) NOT NULL,
    label_en VARCHAR(150) DEFAULT NULL,
    url VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Current student headcount by year level (1-4), one row per academic year, for the homepage chart
CREATE TABLE IF NOT EXISTS student_year_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(10) NOT NULL UNIQUE,
    year1_count INT DEFAULT 0,
    year2_count INT DEFAULT 0,
    year3_count INT DEFAULT 0,
    year4_count INT DEFAULT 0,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student work / portfolio pieces, grouped by academic year ("ผลงานนักศึกษา")
CREATE TABLE IF NOT EXISTS student_work_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_works (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    title_en VARCHAR(255) DEFAULT NULL,
    description TEXT,
    description_en TEXT,
    student_name VARCHAR(150) DEFAULT NULL,
    image_path VARCHAR(255) NOT NULL,
    link_url VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES student_work_batches(id) ON DELETE CASCADE,
    INDEX idx_batch (batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Links shown in the "สำหรับอาจารย์" nav dropdown (e.g. OpenDrive)
CREATE TABLE IF NOT EXISTS teacher_service_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(150) NOT NULL,
    label_en VARCHAR(150) DEFAULT NULL,
    url VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Classrooms / labs shown on the "ห้องเรียน" page
CREATE TABLE IF NOT EXISTS classrooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    name_en VARCHAR(150) DEFAULT NULL,
    description TEXT,
    description_en TEXT,
    image_path VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Up to 6 photos per classroom; classrooms.image_path mirrors the first one.
CREATE TABLE IF NOT EXISTS classroom_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    classroom_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (classroom_id),
    FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Site navigation: main menu items (parent_id NULL) and their submenus.
-- This is the single source of truth for the header's nav bar, fully
-- managed via /admin/nav-menu.php.
CREATE TABLE IF NOT EXISTS nav_menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT DEFAULT NULL,
    label VARCHAR(150) NOT NULL,
    label_en VARCHAR(150) DEFAULT NULL,
    url VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (parent_id) REFERENCES nav_menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed pages (one per site page, admin can edit content for each)
INSERT INTO pages (slug, title, subtitle, content) VALUES
('home', 'หน้าหลัก', 'สาขาวิชาเทคโนโลยีมัลติมีเดีย', '<p>ยินดีต้อนรับสู่เว็บไซต์สาขาวิชาเทคโนโลยีมัลติมีเดีย</p>'),
('course', 'หลักสูตรปริญญาตรี', NULL, '<p>รายละเอียดหลักสูตรปริญญาตรี</p>'),
('history', 'ประวัติหลักสูตร', NULL, '<p>ประวัติความเป็นมาของหลักสูตร</p>'),
('subjects', 'รายวิชาที่เปิดสอน', NULL, '<p>รายวิชาที่เปิดสอนในแต่ละภาคการศึกษา</p>'),
('teachers', 'อาจารย์', NULL, '<p>ทำเนียบคณาจารย์ประจำสาขาวิชา</p>'),
('classroom', 'ห้องเรียน', NULL, '<p>ข้อมูลห้องเรียนและห้องปฏิบัติการ</p>'),
('students', 'นักศึกษาปัจจุบัน', NULL, '<p>ข้อมูลสำหรับนักศึกษาปัจจุบัน</p>'),
('alumni', 'ศิษย์เก่า', NULL, '<p>ทำเนียบศิษย์เก่า</p>'),
('student-services', 'บริการนักศึกษา', NULL, '<p>บริการต่าง ๆ สำหรับนักศึกษา</p>'),
('classroom-online', 'Classroom Online', NULL, '<p>ลิงก์และข้อมูลการเรียนออนไลน์</p>'),
('contact', 'ติดต่อเรา', NULL, '<p>ช่องทางการติดต่อหลักสูตร</p>'),
('career-intro', 'แนะนำอาชีพเมื่อมาเรียนหลักสูตรเทคโนโลยีมัลติมีเดีย', NULL, '<p>แก้ไขเนื้อหาแนะนำอาชีพได้ที่หน้านี้ในระบบหลังบ้าน</p>')
ON DUPLICATE KEY UPDATE slug = slug;

INSERT INTO student_service_links (label, label_en, url, sort_order) VALUES
('ระบบยืม-คืนครุภัณฑ์', 'Equipment Loan System', '#', 0);

INSERT INTO teacher_service_links (label, label_en, url, sort_order) VALUES
('OpenDrive', 'OpenDrive', '#', 0);

-- Default site navigation. Relies on this being a fresh, empty table so the
-- auto-increment IDs come out as 1, 2, 3... in this exact insertion order,
-- which the child rows below reference directly via parent_id.
INSERT INTO nav_menu_items (parent_id, label, label_en, url, sort_order) VALUES
(NULL, 'หน้าหลัก', 'Home', '/index.php', 0),
(NULL, 'เกี่ยวกับหลักสูตร', 'About the Program', '#', 1),
(2, 'หลักสูตรปริญญาตรี', 'Bachelor''s Program', '/course.php', 0),
(2, 'ประวัติ', 'History', '/history.php', 1),
(2, 'รายวิชาที่เปิดสอน', 'Courses Offered', '/subjects.php', 2),
(2, 'อาจารย์', 'Faculty', '/teachers.php', 3),
(2, 'ห้องเรียน', 'Classroom', '/classroom.php', 4),
(NULL, 'ฐานข้อมูลนักศึกษา', 'Student Database', '#', 2),
(8, 'นักศึกษาปัจจุบัน', 'Current Students', '/students.php', 0),
(8, 'ศิษย์เก่า', 'Alumni', '/alumni.php', 1),
(NULL, 'ตารางเวลา', 'Schedule', '#', 3),
(11, 'ปฏิทินการศึกษา', 'Academic Calendar', '/academic-calendar.php', 0),
(11, 'ปฏิทินกิจกรรม', 'Activity Calendar', '/activity-calendar.php', 1),
(NULL, 'บริการนักศึกษา', 'Student Services', '#', 4),
(14, 'ข้อมูลทั่วไป', 'General Information', '/student-services.php', 0),
(14, 'ระบบยืม-คืนครุภัณฑ์', 'Equipment Loan System', '#', 1),
(NULL, 'ผลงานนักศึกษา', 'Student Works', '/student-works.php', 5),
(NULL, 'สำหรับอาจารย์', 'For Faculty', '#', 6),
(18, 'OpenDrive', 'OpenDrive', '#', 0),
(NULL, 'ติดต่อเรา', 'Contact Us', '/contact.php', 7);

INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_name', 'สาขาวิชาเทคโนโลยีมัลติมีเดีย'),
('address', 'ที่อยู่มหาวิทยาลัย'),
('phone', '0-0000-0000'),
('email', 'info@example.ac.th'),
('facebook_url', ''),
('logo_path', '')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- No default admin user is seeded here on purpose (a hard-coded password hash
-- in a SQL file is a security smell). After importing this schema, open
-- /admin/install.php once in your browser to create the first admin account,
-- then delete that file. See README.md for the full setup steps.
