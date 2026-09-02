<?php
/**
 * PDO database connection (singleton).
 */

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                die('<div style="font-family:sans-serif;max-width:640px;margin:80px auto;padding:24px;border:1px solid #f3c6c6;background:#fff5f5;border-radius:12px;color:#7a1f1f;">'
                    . '<h2 style="margin-top:0;">Database connection failed</h2>'
                    . '<p>' . htmlspecialchars($e->getMessage()) . '</p>'
                    . '<p>Check the <code>DB_*</code> constants in <code>config/config.php</code> and make sure you have imported <code>database/schema.sql</code>.</p>'
                    . '</div>');
            }
            die('A database error occurred. Please try again later.');
        }
    }

    return $pdo;
}