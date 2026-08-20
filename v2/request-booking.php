<?php
/*
 * Customer requests a current offer (FR9). Status starts as 'requested'.
 * Staff confirm it later. No card payment in this phase.
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

$offerId = (int) ($_GET['offer_id'] ?? $_POST['offer_id'] ?? 0);
$errors  = [];
$date    = trim((string) ($_POST['travel_date'] ?? ''));
$today   = today_iso();

// Same current-offer rule as offers.php, so expired seed rows cannot be requested.
$offerStmt = get_pdo()->prepare(
    'SELECT id, title, description, destination, price, image_url, start_date, end_date
     FROM offers
     WHERE id = ?
       AND (end_date IS NULL OR end_date >= CURDATE())
     LIMIT 1'
);
$offerStmt->execute([$offerId]);
$offer = $offerStmt->fetch();

if (!$offer) {
    set_flash('That package is no longer available.');
    redirect(app_url('offers.php'));
}

if ($date === '' && !empty($offer['start_date']) && is_today_or_future($offer['start_date'])) {
    $date = (string) $offer['start_date'];
} elseif ($date === '') {
    $date = $today;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors['form'] = 'Your session expired. Please try again.';
    }

    if (!is_iso_date($date) || !is_today_or_future($date)) {
        $errors['travel_date'] = 'Choose today or a later travel date.';
    } elseif (!empty($offer['end_date']) && $date > (string) $offer['end_date']) {
        $errors['travel_date'] = 'That date is after this offer ends. Choose an earlier day.';
    }

    // Don't let them stack the same package while one is still live.
    $dup = get_pdo()->prepare(
        "SELECT id FROM bookings
         WHERE user_id = ? AND offer_id = ? AND status IN ('requested', 'confirmed', 'paid')
         LIMIT 1"
    );
    $dup->execute([(int) $user['id'], $offerId]);
    if ($dup->fetch()) {
        $errors['form'] = 'You already have this package on request or confirmed.';
    }

    if (!$errors) {
        $insert = get_pdo()->prepare(
            'INSERT INTO bookings (user_id, offer_id, package_name, destination, travel_date, price, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([
            (int) $user['id'],
            $offerId,
            $offer['title'],
            $offer['destination'],
            $date,
            $offer['price'],
            'requested',
        ]);
        set_flash('Request received. A member of the London team will confirm it shortly — it now sits on your account as Requested.');
        redirect(app_url('dashboard.php'));
    }
}

$page_title = 'Request package';
require __DIR__ . '/includes/header.php';
?>

<div class="page-intro">
    <h1>Request this package</h1>
    <p class="lede">We will hold this with the London team. Nothing is charged online — a branch confirms the trip and records payment in shop.</p>
</div>

<div class="split">
    <article class="card">
        <div class="card-image">
            <img
                src="<?php echo escape(media_url($offer['image_url'] ?? '')); ?>"
                alt="<?php echo escape($offer['destination'] ?: $offer['title']); ?>"
            >
        </div>
        <div class="card-body">
            <h2><?php echo escape($offer['title']); ?></h2>
            <p class="meta"><?php echo escape($offer['destination']); ?></p>
            <?php if (!empty($offer['description'])): ?>
                <p><?php echo escape($offer['description']); ?></p>
            <?php endif; ?>
            <p class="price"><?php echo escape(format_price($offer['price'])); ?> <span class="meta">pp</span></p>
        </div>
    </article>

    <section class="form-panel">
        <h2>Your request</h2>
        <p class="meta">Logged in as <?php echo escape($user['email']); ?>.</p>

        <?php if (!empty($errors['form'])): ?>
            <p class="notice notice-error"><?php echo escape($errors['form']); ?></p>
        <?php endif; ?>

        <form method="post" action="" data-validate novalidate>
            <?php echo csrf_field(); ?>
            <input type="hidden" name="offer_id" value="<?php echo (int) $offer['id']; ?>">
            <div class="form-grid">
                <div>
                    <label for="travel_date">Preferred travel date</label>
                    <input
                        type="date"
                        id="travel_date"
                        name="travel_date"
                        required
                        min="<?php echo escape($today); ?>"
                        <?php if (!empty($offer['end_date'])): ?>max="<?php echo escape($offer['end_date']); ?>"<?php endif; ?>
                        value="<?php echo escape($date); ?>"
                    >
                    <?php if (!empty($errors['travel_date'])): ?>
                        <p class="field-error"><?php echo escape($errors['travel_date']); ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <button type="submit" class="btn">Send request</button>
                </div>
            </div>
        </form>
        <p class="meta"><a href="<?php echo escape(app_url('offers.php')); ?>">Back to offers</a></p>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
