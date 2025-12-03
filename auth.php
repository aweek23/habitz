<?php
session_start();

$pdo = require __DIR__ . '/config.php';

$registerErrors = [];
$loginErrors = [];
$registerSuccess = null;
$loginSuccess = null;
$activeForm = 'register';

function clean(string $value): string
{
    return trim($value);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $username = clean($_POST['username'] ?? '');
        $email = clean($_POST['email'] ?? '');
        $phone = clean($_POST['phone_number'] ?? '');
        $birthdate = clean($_POST['birthdate'] ?? '');
        $gender = clean($_POST['gender'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '') {
            $registerErrors[] = "Le nom d'utilisateur est requis.";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $registerErrors[] = 'Un email valide est requis.';
        }

        if ($password === '' || strlen($password) < 8) {
            $registerErrors[] = 'Le mot de passe doit comporter au moins 8 caractères.';
        }

        if ($birthdate !== '') {
            $birthDateObj = DateTime::createFromFormat('Y-m-d', $birthdate);
            if (!$birthDateObj || $birthDateObj->format('Y-m-d') !== $birthdate) {
                $registerErrors[] = 'La date de naissance doit respecter le format AAAA-MM-JJ.';
            }
        }

        if (empty($registerErrors)) {
            $creationDate = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
            $rank = 'user';
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            try {
                $stmt = $pdo->prepare('INSERT INTO users (username, email, phone_number, birthdate, gender, rank, password, creation_date) VALUES (:username, :email, :phone_number, :birthdate, :gender, :rank, :password, :creation_date)');
                $stmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':phone_number' => $phone,
                    ':birthdate' => $birthdate ?: null,
                    ':gender' => $gender ?: null,
                    ':rank' => $rank,
                    ':password' => $passwordHash,
                    ':creation_date' => $creationDate,
                ]);

                $registerSuccess = 'Compte créé avec succès. Vous pouvez maintenant vous connecter.';
                $activeForm = 'login';
            } catch (PDOException $e) {
                if ((int) $e->getCode() === 23000) {
                    $registerErrors[] = 'Un compte existe déjà avec cet email ou ce nom d’utilisateur.';
                } else {
                    $registerErrors[] = 'Impossible de créer le compte pour le moment. Merci de réessayer ultérieurement.';
                }
            }
        }
    }

    if ($action === 'login') {
        $loginEmail = clean($_POST['login_email'] ?? '');
        $loginPassword = $_POST['login_password'] ?? '';
        $activeForm = 'login';

        if (!filter_var($loginEmail, FILTER_VALIDATE_EMAIL)) {
            $loginErrors[] = 'Veuillez saisir un email valide.';
        }

        if ($loginPassword === '') {
            $loginErrors[] = 'Le mot de passe est requis.';
        }

        if (empty($loginErrors)) {
            $stmt = $pdo->prepare('SELECT id, username, rank, password FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $loginEmail]);
            $user = $stmt->fetch();

            if ($user && password_verify($loginPassword, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['rank'] = $user['rank'];
                $loginSuccess = 'Connexion réussie. Bienvenue ' . htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') . ' !';
            } else {
                $loginErrors[] = 'Identifiants incorrects.';
            }
        }
    }
}

?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentification</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body class="auth-page">
    <main class="auth-shell" data-active-form="<?= htmlspecialchars($activeForm, ENT_QUOTES, 'UTF-8') ?>">
        <div class="auth-forms">
            <section class="auth-card register-card" id="register-panel">
                <div class="auth-card-header">
                    <h1>Créer un compte</h1>
                    <p>Renseigne tes informations pour créer un compte.</p>
                </div>

                <?php if ($registerSuccess): ?>
                    <div class="auth-alert success"><?= $registerSuccess ?></div>
                <?php endif; ?>

                <?php if ($registerErrors): ?>
                    <div class="auth-alert error">
                        <ul>
                            <?php foreach ($registerErrors as $error): ?>
                                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" class="auth-form">
                    <input type="hidden" name="action" value="register">
                    <label>
                        <span>Nom d'utilisateur</span>
                        <input type="text" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>
                        <span>Email</span>
                        <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>
                        <span>Numéro de téléphone</span>
                        <input type="tel" name="phone_number" value="<?= htmlspecialchars($_POST['phone_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>
                        <span>Date de naissance</span>
                        <input type="date" name="birthdate" value="<?= htmlspecialchars($_POST['birthdate'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>
                        <span>Genre</span>
                        <select name="gender">
                            <option value="" <?= (($_POST['gender'] ?? '') === '') ? 'selected' : '' ?>>Sélectionner</option>
                            <option value="male" <?= (($_POST['gender'] ?? '') === 'male') ? 'selected' : '' ?>>Homme</option>
                            <option value="female" <?= (($_POST['gender'] ?? '') === 'female') ? 'selected' : '' ?>>Femme</option>
                            <option value="other" <?= (($_POST['gender'] ?? '') === 'other') ? 'selected' : '' ?>>Autre</option>
                        </select>
                    </label>
                    <label>
                        <span>Mot de passe</span>
                        <input type="password" name="password" required minlength="8">
                    </label>
                    <button type="submit" class="auth-submit">Créer un compte</button>
                </form>

                <p class="auth-toggle">Déjà inscrit ? <button type="button" data-open-login>Se connecter</button></p>
            </section>

            <section class="auth-card login-card" id="login-panel">
                <div class="auth-card-header">
                    <h1>Connexion</h1>
                    <p>Retrouve tes habitudes et tâches.</p>
                </div>

                <?php if ($loginSuccess): ?>
                    <div class="auth-alert success"><?= $loginSuccess ?></div>
                <?php endif; ?>

                <?php if ($loginErrors): ?>
                    <div class="auth-alert error">
                        <ul>
                            <?php foreach ($loginErrors as $error): ?>
                                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" class="auth-form">
                    <input type="hidden" name="action" value="login">
                    <label>
                        <span>Email</span>
                        <input type="email" name="login_email" required value="<?= htmlspecialchars($_POST['login_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </label>
                    <label>
                        <span>Mot de passe</span>
                        <input type="password" name="login_password" required>
                    </label>
                    <button type="submit" class="auth-submit">Se connecter</button>
                </form>

                <p class="auth-toggle">Nouveau sur Habitz ? <button type="button" data-open-register>Créer un compte</button></p>
            </section>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const shell = document.querySelector('.auth-shell');
            const registerPanel = document.getElementById('register-panel');
            const loginPanel = document.getElementById('login-panel');

            const showForm = (form) => {
                shell.dataset.activeForm = form;
                registerPanel.classList.toggle('is-active', form === 'register');
                loginPanel.classList.toggle('is-active', form === 'login');
            };

            document.querySelectorAll('[data-open-login]').forEach((btn) => {
                btn.addEventListener('click', () => showForm('login'));
            });

            document.querySelectorAll('[data-open-register]').forEach((btn) => {
                btn.addEventListener('click', () => showForm('register'));
            });

            // initial state from server
            showForm(shell.dataset.activeForm || 'register');
        });
    </script>
</body>
</html>
