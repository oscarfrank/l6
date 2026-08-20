<?php
// Inbox of package requests (FR9). FIELD() keeps 'requested' at the top.

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$listStmt = get_pdo()->prepare(
    "SELECT b.id, b.package_name, b.destination, b.travel_date, b.price, b.status, b.created_at,
            u.name AS customer_name, u.email AS customer_email
     FROM bookings b
     INNER JOIN users u ON u.id = b.user_id
     ORDER BY FIELD(b.status, 'requested', 'confirmed', 'paid', 'completed', 'declined', 'cancelled'),
              b.created_at DESC"
);
$listStmt->execute();
$bookings = $listStmt->fetchAll();

$newRequests = [];
$others      = [];
foreach ($bookings as $row) {
    if (strtolower((string) $row['status']) === 'requested') {
        $newRequests[] = $row;
    } else {
        $others[] = $row;
    }
}

$page_title = 'Manage bookings';
$is_admin   = true;
require __DIR__ . '/../includes/header.php';

// Shared row markup for both tables on this page.
function booking_inbox_row(array $booking): void
{
    $status = strtolower((string) $booking['status']);
    $review = app_url('admin/review-booking.php?id=' . (int) $booking['id']);
    ?>
    <tr>
        <td>
            <?php echo escape($booking['customer_name']); ?>
            <p class="meta"><?php echo escape($booking['customer_email']); ?></p>
        </td>
        <td>
            <?php echo escape($booking['package_name']); ?>
            <p class="meta"><?php echo escape($booking['destination']); ?></p>
        </td>
        <td><?php echo escape(format_date($booking['travel_date'])); ?></td>
        <td><?php echo escape(format_price($booking['price'])); ?></td>
        <td><?php echo escape(booking_status_label($booking['status'])); ?></td>
        <td>
            <a class="btn<?php echo $status === 'requested' ? '' : ' btn-ghost'; ?>" href="<?php echo escape($review); ?>">
                Open
            </a>
        </td>
    </tr>
    <?php
}
?>

<div class="page-intro">
    <h1>Package requests</h1>
    <p class="lede">Open a request, then set it to Confirmed so it appears on that customer’s dashboard. Mark Paid when the branch has taken the money in shop.</p>
</div>

<?php if (!$bookings): ?>
    <div class="empty-state">
        <h2>No requests yet</h2>
        <p>When a customer requests a package from Offers, it will be listed here for you to confirm.</p>
    </div>
<?php endif; ?>

<?php if ($newRequests): ?>
    <h2 class="account-subhead">New — awaiting your decision</h2>
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Package</th>
                    <th>Travel</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($newRequests as $booking): ?>
                    <?php booking_inbox_row($booking); ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if ($others): ?>
    <h2 class="account-subhead">Already reviewed</h2>
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Package</th>
                    <th>Travel</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($others as $booking): ?>
                    <?php booking_inbox_row($booking); ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
