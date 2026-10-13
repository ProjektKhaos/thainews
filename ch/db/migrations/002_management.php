<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
return <<<'SQL'
CREATE TABLE IF NOT EXISTS cms_content (
 scope TEXT NOT NULL, key TEXT NOT NULL, draft TEXT NOT NULL, published TEXT,
 updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, published_at TEXT,
 PRIMARY KEY(scope,key)
);
CREATE TABLE IF NOT EXISTS media (
 id INTEGER PRIMARY KEY, filename TEXT NOT NULL UNIQUE, mime TEXT NOT NULL,
 width INTEGER NOT NULL, height INTEGER NOT NULL, label TEXT NOT NULL,
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS admin_setup (
 token_hash TEXT PRIMARY KEY, expires_at TEXT NOT NULL, used_at TEXT
);
CREATE TABLE IF NOT EXISTS gallery_photos (
 photo_key TEXT PRIMARY KEY, label TEXT NOT NULL, category TEXT NOT NULL,
 position INTEGER NOT NULL, visible INTEGER NOT NULL DEFAULT 1 CHECK(visible IN (0,1))
);
INSERT OR IGNORE INTO settings(key,value) VALUES
 ('mail_mode','log'),('mail_enabled','0'),('smtp_port','587'),('smtp_security','starttls'),
 ('backup_retention_days','30'),('public_language','en');
SQL;
