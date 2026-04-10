<?php

declare(strict_types=1);

use App\Core\Database;

define('SKIP_DB_INIT', true);
require_once __DIR__ . '/../src/bootstrap.php';

$isFresh = in_array('--fresh', $argv ?? [], true);

if (($config['db']['driver'] ?? 'sqlite') === 'sqlite' && $isFresh) {
    $sqlitePath = $config['db']['sqlite_path'];
    if (file_exists($sqlitePath)) {
        unlink($sqlitePath);
    }

}

Database::initialize($config['db']);
$db = Database::connection();
$sql = file_get_contents(__DIR__ . '/../database/schema.sql');
if ($sql === false) {
    throw new RuntimeException('Could not read schema.sql');
}

try {
    $db->exec($sql);
    // Idempotent: ensures contact table exists if schema.sql was partially applied earlier
    $db->exec(
        'CREATE TABLE IF NOT EXISTS contact_submissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT NULL,
            message TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );'
    );
    $db->exec('CREATE INDEX IF NOT EXISTS idx_contact_submissions_created_at ON contact_submissions(created_at);');
    echo $isFresh ? "Migration complete (fresh).\n" : "Migration complete.\n";
} catch (Throwable $e) {
    if (($config['db']['driver'] ?? 'sqlite') === 'sqlite' && !$isFresh) {
        throw new RuntimeException(
            "Migration failed on existing SQLite schema. Run: php scripts/migrate.php --fresh\n" . $e->getMessage()
        );
    }

    throw $e;
}
