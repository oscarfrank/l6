<?php
/*
 * Shared helpers for Version 2.
 *
 * Section 7: keep escaping, validators, redirects and CSRF in one file
 * instead of copying them onto every page.
 * Section 8: anything printed in HTML goes through escape().
 *
 * Extra helpers vs Version 1 are for search (FR10-FR12) and bookings (FR9):
 * duration / datetime formatters, a sort whitelist, status pills, flash
 * messages, and the open-redirect guard after login.
 */

/**
 * escape($value)
 * Make a value safe to print in HTML. htmlspecialchars with ENT_QUOTES
 * so quotes cannot break out of attributes. Every database / form value
 * that goes into a page should pass through this (Section 8).
 */
function escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * app_url($path)
 * Build a link to a file in this version folder (v2/).
 *
 * Customer pages sit in the version root (index.php, login.php, ...).
 * Staff pages sit under /admin, so they need ../ to reach CSS and public pages.
 * A leading slash like /assets/... only works if v2/ is the site root.
 * On CloudPanel both versions sit under the same domain, so paths are
 * relative to the current script instead.
 */
function app_url(string $path = ''): string
{
    $path = ltrim($path, '/');

    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir  = str_replace('\\', '/', dirname($scriptName));

    // Staff pages live in /admin, one level below the version root.
    if (preg_match('#/admin$#', $scriptDir) || $scriptDir === '/admin') {
        return '../' . $path;
    }

    return $path;
}

/**
 * media_url($stored)
 * Offers / hotels store a path like /assets/images/paris.png.
 * Turn that into a working URL for the current page.
 * Full http(s) URLs are left alone in case staff paste an external image.
 * Empty values fall back to the Paris image so cards never have a broken src.
 */
function media_url(?string $stored): string
{
    $stored = trim((string) $stored);
    if ($stored === '') {
        return app_url('assets/images/paris.png');
    }
    if (preg_match('#^https?://#i', $stored)) {
        return $stored;
    }
    return app_url(ltrim($stored, '/'));
}

/**
 * is_required($value)
 * True when the field is not empty after trimming spaces.
 * Used on register, contact, login, and the offer admin form.
 */
function is_required($value): bool
{
    return trim((string) $value) !== '';
}

/**
 * is_valid_email($email)
 * True when PHP's filter accepts the address. Used on register, login
 * and the contact form so we don't store or mail obviously bad emails.
 */
