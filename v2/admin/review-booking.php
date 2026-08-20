<?php
// Open one request and set confirmed / paid / declined (FR9).
// That status is what the customer sees on dashboard.php.

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$pdo = get_pdo();
$id  = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$load = $pdo->prepare(
    "SELECT b.id, b.package_name, b.destination, b.travel_date, b.price, b.status, b.created_at,
            u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
     FROM bookings b
     INNER JOIN users u ON u.id = b.user_id
     WHERE b.id = ?
     LIMIT 1"
);
$load->execute([$id]);
$booking = $load->fetch();

if (!$booking) {
    redirect(app_url('admin/manage-bookings.php'));
}

$statuses = [
    'requested' => 'Still waiting; shows as Awaiting confirmation',
    'confirmed' => 'Accepted; shows as a confirmed trip',
    'paid'      => 'Branch has taken payment',
    'declined'  => 'Not taken forward',
];

$status = strtolower((string) $booking['status']);
$errors = [];
$flash  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $errors['form'] = 'Your session expired. Please try again.';
    } else {
        $chosen = strtolower(trim((string) ($_POST['status'] ?? '')));
        if (!isset($statuses[$chosen])) {
            $errors['status'] = 'Choose a valid status.';
        } else {
            $update = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
            $update->execute([$chosen, $id]);
            $status  = $chosen;
            $booking['status'] = $chosen;
            if ($chosen === 'confirmed' || $chosen === 'paid') {
                $flash = 'Saved. This booking now shows on ' . $booking['customer_name'] . '’s account as ' . booking_status_label($chosen) . '.';
            } else {
                $flash = 'Saved. Status on the customer account is now ' . booking_status_label($chosen) . '.';
            }
        }
    }
}

$page_title = 'Review request';
$is_admin   = true;
require __DIR__ . '/../includes/header.php';
?>

<div class="page-intro">
    <h1>Review request</h1>
    <p class="lede">Choose the status that should appear on this customer’s dashboard. Confirming a request makes it a trip on their account.</p>
</div>

<p class="meta"><a href="<?php echo escape(app_url('admin/manage-bookings.php')); ?>">All requests</a></p>

<?php if ($flash): ?>
    <p class="notice notice-success" role="status"><?php echo escape($flash); ?></p>
<?php endif; ?>
<?php if (!empty($errors['form'])): ?>
    <p class="notice notice-error"><?php echo escape($errors['form']); ?></p>
<?php endif; ?>

<div class="split">
    <section class="form-panel">
        <h2><?php echo escape($booking['package_name']); ?></h2>
        <p class="meta"><?php echo escape($booking['destination']); ?></p>
        <p>Travel date: <?php echo escape(format_date($booking['travel_date'])); ?></p>
        <p class="price"><?php echo escape(format_price($booking['price'])); ?> <span class="meta">pp</span></p>
        <p class="meta">Requested <?php echo escape(format_date(substr((string) $booking['created_at'], 0, 10))); ?></p>

        <h3 class="account-subhead">Customer</h3>
        <p><?php echo escape($booking['customer_name']); ?></p>
        <p class="meta"><?php echo escape($booking['customer_email']); ?></p>
        <p class="meta"><?php echo escape($booking['customer_phone']); ?></p>
    </section>

    <section class="form-panel">
        <h2>Status on their dashboard</h2>
        <p class="meta">Currently <strong><?php echo escape(booking_status_label($booking['status'])); ?></strong>.</p>

        <form method="post" action="">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" value="<?php echo (int) $booking['id']; ?>">
            <div class="form-grid">
                <div>
                    <label for="status">Set status</label>
                    <select id="status" name="status" required>
                        <?php foreach ($statuses as $value => $label): ?>
                            <option value="<?php echo escape($value); ?>"<?php echo selected_attr($status, $value); ?>>
                                <?php echo escape(booking_status_label($value)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['status'])): ?>
                        <p class="field-error"><?php echo escape($errors['status']); ?></p>
                    <?php endif; ?>
                    <p class="field-hint">Confirmed or Paid will show under Coming up (or Previous trips) on their account. Requested stays in Awaiting confirmation.</p>
                </div>
                <div>
                    <button type="submit" class="btn">Update customer dashboard</button>
                </div>
            </div>
        </form>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
