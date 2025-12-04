<?php
/**
 * Configuration PDO pour la base de données Habitz.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('APP_HOME')) {
    define('APP_HOME', '/public/index.php');
}

$databaseHost = 'localhost';
$databaseName = 'habitz';
$databaseUser = 'habitz';
$databasePass = 'Ytreza#321';
$databaseCharset = 'utf8mb4';

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $databaseHost, $databaseName, $databaseCharset);

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $databaseUser, $databasePass, $options);
} catch (PDOException $e) {
    throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
}

return $pdo;
