<?php
// Offer catalogue for Version 1. Same end_date filter as the home page.

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = get_pdo();

// Hide expired seed rows from customers. Staff still see them in admin.
$stmt = $pdo->prepare(
    'SELECT id, title, description, destination, price, image_url, is_bestseller, start_date, end_date
     FROM offers
     WHERE end_date IS NULL OR end_date >= CURDATE()
     ORDER BY is_bestseller DESC, price ASC'
);
$stmt->execute();
$offers = $stmt->fetchAll();

$page_title = 'Current offers';
require __DIR__ . '/includes/header.php';
?>

<div class="page-intro">
    <h1>Current offers</h1>
    <p class="lede">Packages available today from Book &amp; Board. Bestsellers are marked. Prices are per person and exclude extras arranged in branch.</p>
</div>

<?php if (!$offers): ?>
    <p class="notice notice-info">No current offers are available. Please contact your nearest branch.</p>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($offers as $offer): ?>
            <?php require __DIR__ . '/includes/offer-card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
