<?php
/*
 * Single PDO connection for Version 2.
 *
 * Every page that talks to MySQL calls get_pdo(). There is no other path
 * to the database (Section 7). Credentials come from includes/config.local.php,
 * which setup.php writes. utf8mb4 on the DSN so destination names with
 * accents store cleanly.
 */

/**
 * get_pdo()
 * Return the shared PDO instance, creating it on the first call.
 * static $pdo means later calls on the same page reuse the connection
 * instead of opening a new one each time.
 *
 * ERRMODE_EXCEPTION: SQL errors throw instead of failing silently.
 * FETCH_ASSOC: rows come back as column-name => value.
 * EMULATE_PREPARES false: bound values stay out of the SQL string (Section 8).
 */
function get_pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $local = __DIR__ . '/config.local.php';
    if (is_file($local)) {
        $cfg    = require $local;
        $host   = (string) ($cfg['host'] ?? '127.0.0.1');
        $port   = (string) ($cfg['port'] ?? '3306');
        $dbname = (string) ($cfg['dbname'] ?? '');
        $user   = (string) ($cfg['user'] ?? '');
        $pass   = (string) ($cfg['pass'] ?? '');
    } else {
        // Placeholders until setup.php has been run.
        $host    = '127.0.0.1';
        $port    = '3306';
        $dbname  = 'bookandboard';
        $user    = 'bookandboard';
        $pass    = 'change-me';
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}
