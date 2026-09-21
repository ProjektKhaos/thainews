<?php
// Senast uppdaterad: 2026-09-20 18:05 | PDO wrapper

declare(strict_types=1);

namespace ThaiNews;

use PDO;

final class Database
{
    private PDO $pdo;

    public function __construct(Config $config)
    {
        $this->pdo = new PDO(
            $config->requireString('db.dsn'),
            $config->requireString('db.user'),
            $config->requireString('db.pass'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        $this->pdo->exec("SET time_zone = '+00:00'");
        $this->pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    public function pdo(): PDO { return $this->pdo; }
}
