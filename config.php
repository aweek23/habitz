<?php
/**
 * Basic PDO configuration for database access.
 *
 * Override credentials via environment variables:
 * DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET.
 */

$databaseHost = getenv('DB_HOST') ?: 'localhost';
$databaseName = getenv('DB_NAME') ?: 'habitz';
$databaseUser = getenv('DB_USER') ?: 'root';
$databasePass = getenv('DB_PASS') ?: '';
$databaseCharset = getenv('DB_CHARSET') ?: 'utf8mb4';

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
