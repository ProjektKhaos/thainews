ALTER TABLE user_preferences
  ADD COLUMN IF NOT EXISTS font_family VARCHAR(20) NOT NULL DEFAULT 'inter' AFTER language,
  ADD COLUMN IF NOT EXISTS font_size VARCHAR(20) NOT NULL DEFAULT 'medium' AFTER font_family;

INSERT IGNORE INTO schema_migrations(version) VALUES('004-user-typography');
