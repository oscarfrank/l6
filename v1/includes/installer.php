<?php
/*
 * Same installer as v2/includes/installer.php.
 * If the v2 folder is next to this one we import v2's schema so both
 * versions talk to the same tables.
 */

/**
 * True when setup is opened on localhost (prefill Docker MySQL).
 */
function installer_is_local_request(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    $host = explode(':', $host)[0];

    return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
}

/** Default form values: Docker 3308/root/root, otherwise CloudPanel 3306. */
function installer_defaults(): array
{
    $local = installer_is_local_request();

    return [
        'host'   => '127.0.0.1',
        'port'   => $local ? '3308' : '3306',
        'dbname' => 'bookandboard',
        'user'   => $local ? 'root' : '',
        'pass'   => $local ? 'root' : '',
    ];
}

/** Path to config.local.php in this version folder. */
function installer_config_path(string $root): string
{
    return $root . '/includes/config.local.php';
}

/** Path to setup.lock (stops the installer running twice). */
function installer_lock_path(string $root): string
{
    return $root . '/includes/setup.lock';
}

/** Prefer v2/sql if it exists so both versions share the full schema. */
function installer_sql_dir(string $root): string
{
    if (basename($root) === 'v2') {
        return $root . '/sql';
    }
    $siblingV2 = dirname($root) . '/v2/sql';
    if (is_file($siblingV2 . '/schema.sql')) {
        return $siblingV2;
    }
    return $root . '/sql';
}

/** Write config into this folder and the sibling v1/v2 folder if present. */
function installer_config_targets(string $root): array
{
    $targets = [installer_config_path($root)];
    $parent  = dirname($root);
    foreach (['v1', 'v2'] as $dir) {
        $path = $parent . '/' . $dir . '/includes/config.local.php';
        if (is_dir(dirname($path)) && !in_array($path, $targets, true)) {
            $targets[] = $path;
        }
    }
    return $targets;
}

/** Save MySQL details as a PHP return array. */
function installer_write_config(string $path, array $cfg): void
{
    $export = var_export($cfg, true);
    $php    = "<?php\nreturn " . $export . ";\n";
    if (file_put_contents($path, $php) === false) {
        throw new RuntimeException('Could not write ' . $path . '. Make includes/ writable, or paste the file by hand.');
    }
}

/** Split schema.sql / seed.sql into statements (strip -- comments first). */
function installer_sql_statements(string $sql): array
{
    $sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;
    $parts = explode(';', $sql);
    $out = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $out[] = $part;
        }
    }
    return $out;
}

/** Execute every statement in a .sql file. */
function installer_run_sql_file(PDO $pdo, string $file): void
{
    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException('Could not read ' . $file);
    }
    foreach (installer_sql_statements($sql) as $statement) {
        $pdo->exec($statement);
    }
}

/** PDO connection using the details from the setup form. */
function installer_connect(array $cfg): PDO
{
    $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset=utf8mb4";
    return new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}

/** Escape for the setup HTML (functions.php is not loaded here). */
function installer_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Render / run the setup page. After a successful save we write setup.lock.
 */
