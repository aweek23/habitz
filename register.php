<?php
$pdo = require __DIR__ . '/config.php';

function redirectWithError(string $message): void
{
    $_SESSION['auth_error'] = $message;
    $_SESSION['auth_error_tab'] = 'signup';
    header('Location: auth.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithError('Méthode non autorisée.');
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$dialCode = trim($_POST['dial_code'] ?? '');
$phoneLocal = trim($_POST['phone_local'] ?? '');
$birthdateRaw = trim($_POST['birthdate'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';

if ($username === '' || $email === '' || $birthdateRaw === '' || $password === '' || $passwordConfirm === '') {
    redirectWithError('Tous les champs obligatoires doivent être renseignés.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectWithError('Adresse email invalide.');
}

if ($password !== $passwordConfirm) {
    redirectWithError('Les mots de passe ne correspondent pas.');
}

$birthdate = DateTimeImmutable::createFromFormat('Y-m-d', $birthdateRaw);
if (!$birthdate) {
    redirectWithError('Date de naissance invalide.');
}

$phoneNumber = null;
if ($dialCode !== '' && $phoneLocal !== '') {
    $phoneNumber = $dialCode . ' ' . preg_replace('/\s+/', ' ', $phoneLocal);
}

try {
    $existingStmt = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email' . ($phoneNumber ? ' OR phone_number = :phone' : ''));
    $existingStmt->bindValue(':username', $username);
    $existingStmt->bindValue(':email', $email);
    if ($phoneNumber) {
        $existingStmt->bindValue(':phone', $phoneNumber);
    }
    $existingStmt->execute();
    if ($existingStmt->fetch()) {
        redirectWithError('Un compte existe déjà avec ces informations.');
    }

    $insertStmt = $pdo->prepare('INSERT INTO users (username, email, phone_number, birthdate, gender, password, rank, creation_date) VALUES (:username, :email, :phone_number, :birthdate, :gender, :password, :rank, :creation_date)');

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $creationDate = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');

    $insertStmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':phone_number' => $phoneNumber,
        ':birthdate' => $birthdate->format('Y-m-d'),
        ':gender' => $gender !== '' ? $gender : null,
        ':password' => $hashedPassword,
        ':rank' => 'user',
        ':creation_date' => $creationDate,
    ]);

    $_SESSION['user_id'] = (int) $pdo->lastInsertId();
    $_SESSION['username'] = $username;
    $_SESSION['rank'] = 'user';

    header('Location: ' . APP_HOME);
    exit;
} catch (Throwable $e) {
    redirectWithError('Erreur lors de la création du compte. Veuillez réessayer.');
}
