<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* aapanel / MySQL */
$DB_HOST = 'localhost';
$DB_NAME = 'habitz';
$DB_USER = 'habitz';
$DB_PASS = 'Ytreza321'; // ← ton mot de passe MySQL

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo "Erreur base de données.";
    exit;
}

/* Après login/inscription → retourne à la page principale (racine) */
define('APP_HOME', '../index.html');

/* Nom de la table utilisateurs (celle que tu as créée) */
define('TABLE_USERS', 'habitz');

/* Table de préférences de navigation (créée automatiquement si absente) */
define('TABLE_USER_NAV', 'user_nav');
