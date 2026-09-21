# Thai News

Thai News is a lightweight, server-rendered news aggregator for English-language reporting about Thailand. It collects RSS and Atom feeds from 11 publishers, groups the latest stories by source, and lets each visitor choose language, source visibility, ordering, and light or dark appearance.

Live site: [thainews.aberg.online](https://thainews.aberg.online/)

## Features

- Eleven enabled news sources with isolated fetch failures and feed fallbacks.
- Responsive source cards: three columns on desktop, two on tablets, and one on mobile.
- English, Thai, and Swedish interface languages.
- Per-visitor source ordering and visibility, with a no-JavaScript settings fallback.
- Light and dark themes.
- Text-focused article cards with compact headlines, excerpts, and localized read-more links.
- Optional cached headline translation through Google Cloud Translation.
- Idempotent database schema and source seed scripts.
- PHPUnit, Playwright, and axe accessibility tests.

The default sources are Bangkok Post, Thai PBS World, The Nation Thailand, Khaosod English, The Thaiger, Chiang Mai CityNews, Chiang Rai Times, Pattaya Mail, TAT Newsroom, The Phuket News, and ASEAN NOW.

## Requirements

- PHP 8.3 or newer
- PHP extensions: cURL, DOM/XML, Intl, JSON, Mbstring, and PDO MySQL
- MariaDB or MySQL with InnoDB and `utf8mb4`
- Composer 2
- Node.js and npm for frontend assets and browser tests
- Apache 2.4, or another web server configured with equivalent access restrictions

## Installation

Clone the repository and install dependencies:

```bash
git clone https://github.com/ProjektKhaos/thainews.git
cd thainews
composer install --no-dev --optimize-autoloader
npm ci
npm run assets
```

Create the runtime directories and allow the web/cron user to write only there:

```bash
install -d -o www-data -g www-data -m 0770 \
  storage/cache storage/locks storage/logs
```

## Configuration

Production secrets must stay outside the web root. Copy the example and edit it:

```bash
sudo install -d -o root -g www-data -m 0750 /etc/thainews
sudo install -o root -g www-data -m 0640 \
  app/config.example.php /etc/thainews/config.php
```

Set `THAI_NEWS_CONFIG_FILE=/etc/thainews/config.php` for PHP and all cron jobs. Generate independent random values of at least 32 bytes for `app_secret` and `rate_limit_secret`, and provide a least-privilege database account.

Google Translation is disabled in the example configuration. Enable it only after adding valid credentials to the external configuration file.

The supplied Apache virtual-host examples are in [`deploy/apache`](deploy/apache). Adjust domain names, paths, and certificate locations for your environment.

## Database

Create an empty database and apply the schema and seed:

```bash
mariadb -u YOUR_USER -p YOUR_DATABASE < sql/schema.sql
mariadb -u YOUR_USER -p YOUR_DATABASE < sql/seed.sql
```

Both scripts are idempotent. The seed creates or updates the 11 default sources without deleting articles or visitor preferences.

## Fetching news

Run a dry run first, then fetch and store articles:

```bash
THAI_NEWS_CONFIG_FILE=/etc/thainews/config.php php cron/fetch_news.php --dry-run
THAI_NEWS_CONFIG_FILE=/etc/thainews/config.php php cron/fetch_news.php
```

The recommended production schedule fetches every ten minutes. Optional translation runs two minutes after each fetch window. A ready-to-adapt crontab is available in [`deploy/cron/thainews`](deploy/cron/thainews).

## Development and tests

Install development dependencies and the Playwright browsers:

```bash
composer install
npm ci
npx playwright install
```

Run unit tests:

```bash
composer test
```

Database integration tests require a separate disposable database. Never point the test suite at production:

```bash
THAI_NEWS_TEST_DSN='mysql:host=127.0.0.1;dbname=thai_news_test;charset=utf8mb4' \
THAI_NEWS_TEST_USER='thai_news_test' \
THAI_NEWS_TEST_PASS='replace-me' \
composer test
```

Run browser and accessibility tests against a local or deployed instance:

```bash
THAI_NEWS_BASE_URL='http://127.0.0.1:8080' npm run test:e2e
```

## Security notes

- Keep the real configuration, credentials, logs, caches, database dumps, and release archives out of Git.
- Deny public access to `app`, `includes`, `cron`, `sql`, `storage`, `tests`, `vendor`, `docs`, `scripts`, and `deploy`.
- Serve production traffic over HTTPS and retain the supplied security headers.
- Run cron and PHP with access only to the runtime directories they need.
- Treat all publisher content as untrusted input.

## License

This project is proprietary. No open-source license is granted by this repository.
