-- Teacher "identity" words that drive the themed photo effects. The site
-- adds this column automatically on first use (ensure_teacher_identity_column()).
ALTER TABLE teachers ADD COLUMN identity_tags VARCHAR(255) DEFAULT NULL;