function is_valid_email($email): bool
{
    return filter_var((string) $email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * is_numeric_value($value)
 * True for integers or decimals (price fields).
 */
function is_numeric_value($value): bool
{
    return is_numeric($value);
}

/**
 * is_non_negative_number($value)
 * Price cannot be negative. Used when staff add / edit an offer (FR5).
 */
function is_non_negative_number($value): bool
{
    return is_numeric($value) && (float) $value >= 0;
}

/**
 * redirect($path)
 * Send a Location header and stop. exit is required so the rest of the
 * script cannot keep printing HTML after the redirect.
 */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/**
 * format_price($price)
 * Show a stored decimal as sterling, e.g. 429.00 becomes £429.00.
 */
function format_price($price): string
{
    return '£' . number_format((float) $price, 2);
}

/**
 * format_date($date)
 * Turn a YYYY-MM-DD database date into something readable (16 Aug 2026).
 * Invalid values are escaped and returned as-is rather than crashing.
 */
function format_date($date): string
{
    if ($date === null || $date === '') {
        return '';
    }
    $dt = DateTime::createFromFormat('Y-m-d', (string) $date);
    if ($dt === false) {
        return escape((string) $date);
    }
    return $dt->format('j M Y');
}

/**
 * csrf_token()
 * Return the per-session CSRF token, creating one if needed.
 * random_bytes(32) gives 64 hex characters. Forms include this as a
 * hidden field; csrf_verify() checks it on POST (Section 8).
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * csrf_verify($token)
 * True when the posted token matches the session token.
 * hash_equals is used so the comparison is not vulnerable to timing attacks.
 */
function csrf_verify($token): bool
{
    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * csrf_field()
 * HTML for the hidden CSRF input. Call only after the session has started.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . escape(csrf_token())
        . '">';
}

/**
 * format_duration($minutes)
 * Flights store duration as minutes. Show 130 as 2h 10m on result cards (FR10).
 */
function format_duration($minutes): string
{
    $minutes = (int) $minutes;
    $hours   = intdiv($minutes, 60);
    $remain  = $minutes % 60;
    return $hours . 'h ' . str_pad((string) $remain, 2, '0', STR_PAD_LEFT) . 'm';
}

/**
 * format_datetime($value)
 * Format a MySQL DATETIME for flight cards, e.g. 20 Aug, 07:15.
 */
function format_datetime($value): string
{
    if ($value === null || $value === '') {
        return '';
    }
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', (string) $value);
    if ($dt === false) {
        $dt = date_create((string) $value);
    }
    if ($dt === false) {
        return escape((string) $value);
    }
    return $dt->format('j M, H:i');
}

/**
 * sort_column($requested, $allowed, $default)
 * Map a sort key from the query string onto a known ORDER BY clause.
 *
 * MySQL will not let you bind ORDER BY as a placeholder the way WHERE can,
 * so the value from ?sort= cannot be dropped into SQL raw. If the key is
 * not in $allowed we use $default instead (FR12).
 */
function sort_column(string $requested, array $allowed, string $default): string
{
    return $allowed[$requested] ?? $allowed[$default] ?? reset($allowed);
}

/**
 * today_iso()
 * Today's date as YYYY-MM-DD in the server timezone.
 * Used as the minimum allowed travel / check-in date (FR10-FR12).
 */
function today_iso(): string
{
    return date('Y-m-d');
}

/**
 * is_iso_date($value)
 * True when the string is a real calendar date in YYYY-MM-DD form.
 * createFromFormat still accepts 2026-13-40 unless we check that format()
 * round-trips to the same string.
 */
function is_iso_date($value): bool
{
    $value = (string) $value;
    $dt = DateTime::createFromFormat('Y-m-d', $value);
    return $dt instanceof DateTime && $dt->format('Y-m-d') === $value;
}

/**
 * is_today_or_future($value)
 * Past dates are rejected on the search forms so a customer cannot enquire
 * for travel that has already happened (FR10 / FR11).
 */
function is_today_or_future($value): bool
{
    return is_iso_date($value) && (string) $value >= today_iso();
}

/**
 * next_iso_date($value)
 * Return YYYY-MM-DD for the day after $value.
 * Hotel check-out cannot be the same day as (or before) check-in.
 */
function next_iso_date(string $value): string
{
    $dt = DateTime::createFromFormat('Y-m-d', $value);
    if (!$dt instanceof DateTime) {
        return today_iso();
    }
    $dt->modify('+1 day');
    return $dt->format('Y-m-d');
}

/**
 * query_url($path, $params)
 * Build a page URL from a path and query parameters.
 * Empty values are dropped so "pick another date" links stay short.
 */
function query_url(string $path, array $params): string
{
    $filtered = [];
    foreach ($params as $key => $value) {
        if ($value !== '' && $value !== null) {
            $filtered[$key] = $value;
        }
    }
    $query = http_build_query($filtered);
    return app_url($path) . ($query !== '' ? '?' . $query : '');
}

/**
 * selected_attr($current, $value)
 * Return the HTML selected attribute when a <select> option matches
 * the current value, otherwise an empty string.
 */
function selected_attr($current, $value): string
{
    return (string) $current === (string) $value ? ' selected' : '';
}

/**
 * destination_image($destination)
 * Pick a local image for a booking / offer card from the destination name.
 * Unknown places fall back to Paris so a new offer without a photo still
 * has a card image.
 */
function destination_image(?string $destination): string
{
    $key = strtolower(trim((string) $destination));
    $map = [
        'paris'               => 'paris.png',
        'rome'                => 'rome.png',
        'new york'            => 'newyork.png',
        'maldives'            => 'maldives.png',
        'santorini'           => 'santorini.png',
        'dubai'               => 'dubai.png',
        'barcelona'           => 'barcelona.png',
        'scottish highlands'  => 'highlands.png',
        'edinburgh'           => 'highlands.png',
        'tokyo'               => 'tokyo.png',
        'amsterdam'           => 'amsterdam.png',
    ];
    $file = $map[$key] ?? 'paris.png';
    return app_url('assets/images/' . $file);
}

/**
 * booking_status_class($status)
 * CSS class for the coloured status pill on dashboard / staff inbox cards (FR9).
 */
function booking_status_class(?string $status): string
{
    $status = strtolower(trim((string) $status));
    return match ($status) {
        'requested'           => 'status-pill status-wait',
        'paid'                => 'status-pill status-paid',
        'completed'           => 'status-pill status-done',
        'declined', 'cancelled' => 'status-pill status-cancelled',
        default               => 'status-pill status-live',
    };
}

/**
 * booking_status_label($status)
 * Customer-facing label for a booking status (Requested, Confirmed, Paid, ...).
 */
function booking_status_label(?string $status): string
{
    $status = strtolower(trim((string) $status));
    return match ($status) {
        'requested' => 'Requested',
        'confirmed' => 'Confirmed',
        'paid'      => 'Paid',
        'completed' => 'Completed',
        'declined'  => 'Declined',
        'cancelled' => 'Cancelled',
        default     => 'On file',
    };
}

/**
 * safe_next_path($value)
 * After login we send the user back to ?next=...
 * Only allow filenames we actually have, and block //evil.com style URLs
 * so this cannot be used as an open redirect.
 */
function safe_next_path($value): string
{
    $value = trim((string) $value);
    if ($value === '' || str_contains($value, '://') || str_starts_with($value, '//')) {
        return '';
    }

    $path  = ltrim((string) (parse_url($value, PHP_URL_PATH) ?? ''), '/');
    $query = (string) (parse_url($value, PHP_URL_QUERY) ?? '');
    $base  = basename($path);

    $allowed = [
        'index.php',
        'offers.php',
        'branches.php',
        'contact.php',
        'dashboard.php',
        'search-flights.php',
        'search-hotels.php',
        'request-booking.php',
        'register.php',
    ];
    if (!in_array($base, $allowed, true)) {
        return '';
    }

    // Request a package needs the offer id or the page has nothing to show.
    if ($base === 'request-booking.php') {
        parse_str($query, $parts);
        $id = (int) ($parts['offer_id'] ?? 0);
        return $id > 0 ? 'request-booking.php?offer_id=' . $id : '';
    }

    return $base;
}

/**
 * set_flash($message)
 * Store a one-time notice in the session (e.g. "request sent").
 * take_flash() reads it on the next page and then deletes it.
 */
function set_flash(string $message): void
{
    $_SESSION['flash_message'] = $message;
}

/**
 * take_flash()
 * Return the flash message and clear it so it only shows once.
 */
function take_flash(): string
{
    $message = (string) ($_SESSION['flash_message'] ?? '');
    unset($_SESSION['flash_message']);
    return $message;
}

/**
 * enquiry_url($params)
 * Link to the contact form with enquiry details in the query string
 * (offer / flight / hotel cards use this).
 */
function enquiry_url(array $params): string
{
    return query_url('contact.php', $params);
}

/**
 * enquiry_plain($value, $max)
 * Flatten user-supplied enquiry fragments to a single safe line
 * before they go into the contact message. Caps length so the query
 * string cannot dump a huge blob into the textarea.
 */
function enquiry_plain($value, int $max = 120): string
{
    $value = trim((string) $value);
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max);
    }
    return substr($value, 0, $max);
}

