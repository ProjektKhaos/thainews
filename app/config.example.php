<?php
// Senast uppdaterad: 2026-09-20 18:05 | Copy OUTSIDE DocumentRoot and point THAI_NEWS_CONFIG_FILE to it.

declare(strict_types=1);

return [
    'env' => 'production',
    'public_origin' => 'https://thainews.aberg.online',
    'base_url' => '',
    'timezone' => 'Asia/Bangkok',
    'default_language' => 'en',
    'supported_languages' => ['en', 'th', 'sv'],
    'asset_version' => '1.0.26',
    'app_secret' => 'CHANGE_TO_AT_LEAST_32_RANDOM_BYTES',
    'rate_limit_secret' => 'CHANGE_TO_A_DIFFERENT_32_BYTE_SECRET',
    'db' => [
        'dsn' => 'mysql:host=127.0.0.1;dbname=thai_news;charset=utf8mb4',
        'user' => 'thai_news',
        'pass' => 'CHANGE_ME',
    ],
    'fetch' => [
        'user_agent' => 'ThaiNewsAggregator/1.0 (+https://thainews.aberg.online/)',
        'connect_timeout' => 5,
        'timeout' => 20,
        'max_bytes' => 2097152,
    ],
    'translation' => [
        'enabled' => false,
        'provider' => 'google_cloud_v2',
        'api_key' => 'CHANGE_TO_GOOGLE_CLOUD_TRANSLATION_API_KEY',
        'connect_timeout' => 5,
        'timeout' => 30,
        'batch_limit' => 50,
    ],
    // Omit storage to use <project_root>/storage. Override only with explicit absolute paths.
];
