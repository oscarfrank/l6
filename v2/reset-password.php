<?php
/*
 * "Forgot password" page. We never look up the email or send mail
 * (same as contact.php) so we don't leak whether an address is registered.
 * A valid-looking email just shows a confirmation.
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

start_app_session();

if (is_user_logged_in()) {
    redirect(app_url('dashboard.php'));
}

$email   = '';
$error   = '';
$sent    = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Please try again.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));

        if (!is_required($email) || !is_valid_email($email)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Same success message for every valid-looking email. No SELECT, no mail().
            $sent = true;
            $email = '';
        }
    }
}

$page_title = 'Reset password';
$body_class = 'page-auth';
require __DIR__ . '/includes/header.php';
?>

<div class="auth-shell">
    <aside class="auth-aside">
        <p class="hero-kicker">Account help</p>
        <h2>Forgot your password?</h2>
        <p>Enter the email on your account. In this demo we do not send a real message, but the confirmation is the same as a live reset.</p>
    </aside>

    <section class="form-panel auth-card">
        <h1>Reset password</h1>
        <p class="lede">We will send reset instructions if that email is on file.</p>

        <?php if ($sent): ?>
            <p class="notice notice-success" role="status">If that email address is registered with Book &amp; Board, we have sent password-reset instructions. Please check your inbox (and junk folder).</p>
            <div class="auth-links">
                <p class="meta"><a href="<?php echo escape(app_url('login.php')); ?>">Back to log in</a></p>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <p class="notice notice-error"><?php echo escape($error); ?></p>
            <?php endif; ?>

            <form method="post" action="" data-validate novalidate>
                <?php echo csrf_field(); ?>
                <div class="form-grid">
                    <div>
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required autocomplete="email" value="<?php echo escape($email); ?>">
                    </div>
                    <div>
                        <button type="submit" class="btn">Send reset link</button>
                    </div>
                </div>
            </form>
            <div class="auth-links">
                <p class="meta">Remembered it? <a href="<?php echo escape(app_url('login.php')); ?>">Log in</a></p>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