/**
 * build_enquiry_message($q)
 * Draft the contact-form message when someone clicks "ask a branch"
 * on an offer, flight or hotel card. $q is the query string (or equivalent).
 * Returns an empty string if there is nothing to draft.
 */
function build_enquiry_message(array $q): string
{
    $about = strtolower(enquiry_plain($q['about'] ?? '', 20));

    if ($about === 'flight') {
        $airline = enquiry_plain($q['airline'] ?? '') ?: 'the listed airline';
        $origin  = enquiry_plain($q['origin'] ?? '');
        $dest    = enquiry_plain($q['destination'] ?? '');
        $route   = ($origin !== '' && $dest !== '') ? $origin . ' to ' . $dest : 'this route';
        $when    = enquiry_plain($q['depart'] ?? '');
        $price   = enquiry_plain($q['price'] ?? '');
        $whenBit = $when !== '' ? ', departing ' . $when : '';
        $costBit = $price !== '' ? ', currently listed at ' . format_price($price) : '';
        return "Hello,\n\nI would like to hold a seat on {$airline} from {$route}{$whenBit}{$costBit}. Please check availability and call me back to book.\n\nThank you.";
    }

    if ($about === 'hotel') {
        $name  = enquiry_plain($q['name'] ?? '') ?: 'this hotel';
        $city  = enquiry_plain($q['city'] ?? '');
        $in    = enquiry_plain($q['check_in'] ?? '');
        $out   = enquiry_plain($q['check_out'] ?? '');
        $price = enquiry_plain($q['price'] ?? '');
        $place = $city !== '' ? $name . ' in ' . $city : $name;
        $dates = '';
        if ($in !== '' && $out !== '') {
            $dates = ', from ' . $in . ' to ' . $out;
        } elseif ($in !== '') {
            $dates = ', checking in ' . $in;
        }
        $costBit = $price !== '' ? ', listed at ' . format_price($price) . ' per night' : '';
        return "Hello,\n\nI would like to check availability at {$place}{$dates}{$costBit}. Please confirm and call me back to book.\n\nThank you.";
    }

    if ($about === 'offer') {
        $title = enquiry_plain($q['title'] ?? '') ?: 'this package';
        $dest  = enquiry_plain($q['destination'] ?? '');
        $price = enquiry_plain($q['price'] ?? '');
        $place = $dest !== '' ? $title . ' to ' . $dest : $title;
        $costBit = $price !== '' ? ', listed at ' . format_price($price) . ' per person' : '';
        return "Hello,\n\nI am interested in {$place}{$costBit}. Please contact me so we can book this offer through the branch.\n\nThank you.";
    }

    return '';
}
