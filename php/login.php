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
$rankValues = ['user','premium','moderator','administrator'];
$hasRankCol = false;
try {
    $pdo->query("SELECT `rank` FROM `".TABLE_USERS."` LIMIT 0");
    $hasRankCol = true;
} catch (Throwable $e) {
    try {
        $pdo->exec("ALTER TABLE `".TABLE_USERS."` ADD COLUMN `rank` ENUM('user','premium','moderator','administrator') NOT NULL DEFAULT 'user' AFTER `gender`");
        $hasRankCol = true;
    } catch (Throwable $e2) {}
}

if ($identifier === '' || $password === '') {
    fail("Identifiants manquants.");
}

$selectFields = "id, username, email, phone, password_hash" . ($hasRankCol ? ", rank" : "");
$st = $pdo->prepare("SELECT {$selectFields} FROM ".TABLE_USERS." WHERE username = :id OR email = :id LIMIT 1");
$st->execute([':id' => $identifier]);
$user = $st->fetch();

if (!$user) {
    $digits = preg_replace('/\D+/', '', $identifier);
    if ($digits !== '') {
        $st = $pdo->prepare("SELECT {$selectFields} FROM ".TABLE_USERS." WHERE REPLACE(REPLACE(phone, '+',''), ' ', '') = :d LIMIT 1");
        $st->execute([':d' => $digits]);
        $user = $st->fetch();
    }
}

if (!$user || !password_verify($password, $user['password_hash'])) {
    fail("Identifiant ou mot de passe incorrect.");
}

$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['username'] = $user['username'];
$rank = ($hasRankCol && isset($user['rank']) && in_array($user['rank'], $rankValues, true)) ? $user['rank'] : 'user';
$_SESSION['rank'] = $rank;

header('Location: ' . APP_HOME);
exit;
