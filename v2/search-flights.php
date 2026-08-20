<?php
/*
 * Flight search (FR10) with refine controls on the results (FR12).
 *
 * GET so a result URL can be bookmarked. Values are bound as placeholders.
 * We run the route/date query twice conceptually: once unfiltered (to know
 * if the route exists) and once with price/stops/duration applied. That
 * lets the empty state say "no flights" vs "filters hid them all".
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

start_app_session();

// GET for search + FR12 filters. Shareable URL, and PHP can still bind the values.
$origin      = trim((string) ($_GET['origin'] ?? ''));
$destination = trim((string) ($_GET['destination'] ?? ''));
$date        = trim((string) ($_GET['date'] ?? ''));
$maxPrice    = trim((string) ($_GET['max_price'] ?? ''));
$maxStops    = trim((string) ($_GET['max_stops'] ?? ''));
$maxDuration = trim((string) ($_GET['max_duration'] ?? ''));
$sort        = trim((string) ($_GET['sort'] ?? 'price'));

$errors = [];
$today  = today_iso();

if ($origin !== '' && $destination !== '' && strcasecmp($origin, $destination) === 0) {
    $errors['destination'] = 'Destination must be different from origin.';
}

if ($date !== '') {
    if (!is_iso_date($date)) {
        $errors['date'] = 'Use a valid travel date.';
    } elseif (!is_today_or_future($date)) {
        $errors['date'] = 'Travel date cannot be in the past. Choose today or a later date.';
    }
}

// Only accept the option values from the <select>s. Anything else is ignored.
if ($maxPrice !== '' && !in_array($maxPrice, ['100', '250', '500'], true)) {
    $maxPrice = '';
}
if ($maxStops !== '' && !in_array($maxStops, ['0', '1', '2'], true)) {
    $maxStops = '';
}
if ($maxDuration !== '' && !in_array($maxDuration, ['180', '360', '720'], true)) {
    $maxDuration = '';
}

$orderBy = sort_column($sort, [
    'price'    => 'price ASC, duration_minutes ASC',
    'duration' => 'duration_minutes ASC, price ASC',
], 'price');
$sort = array_key_exists($sort, ['price' => true, 'duration' => true]) ? $sort : 'price';

// Build WHERE from whatever they filled in. Always hide past departures.
$selectSql = 'SELECT id, airline, origin, destination, depart_time, arrive_time,
                     duration_minutes, stops, price
              FROM flights
              WHERE DATE(depart_time) >= CURDATE()';
$params = [];

if (empty($errors['destination']) && $origin !== '') {
    $selectSql .= ' AND LOWER(origin) = LOWER(?)';
    $params[]   = $origin;
}
if (empty($errors['destination']) && $destination !== '') {
    $selectSql .= ' AND LOWER(destination) = LOWER(?)';
    $params[]   = $destination;
}
if ($date !== '' && empty($errors['date'])) {
    $selectSql .= ' AND DATE(depart_time) = ?';
    $params[]   = $date;
}

// Keep a copy without FR12 filters so we can tell "no flights" from "filters too tight".
$routeSql    = $selectSql . ' ORDER BY ' . $orderBy;
$routeParams = $params;

if ($maxPrice !== '') {
    $selectSql .= ' AND price <= ?';
    $params[]   = $maxPrice;
}
if ($maxStops !== '') {
    $selectSql .= ' AND stops <= ?';
    $params[]   = (int) $maxStops;
}
if ($maxDuration !== '') {
    $selectSql .= ' AND duration_minutes <= ?';
    $params[]   = (int) $maxDuration;
}

$selectSql .= ' ORDER BY ' . $orderBy;

$flights      = [];
$routeFlights = [];
if (!$errors) {
    $routeStmt = get_pdo()->prepare($routeSql);
    $routeStmt->execute($routeParams);
    $routeFlights = $routeStmt->fetchAll();

    $stmt = get_pdo()->prepare($selectSql);
    $stmt->execute($params);
    $flights = $stmt->fetchAll();
}

// Cities for the datalist suggestions (no extra JS library).
$citiesStmt = get_pdo()->prepare(
    'SELECT DISTINCT origin AS city FROM flights
     UNION
     SELECT DISTINCT destination FROM flights
     ORDER BY city'
);
$citiesStmt->execute();
$cities = $citiesStmt->fetchAll();

$searched = $_GET !== [];

// If that route/date is empty, show other dates on the same route, or other airports.
$altDates        = [];
$altFlights      = [];
$altDestinations = [];
$altOrigins      = [];

if ($searched && !$routeFlights && !$errors) {
    $pdo = get_pdo();

    if ($origin !== '' && $destination !== '') {
        $dateStmt = $pdo->prepare(
            'SELECT DATE(depart_time) AS travel_date,
                    COUNT(*) AS flight_count,
                    MIN(price) AS from_price
             FROM flights
             WHERE DATE(depart_time) >= CURDATE()
               AND LOWER(origin) = LOWER(?)
               AND LOWER(destination) = LOWER(?)
             GROUP BY DATE(depart_time)
             ORDER BY travel_date ASC'
        );
        $dateStmt->execute([$origin, $destination]);
        $altDates = $dateStmt->fetchAll();

        $sampleStmt = $pdo->prepare(
            'SELECT id, airline, origin, destination, depart_time, arrive_time,
                    duration_minutes, stops, price
             FROM flights
             WHERE DATE(depart_time) >= CURDATE()
               AND LOWER(origin) = LOWER(?)
               AND LOWER(destination) = LOWER(?)
             ORDER BY depart_time ASC, price ASC
             LIMIT 6'
        );
        $sampleStmt->execute([$origin, $destination]);
        $altFlights = $sampleStmt->fetchAll();
    }

    // Route has no future rows at all: suggest other destinations from this origin.
    if (!$altDates && $origin !== '') {
        $toStmt = $pdo->prepare(
            'SELECT destination,
                    MIN(DATE(depart_time)) AS next_date,
                    MIN(price) AS from_price
             FROM flights
             WHERE DATE(depart_time) >= CURDATE()
               AND LOWER(origin) = LOWER(?)
             GROUP BY destination
             ORDER BY destination'
        );
        $toStmt->execute([$origin]);
        $altDestinations = $toStmt->fetchAll();
    }

    if (!$altDates && $destination !== '') {
        $fromStmt = $pdo->prepare(
            'SELECT origin,
                    MIN(DATE(depart_time)) AS next_date,
                    MIN(price) AS from_price
             FROM flights
             WHERE DATE(depart_time) >= CURDATE()
               AND LOWER(destination) = LOWER(?)
             GROUP BY origin
             ORDER BY origin'
        );
        $fromStmt->execute([$destination]);
        $altOrigins = $fromStmt->fetchAll();
    }
}

$page_title = 'Search flights';
require __DIR__ . '/includes/header.php';
?>

<div class="page-intro">
    <h1>Search flights</h1>
    <p class="lede">Search by route and date. Refine price, travel time and stops on the results. Booking is not available in this phase — call a branch to hold a seat.</p>
</div>

<form class="search-bar" method="get" action="" data-validate>
    <div class="form-grid">
        <div>
            <label for="origin">Origin</label>
            <input type="text" id="origin" name="origin" list="city-list" value="<?php echo escape($origin); ?>" placeholder="e.g. London">
        </div>
        <div>
            <label for="destination">Destination</label>
            <input type="text" id="destination" name="destination" list="city-list" value="<?php echo escape($destination); ?>" placeholder="e.g. Paris">
            <?php if (!empty($errors['destination'])): ?><p class="field-error"><?php echo escape($errors['destination']); ?></p><?php endif; ?>
        </div>
        <div>
            <label for="date">Date</label>
            <input type="date" id="date" name="date" min="<?php echo escape($today); ?>" value="<?php echo escape($date); ?>">
            <?php if (!empty($errors['date'])): ?><p class="field-error"><?php echo escape($errors['date']); ?></p><?php endif; ?>
        </div>
        <div class="search-bar-action">
            <button type="submit" class="btn">Search flights</button>
        </div>
    </div>
    <datalist id="city-list">
        <?php foreach ($cities as $city): ?>
            <option value="<?php echo escape($city['city']); ?>"></option>
        <?php endforeach; ?>
    </datalist>
</form>

<?php if ($errors): ?>
    <p class="notice notice-error">Please correct the highlighted fields. Past dates and the same city for origin and destination are not allowed.</p>
<?php endif; ?>

<?php if ($searched && !$routeFlights && !$errors): ?>
    <div class="empty-state">
        <h2>No flights matched that search</h2>
        <?php if ($date !== '' && $origin !== '' && $destination !== ''): ?>
            <p>Nothing published for <?php echo escape($origin); ?> → <?php echo escape($destination); ?> on <?php echo escape(format_date($date)); ?>.</p>
        <?php else: ?>
            <p>Nothing matched that route and date.</p>
        <?php endif; ?>
    </div>

    <?php if ($altDates): ?>
        <section class="alt-panel">
            <h2>Available dates for this route</h2>
            <p class="meta"><?php echo escape($origin); ?> → <?php echo escape($destination); ?>. Choose a date to see those flights.</p>
            <ul class="alt-dates">
                <?php foreach ($altDates as $row): ?>
                    <li>
                        <a class="date-chip" href="<?php echo escape(query_url('search-flights.php', [
                            'origin'      => $origin,
                            'destination' => $destination,
                            'date'        => $row['travel_date'],
                        ])); ?>">
                            <strong><?php echo escape(format_date($row['travel_date'])); ?></strong>
                            <span>
                                <?php echo (int) $row['flight_count']; ?> flight<?php echo (int) $row['flight_count'] === 1 ? '' : 's'; ?>
                                from <?php echo escape(format_price($row['from_price'])); ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if ($altFlights): ?>
        <div class="section-heading">
            <h2>Next flights on this route</h2>
        </div>
        <div class="card-grid two-col">
            <?php foreach ($altFlights as $flight): ?>
                <?php require __DIR__ . '/includes/flight-card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($altDestinations): ?>
        <section class="alt-panel">
            <h2>We do fly from <?php echo escape($origin); ?></h2>
            <p class="meta">No published flights to <?php echo $destination !== '' ? escape($destination) : 'that destination'; ?>. These routes are available:</p>
            <ul class="alt-links">
                <?php foreach ($altDestinations as $row): ?>
                    <li>
                        <a class="alt-link" href="<?php echo escape(query_url('search-flights.php', [
                            'origin'      => $origin,
                            'destination' => $row['destination'],
                            'date'        => $row['next_date'],
                        ])); ?>">
                            <?php echo escape($origin); ?> → <?php echo escape($row['destination']); ?>
                            (from <?php echo escape(format_price($row['from_price'])); ?>, next <?php echo escape(format_date($row['next_date'])); ?>)
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if ($altOrigins): ?>
        <section class="alt-panel">
            <h2>Other airports into <?php echo escape($destination); ?></h2>
            <ul class="alt-links">
                <?php foreach ($altOrigins as $row): ?>
                    <li>
                        <a class="alt-link" href="<?php echo escape(query_url('search-flights.php', [
                            'origin'      => $row['origin'],
                            'destination' => $destination,
                            'date'        => $row['next_date'],
                        ])); ?>">
                            <?php echo escape($row['origin']); ?> → <?php echo escape($destination); ?>
                            (from <?php echo escape(format_price($row['from_price'])); ?>)
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if (!$altDates && !$altDestinations && !$altOrigins): ?>
        <p class="notice notice-info">Try a nearby date or another UK city (London, Manchester, Edinburgh or Birmingham).</p>
    <?php endif; ?>
<?php elseif ($searched && $routeFlights && !$flights && !$errors): ?>
    <?php require __DIR__ . '/includes/flight-result-filters.php'; ?>
    <div class="empty-state">
        <h2>No flights match these refinements</h2>
        <p>Flights exist for that search, but none sit inside the price, stops or travel-time limits. Choose Any stops or a higher price.</p>
    </div>
<?php elseif ($flights): ?>
    <?php require __DIR__ . '/includes/flight-result-filters.php'; ?>
    <div class="card-grid two-col">
        <?php foreach ($flights as $flight): ?>
            <?php require __DIR__ . '/includes/flight-card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p class="notice notice-info">Enter a route or leave the fields blank and search to see every published flight.</p>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
