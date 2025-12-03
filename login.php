<?php
$pdo = require __DIR__ . '/config.php';

function redirectWithLoginError(string $message): void
{
    $_SESSION['auth_error'] = $message;
    $_SESSION['auth_error_tab'] = 'login';
    header('Location: auth.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithLoginError('Méthode non autorisée.');
}

$identifier = trim($_POST['identifier'] ?? '');
$password = $_POST['password'] ?? '';

if ($identifier === '' || $password === '') {
    redirectWithLoginError('Identifiants manquants.');
}

try {
    $stmt = $pdo->prepare('SELECT id, username, email, phone_number, password, rank FROM users WHERE username = :identifier OR email = :identifier OR phone_number = :identifier LIMIT 1');
    $stmt->execute([':identifier' => $identifier]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        redirectWithLoginError('Identifiant ou mot de passe incorrect.');
    }

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['rank'] = $user['rank'] ?? 'user';

    header('Location: ' . APP_HOME);
    exit;
} catch (Throwable $e) {
    redirectWithLoginError('Une erreur est survenue lors de la connexion.');
}
