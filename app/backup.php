<?php
/**
 * Database backup: a complete SQL dump (structure + data) that phpMyAdmin or the
 * mysql command can import. Files are kept in storage/backups (not web-accessible).
 */
declare(strict_types=1);

function backup_dir(): string
{
    $dir = APP_ROOT . '/storage/backups';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

/** Write a backup file and return its file name. */
function create_backup(string $reason = 'manual'): string
{
    @set_time_limit(300);
    $gz = function_exists('gzopen');
    $name = 'backup-' . date('Y-m-d-His') . '-' . $reason . '.sql' . ($gz ? '.gz' : '');
    $path = backup_dir() . '/' . $name;
    $fh = $gz ? gzopen($path, 'wb6') : fopen($path, 'wb');
    if (!$fh) {
        throw new RuntimeException('Could not write to storage/backups. Check folder permissions.');
    }
    $w = fn(string $s) => $gz ? gzwrite($fh, $s) : fwrite($fh, $s);
    $pdo = db();
    $dbName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    $w("-- Seyal backup\n-- Database: $dbName\n-- Created: " . now() . "\n-- App version: " . APP_VERSION . "\n\n");
    $w("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

    $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $create = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '', $table) . '`')->fetch(PDO::FETCH_NUM)[1];
        $w("-- ----------------------------\n-- Table `$table`\n-- ----------------------------\nDROP TABLE IF EXISTS `$table`;\n$create;\n\n");
        $offset = 0;
        while (true) {
            $rows = $pdo->query('SELECT * FROM `' . str_replace('`', '', $table) . '` LIMIT 500 OFFSET ' . $offset)->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) {
                break;
            }
            $cols = '`' . implode('`,`', array_keys($rows[0])) . '`';
            $values = [];
            foreach ($rows as $r) {
                $values[] = '(' . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), $r)) . ')';
            }
            $w("INSERT INTO `$table` ($cols) VALUES\n" . implode(",\n", $values) . ";\n");
            $offset += 500;
        }
        $w("\n");
    }
    $w("SET FOREIGN_KEY_CHECKS = 1;\n");
    $gz ? gzclose($fh) : fclose($fh);
    return $name;
}

function list_backups(): array
{
    $files = glob(backup_dir() . '/backup-*.sql*') ?: [];
    rsort($files);
    return array_map(fn($f) => ['name' => basename($f), 'size' => filesize($f), 'time' => date('Y-m-d H:i:s', filemtime($f))], $files);
}

function backup_path(string $name): ?string
{
    $name = basename($name);
    $path = backup_dir() . '/' . $name;
    return (preg_match('/^backup-[\w-]+\.sql(\.gz)?$/', $name) && is_file($path)) ? $path : null;
}

/** Keep only the newest $keep automatic backups. */
function prune_backups(int $keep): void
{
    $auto = array_values(array_filter(list_backups(), fn($b) => str_contains($b['name'], '-auto.')));
    foreach (array_slice($auto, $keep) as $b) {
        @unlink(backup_dir() . '/' . $b['name']);
    }
}

/** Zip of the uploads folder (task attachments). Returns a temp file path. */
function zip_uploads(): ?string
{
    if (!class_exists('ZipArchive')) {
        return null;
    }
    $tmp = tempnam(sys_get_temp_dir(), 'upl');
    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::OVERWRITE);
    foreach (glob(APP_ROOT . '/uploads/*') ?: [] as $f) {
        if (is_file($f) && !in_array(basename($f), ['.htaccess', '.gitkeep'], true)) {
            $zip->addFile($f, 'uploads/' . basename($f));
        }
    }
    $zip->addFromString('README.txt', "Task attachments from Seyal, " . now() . ".\nRestore by copying the files back into the app's uploads/ folder.\n");
    $zip->close();
    return $tmp;
}
