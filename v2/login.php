<?php
/*
 * Customer login (FR7).
 * password_verify against users.password_hash. One generic error so we
 * don't tell an attacker whether the email exists. Staff typing "admin"
 * get sent to the staff login instead.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

start_app_session();

$next = safe_next_path($_POST['next'] ?? $_GET['next'] ?? '');

// Already signed in: skip the form and go to the dashboard (or ?next=).
if (is_user_logged_in()) {
    redirect($next !== '' ? app_url($next) : app_url('dashboard.php'));
}

$email = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Reject the POST if the CSRF token is missing or stale.
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Please try again.';
    } else {
        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        // Staff use a username, not an email. Don't treat "admin" as a failed customer login.
        if (strcasecmp($email, 'admin') === 0) {
            redirect(app_url('admin/admin-login.php'));
        }

        if (!is_required($email) || !is_required($password) || !is_valid_email($email)) {
            $error = 'Invalid email or password.';
        } else {
            // Look up by email. password_verify checks the stored hash (Section 8).
            $stmt = get_pdo()->prepare(
                'SELECT id, password_hash FROM users WHERE email = ? LIMIT 1'
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                login_user((int) $user['id']);
                redirect($next !== '' ? app_url($next) : app_url('dashboard.php'));
            }

            // Same wording as the empty-field case so we don't leak which check failed.
            $error = 'Invalid email or password.';
        }
    }
}

$page_title = 'Log in';
$body_class = 'page-auth';
require __DIR__ . '/includes/header.php';
?>

<div class="auth-shell">
    <aside class="auth-aside">
        <p class="hero-kicker">Customer account</p>
        <h2>Welcome back.</h2>
        <p>Sign in to see your details and previous packages.</p>
    </aside>

    <section class="form-panel auth-card">
        <h1>Log in</h1>
        <p class="lede">Use the email and password for your Book &amp; Board account.</p>

        <?php if ($error): ?>
            <p class="notice notice-error"><?php echo escape($error); ?></p>
        <?php endif; ?>

        <form method="post" action="" data-validate novalidate>
            <?php echo csrf_field(); ?>
            <?php if ($next !== ''): ?>
                <input type="hidden" name="next" value="<?php echo escape($next); ?>">
            <?php endif; ?>
            <div class="form-grid">
                <div>
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email" value="<?php echo escape($email); ?>">
                </div>
                <div>
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                    <p class="field-hint"><a href="<?php echo escape(app_url('reset-password.php')); ?>">Forgot password?</a></p>
                </div>
                <div>
                    <button type="submit" class="btn">Log in</button>
                </div>
            </div>
        </form>
        <div class="auth-links">
            <p class="meta">New customer? <a href="<?php echo escape($next !== '' ? query_url('register.php', ['next' => $next]) : app_url('register.php')); ?>">Create an account</a></p>
            <p class="meta">Staff? <a href="<?php echo escape(app_url('admin/admin-login.php')); ?>">Staff login</a></p>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
