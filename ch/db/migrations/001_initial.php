<?php
// Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok)
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
return <<<'SQL'
CREATE TABLE IF NOT EXISTS schema_migrations(version INTEGER PRIMARY KEY, applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS settings(key TEXT PRIMARY KEY, value TEXT NOT NULL);
INSERT OR IGNORE INTO settings(key,value) VALUES
 ('booking_public_enabled','0'),('rule_version','1'),('max_guests','0'),('min_nights','1'),('max_nights','90'),('included_guests','1'),('discount_stacking','0');
CREATE TABLE IF NOT EXISTS admin_users(
 id INTEGER PRIMARY KEY, username TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL,
 active INTEGER NOT NULL DEFAULT 1 CHECK(active IN (0,1)), role TEXT NOT NULL DEFAULT 'admin',
 failed_count INTEGER NOT NULL DEFAULT 0, locked_until TEXT, session_version INTEGER NOT NULL DEFAULT 1,
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS rate_rules(
 id INTEGER PRIMARY KEY, label TEXT NOT NULL, start_date TEXT NOT NULL, end_date TEXT NOT NULL,
 weekday INTEGER CHECK(weekday BETWEEN 1 AND 7), amount_satang INTEGER NOT NULL CHECK(amount_satang BETWEEN 0 AND 1000000000),
 priority INTEGER NOT NULL DEFAULT 0, active INTEGER NOT NULL DEFAULT 0 CHECK(active IN (0,1)),
 CHECK(start_date<end_date)
);
CREATE TABLE IF NOT EXISTS discount_rules(
 id INTEGER PRIMARY KEY, label TEXT NOT NULL, family TEXT NOT NULL, min_nights INTEGER NOT NULL CHECK(min_nights>0),
 max_nights INTEGER, kind TEXT NOT NULL CHECK(kind IN ('percent','fixed')),
 value INTEGER NOT NULL CHECK(value BETWEEN 0 AND 100000000000), priority INTEGER NOT NULL DEFAULT 0,
 start_date TEXT, end_date TEXT, code TEXT, min_advance_days INTEGER, max_advance_days INTEGER,
 active INTEGER NOT NULL DEFAULT 0 CHECK(active IN (0,1)),
 CHECK(max_nights IS NULL OR max_nights>=min_nights), CHECK(kind!='percent' OR value<=10000),
 CHECK(min_advance_days IS NULL OR min_advance_days>=0), CHECK(max_advance_days IS NULL OR max_advance_days>=0)
);
CREATE TABLE IF NOT EXISTS fee_rules(
 id INTEGER PRIMARY KEY, label TEXT NOT NULL, kind TEXT NOT NULL DEFAULT 'fixed' CHECK(kind IN ('fixed','percent')),
 amount_satang INTEGER NOT NULL DEFAULT 0 CHECK(amount_satang BETWEEN 0 AND 1000000000),
 percent_bps INTEGER NOT NULL DEFAULT 0 CHECK(percent_bps BETWEEN 0 AND 10000),
 basis TEXT NOT NULL DEFAULT 'nights_after_discount' CHECK(basis IN ('nights_after_discount','subtotal_plus_fees')),
 per_night INTEGER NOT NULL DEFAULT 0, per_extra_guest INTEGER NOT NULL DEFAULT 0,
 active INTEGER NOT NULL DEFAULT 0 CHECK(active IN (0,1)),
 CHECK(kind='fixed' OR (per_night=0 AND per_extra_guest=0))
);
CREATE TABLE IF NOT EXISTS availability_blocks(
 id INTEGER PRIMARY KEY, check_in TEXT NOT NULL, check_out TEXT NOT NULL, reason TEXT NOT NULL DEFAULT '',
 active INTEGER NOT NULL DEFAULT 1 CHECK(active IN (0,1)), created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CHECK(check_in<check_out)
);
CREATE INDEX IF NOT EXISTS blocks_dates ON availability_blocks(active,check_in,check_out);
CREATE TABLE IF NOT EXISTS quotes(
 id INTEGER PRIMARY KEY, token TEXT NOT NULL UNIQUE, check_in TEXT NOT NULL, check_out TEXT NOT NULL,
 guests INTEGER NOT NULL CHECK(guests>0), code TEXT, snapshot_json TEXT NOT NULL,
 total_satang INTEGER NOT NULL CHECK(total_satang>=0), rule_version INTEGER NOT NULL,
 expires_at TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CHECK(check_in<check_out)
);
CREATE TABLE IF NOT EXISTS bookings(
 id INTEGER PRIMARY KEY, reference TEXT NOT NULL UNIQUE, idempotency_key TEXT NOT NULL UNIQUE,
 quote_id INTEGER REFERENCES quotes(id), name TEXT NOT NULL, email TEXT NOT NULL, phone TEXT NOT NULL DEFAULT '',
 message TEXT NOT NULL DEFAULT '', language TEXT NOT NULL CHECK(language IN ('en','sv','th')),
 check_in TEXT NOT NULL, check_out TEXT NOT NULL, guests INTEGER NOT NULL CHECK(guests>0),
 status TEXT NOT NULL CHECK(status IN ('inquiry','hold','confirmed','rejected','cancelled','expired')),
 payment_status TEXT NOT NULL DEFAULT 'unpaid' CHECK(payment_status IN ('unpaid','payment_instructions_sent','paid_manual','refunded_manual')),
 expires_at TEXT, total_satang INTEGER NOT NULL CHECK(total_satang>=0), price_snapshot_json TEXT NOT NULL,
 policy_version TEXT, consent_at TEXT,
 internal_note TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, CHECK(check_in<check_out)
);
CREATE INDEX IF NOT EXISTS bookings_dates_status ON bookings(status,check_in,check_out);
CREATE TABLE IF NOT EXISTS booking_revisions(
 id INTEGER PRIMARY KEY, booking_id INTEGER NOT NULL REFERENCES bookings(id), actor TEXT NOT NULL,
 before_json TEXT NOT NULL, after_json TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS audit_log(
 id INTEGER PRIMARY KEY, booking_id INTEGER REFERENCES bookings(id), actor TEXT NOT NULL,
 action TEXT NOT NULL, status_from TEXT, status_to TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS notification_outbox(
 id INTEGER PRIMARY KEY, event_key TEXT NOT NULL UNIQUE, kind TEXT NOT NULL, language TEXT NOT NULL,
 payload_json TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending','sent','failed','simulated')),
 attempts INTEGER NOT NULL DEFAULT 0, next_attempt_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
 sent_at TEXT, last_error TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS rate_limits(
 key TEXT PRIMARY KEY, window_start INTEGER NOT NULL, attempts INTEGER NOT NULL
);
SQL;
