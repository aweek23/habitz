<?php
require __DIR__ . '/config.php';

/*
  API nav_prefs.php
    GET  ?action=get
      -> { ok:true, items:{ key:{ visible:"Yes"|"No", ord:int }, ... }, open_sections:[...] }

    POST JSON
      { action:"toggle", key:"tasks", visible:"Yes"|"No" }
      { action:"reorder", order:["tasks","habits", ... visibles dans l'ordre choisi] }
      { action:"open_sections", open_sections:["tasks", ...] }
*/

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'reason' => 'not_logged_in']);
    exit;
}

$uid = (int) $_SESSION['user_id'];

if (!defined('TABLE_USER_NAV_ITEMS')) {
    define('TABLE_USER_NAV_ITEMS', 'user_nav_items');
}
if (!defined('TABLE_USER_NAV')) {
    define('TABLE_USER_NAV', 'user_nav');
}

// Canon de la navbar (même séquence que le HTML)
$NAV_KEYS = [
    'tasks', 'habits', 'projects', 'sport', 'food', 'calendar', 'body', 'finances',
    'clock', 'events', 'news', 'drive',
];

// -- Helpers ---------------------------------------------------------------
function ensure_tables(PDO $pdo): void
{
    // Ordre + visibilité
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `' . TABLE_USER_NAV_ITEMS . '` (' .
        '  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,' .
        '  `user_id` INT NOT NULL,' .
        '  `nav_key` VARCHAR(64) NOT NULL,' .
        "  `visible` ENUM('Yes','No') NOT NULL DEFAULT 'Yes'," .
        '  `ord` INT NULL,' .
        '  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,' .
        '  PRIMARY KEY (`id`),' .
        '  UNIQUE KEY `u_user_nav_item` (`user_id`,`nav_key`)' .
        ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );

    // Sections ouvertes (facultatif)
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `' . TABLE_USER_NAV . '` (' .
        '  `user_id` INT NOT NULL,' .
        '  `open_sections` TEXT NOT NULL,' .
        '  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,' .
        '  PRIMARY KEY (`user_id`)' .
        ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );
}

