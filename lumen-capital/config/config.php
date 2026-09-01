<?php
/**
 * Lumen Capital — Core Configuration
 * -----------------------------------------------------------------------
 * Edit the DB_* constants below to match your MySQL/MariaDB setup.
 * On a stock XAMPP/WAMP/MAMP install the defaults (root / no password)
 * generally work out of the box once you've imported database/schema.sql.
 * -----------------------------------------------------------------------
 */

// ---------------------------------------------------------------------
// Database credentials (env vars override these, useful for deployment)
// ---------------------------------------------------------------------
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'lumen_capital');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// ---------------------------------------------------------------------
// App-level settings
// ---------------------------------------------------------------------
define('APP_NAME', 'Lumen Capital');
define('APP_DEBUG', true);          // set to false in production
define('APP_TIMEZONE', 'America/Los_Angeles');
define('SESSION_NAME', 'lumen_session');

date_default_timezone_set(APP_TIMEZONE);

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// ---------------------------------------------------------------------
// Secure session bootstrap
// ---------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---------------------------------------------------------------------
// Auto-detect the base URL so the app works regardless of the folder
// name it's installed under (e.g. htdocs/lumen-capital vs. docroot).
// ---------------------------------------------------------------------
function lumen_detect_base_url(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $parts = explode('/', trim($scriptName, '/'));
    array_pop($parts); // drop the filename
    if (!empty($parts) && in_array(end($parts), ['user', 'admin', 'cron'], true)) {
        array_pop($parts); // drop the one-level subdirectory
    }
    $base = '/' . implode('/', array_filter($parts));
    if ($base === '/') {
        $base = '';
    }
    return $protocol . '://' . $host . $base;
}

define('BASE_URL', lumen_detect_base_url());
