<?php
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'reason' => 'not_logged_in']);
    exit;
}

$uid = (int) $_SESSION['user_id'];

try {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `' . TABLE_USER_NAV . '` (' .
        '  `user_id` INT NOT NULL,' .
        '  `menu_order` TEXT NOT NULL,' .
        '  `open_sections` TEXT NOT NULL,' .
        '  `disabled_keys` TEXT NOT NULL,' .
        '  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,' .
        '  PRIMARY KEY (`user_id`)' .
        ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'reason' => 'db_error']);
    exit;
}

function normalize_keys(array $values): array
{
    $out = [];
    foreach ($values as $val) {
        if (!is_string($val) || $val === '') {
            continue;
        }
        $out[] = $val;
    }
    return array_values(array_unique($out));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare('SELECT menu_order, open_sections, disabled_keys FROM `' . TABLE_USER_NAV . '` WHERE user_id = :uid LIMIT 1');
        $stmt->execute([':uid' => $uid]);
        $row = $stmt->fetch();
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'reason' => 'db_error']);
        exit;
    }

    $menuOrder = [];
    $openSections = [];
    $disabledKeys = [];

    if ($row) {
        $menuOrder = json_decode($row['menu_order'], true) ?: [];
        $openSections = json_decode($row['open_sections'], true) ?: [];
        $disabledKeys = json_decode($row['disabled_keys'], true) ?: [];
    }

    echo json_encode([
        'ok' => true,
        'menu_order' => normalize_keys($menuOrder),
        'open_sections' => normalize_keys($openSections),
        'disabled_keys' => normalize_keys($disabledKeys),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (!is_array($json)) {
        echo json_encode(['ok' => false, 'reason' => 'bad_json']);
        exit;
    }

    $menuOrder = normalize_keys($json['menu_order'] ?? []);
    $openSections = normalize_keys($json['open_sections'] ?? []);
    $disabledKeys = normalize_keys($json['disabled_keys'] ?? []);

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO `' . TABLE_USER_NAV . '` (user_id, menu_order, open_sections, disabled_keys) VALUES (:uid, :mo, :os, :dk)' .
            ' ON DUPLICATE KEY UPDATE menu_order = VALUES(menu_order), open_sections = VALUES(open_sections), disabled_keys = VALUES(disabled_keys)'
        );
        $stmt->execute([
            ':uid' => $uid,
            ':mo' => json_encode($menuOrder, JSON_UNESCAPED_UNICODE),
            ':os' => json_encode($openSections, JSON_UNESCAPED_UNICODE),
            ':dk' => json_encode($disabledKeys, JSON_UNESCAPED_UNICODE),
        ]);
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'reason' => 'db_error']);
        exit;
    }

    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'reason' => 'unsupported']);
