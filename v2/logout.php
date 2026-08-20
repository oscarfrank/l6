<?php
/*
 * Customer logout (FR7).
 * Clear the user_id, wipe the rest of $_SESSION, expire the cookie,
 * then send them back to login.php.
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

start_app_session();
logout_user();

// Empty the session array, then expire the cookie so the browser drops it.
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

redirect(app_url('login.php'));
