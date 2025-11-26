<?php
require __DIR__.'/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: auth.php');
    exit;
}

function fail($msg) {
    $_SESSION['auth_error'] = $msg;
    $_SESSION['auth_error_tab'] = 'login'; // rester sur l’onglet Connexion
    header('Location: auth.php');
    exit;
}

$identifier = trim($_POST['identifier'] ?? '');
$password   = $_POST['password'] ?? '';

if ($identifier === '' || $password === '') {
    fail("Identifiants manquants.");
}

$st = $pdo->prepare("SELECT id, username, email, phone, password_hash FROM ".TABLE_USERS."
                     WHERE username = :id OR email = :id LIMIT 1");
$st->execute([':id' => $identifier]);
$user = $st->fetch();

if (!$user) {
    $digits = preg_replace('/\D+/', '', $identifier);
    if ($digits !== '') {
        $st = $pdo->prepare("SELECT id, username, email, phone, password_hash
                             FROM ".TABLE_USERS."
                             WHERE REPLACE(REPLACE(phone, '+',''), ' ', '') = :d
                             LIMIT 1");
        $st->execute([':d' => $digits]);
        $user = $st->fetch();
    }
}

if (!$user || !password_verify($password, $user['password_hash'])) {
    fail("Identifiant ou mot de passe incorrect.");
}

$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['username'] = $user['username'];

header('Location: ' . APP_HOME);
exit;
