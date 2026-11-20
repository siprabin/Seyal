<?php
/**
 * Router for PHP's built-in web server — for local evaluation and development only.
 *
 *   php -S 127.0.0.1:8000 server.php
 *
 * The built-in server ignores .htaccess, so this file blocks the private folders
 * (configuration, backups, uploads, source code) that Apache/Nginx rules normally protect.
 * Use Apache, LiteSpeed or Nginx for anything other than trying the app on your own computer.
 */
$path = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

$private = '#^/(app|config|lib|lang|pages|views|storage|uploads|tools|install/migrations)(/|$)#i';
$blocked = '#(/\.|\.(sql|gz|log|md|ini|bak|tsv)$)#i';

if (preg_match($private, $path) || preg_match($blocked, $path) || $path === '/server.php') {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "404 Not Found\n";
    return true;
}

return false; // let the built-in server serve the file (or index.php)