function seed_nav_items(PDO $pdo, int $uid, array $canon): void
{
    $st = $pdo->prepare('SELECT COUNT(*) c FROM `' . TABLE_USER_NAV_ITEMS . '` WHERE user_id=:u');
    $st->execute([':u' => $uid]);
    $has = (int) $st->fetchColumn() > 0;
    if ($has) {
        return;
    }

    $pdo->beginTransaction();
    try {
        $ins = $pdo->prepare(
            'INSERT INTO `' . TABLE_USER_NAV_ITEMS . '` (user_id, nav_key, visible, ord) VALUES (:u,:k,"Yes",:o)'
        );
        foreach ($canon as $idx => $key) {
            $ins->execute([':u' => $uid, ':k' => $key, ':o' => $idx + 1]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function fetch_items(PDO $pdo, int $uid, array $canon): array
{
    $st = $pdo->prepare('SELECT nav_key, visible, ord FROM `' . TABLE_USER_NAV_ITEMS . '` WHERE user_id=:u');
    $st->execute([':u' => $uid]);

    $out = [];
    foreach ($st->fetchAll() as $row) {
        $key = $row['nav_key'];
        if (!in_array($key, $canon, true)) continue;
        $out[$key] = [
            'visible' => $row['visible'],
            'ord'     => is_null($row['ord']) ? null : (int) $row['ord'],
        ];
    }

    // ajoute les manquants en mémoire
    $max = 0;
    foreach ($out as $v) {
        if (!is_null($v['ord'])) {
            $max = max($max, (int) $v['ord']);
        }
    }
    $idx = $max;
    foreach ($canon as $key) {
        if (!isset($out[$key])) {
            $idx++;
            $out[$key] = ['visible' => 'Yes', 'ord' => $idx];
        }
    }

    return $out;
}

function save_items(PDO $pdo, int $uid, array $orderedKeys, array $current): void
{
    $pdo->beginTransaction();
    try {
        $upd = $pdo->prepare(
            'UPDATE `' . TABLE_USER_NAV_ITEMS . '` SET ord=:o WHERE user_id=:u AND nav_key=:k'
        );
        foreach ($orderedKeys as $idx => $key) {
            $upd->execute([':o' => $idx + 1, ':u' => $uid, ':k' => $key]);
        }

        // ajoute les lignes manquantes au besoin
        $ins = $pdo->prepare(
            'INSERT IGNORE INTO `' . TABLE_USER_NAV_ITEMS . '` (user_id, nav_key, visible, ord) VALUES (:u,:k,"Yes",:o)'
        );
        foreach ($orderedKeys as $idx => $key) {
            if (!isset($current[$key])) {
                $ins->execute([':u' => $uid, ':k' => $key, ':o' => $idx + 1]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function fetch_open_sections(PDO $pdo, int $uid): array
{
    $st = $pdo->prepare('SELECT open_sections FROM `' . TABLE_USER_NAV . '` WHERE user_id=:u LIMIT 1');
    $st->execute([':u' => $uid]);
    $row = $st->fetch();
    if (!$row) {
        return [];
    }
    return json_decode($row['open_sections'], true) ?: [];
}

function save_open_sections(PDO $pdo, int $uid, array $sections): void
{
    $st = $pdo->prepare(
        'INSERT INTO `' . TABLE_USER_NAV . '` (user_id, open_sections) VALUES (:u, :os)' .
        ' ON DUPLICATE KEY UPDATE open_sections = VALUES(open_sections)'
    );
    $st->execute([':u' => $uid, ':os' => json_encode(array_values($sections), JSON_UNESCAPED_UNICODE)]);
}

// -- Boot ---------------------------------------------------------------
try {
    ensure_tables($pdo);
    seed_nav_items($pdo, $uid, $NAV_KEYS);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'reason' => 'db_error']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'get') {
    $items = fetch_items($pdo, $uid, $NAV_KEYS);
    $open  = fetch_open_sections($pdo, $uid);
    echo json_encode(['ok' => true, 'items' => $items, 'open_sections' => $open], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $raw  = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (!is_array($json)) {
        echo json_encode(['ok' => false, 'reason' => 'bad_json']);
        exit;
    }

    $act = $json['action'] ?? '';

    if ($act === 'toggle') {
        $key = $json['key'] ?? '';
        $vis = $json['visible'] ?? '';
        if (!in_array($key, $NAV_KEYS, true) || !in_array($vis, ['Yes', 'No'], true)) {
            echo json_encode(['ok' => false, 'reason' => 'bad_args']);
            exit;
        }

        $existing = fetch_items($pdo, $uid, $NAV_KEYS);
        $nextOrd  = count($existing) + 1;
        if (isset($existing[$key]) && isset($existing[$key]['ord'])) {
            $nextOrd = (int) $existing[$key]['ord'];
        }

        $stmt = $pdo->prepare(
            'INSERT INTO `' . TABLE_USER_NAV_ITEMS . '` (user_id, nav_key, visible, ord)'
            . ' VALUES (:u,:k,:v,:o)'
            . ' ON DUPLICATE KEY UPDATE visible=VALUES(visible), ord=IF(ord IS NULL, VALUES(ord), ord)'
        );
        try {
            $stmt->execute([':u' => $uid, ':k' => $key, ':v' => $vis, ':o' => $nextOrd]);
            echo json_encode(['ok' => true]);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'reason' => 'db_error']);
        }
        exit;
    }

    if ($act === 'reorder') {
        $order = $json['order'] ?? [];
        if (!is_array($order)) {
            echo json_encode(['ok' => false, 'reason' => 'bad_order']);
            exit;
        }

        $normalized = [];
        foreach ($order as $k) {
            if (is_string($k) && in_array($k, $NAV_KEYS, true) && !in_array($k, $normalized, true)) {
                $normalized[] = $k;
            }
        }

        // on complète avec les clés restantes, en respectant l'ordre existant
        $current = fetch_items($pdo, $uid, $NAV_KEYS);
        uasort($current, function ($a, $b) {
            $ao = $a['ord'] ?? PHP_INT_MAX;
            $bo = $b['ord'] ?? PHP_INT_MAX;
            if ($ao === $bo) return 0;
            return ($ao < $bo) ? -1 : 1;
        });

        foreach (array_keys($current) as $existingKey) {
            if (in_array($existingKey, $normalized, true)) continue;
            $normalized[] = $existingKey;
        }
        foreach ($NAV_KEYS as $canonKey) {
            if (in_array($canonKey, $normalized, true)) continue;
            $normalized[] = $canonKey;
        }

        try {
            save_items($pdo, $uid, $normalized, $current);
            echo json_encode(['ok' => true]);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'reason' => 'db_error']);
        }
        exit;
    }

    if ($act === 'open_sections') {
        $open = $json['open_sections'] ?? [];
        if (!is_array($open)) {
            echo json_encode(['ok' => false, 'reason' => 'bad_open_sections']);
            exit;
        }
        $valid = [];
        foreach ($open as $k) {
            if (is_string($k) && in_array($k, $NAV_KEYS, true) && !in_array($k, $valid, true)) {
                $valid[] = $k;
            }
        }
        try {
            save_open_sections($pdo, $uid, $valid);
            echo json_encode(['ok' => true]);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'reason' => 'db_error']);
        }
        exit;
    }

    echo json_encode(['ok' => false, 'reason' => 'bad_action']);
    exit;
}

echo json_encode(['ok' => false, 'reason' => 'bad_request']);