<?php
// Staff login for Version 1. Same admins table as v2 (shared database).

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

start_app_session();

// Already signed in: go straight to manage offers.
if (is_admin_logged_in()) {
    redirect(app_url('admin/manage-offers.php'));
}

$username = '';
$error    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Please try again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!is_required($username) || !is_required($password)) {
            // Don't say which field was empty.
            $error = 'Invalid username or password.';
        } else {
            $stmt = get_pdo()->prepare(
                'SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1'
            );
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            // password_verify() against the stored hash (never compare plaintext).
            if ($admin && password_verify($password, $admin['password_hash'])) {
                login_admin((int) $admin['id'], $admin['username']);
                redirect(app_url('admin/manage-offers.php'));
            }

            $error = 'Invalid username or password.';
        }
    }
}

$page_title = 'Staff login';
$is_admin   = false;
$body_class = 'page-auth';
require __DIR__ . '/../includes/header.php';
?>

<div class="auth-shell">
    <aside class="auth-aside staff">
        <p class="hero-kicker">London headquarters</p>
        <h2>Staff access.</h2>
        <p>Manage offers and site content. Use your staff username, not a customer email.</p>
    </aside>

    <section class="form-panel auth-card">
        <h1>Staff login</h1>
        <p class="lede">Username <strong>admin</strong> — this is not an email address.</p>

        <?php if ($error): ?>
            <p class="notice notice-error"><?php echo escape($error); ?></p>
        <?php endif; ?>

        <form method="post" action="" data-validate novalidate>
            <?php echo csrf_field(); ?>
            <div class="form-grid">
                <div>
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="username" value="<?php echo escape($username); ?>">
                </div>
                <div>
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <div>
                    <button type="submit" class="btn">Log in</button>
                </div>
            </div>
        </form>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
