<?php
/*
 * Staff login helpers for Version 1.
 * FR5: only a logged-in admin can add / edit / delete offers.
 * Customer accounts do not exist until Version 2 (see v2/includes/auth.php).
 */

/**
 * start_app_session()
 * Start the PHP session if it is not already active.
 * Called from the shared header so admin pages can test the login flag
 * and the contact form can keep CSRF working.
 *
 * cookie_httponly: JavaScript cannot read the session cookie.
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
 * True when a staff member has a valid admin session.
 */
function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

/**
 * require_admin()
 * Guard: redirect to the staff login page when no admin session exists.
 * Include this at the top of every page under admin/ except admin-login.php.
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
 * Regenerates the session id so a stolen pre-login cookie cannot be reused
 * (session fixation, Section 8).
 */
function login_admin(int $adminId, string $username): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $adminId;
    $_SESSION['admin_username'] = $username;
}

/**
 * logout_admin()
 * Clear the staff session keys. admin-logout.php then destroys the cookie.
 */
function logout_admin(): void
{
    unset($_SESSION['admin_id'], $_SESSION['admin_username']);
}
