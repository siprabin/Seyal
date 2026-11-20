<?php
/**
 * Translation checker.
 *   php tools/i18n_check.php              summary for every language
 *   php tools/i18n_check.php --lang=hi    list missing strings for one language
 *   php tools/i18n_check.php --all        list every English UI string
 *
 * It also reports translations whose :placeholders (e.g. :name, :date) do not match the
 * English text, because those would show wrong values on screen.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}
$root = dirname(__DIR__);

/** Every English string used with t(), including labels passed through t() indirectly. */
function ui_strings(string $root): array
{
    $keys = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $path = str_replace('\\', '/', $f->getPathname());
        if ($f->getExtension() !== 'php' || preg_match('#/(lib|lang|install|tools|config)/#', $path)) {
            continue;
        }
        $src = file_get_contents($path);
        preg_match_all("/\\bt\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/", $src, $m1);
        preg_match_all('/\bt\(\s*"((?:[^"\\\\]|\\\\.)*)"/', $src, $m2);
        foreach (array_merge($m1[1], $m2[1]) as $k) {
            $keys[stripslashes($k)] = true;
        }
        if (preg_match('#/app/(permissions|audit)\.php$#', $path)) {
            preg_match_all("/=>\\s*\\['([A-Za-z ]+)',\\s*'((?:[^'\\\\]|\\\\.)+)'\\]/", $src, $m3);
            foreach (array_merge($m3[1], array_map('stripslashes', $m3[2])) as $k) {
                $keys[$k] = true;
            }
            preg_match_all("/'[a-z_]+'\\s*=>\\s*'([A-Z][A-Za-z \\/&-]+)'/", $src, $m4);
            foreach ($m4[1] as $k) {
                $keys[$k] = true;
            }
        }
    }
    ksort($keys);
    return array_keys($keys);
}

function placeholders(string $s): array
{
    preg_match_all('/:[a-z]+/', $s, $m);
    $p = array_unique($m[0]);
    sort($p);
    return $p;
}

$keys = ui_strings($root);
$opts = getopt('', ['lang:', 'all']);
if (isset($opts['all'])) {
    echo implode(PHP_EOL, $keys), PHP_EOL;
    fwrite(STDERR, count($keys) . " strings\n");
    exit(0);
}

$langs = [];
foreach (glob("$root/lang/*.php") as $file) {
    $langs[basename($file, '.php')] = $file;
}
if (isset($opts['lang'])) {
    $langs = array_intersect_key($langs, [$opts['lang'] => 1]);
}
$problems = 0;
foreach ($langs as $code => $file) {
    $table = require $file;
    $missing = array_values(array_filter($keys, fn($k) => !isset($table[$k])));
    $badPh = [];
    foreach ($table as $en => $tr) {
        if (placeholders((string)$en) !== placeholders((string)$tr)) {
            $badPh[] = $en;
        }
    }
    printf("%-3s %4d translated, %3d missing, %d placeholder problems\n", $code, count($keys) - count($missing), count($missing), count($badPh));
    if (isset($opts['lang']) || count($langs) === 1) {
        foreach ($missing as $k) {
            echo "  MISSING: $k\n";
        }
    }
    foreach ($badPh as $k) {
        echo "  PLACEHOLDER: $k  =>  {$table[$k]}\n";
    }
    $problems += count($missing) + count($badPh);
}
exit($problems ? 1 : 0);
