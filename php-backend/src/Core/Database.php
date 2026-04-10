<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function initialize(array $config): void
    {
        if (self::$connection !== null) {
            return;
        }

        try {
            if (($config['driver'] ?? 'sqlite') === 'sqlite') {
                $sqlitePath = $config['sqlite_path'];
                $dir = dirname($sqlitePath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                self::$connection = new PDO('sqlite:' . $sqlitePath);
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $config['host'],
                    $config['port'],
                    $config['database']
                );
                self::$connection = new PDO($dsn, $config['username'], $config['password']);
            }

            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            throw new RuntimeException('Database connection failed: ' . $exception->getMessage());
        }
    }

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            throw new RuntimeException('Database is not initialized.');
        }

        return self::$connection;
    }
}
