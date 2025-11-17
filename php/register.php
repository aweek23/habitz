<?php
require __DIR__.'/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: auth.php');
    exit;
}
function back_with_error($msg){
    $_SESSION['auth_error'] = $msg;
    $_SESSION['auth_error_tab'] = 'signup';
    header('Location: auth.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$dial     = trim($_POST['dial_code'] ?? '');
$phoneLoc = trim($_POST['phone_local'] ?? '');
$birth    = trim($_POST['birthdate'] ?? '');
$gender   = trim($_POST['gender'] ?? ''); // "Homme" | "Femme" | "Autre" | ""

$pass     = $_POST['password'] ?? '';
$pass2    = $_POST['password_confirm'] ?? '';

// Champs requis de base
if ($username==='' || $email==='' || $birth==='' || $pass==='' || $pass2==='') back_with_error("Champs requis manquants.");
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) back_with_error("Email invalide.");
if ($pass !== $pass2) back_with_error("Les mots de passe ne correspondent pas.");
if (!preg_match('/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d!@#$%^&*()_+\-=\[\]{};\'":\\|,.<>\/?~]{8,32}$/', $pass)) back_with_error("Le mot de passe ne respecte pas les règles (8-32, 1 maj, 1 chiffre).");

// Vérif / ajout colonne gender si absente
try { $pdo->query("SELECT `gender` FROM `".TABLE_USERS."` LIMIT 0"); }
catch (Throwable $e) {
    try {
        $pdo->exec("ALTER TABLE `".TABLE_USERS."` ADD COLUMN `gender` ENUM('Homme','Femme','Autre') NULL DEFAULT NULL");
    } catch (Throwable $e2) { /* best effort */ }
}

// Validation date naissance
$bd = DateTime::createFromFormat('Y-m-d', $birth);
if (!$bd) back_with_error("Date de naissance invalide.");
$today = new DateTime('today');
if ($bd->diff($today)->y < 13) back_with_error("Vous devez avoir au moins 13 ans.");

// Téléphone (optionnel)
$phone = null;
if ($phoneLoc !== '') {
    $digits = preg_replace('/\D+/', '', $dial . $phoneLoc);
    if (strlen($digits) < 8 || strlen($digits) > 15) back_with_error("Numéro de téléphone invalide.");
    $phone = '+' . $digits;
}

// Normalise genre
if (!in_array($gender, ['Homme','Femme','Autre',''], true)) $gender = '';

// Unicité
$st=$pdo->prepare("SELECT id FROM ".TABLE_USERS." WHERE username=:u LIMIT 1"); $st->execute([':u'=>$username]); if($st->fetch()) back_with_error("Nom d’utilisateur indisponible.");
$st=$pdo->prepare("SELECT id FROM ".TABLE_USERS." WHERE email=:e LIMIT 1"); $st->execute([':e'=>$email]); if($st->fetch()) back_with_error("Email déjà utilisé.");
if ($phone){ $st=$pdo->prepare("SELECT id FROM ".TABLE_USERS." WHERE phone=:p LIMIT 1"); $st->execute([':p'=>$phone]); if($st->fetch()) back_with_error("Téléphone déjà utilisé."); }

// Insertion utilisateur
$hash=password_hash($pass,PASSWORD_DEFAULT);

// Prépare SQL d'insert incluant éventuellement gender si la colonne existe
$hasGenderCol = false;
try { $pdo->query("SELECT `gender` FROM `".TABLE_USERS."` LIMIT 0"); $hasGenderCol = true; } catch (Throwable $e) { $hasGenderCol = false; }

if ($hasGenderCol) {
    $ins=$pdo->prepare("INSERT INTO ".TABLE_USERS." (username,email,phone,password_hash,birthdate,gender,created_at) VALUES (:u,:e,:p,:h,:b,:g,NOW())");
    $ins->execute([':u'=>$username, ':e'=>$email, ':p'=>$phone, ':h'=>$hash, ':b'=>$birth, ':g'=>($gender?:NULL)]);
} else {
    $ins=$pdo->prepare("INSERT INTO ".TABLE_USERS." (username,email,phone,password_hash,birthdate,created_at) VALUES (:u,:e,:p,:h,:b,NOW())");
    $ins->execute([':u'=>$username, ':e'=>$email, ':p'=>$phone, ':h'=>$hash, ':b'=>$birth]);
}
$userId = (int)$pdo->lastInsertId();

/* Seed prefs nav: si Homme ou Autre -> masquer "cycle" */
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `".TABLE_USER_NAV."` (
        `user_id` INT NOT NULL,
        `menu_order` TEXT NOT NULL,
        `open_sections` TEXT NOT NULL,
        `disabled_keys` TEXT NOT NULL,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM `".TABLE_USER_NAV."` LIKE 'disabled_keys'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE `".TABLE_USER_NAV."` ADD COLUMN `disabled_keys` TEXT NOT NULL DEFAULT '[]'");
    }
} catch (Throwable $e) {}

if ($gender === 'Homme' || $gender === 'Autre') {
    $disabled = json_encode(['cycle'], JSON_UNESCAPED_UNICODE);
    $seed = $pdo->prepare("
        INSERT INTO `".TABLE_USER_NAV."` (user_id, menu_order, open_sections, disabled_keys)
        VALUES (:uid, '[]', '[]', :dis)
        ON DUPLICATE KEY UPDATE
          disabled_keys = IF(disabled_keys IS NULL OR disabled_keys='' OR disabled_keys='[]', VALUES(disabled_keys), disabled_keys)
    ");
    try { $seed->execute([':uid'=>$userId, ':dis'=>$disabled]); } catch (Throwable $e) {}
}

// Session + redirection
$_SESSION['user_id']=$userId;
$_SESSION['username']=$username;

header('Location: ' . APP_HOME);
exit;
