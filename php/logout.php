<?php
require __DIR__.'/config.php';

// Invalide la session proprement
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

// Si appel direct via navigateur -> redirige vers auth
if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Location: auth.php');
    exit;
}

// Si appelé en fetch() -> renvoie un OK JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true]);
