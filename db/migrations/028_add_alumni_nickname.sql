-- Optional nickname shown under the real name (auto-added by ensure_alumni_portfolio()).
ALTER TABLE alumni_members ADD COLUMN nickname VARCHAR(60) DEFAULT NULL;
