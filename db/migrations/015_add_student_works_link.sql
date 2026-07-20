-- Run this only if you already imported db/schema.sql (or an earlier migration) before this change.
-- (A fresh import of schema.sql already includes this.)
SET NAMES utf8mb4;

ALTER TABLE student_works ADD COLUMN link_url VARCHAR(255) DEFAULT NULL AFTER image_path;
