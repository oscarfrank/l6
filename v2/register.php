<?php
/*
 * Registration (FR6). Name, email, phone, address go in the users row (FR8).
 * Duplicate emails are rejected. Password is hashed before INSERT.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

start_app_session();

$next = safe_next_path($_POST['next'] ?? $_GET['next'] ?? '');

// Already registered and signed in: don't show the form again.
if (is_user_logged_in()) {
    redirect($next !== '' ? app_url($next) : app_url('dashboard.php'));
}

$fields = [
    'name'     => '',
    'email'    => '',
    'phone'    => '',
    'address'  => '',
];
$password = '';
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors['form'] = 'Your session expired. Please try again.';
    }

    foreach ($fields as $key => $unused) {
        $fields[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    $password = (string) ($_POST['password'] ?? '');

    // Server-side checks (JS is optional). All of these must pass before INSERT.
    if (!is_required($fields['name'])) {
        $errors['name'] = 'Please enter your name.';
    }
    if (!is_required($fields['email'])) {
        $errors['email'] = 'Please enter your email address.';
    } elseif (!is_valid_email($fields['email'])) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if (!is_required($password)) {
        $errors['password'] = 'Please choose a password.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Use at least 8 characters.';
    }
    if (!is_required($fields['phone'])) {
        $errors['phone'] = 'Please enter a telephone number.';
    }
    if (!is_required($fields['address'])) {
        $errors['address'] = 'Please enter your address.';
    }

    // Check the email isn't already registered (prepared, so the value is bound).
    if (empty($errors['email'])) {
        $dup = get_pdo()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $dup->execute([$fields['email']]);
        if ($dup->fetch()) {
            $errors['email'] = 'An account with that email already exists.';
        }
    }

    if (!$errors) {
        // Never store plaintext. PASSWORD_DEFAULT uses bcrypt (or whatever PHP has).
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $insert = get_pdo()->prepare(
            'INSERT INTO users (name, email, password_hash, phone, address)
             VALUES (?, ?, ?, ?, ?)'
        );
        $insert->execute([
            $fields['name'],
            $fields['email'],
            $hash,
            $fields['phone'],
            $fields['address'],
        ]);

        // Log them in immediately so they don't have to fill login.php next.
        login_user((int) get_pdo()->lastInsertId());
        redirect($next !== '' ? app_url($next) : app_url('dashboard.php'));
    }
}

$page_title = 'Create an account';
$body_class = 'page-auth';
require __DIR__ . '/includes/header.php';
?>

<div class="auth-shell">
    <aside class="auth-aside register">
        <p class="hero-kicker">New customer</p>
        <h2>Join Book &amp; Board.</h2>
        <p>Keep your details on file and see packages you have previously booked with the agency.</p>
    </aside>

    <section class="form-panel auth-card">
        <h1>Create an account</h1>
        <p class="lede">All fields are required. Your password is stored only as a hash.</p>

        <?php if (!empty($errors['form'])): ?>
            <p class="notice notice-error"><?php echo escape($errors['form']); ?></p>
        <?php endif; ?>

        <form method="post" action="" data-validate novalidate>
            <?php echo csrf_field(); ?>
            <?php if ($next !== ''): ?>
                <input type="hidden" name="next" value="<?php echo escape($next); ?>">
            <?php endif; ?>
            <div class="form-grid two">
                <div>
                    <label for="name">Full name</label>
                    <input type="text" id="name" name="name" required autocomplete="name" value="<?php echo escape($fields['name']); ?>">
                    <?php if (!empty($errors['name'])): ?><p class="field-error"><?php echo escape($errors['name']); ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autocomplete="email" value="<?php echo escape($fields['email']); ?>">
                    <?php if (!empty($errors['email'])): ?><p class="field-error"><?php echo escape($errors['email']); ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password" minlength="8">
                    <p class="field-hint">At least 8 characters.</p>
                    <?php if (!empty($errors['password'])): ?><p class="field-error"><?php echo escape($errors['password']); ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="phone">Telephone</label>
                    <input type="tel" id="phone" name="phone" required autocomplete="tel" value="<?php echo escape($fields['phone']); ?>">
                    <?php if (!empty($errors['phone'])): ?><p class="field-error"><?php echo escape($errors['phone']); ?></p><?php endif; ?>
                </div>
                <div class="full">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" required autocomplete="street-address" value="<?php echo escape($fields['address']); ?>">
                    <?php if (!empty($errors['address'])): ?><p class="field-error"><?php echo escape($errors['address']); ?></p><?php endif; ?>
                </div>
                <div class="full">
                    <button type="submit" class="btn">Create account</button>
                </div>
            </div>
        </form>
        <div class="auth-links">
            <p class="meta">Already registered? <a href="<?php echo escape(app_url('login.php')); ?>">Log in</a></p>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
