-- Run this only if you already imported db/schema.sql (or an earlier migration) before this change.
-- (A fresh import of schema.sql already includes this.)
SET NAMES utf8mb4;

INSERT INTO pages (slug, title, subtitle, content) VALUES
('career-intro', 'แนะนำอาชีพเมื่อมาเรียนหลักสูตรเทคโนโลยีมัลติมีเดีย', NULL, '<p>แก้ไขเนื้อหาแนะนำอาชีพได้ที่หน้านี้ในระบบหลังบ้าน</p>')
ON DUPLICATE KEY UPDATE slug = slug;
