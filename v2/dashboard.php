<?php
/*
 * Signed-in customer area.
 * FR8: show / update name, phone, address (email stays as the login key).
 * FR9: that customer's bookings only (WHERE user_id = session id).
 * require_login() sends guests to login.php.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$user = current_user();
if ($user === null) {
    logout_user();
    redirect(app_url('login.php'));
}

$profileErrors  = [];
$passwordErrors = [];
$profileFlash   = '';
$passwordFlash  = '';

$name    = (string) $user['name'];
$phone   = (string) ($user['phone'] ?? '');
$address = (string) ($user['address'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $expired = 'Your session expired. Please try again.';
        if (($_POST['action'] ?? '') === 'change_password') {
            $passwordErrors['form'] = $expired;
        } else {
            $profileErrors['form'] = $expired;
        }
    } else {
        $action = (string) ($_POST['action'] ?? '');

        // Two forms post to this page; the hidden action field says which one.
        if ($action === 'update_profile') {
            $name    = trim((string) ($_POST['name'] ?? ''));
            $phone   = trim((string) ($_POST['phone'] ?? ''));
            $address = trim((string) ($_POST['address'] ?? ''));

            if (!is_required($name)) {
                $profileErrors['name'] = 'Please enter your name.';
            }
            if (!is_required($phone)) {
                $profileErrors['phone'] = 'Please enter a telephone number.';
            }
            if (!is_required($address)) {
                $profileErrors['address'] = 'Please enter your address.';
            }

            if (!$profileErrors) {
                $update = get_pdo()->prepare(
                    'UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?'
                );
                $update->execute([$name, $phone, $address, (int) $user['id']]);
                $user = current_user();
                $name    = (string) $user['name'];
                $phone   = (string) ($user['phone'] ?? '');
                $address = (string) ($user['address'] ?? '');
                $profileFlash = 'Your details have been saved.';
            }
        }

        if ($action === 'change_password') {
            $current = (string) ($_POST['current_password'] ?? '');
            $new     = (string) ($_POST['new_password'] ?? '');
            $confirm = (string) ($_POST['confirm_password'] ?? '');

            if (!is_required($current) || !is_required($new) || !is_required($confirm)) {
                $passwordErrors['form'] = 'Please fill in every password field.';
            } elseif (strlen($new) < 8) {
                $passwordErrors['new_password'] = 'Use at least 8 characters.';
            } elseif ($new !== $confirm) {
                $passwordErrors['confirm_password'] = 'The new passwords do not match.';
            } else {
                $hashStmt = get_pdo()->prepare(
                    'SELECT password_hash FROM users WHERE id = ? LIMIT 1'
                );
                $hashStmt->execute([(int) $user['id']]);
                $row = $hashStmt->fetch();

                if (!$row || !password_verify($current, $row['password_hash'])) {
                    $passwordErrors['current_password'] = 'That is not your current password.';
                } else {
                    // Same hashing as register.php. The old hash is never stored in the session.
                    $newHash = password_hash($new, PASSWORD_DEFAULT);
                    $pwUpdate = get_pdo()->prepare(
                        'UPDATE users SET password_hash = ? WHERE id = ?'
                    );
                    $pwUpdate->execute([$newHash, (int) $user['id']]);
                    $passwordFlash = 'Your password has been changed.';
                }
            }
        }
    }
}

$bookingStmt = get_pdo()->prepare(
    'SELECT id, package_name, destination, travel_date, price, status
     FROM bookings
     WHERE user_id = ?
     ORDER BY travel_date DESC'
);
$bookingStmt->execute([(int) $user['id']]);
$bookings = $bookingStmt->fetchAll();

$today     = today_iso();
$awaiting  = [];
$upcoming  = [];
$past      = [];
$declined  = [];
// Split the list so the template can show awaiting / upcoming / past separately.
foreach ($bookings as $booking) {
    $status = strtolower((string) ($booking['status'] ?? ''));
    $travel = (string) ($booking['travel_date'] ?? '');
    if ($status === 'requested') {
        $awaiting[] = $booking;
    } elseif (in_array($status, ['declined', 'cancelled'], true)) {
        $declined[] = $booking;
    } elseif ($travel !== '' && $travel >= $today && in_array($status, ['confirmed', 'paid'], true)) {
        $upcoming[] = $booking;
    } else {
        $past[] = $booking;
    }
}

$flash = take_flash();

$page_title = 'Your account';
$body_class = 'page-account';
require __DIR__ . '/includes/header.php';
?>

<div class="account-hero">
    <p class="hero-kicker">Your account</p>
    <h1>Hello, <?php echo escape($user['name']); ?></h1>
    <p class="lede">Request a package from Offers, then watch it move from requested to confirmed once the London team accept it.</p>
    <p class="meta">Member since <?php echo escape(format_date(substr((string) $user['created_at'], 0, 10))); ?></p>
</div>

<?php if ($flash): ?>
    <p class="notice notice-success" role="status"><?php echo escape($flash); ?></p>
<?php endif; ?>

<section>
    <div class="section-heading">
        <h2>Your packages</h2>
        <a href="<?php echo escape(app_url('offers.php')); ?>">Request a package</a>
    </div>

    <?php if (!$bookings): ?>
        <div class="empty-state">
            <h2>No packages on file yet</h2>
            <p>Browse <a href="<?php echo escape(app_url('offers.php')); ?>">current offers</a> and request a package. It will show here as Requested until a staff member confirms it.</p>
        </div>
    <?php else: ?>
        <?php if ($awaiting): ?>
            <h3 class="account-subhead">Awaiting confirmation</h3>
            <div class="card-grid">
                <?php foreach ($awaiting as $booking): ?>
                    <?php require __DIR__ . '/includes/booking-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($upcoming): ?>
            <h3 class="account-subhead">Coming up</h3>
            <div class="card-grid">
                <?php foreach ($upcoming as $booking): ?>
                    <?php require __DIR__ . '/includes/booking-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($past): ?>
            <h3 class="account-subhead"><?php echo ($awaiting || $upcoming) ? 'Previous trips' : 'On file'; ?></h3>
            <div class="card-grid">
                <?php foreach ($past as $booking): ?>
                    <?php require __DIR__ . '/includes/booking-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($declined): ?>
            <h3 class="account-subhead">Not taken forward</h3>
            <div class="card-grid">
                <?php foreach ($declined as $booking): ?>
                    <?php require __DIR__ . '/includes/booking-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<div class="split split-even account-settings">
    <section class="form-panel">
        <h2>Your details</h2>
        <p class="meta">Kept on this account so a branch can reach you. Email cannot be changed here.</p>

        <?php if ($profileFlash): ?>
            <p class="notice notice-success" role="status"><?php echo escape($profileFlash); ?></p>
        <?php endif; ?>
        <?php if (!empty($profileErrors['form'])): ?>
            <p class="notice notice-error"><?php echo escape($profileErrors['form']); ?></p>
        <?php endif; ?>

        <form method="post" action="" data-validate novalidate>
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="update_profile">
            <div class="form-grid two">
                <div>
                    <label for="name">Full name</label>
                    <input type="text" id="name" name="name" required autocomplete="name" value="<?php echo escape($name); ?>">
                    <?php if (!empty($profileErrors['name'])): ?><p class="field-error"><?php echo escape($profileErrors['name']); ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="email">Email</label>
                    <input type="email" id="email" value="<?php echo escape($user['email']); ?>" disabled>
                </div>
                <div>
                    <label for="phone">Telephone</label>
                    <input type="tel" id="phone" name="phone" required autocomplete="tel" value="<?php echo escape($phone); ?>">
                    <?php if (!empty($profileErrors['phone'])): ?><p class="field-error"><?php echo escape($profileErrors['phone']); ?></p><?php endif; ?>
                </div>
                <div class="full">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" required autocomplete="street-address" value="<?php echo escape($address); ?>">
                    <?php if (!empty($profileErrors['address'])): ?><p class="field-error"><?php echo escape($profileErrors['address']); ?></p><?php endif; ?>
                </div>
                <div class="full">
                    <button type="submit" class="btn">Save details</button>
                </div>
            </div>
        </form>
    </section>

    <section class="form-panel form-panel-quiet">
        <h2>Password</h2>
        <p class="meta">Use at least 8 characters. You will stay signed in after a change.</p>

        <?php if ($passwordFlash): ?>
            <p class="notice notice-success" role="status"><?php echo escape($passwordFlash); ?></p>
        <?php endif; ?>
        <?php if (!empty($passwordErrors['form'])): ?>
            <p class="notice notice-error"><?php echo escape($passwordErrors['form']); ?></p>
        <?php endif; ?>

        <form method="post" action="" data-validate novalidate>
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="change_password">
            <div class="form-grid">
                <div>
                    <label for="current_password">Current password</label>
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                    <?php if (!empty($passwordErrors['current_password'])): ?><p class="field-error"><?php echo escape($passwordErrors['current_password']); ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="new_password">New password</label>
                    <input type="password" id="new_password" name="new_password" required autocomplete="new-password" minlength="8">
                    <?php if (!empty($passwordErrors['new_password'])): ?><p class="field-error"><?php echo escape($passwordErrors['new_password']); ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="confirm_password">Confirm new password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" minlength="8">
                    <?php if (!empty($passwordErrors['confirm_password'])): ?><p class="field-error"><?php echo escape($passwordErrors['confirm_password']); ?></p><?php endif; ?>
                </div>
                <div>
                    <button type="submit" class="btn btn-ghost">Change password</button>
                </div>
            </div>
        </form>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
