<?php
/*
 * Hotel search (FR11). City is the real filter.
 * Check-in / check-out are stored on the enquiry only; there is no
 * availability calendar (later phases). Price and stars are FR12 on the results.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

start_app_session();

$city      = trim((string) ($_GET['city'] ?? ''));
$checkIn   = trim((string) ($_GET['check_in'] ?? ''));
$checkOut  = trim((string) ($_GET['check_out'] ?? ''));
$maxPrice  = trim((string) ($_GET['max_price'] ?? ''));
$minStars  = trim((string) ($_GET['min_stars'] ?? ''));
$sort      = trim((string) ($_GET['sort'] ?? 'price'));

$errors = [];
$today  = today_iso();
$checkOutMin = $today;

if ($checkIn !== '') {
    if (!is_iso_date($checkIn)) {
        $errors['check_in'] = 'Use a valid check-in date.';
    } elseif (!is_today_or_future($checkIn)) {
        $errors['check_in'] = 'Check-in cannot be in the past. Choose today or a later date.';
    }
}
if ($checkOut !== '') {
    if (!is_iso_date($checkOut)) {
        $errors['check_out'] = 'Use a valid check-out date.';
    } elseif ($checkIn !== '' && empty($errors['check_in']) && $checkOut <= $checkIn) {
        $errors['check_out'] = 'Check-out must be after check-in.';
        $checkOut = '';
    } elseif ($checkIn === '' && !is_today_or_future($checkOut)) {
        $errors['check_out'] = 'Check-out cannot be in the past.';
    }
}

if ($checkIn !== '' && empty($errors['check_in'])) {
    $checkOutMin = next_iso_date($checkIn);
}

if ($maxPrice !== '' && !in_array($maxPrice, ['150', '250', '400'], true)) {
    $maxPrice = '';
}
if ($minStars !== '' && !in_array($minStars, ['3', '4', '5'], true)) {
    $minStars = '';
}

$orderBy = sort_column($sort, [
    'price'  => 'price_per_night ASC, star_rating DESC',
    'rating' => 'star_rating DESC, price_per_night ASC',
], 'price');
$sort = array_key_exists($sort, ['price' => true, 'rating' => true]) ? $sort : 'price';

// 1=1 so we can append AND city / price / stars without a special first-clause case.
$selectSql = 'SELECT id, name, city, star_rating, price_per_night, amenities, image_url
              FROM hotels
              WHERE 1=1';
$params = [];

if ($city !== '') {
    $selectSql .= ' AND LOWER(city) = LOWER(?)';
    $params[]   = $city;
}

// Unfiltered city set, same trick as flights: distinguish empty city vs tight filters.
$citySql    = $selectSql . ' ORDER BY ' . $orderBy;
$cityParams = $params;

if ($maxPrice !== '') {
    $selectSql .= ' AND price_per_night <= ?';
    $params[]   = $maxPrice;
}
if ($minStars !== '') {
    $selectSql .= ' AND star_rating >= ?';
    $params[]   = (int) $minStars;
}

$selectSql .= ' ORDER BY ' . $orderBy;

$hotels     = [];
$cityHotels = [];
if (!$errors) {
    $cityStmt = get_pdo()->prepare($citySql);
    $cityStmt->execute($cityParams);
    $cityHotels = $cityStmt->fetchAll();

    $stmt = get_pdo()->prepare($selectSql);
    $stmt->execute($params);
    $hotels = $stmt->fetchAll();
}

$cityStmt = get_pdo()->prepare('SELECT DISTINCT city FROM hotels ORDER BY city');
$cityStmt->execute();
$cities = $cityStmt->fetchAll();

$searched = $_GET !== [];

// Unknown city: list the cities we do have instead of a blank page.
$altCities = [];

if ($searched && !$cityHotels && !$errors && $city !== '') {
    $cityAlt = get_pdo()->prepare(
        'SELECT city, COUNT(*) AS hotel_count, MIN(price_per_night) AS from_price
         FROM hotels
         GROUP BY city
         ORDER BY city'
    );
    $cityAlt->execute();
    $altCities = $cityAlt->fetchAll();
}

$page_title = 'Search hotels';
require __DIR__ . '/includes/header.php';
?>

<div class="page-intro">
    <h1>Search hotels</h1>
    <p class="lede">Search by city and optional dates. Refine price and star rating on the results. Dates are for your enquiry — availability is confirmed in branch.</p>
</div>

<form class="search-bar" method="get" action="" data-validate>
    <div class="form-grid">
        <div>
            <label for="city">City</label>
            <input type="text" id="city" name="city" list="hotel-cities" value="<?php echo escape($city); ?>" placeholder="e.g. Paris">
        </div>
        <div>
            <label for="check_in">Check-in (optional)</label>
            <input type="date" id="check_in" name="check_in" min="<?php echo escape($today); ?>" value="<?php echo escape($checkIn); ?>">
            <?php if (!empty($errors['check_in'])): ?><p class="field-error"><?php echo escape($errors['check_in']); ?></p><?php endif; ?>
        </div>
        <div>
            <label for="check_out">Check-out (optional)</label>
            <input type="date" id="check_out" name="check_out" min="<?php echo escape($checkOutMin); ?>" data-min-today="<?php echo escape($today); ?>" value="<?php echo escape($checkOut); ?>">
            <?php if (!empty($errors['check_out'])): ?><p class="field-error"><?php echo escape($errors['check_out']); ?></p><?php endif; ?>
        </div>
        <div class="search-bar-action">
            <button type="submit" class="btn">Search hotels</button>
        </div>
    </div>
    <datalist id="hotel-cities">
        <?php foreach ($cities as $row): ?>
            <option value="<?php echo escape($row['city']); ?>"></option>
        <?php endforeach; ?>
    </datalist>
</form>

<?php if ($errors): ?>
    <p class="notice notice-error">Please correct the highlighted fields. Past dates are not allowed, and check-out must be after check-in.</p>
<?php endif; ?>

<?php if (($checkIn || $checkOut) && empty($errors['check_in']) && empty($errors['check_out'])): ?>
    <p class="meta">
        Enquiry dates:
        <?php echo $checkIn ? escape(format_date($checkIn)) : 'open'; ?>
        –
        <?php echo $checkOut ? escape(format_date($checkOut)) : 'open'; ?>
    </p>
<?php endif; ?>

<?php if ($searched && !$cityHotels && !$errors): ?>
    <div class="empty-state">
        <h2>No hotels matched that search</h2>
        <?php if ($city !== ''): ?>
            <p>We do not currently list hotels in <?php echo escape($city); ?>.</p>
        <?php else: ?>
            <p>Nothing matched that city.</p>
        <?php endif; ?>
    </div>

    <?php if ($altCities): ?>
        <section class="alt-panel">
            <h2>Cities we do list</h2>
            <p class="meta">Choose a city to see hotels there.</p>
            <ul class="alt-links">
                <?php foreach ($altCities as $row): ?>
                    <li>
                        <a class="alt-link" href="<?php echo escape(query_url('search-hotels.php', [
                            'city'      => $row['city'],
                            'check_in'  => $checkIn,
                            'check_out' => $checkOut,
                        ])); ?>">
                            <?php echo escape($row['city']); ?>
                            — <?php echo (int) $row['hotel_count']; ?> hotel<?php echo (int) $row['hotel_count'] === 1 ? '' : 's'; ?>
                            from <?php echo escape(format_price($row['from_price'])); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
<?php elseif ($searched && $cityHotels && !$hotels && !$errors): ?>
    <?php require __DIR__ . '/includes/hotel-result-filters.php'; ?>
    <div class="empty-state">
        <h2>No hotels match these refinements</h2>
        <p>Properties exist for that city, but none sit inside the price or star-rating limits. Try Any price or a lower star rating.</p>
    </div>
<?php elseif ($hotels): ?>
    <?php require __DIR__ . '/includes/hotel-result-filters.php'; ?>
    <div class="card-grid">
        <?php foreach ($hotels as $hotel): ?>
            <?php require __DIR__ . '/includes/hotel-card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p class="notice notice-info">Choose a city or leave the fields blank and search to see every partner hotel.</p>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
