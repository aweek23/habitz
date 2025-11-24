<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: auth.php');
    exit;
}

function redirect_with_error(string $message): void
{
    $_SESSION['auth_error'] = $message;
    $_SESSION['auth_error_tab'] = 'signup';
    header('Location: auth.php');
    exit;
}

function require_fields(string ...$values): void
{
    foreach ($values as $value) {
        if ($value === '') {
            redirect_with_error('Champs requis manquants.');
        }
    }
}

function validate_email(string $email): void
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirect_with_error('Email invalide.');
    }
}

function validate_passwords(string $password, string $confirmation): void
{
    $pattern = '/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d!@#$%^&*()_+\-=\[\]{};\'":\\|,.<>\/?~]{8,32}$/';

    require_fields($password, $confirmation);

    if ($password !== $confirmation) {
        redirect_with_error('Les mots de passe ne correspondent pas.');
    }

    if (!preg_match($pattern, $password)) {
        redirect_with_error('Le mot de passe doit contenir 8 à 32 caractères, une majuscule et un chiffre.');
    }
}

function validate_birthdate(string $raw): string
{
    $birthDate = DateTime::createFromFormat('Y-m-d', $raw);
    if (!$birthDate) {
        redirect_with_error('Date de naissance invalide.');
    }

    $today = new DateTime('today');
    if ($birthDate->diff($today)->y < 13) {
        redirect_with_error('Vous devez avoir au moins 13 ans.');
    }

    return $birthDate->format('Y-m-d');
}

function normalize_gender(string $gender): string
{
    $allowed = ['Homme', 'Femme', 'Autre'];
    return in_array($gender, $allowed, true) ? $gender : '';
}

function normalize_phone(string $dial, string $local): ?string
{
    if ($local === '') {
        return null;
    }

    $digits = preg_replace('/\D+/', '', $dial . $local);
    if ($digits === '' || strlen($digits) < 8 || strlen($digits) > 15) {
        redirect_with_error('Numéro de téléphone invalide.');
    }

    return '+' . $digits;
}

function ensure_gender_column(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT `gender` FROM `' . TABLE_USERS . '` LIMIT 0');
        return true;
    } catch (Throwable $exception) {
        try {
            $pdo->exec("ALTER TABLE `" . TABLE_USERS . "` ADD COLUMN `gender` ENUM('Homme','Femme','Autre') NULL DEFAULT NULL");
            return true;
        } catch (Throwable $ignored) {
            return false;
        }
    }
}

function assert_unique(PDO $pdo, string $column, string $value, string $message): void
{
    $statement = $pdo->prepare('SELECT id FROM ' . TABLE_USERS . ' WHERE ' . $column . ' = :value LIMIT 1');
    $statement->execute([':value' => $value]);

    if ($statement->fetch()) {
        redirect_with_error($message);
    }
}

function insert_user(PDO $pdo, array $data, bool $allowGender): int
{
    $columns = ['username', 'email', 'phone', 'password_hash', 'birthdate'];
    $placeholders = [':u', ':e', ':p', ':h', ':b'];
    $params = [
        ':u' => $data['username'],
        ':e' => $data['email'],
        ':p' => $data['phone'],
        ':h' => $data['password_hash'],
        ':b' => $data['birthdate'],
    ];

    if ($allowGender) {
        $columns[] = 'gender';
        $placeholders[] = ':g';
        $params[':g'] = $data['gender'] ?: null;
    }

    $sql = sprintf(
        'INSERT INTO %s (%s,created_at) VALUES (%s,NOW())',
        TABLE_USERS,
        implode(',', $columns),
        implode(',', $placeholders)
    );

    $insert = $pdo->prepare($sql);
    $insert->execute($params);

    return (int) $pdo->lastInsertId();
}

