<?php
session_start();

// Clear session data and destroy the session cookie if it exists.
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

session_destroy();

// Redirect to the authentication page after logout.
header('Location: /auth.php');
exit;
