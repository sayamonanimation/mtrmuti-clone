-- Portfolio presentation layout (classic, cinema, code, studio, production); auto-added by ensure_alumni_portfolio().
ALTER TABLE alumni_members ADD COLUMN portfolio_layout VARCHAR(20) NOT NULL DEFAULT 'classic';