function installer_run(string $root): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // Lock file is created after a successful run so this page cannot wipe the DB again.
    $locked  = is_file(installer_lock_path($root));
    $fields  = installer_defaults();
    $errors  = [];
    $success = '';
    $seed    = true;

    if (!$locked && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if ($token === '' || !hash_equals((string) ($_SESSION['setup_csrf'] ?? ''), $token)) {
            $errors[] = 'Your session expired. Please try again.';
        }

        foreach (['host', 'port', 'dbname', 'user'] as $key) {
            $fields[$key] = trim((string) ($_POST[$key] ?? ''));
        }
        $fields['pass'] = (string) ($_POST['pass'] ?? '');
        $seed = isset($_POST['seed']);

        if ($fields['host'] === '' || $fields['dbname'] === '' || $fields['user'] === '') {
            $errors[] = 'Host, database name and user are required.';
        }
        if ($fields['port'] === '' || !ctype_digit($fields['port'])) {
            $errors[] = 'Port must be a number (usually 3306).';
        }

        if (!$errors) {
            try {
                $pdo = installer_connect($fields);
                if ($seed) {
                    $sqlDir = installer_sql_dir($root);
                    installer_run_sql_file($pdo, $sqlDir . '/schema.sql');
                    installer_run_sql_file($pdo, $sqlDir . '/seed.sql');
                }
                foreach (installer_config_targets($root) as $path) {
                    installer_write_config($path, $fields);
                    file_put_contents(dirname($path) . '/setup.lock', date('c') . "\n");
                }
                $success = $seed
                    ? 'Connected, tables created, and demo data loaded. Setup is now locked.'
                    : 'Connection saved. Setup is now locked. Import sql/schema.sql and sql/seed.sql in phpMyAdmin if the tables are still empty.';
            } catch (Throwable $e) {
                $errors[] = 'Could not finish setup: ' . $e->getMessage();
            }
        }
    }

    if (empty($_SESSION['setup_csrf'])) {
        $_SESSION['setup_csrf'] = bin2hex(random_bytes(16));
    }

    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup — Book &amp; Board</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <main id="main">
        <div class="page-intro">
            <h1>Install Book &amp; Board</h1>
            <p class="lede">Create an empty database in CloudPanel first, then enter the details here. This page turns itself off after a successful run.</p>
        </div>

        <section class="form-panel" style="max-width:36rem;">
            <?php if ($locked && $success === ''): ?>
                <p class="notice notice-info">Setup has already been completed on this copy. To run it again (this drops and recreates tables), delete <code>includes/setup.lock</code> and <code>includes/config.local.php</code> from this version folder.</p>
                <p class="meta"><a href="index.php">Go to the site</a></p>
            <?php elseif ($success !== ''): ?>
                <p class="notice notice-success" role="status"><?php echo installer_h($success); ?></p>
                <p>Staff login: <strong>admin</strong> / <strong>admin123</strong></p>
                <p class="meta">Customer: info@oscarmini.com / demo123</p>
                <p><a class="btn" href="index.php">Open the site</a></p>
            <?php else: ?>
                <?php foreach ($errors as $error): ?>
                    <p class="notice notice-error"><?php echo installer_h($error); ?></p>
                <?php endforeach; ?>

                <form method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo installer_h((string) $_SESSION['setup_csrf']); ?>">
                    <div class="form-grid">
                        <div>
                            <label for="host">MySQL host</label>
                            <input type="text" id="host" name="host" required value="<?php echo installer_h($fields['host']); ?>">
                            <p class="field-hint">Usually 127.0.0.1. Local Docker is prefilled below; CloudPanel is port 3306 with the user you created.</p>
                        </div>
                        <div>
                            <label for="port">Port</label>
                            <input type="text" id="port" name="port" required value="<?php echo installer_h($fields['port']); ?>">
                        </div>
                        <div>
                            <label for="dbname">Database name</label>
                            <input type="text" id="dbname" name="dbname" required value="<?php echo installer_h($fields['dbname']); ?>">
                        </div>
                        <div>
                            <label for="user">Database user</label>
                            <input type="text" id="user" name="user" required autocomplete="username" value="<?php echo installer_h($fields['user']); ?>">
                        </div>
                        <div>
                            <label for="pass">Database password</label>
                            <input type="password" id="pass" name="pass" autocomplete="new-password" value="<?php echo installer_h($fields['pass']); ?>">
                        </div>
                        <div class="checkbox-row">
                            <input type="checkbox" id="seed" name="seed" value="1"<?php echo $seed ? ' checked' : ''; ?>>
                            <label for="seed">Create tables and load demo offers, flights, hotels and test accounts</label>
                        </div>
                        <div>
                            <button type="submit" class="btn">Save and install</button>
                        </div>
                    </div>
                </form>
                <p class="field-hint">Loading demo data replaces existing Book &amp; Board tables in this database.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
    <?php
}
