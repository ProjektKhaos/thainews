-- Thai News schema v1 — idempotent, MariaDB/MySQL
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS schema_migrations (
  version VARCHAR(64) PRIMARY KEY,
  installed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_sources (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE,
  name VARCHAR(200) NOT NULL,
  website_url VARCHAR(1000) NOT NULL,
  feed_url VARCHAR(1000) NOT NULL,
  adapter_type VARCHAR(80) NOT NULL,
  source_language VARCHAR(10) NOT NULL DEFAULT 'en',
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  default_order INT NOT NULL DEFAULT 100,
  adapter_config JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_sources_enabled_order(enabled, default_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS articles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_id BIGINT UNSIGNED NOT NULL,
  external_id VARCHAR(255) NOT NULL,
  canonical_url TEXT NOT NULL,
  url_hash CHAR(64) NOT NULL,
  title VARCHAR(1000) NOT NULL,
  excerpt TEXT NOT NULL,
  image_url TEXT NULL,
  published_at DATETIME NOT NULL,
  first_seen_at DATETIME NOT NULL,
  last_seen_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  CONSTRAINT fk_articles_source FOREIGN KEY(source_id) REFERENCES news_sources(id) ON DELETE CASCADE,
  UNIQUE KEY uq_articles_external(source_id, external_id),
  UNIQUE KEY uq_articles_urlhash(source_id, url_hash),
  INDEX idx_articles_source_published(source_id, published_at DESC),
  INDEX idx_articles_published(published_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS article_translations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  article_id BIGINT UNSIGNED NOT NULL,
  language VARCHAR(10) NOT NULL,
  translated_title VARCHAR(1000) NULL,
  source_text_hash CHAR(64) NOT NULL,
  provider VARCHAR(80) NOT NULL,
  status ENUM('success','failed') NOT NULL,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  last_error_code VARCHAR(64) NULL,
  next_attempt_at DATETIME NULL,
  translated_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  CONSTRAINT fk_translations_article FOREIGN KEY(article_id) REFERENCES articles(id) ON DELETE CASCADE,
  UNIQUE KEY uq_article_translation(article_id, language),
  INDEX idx_translation_retry(language, status, next_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS translation_memory (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_language VARCHAR(10) NOT NULL,
  target_language VARCHAR(10) NOT NULL,
  source_text_hash CHAR(64) NOT NULL,
  source_text VARCHAR(1000) NOT NULL,
  translated_text VARCHAR(1000) NOT NULL,
  provider VARCHAR(80) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_translation_memory(source_language, target_language, source_text_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fetch_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  started_at DATETIME NOT NULL,
  finished_at DATETIME NULL,
  status ENUM('running','success','partial','failed') NOT NULL,
  received_count INT NOT NULL DEFAULT 0,
  inserted_count INT NOT NULL DEFAULT 0,
  updated_count INT NOT NULL DEFAULT 0,
  rejected_count INT NOT NULL DEFAULT 0,
  error_code VARCHAR(64) NULL,
  INDEX idx_fetch_started(started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS source_fetch_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fetch_run_id BIGINT UNSIGNED NOT NULL,
  source_id BIGINT UNSIGNED NOT NULL,
  started_at DATETIME NOT NULL,
  finished_at DATETIME NULL,
  status ENUM('running','success','failed') NOT NULL,
  received_count INT NOT NULL DEFAULT 0,
  inserted_count INT NOT NULL DEFAULT 0,
  updated_count INT NOT NULL DEFAULT 0,
  rejected_count INT NOT NULL DEFAULT 0,
  error_code VARCHAR(64) NULL,
  CONSTRAINT fk_sfr_run FOREIGN KEY(fetch_run_id) REFERENCES fetch_runs(id) ON DELETE CASCADE,
  CONSTRAINT fk_sfr_source FOREIGN KEY(source_id) REFERENCES news_sources(id) ON DELETE CASCADE,
  INDEX idx_sfr_run(fetch_run_id), INDEX idx_sfr_source(source_id, started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_preferences (
  visitor_hash CHAR(64) PRIMARY KEY,
  language VARCHAR(10) NOT NULL DEFAULT 'en',
  font_family VARCHAR(20) NOT NULL DEFAULT 'inter',
  font_size VARCHAR(20) NOT NULL DEFAULT 'medium',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  last_seen_at DATETIME NOT NULL,
  INDEX idx_pref_last_seen(last_seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_source_preferences (
  visitor_hash CHAR(64) NOT NULL,
  source_id BIGINT UNSIGNED NOT NULL,
  position INT NOT NULL,
  visible TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY(visitor_hash, source_id),
  CONSTRAINT fk_usp_source FOREIGN KEY(source_id) REFERENCES news_sources(id) ON DELETE CASCADE,
  INDEX idx_usp_order(visitor_hash, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_rate_limits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  route VARCHAR(100) NOT NULL,
  client_key_hash CHAR(64) NOT NULL,
  window_start DATETIME NOT NULL,
  request_count INT UNSIGNED NOT NULL DEFAULT 1,
  expires_at DATETIME NOT NULL,
  UNIQUE KEY uq_rate(route, client_key_hash, window_start),
  INDEX idx_rate_expiry(expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations(version) VALUES('001-initial');
INSERT IGNORE INTO schema_migrations(version) VALUES('002-article-translations');
INSERT IGNORE INTO schema_migrations(version) VALUES('003-translation-memory');
INSERT IGNORE INTO schema_migrations(version) VALUES('004-user-typography');
