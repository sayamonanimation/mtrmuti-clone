-- Cohort years: current-student cohorts move to alumni automatically once
-- entry_year + course_years is reached. Added automatically on first use
-- (ensure_batch_year_columns()).
ALTER TABLE student_batches ADD COLUMN entry_year INT DEFAULT NULL;
ALTER TABLE student_batches ADD COLUMN course_years INT NOT NULL DEFAULT 4;
ALTER TABLE alumni_batches ADD COLUMN graduation_year INT DEFAULT NULL;
