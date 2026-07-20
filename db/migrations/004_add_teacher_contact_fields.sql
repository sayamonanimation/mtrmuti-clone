-- Run this only if you already imported db/schema.sql (or an earlier migration) before this change.
-- (A fresh import of schema.sql already includes these.)
SET NAMES utf8mb4;

ALTER TABLE teachers
    ADD COLUMN phone VARCHAR(50) AFTER position,
    ADD COLUMN email VARCHAR(150) AFTER phone;
