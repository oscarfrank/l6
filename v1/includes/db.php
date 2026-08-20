<?php
/*
 * PDO connection for Version 1.
 * Same database as Version 2 (setup writes config.local.php into both folders).
 * Every query goes through get_pdo() so credentials live in one place.
 */

/**
 * get_pdo()
 * Return the shared PDO instance, creating it on the first call.
 * static $pdo reuses the connection if this page already opened one.
 *
 * ERRMODE_EXCEPTION: SQL errors throw instead of failing silently.
 * FETCH_ASSOC: rows come back as column-name => value.
 * EMULATE_PREPARES false: bound values stay out of the SQL string.
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
        // Used only until /setup.php has been run.
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
