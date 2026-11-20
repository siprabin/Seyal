<?php
/**
 * Database migrations. Each file in install/migrations/ runs once, in name order.
 * Phase 2 and 3 ship new migration files; the admin applies them from
 * Settings → System (or they run automatically during a fresh install).
 */
declare(strict_types=1);

function migration_files(): array
{
    $files = glob(APP_ROOT . '/install/migrations/*.sql') ?: [];
    sort($files);
    return $files;
}

function applied_migrations(PDO $pdo): array
{
    try {
        return $pdo->query('SELECT id FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [];
    }
}

function pending_migrations(PDO $pdo): array
{
    $done = applied_migrations($pdo);
    return array_values(array_filter(migration_files(), fn($f) => !in_array(basename($f), $done, true)));
}

/** Split a SQL file into statements (statements end with ";" at end of line). */
function split_sql(string $sql): array
{
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $parts = preg_split('/;\s*(\r?\n|$)/', $sql);
    return array_values(array_filter(array_map('trim', $parts), fn($s) => $s !== ''));
}

/** Run pending migrations. Returns the list of applied file names. */
function run_migrations(PDO $pdo): array
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (id VARCHAR(150) PRIMARY KEY, applied_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $applied = [];
    foreach (pending_migrations($pdo) as $file) {
        foreach (split_sql((string)file_get_contents($file)) as $stmt) {
            $pdo->exec($stmt);
        }
        $pdo->prepare('INSERT INTO migrations (id, applied_at) VALUES (?, ?)')->execute([basename($file), date('Y-m-d H:i:s')]);
        $applied[] = basename($file);
    }
    return $applied;
}
