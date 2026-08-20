<?php
/*
 * Shared helpers for Version 1 (public site + staff offer admin).
 *
 * Section 7: keep escaping, validators, redirects and CSRF in one file.
 * Section 8: every value printed in HTML should go through escape().
 * Version 2 copies this file and adds search / booking helpers on top.
 */

/**
 * escape($value)
 * Make a value safe to print in HTML. htmlspecialchars with ENT_QUOTES
 * so quotes in titles cannot break out of attributes.
 * Anything from the database or a form should go through this first.
 */
function escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * app_url($path)
 * Relative URL for this version folder (v1/).
 * Admin scripts sit in /v1/admin/ so they need ../ to reach CSS and public pages.
 * Absolute paths from the domain root would miss /v1/ on the live host.
 */
function app_url(string $path = ''): string
{
    $path = ltrim($path, '/');

    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir  = str_replace('\\', '/', dirname($scriptName));

    if (preg_match('#/admin$#', $scriptDir) || $scriptDir === '/admin') {
        return '../' . $path;
    }

    return $path;
}

/**
 * media_url($stored)
 * Turn a stored image path (often /assets/images/paris.png) into a working
 * URL for the current page. External http(s) URLs are left as-is.
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
 * Used on the contact form and the staff add/edit offer form.
 */
function is_required($value): bool
{
    return trim((string) $value) !== '';
}

/**
 * is_valid_email($email)
 * True when PHP's filter accepts the address. Used on the contact form.
 */
function is_valid_email($email): bool
{
    return filter_var((string) $email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * is_numeric_value($value)
 * True for integers or decimals (price on the offer form).
 */
function is_numeric_value($value): bool
{
    return is_numeric($value);
}

/**
 * is_non_negative_number($value)
 * Price cannot be negative when staff add or edit an offer (FR5).
 */
function is_non_negative_number($value): bool
{
    return is_numeric($value) && (float) $value >= 0;
}

/**
 * redirect($path)
 * Send a Location header and stop so nothing else on the page runs.
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
 * Turn a YYYY-MM-DD database date into 16 Aug 2026 for display.
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
 * Forms include this as a hidden field; csrf_verify() checks it on POST.
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
 * hash_equals avoids a timing leak when comparing the two strings.
 */
function csrf_verify($token): bool
{
    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * csrf_field()
 * HTML for the hidden CSRF input. Session must already be started.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . escape(csrf_token())
        . '">';
}

/**
 * query_url($path, $params)
 * Build a page URL from a path and query parameters.
 * Empty values are dropped so the query string stays tidy.
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
 * the current value (used on the contact form branch list).
 */
function selected_attr($current, $value): string
{
    return (string) $current === (string) $value ? ' selected' : '';
}

/**
 * enquiry_url($params)
 * Link to the contact form with offer details in the query string.
 * Offer cards use this for "Ask a branch to book".
 */
function enquiry_url(array $params): string
{
    return query_url('contact.php', $params);
}

/**
 * enquiry_plain($value, $max)
 * Flatten query-string fragments to a single safe line before they go
 * into the contact message. Caps length so the textarea cannot be flooded.
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
 * Draft the contact-form message when someone clicks through from an offer
 * card. $q is the query string. Returns an empty string if there is nothing
 * to draft (plain contact form).
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