function seed_navigation(PDO $pdo, int $userId, string $gender): void
{
    $navCanon = [
        'tasks', 'habits', 'projects', 'sport', 'food', 'calendar', 'body', 'finances',
        'clock', 'events', 'news', 'drive',
    ];

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `' . TABLE_USER_NAV . '` (
            `user_id` INT NOT NULL,
            `menu_order` TEXT NOT NULL,
            `open_sections` TEXT NOT NULL,
            `disabled_keys` TEXT NOT NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `' . TABLE_USER_NAV_ITEMS . '` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT NOT NULL,
            `nav_key` VARCHAR(64) NOT NULL,
            `visible` ENUM("Yes","No") NOT NULL DEFAULT "Yes",
            `ord` INT NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `u_user_nav_item` (`user_id`,`nav_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `" . TABLE_USER_NAV . "` LIKE 'disabled_keys'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec('ALTER TABLE `' . TABLE_USER_NAV . "` ADD COLUMN `disabled_keys` TEXT NOT NULL DEFAULT '[]'");
        }
    } catch (Throwable $exception) {
        return;
    }

    if ($gender !== 'Homme' && $gender !== 'Autre') {
        return;
    }

    // Seed des items si absent
    try {
        $st = $pdo->prepare('SELECT COUNT(*) c FROM `' . TABLE_USER_NAV_ITEMS . '` WHERE user_id=:u');
        $st->execute([':u' => $userId]);
        $has = (int) $st->fetchColumn() > 0;
        if (!$has) {
            $ins = $pdo->prepare(
                'INSERT INTO `' . TABLE_USER_NAV_ITEMS . '` (user_id, nav_key, visible, ord) VALUES (:u,:k,"Yes",:o)'
            );
            foreach ($navCanon as $idx => $key) {
                $ins->execute([':u' => $userId, ':k' => $key, ':o' => $idx + 1]);
            }
        }
    } catch (Throwable $exception) {
        // Préférences optionnelles : on ignore les erreurs.
    }

    $seed = $pdo->prepare(
        "INSERT INTO `" . TABLE_USER_NAV . "` (user_id, menu_order, open_sections, disabled_keys)
         VALUES (:uid, :mo, :os, :dk)
         ON DUPLICATE KEY UPDATE
           disabled_keys = IF(disabled_keys IS NULL OR disabled_keys='' OR disabled_keys='[]', VALUES(disabled_keys), disabled_keys)"
    );

    try {
        $seed->execute([
            ':uid' => $userId,
            ':mo' => '[]',
            ':os' => '[]',
            ':dk' => json_encode(['cycle'], JSON_UNESCAPED_UNICODE),
        ]);
    } catch (Throwable $exception) {
        // Préférences optionnelles : on ignore les erreurs.
    }
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$dial = trim($_POST['dial_code'] ?? '');
$phoneLocal = trim($_POST['phone_local'] ?? '');
$birth = trim($_POST['birthdate'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConfirmation = $_POST['password_confirm'] ?? '';

require_fields($username, $email, $birth);
validate_email($email);
validate_passwords($password, $passwordConfirmation);

$hasGenderColumn = ensure_gender_column($pdo);
$birthDate = validate_birthdate($birth);
$normalizedPhone = normalize_phone($dial, $phoneLocal);
$normalizedGender = normalize_gender($gender);

assert_unique($pdo, 'username', $username, 'Nom d’utilisateur indisponible.');
assert_unique($pdo, 'email', $email, 'Email déjà utilisé.');
if ($normalizedPhone !== null) {
    assert_unique($pdo, 'phone', $normalizedPhone, 'Téléphone déjà utilisé.');
}

$userId = insert_user($pdo, [
    'username' => $username,
    'email' => $email,
    'phone' => $normalizedPhone,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'birthdate' => $birthDate,
    'gender' => $normalizedGender,
], $hasGenderColumn);

seed_navigation($pdo, $userId, $normalizedGender);

$_SESSION['user_id'] = $userId;
$_SESSION['username'] = $username;

header('Location: ' . APP_HOME);
exit;
