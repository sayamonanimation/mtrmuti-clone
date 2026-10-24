-- Cohort number and student code for the "รุ่น 20 รหัส 69" label (auto-added by ensure_batch_code_columns()).
ALTER TABLE student_batches ADD COLUMN batch_no VARCHAR(20) DEFAULT NULL;
ALTER TABLE alumni_batches ADD COLUMN batch_no VARCHAR(20) DEFAULT NULL;
ALTER TABLE alumni_batches ADD COLUMN student_code VARCHAR(10) DEFAULT NULL;
