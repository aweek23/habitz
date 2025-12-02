<?php
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: php/auth.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

if (!function_exists('esc')) {
    function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('fetch_username')) {
    function fetch_username(PDO $pdo, int $userId): string
    {
        try {
            $stmt = $pdo->prepare('SELECT username FROM ' . TABLE_USERS . ' WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $userId]);
            $row = $stmt->fetch();

            return $row['username'] ?? 'Utilisateur';
        } catch (Throwable $exception) {
            return 'Utilisateur';
        }
    }
}

$menuItems = [
    ['key' => 'tasks', 'label' => 'Tâches'],
    ['key' => 'habits', 'label' => 'Habitudes'],
    ['key' => 'projects', 'label' => 'Projets'],
    [
        'key' => 'sport',
        'label' => 'Sport',
        'submenu' => [
            ['label' => 'Entraînements'],
            ['label' => 'Pas'],
        ],
    ],
    ['key' => 'food', 'label' => 'Alimentation'],
    ['key' => 'calendar', 'label' => 'Calendrier'],
    [
        'key' => 'body',
        'label' => 'Corps',
        'submenu' => [
            ['label' => 'Sommeil', 'href' => 'sleep.php'],
            ['label' => 'Poids'],
            ['label' => 'Glycémie'],
            ['label' => 'Pression artérielle'],
            ['label' => 'Cycle menstruel'],
            ['label' => 'Composition corporelle'],
        ],
    ],
    [
        'key' => 'finances',
        'label' => 'Finances',
        'submenu' => [
            ['label' => 'Budget', 'href' => 'finances.php'],
            ['label' => 'Patrimoine'],
            ['label' => 'Comptes'],
        ],
    ],
    ['key' => 'clock', 'label' => 'Horloge'],
    ['key' => 'events', 'label' => 'Evènements'],
    ['key' => 'news', 'label' => 'Actualités, news, etc'],
    ['key' => 'drive', 'label' => 'Drive'],
];

$currentUsername = fetch_username($pdo, $userId);
