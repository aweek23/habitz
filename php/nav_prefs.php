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

if (!defined('TABLE_USER_NAV_ITEMS')) {
    define('TABLE_USER_NAV_ITEMS', 'user_nav_items');
}

$uid = (int) $_SESSION['user_id'];

// Canon des boutons de la navbar (même ordre que le HTML)
$NAV_CANON = [
    'tasks', 'habits', 'projects', 'sport', 'food', 'calendar', 'body', 'finances',
    'clock', 'events', 'news', 'drive',
];
$NAV_KEYS = $NAV_CANON;

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

function ensure_nav_tables(PDO $pdo): void
{
    // Table des items (ordre + visibilité)
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

    // Table des sections ouvertes (on réutilise TABLE_USER_NAV pour stocker l’état)
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
            'INSERT INTO `' . TABLE_USER_NAV_ITEMS . '` (user_id, nav_key, visible, ord) VALUES (:u, :k, "Yes", :o)'
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

function migrate_legacy_nav(PDO $pdo, int $uid, array $canon): void
{
    try {
        $hasMenuOrder = $pdo->query("SHOW COLUMNS FROM `" . TABLE_USER_NAV . "` LIKE 'menu_order'")->rowCount() > 0;
        $hasDisabled  = $pdo->query("SHOW COLUMNS FROM `" . TABLE_USER_NAV . "` LIKE 'disabled_keys'")->rowCount() > 0;
    } catch (Throwable $e) {
        return;
    }

    if (!$hasMenuOrder && !$hasDisabled) {
        return;
    }

    $stmt = $pdo->prepare('SELECT menu_order, disabled_keys FROM `' . TABLE_USER_NAV . '` WHERE user_id=:u LIMIT 1');
    $stmt->execute([':u' => $uid]);
    $row = $stmt->fetch();
    if (!$row) {
        return;
    }

    $order = normalize_keys(json_decode($row['menu_order'] ?? '[]', true) ?: []);
    $disabled = normalize_keys(json_decode($row['disabled_keys'] ?? '[]', true) ?: []);

    if (empty($order) && empty($disabled)) {
        return;
    }

    $pdo->beginTransaction();
    try {
        $i = 1;
        $upd = $pdo->prepare(
            'UPDATE `' . TABLE_USER_NAV_ITEMS . '` SET ord=:o, visible="Yes" WHERE user_id=:u AND nav_key=:k'
        );
        foreach ($order as $k) {
            if (!in_array($k, $canon, true)) continue;
            $upd->execute([':o' => $i, ':u' => $uid, ':k' => $k]);
            $i++;
        }
        foreach ($canon as $k) {
            if (in_array($k, $order, true)) continue;
            $upd->execute([':o' => $i, ':u' => $uid, ':k' => $k]);
            $i++;
        }

        if (!empty($disabled)) {
            $hide = $pdo->prepare('UPDATE `' . TABLE_USER_NAV_ITEMS . '` SET visible="No", ord=NULL WHERE user_id=:u AND nav_key=:k');
            foreach ($disabled as $k) {
                if (!in_array($k, $canon, true)) continue;
                $hide->execute([':u' => $uid, ':k' => $k]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
    }
}

function fetch_open_sections(PDO $pdo, int $uid): array
{
    $stmt = $pdo->prepare('SELECT open_sections FROM `' . TABLE_USER_NAV . '` WHERE user_id=:u LIMIT 1');
    $stmt->execute([':u' => $uid]);
    $row = $stmt->fetch();
    if (!$row) {
        $pdo->prepare('INSERT INTO `' . TABLE_USER_NAV . '` (user_id, open_sections) VALUES (:u, :os)')
            ->execute([':u' => $uid, ':os' => '[]']);
        return [];
    }
    return json_decode($row['open_sections'], true) ?: [];
}

function save_open_sections(PDO $pdo, int $uid, array $sections): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO `' . TABLE_USER_NAV . '` (user_id, open_sections) VALUES (:u, :os) ' .
        'ON DUPLICATE KEY UPDATE open_sections = VALUES(open_sections)'
    );
    $stmt->execute([
        ':u'  => $uid,
        ':os' => json_encode(array_values($sections), JSON_UNESCAPED_UNICODE),
    ]);
}

function fetch_items(PDO $pdo, int $uid, array $canon): array
{
    $stmt = $pdo->prepare('SELECT nav_key, visible, ord FROM `' . TABLE_USER_NAV_ITEMS . '` WHERE user_id=:u');
    $stmt->execute([':u' => $uid]);
    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $k = $row['nav_key'];
        $out[$k] = [
            'visible' => $row['visible'],
            'ord' => is_null($row['ord']) ? null : (int) $row['ord'],
        ];
    }

    // ajoute les clés manquantes
    $max = 0;
    foreach ($out as $v) {
        if (!is_null($v['ord']) && $v['visible'] === 'Yes') {
            $max = max($max, (int) $v['ord']);
        }
    }

    $pdo->beginTransaction();
    try {
        $ins = $pdo->prepare(
            'INSERT INTO `' . TABLE_USER_NAV_ITEMS . '` (user_id, nav_key, visible, ord) VALUES (:u,:k, "Yes", :o)'
        );
        foreach ($canon as $idx => $key) {
            if (isset($out[$key])) {
                continue;
            }
            $max++;
            $ins->execute([':u' => $uid, ':k' => $key, ':o' => $max]);
            $out[$key] = ['visible' => 'Yes', 'ord' => $max];
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return $out;
}

function reorder_items(PDO $pdo, int $uid, array $order, array $canon): void
{
    $valid = array_values(array_intersect($order, $canon));
    if (empty($valid)) {
        return;
    }
    $pdo->beginTransaction();
    try {
        $upd = $pdo->prepare(
            'UPDATE `' . TABLE_USER_NAV_ITEMS . '` SET ord=:o WHERE user_id=:u AND nav_key=:k AND visible="Yes"'
        );
        $i = 1;
        foreach ($valid as $k) {
            $upd->execute([':o' => $i, ':u' => $uid, ':k' => $k]);
            $i++;
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function toggle_item(PDO $pdo, int $uid, string $key, string $visible, array $canon): void
{
    if (!in_array($key, $canon, true) || !in_array($visible, ['Yes', 'No'], true)) {
        throw new InvalidArgumentException('bad_args');
    }

    // assure l’existence de la ligne
    $pdo->prepare(
        'INSERT IGNORE INTO `' . TABLE_USER_NAV_ITEMS . '` (user_id, nav_key, visible, ord) VALUES (:u,:k,"Yes",NULL)'
    )->execute([':u' => $uid, ':k' => $key]);

    $stmt = $pdo->prepare('SELECT visible, ord FROM `' . TABLE_USER_NAV_ITEMS . '` WHERE user_id=:u AND nav_key=:k LIMIT 1');
    $stmt->execute([':u' => $uid, ':k' => $key]);
    $row = $stmt->fetch();
    if (!$row) {
        throw new RuntimeException('not_found');
    }

    if ($visible === 'No' && $row['visible'] === 'Yes') {
        $oldOrd = (int) $row['ord'];
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE `' . TABLE_USER_NAV_ITEMS . '` SET visible="No", ord=NULL WHERE user_id=:u AND nav_key=:k')
                ->execute([':u' => $uid, ':k' => $key]);
            $pdo->prepare(
                'UPDATE `' . TABLE_USER_NAV_ITEMS . '` SET ord=ord-1 WHERE user_id=:u AND visible="Yes" AND ord>:o'
            )->execute([':u' => $uid, ':o' => $oldOrd]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    } elseif ($visible === 'Yes' && $row['visible'] === 'No') {
        $maxStmt = $pdo->prepare('SELECT COALESCE(MAX(ord),0) FROM `' . TABLE_USER_NAV_ITEMS . '` WHERE user_id=:u AND visible="Yes"');
        $maxStmt->execute([':u' => $uid]);
        $new = (int) $maxStmt->fetchColumn() + 1;
        $pdo->prepare('UPDATE `' . TABLE_USER_NAV_ITEMS . '` SET visible="Yes", ord=:o WHERE user_id=:u AND nav_key=:k')
            ->execute([':o' => $new, ':u' => $uid, ':k' => $key]);
    }
}

try {
    ensure_nav_tables($pdo);
    seed_nav_items($pdo, $uid, $NAV_CANON);
    migrate_legacy_nav($pdo, $uid, $NAV_CANON);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'reason' => 'db_error']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $items = fetch_items($pdo, $uid, $NAV_CANON);
        $openSections = normalize_keys(fetch_open_sections($pdo, $uid));
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'reason' => 'db_error']);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'items' => $items,
        'open_sections' => $openSections,
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

    $action = $json['action'] ?? '';

    try {
        if ($action === 'toggle') {
            toggle_item($pdo, $uid, $json['key'] ?? '', $json['visible'] ?? '', $NAV_CANON);
            echo json_encode(['ok' => true]);
            exit;
        }

        if ($action === 'reorder') {
            $order = $json['order'] ?? null;
            if (!is_array($order) || empty($order)) {
                echo json_encode(['ok' => false, 'reason' => 'bad_order']);
                exit;
            }
            reorder_items($pdo, $uid, $order, $NAV_CANON);
            echo json_encode(['ok' => true]);
            exit;
        }

        if ($action === 'open_sections') {
            $sections = normalize_keys($json['open_sections'] ?? []);
            save_open_sections($pdo, $uid, $sections);
            echo json_encode(['ok' => true]);
            exit;
        }
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'reason' => 'db_error']);
        exit;
    }

    echo json_encode(['ok' => false, 'reason' => 'unsupported']);
    exit;
}

echo json_encode(['ok' => false, 'reason' => 'unsupported']);
