<?php
/**
 * Scheduler entry point. Run every 5 minutes.
 *
 *   Command line (recommended):  php /path/to/app/cron.php
 *   Web (if your host only offers URL cron):  https://your-site/cron.php?key=<key from Settings → Daily work>
 */
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    $key = (string)($_GET['key'] ?? '');
    $expected = (string)setting('cron_key', '');
    if ($expected === '' || !hash_equals($expected, $key)) {
        http_response_code(403);
        exit('Forbidden');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

foreach (run_scheduled_tasks() as $line) {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $line . PHP_EOL;
}
