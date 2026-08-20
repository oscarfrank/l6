<?php
// Version 1 home. Same FR1 queries as v2 (bestsellers + current offers).

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = get_pdo();

$bestsellerStmt = $pdo->prepare(
    'SELECT id, title, description, destination, price, image_url, is_bestseller
     FROM offers
     WHERE is_bestseller = 1
       AND (end_date IS NULL OR end_date >= CURDATE())
     ORDER BY price ASC'
);
$bestsellerStmt->execute();
$bestsellers = $bestsellerStmt->fetchAll();

// Second grid: newest current offers, not only bestsellers.
$currentStmt = $pdo->prepare(
    'SELECT id, title, description, destination, price, image_url, is_bestseller
     FROM offers
     WHERE end_date IS NULL OR end_date >= CURDATE()
     ORDER BY created_at DESC
     LIMIT 6'
);
$currentStmt->execute();
$currentOffers = $currentStmt->fetchAll();

$page_title = 'Home';
$body_class = 'page-home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <p class="hero-kicker">Established 1975 · London and four regional branches</p>
    <h1>Travel, planned properly.</h1>
    <p>Book &amp; Board is a UK high-street agency moving online. Browse this season’s packages, find your nearest branch, or speak to the London team.</p>
    <div class="hero-actions">
        <a class="btn" href="<?php echo escape(app_url('offers.php')); ?>">See current offers</a>
        <a class="btn btn-secondary" href="<?php echo escape(app_url('branches.php')); ?>">Our branches</a>
    </div>
</section>

<section>
    <div class="section-heading">
        <h2>Bestselling packages</h2>
        <a href="<?php echo escape(app_url('offers.php')); ?>">All offers</a>
    </div>

    <?php if (!$bestsellers): ?>
        <p class="notice notice-info">No bestselling offers are currently listed. Please check back shortly.</p>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($bestsellers as $offer): ?>
                <?php require __DIR__ . '/includes/offer-card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section>
    <div class="section-heading">
        <h2>Current offers</h2>
    </div>

    <?php if (!$currentOffers): ?>
        <p class="notice notice-info">There are no current offers at the moment.</p>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($currentOffers as $offer): ?>
                <?php require __DIR__ . '/includes/offer-card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
