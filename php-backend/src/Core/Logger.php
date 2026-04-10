<?php

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    private static string $logFile = __DIR__ . '/../../storage/logs/app.log';

    public static function log(string $level, string $message, array $context = []): void
    {
        $dir = dirname(self::$logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $entry = sprintf(
            "[%s] %s: %s %s\n",
            date('c'),
            strtoupper($level),
            $message,
            $context ? json_encode($context) : ''
        );

        file_put_contents(self::$logFile, $entry, FILE_APPEND);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }
}
