<?php
$pdo = require __DIR__ . '/config.php';

function getClientIp(): ?string
{
    foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $candidate = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }
    }

    return null;
}

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
$ipAddress = getClientIp();

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

    $updateIpStmt = $pdo->prepare('UPDATE users SET ip = :ip WHERE id = :id');
    $updateIpStmt->execute([
        ':ip' => $ipAddress,
        ':id' => $user['id'],
    ]);

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['rank'] = $user['rank'] ?? 'user';

    header('Location: ' . APP_HOME);
    exit;
} catch (Throwable $e) {
    if ($e instanceof PDOException && $e->getCode() === '42S22') {
        redirectWithLoginError('Schéma utilisateur obsolète : mettez à jour la table via sql/users_table.sql.');
    }

    redirectWithLoginError('Une erreur est survenue lors de la connexion.');
}
