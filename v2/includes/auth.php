<?php
/*
 * Sessions for staff (FR5) and customers (FR6-FR9).
 *
 * Staff and customers use different session keys (admin_id vs user_id)
 * so logging into one area does not count as being logged into the other.
 * All guarded pages call require_admin() or require_login() at the top.
 */

/**
 * start_app_session()
 * Start the PHP session if it is not already active.
 * Called from the shared header so every page can read the login flags
 * and flash messages.
 *
 * cookie_httponly: JavaScript on the page cannot read the session cookie.
 * use_strict_mode: ignore a session id the browser invented itself.
 */
function start_app_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode', '1');
        session_start();
    }
}

/**
 * is_admin_logged_in()
 * True when a staff member has a valid admin session (FR5).
 */
function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

/**
 * require_admin()
 * Guard for every page under /admin except admin-login.php.
 * If there is no admin session, send them to the staff login page.
 */
function require_admin(): void
{
    start_app_session();

    if (!is_admin_logged_in()) {
        redirect(app_url('admin/admin-login.php'));
    }
}

/**
 * login_admin($adminId, $username)
 * Record a successful staff login.
 * session_regenerate_id(true) issues a new session id so a stolen
 * pre-login cookie cannot be reused (session fixation, Section 8).
 */
function login_admin(int $adminId, string $username): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $adminId;
    $_SESSION['admin_username'] = $username;
}

/**
 * logout_admin()
 * Clear the staff session keys. admin-logout.php then destroys the rest
 * of the session and the cookie.
 */
function logout_admin(): void
{
    unset($_SESSION['admin_id'], $_SESSION['admin_username']);
}

/**
 * is_user_logged_in()
 * True when a customer has a valid user session (FR7).
 */
function is_user_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * require_login()
 * Guard for dashboard.php and request-booking.php (FR8 / FR9).
 * Guests are sent to login.php with ?next= so they come back to the
 * same page after signing in. safe_next_path() stops open redirects.
 */
function require_login(): void
{
    start_app_session();

    if (!is_user_logged_in()) {
        $here = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'dashboard.php'));
        $qs   = (string) ($_SERVER['QUERY_STRING'] ?? '');
        $next = $here . ($qs !== '' ? '?' . $qs : '');
        $safe = function_exists('safe_next_path') ? safe_next_path($next) : '';
        $login = app_url('login.php');
        redirect($safe !== '' ? $login . '?next=' . rawurlencode($safe) : $login);
    }
}

/**
 * login_user($userId)
 * Record a successful customer login (FR7).
 * Regenerates the session id for the same reason as login_admin().
 */
function login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

/**
 * logout_user()
 * Clear the customer session key. logout.php then destroys the cookie.
 */
function logout_user(): void
{
    unset($_SESSION['user_id']);
}

/**
 * current_user()
 * Load the signed-in customer's stored contact details (FR8).
 * Password hash is never selected. Returns null if the session is empty
 * or the row has been removed.
 */
function current_user(): ?array
{
    if (!is_user_logged_in()) {
        return null;
    }

    $stmt = get_pdo()->prepare(
        'SELECT id, name, email, phone, address, created_at
         FROM users
         WHERE id = ?
         LIMIT 1'
    );
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}
