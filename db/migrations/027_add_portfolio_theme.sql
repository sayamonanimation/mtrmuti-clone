-- Portfolio look chosen by the owner (red, cyber, gold, minimal). Added
-- automatically on first use (ensure_alumni_portfolio() in includes/hub-portfolio.php).
ALTER TABLE alumni_members ADD COLUMN portfolio_theme VARCHAR(20) NOT NULL DEFAULT 'red';
