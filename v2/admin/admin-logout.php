<?php
/*
 * Staff logout (FR5). Same cookie wipe as the customer logout.php.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

start_app_session();
logout_admin();

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

redirect(app_url('admin/admin-login.php'));
