<?php
require __DIR__.'/config.php';

/*
  Endpoints:
    GET  modules_prefs.php?action=get&layout=all|{1..4}
      -> { ok:true, layouts: { layout:{ key:{visible:"Yes"|"No", ord:int|null}, ... } } }
         { ok:true, layout:3, modules:{...} }

    POST modules_prefs.php  (JSON)
      { action:"toggle", layout:3, key:"pedometer", visible:"Yes"|"No" }
      { action:"reorder", layout:2, order:["pedometer","steps",... visibles de haut en bas] }
*/

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!defined('TABLE_USER_MODULES')) define('TABLE_USER_MODULES', 'user_module_prefs');

// Les dispositions autorisées (nombre de colonnes)
$ALLOWED_LAYOUTS = [1, 2, 3, 4];

function normalize_layout($value, array $allowed)
{
    $layout = (int) $value;
    return in_array($layout, $allowed, true) ? $layout : null;
}

try {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['ok'=>false, 'reason'=>'not_logged_in']);
        exit;
    }
    $uid = (int)$_SESSION['user_id'];

    // 1) Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `".TABLE_USER_MODULES."` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT NOT NULL,
            `layout` TINYINT NOT NULL DEFAULT 3,
            `module_key` VARCHAR(32) NOT NULL,
            `visible` ENUM('Yes','No') NOT NULL DEFAULT 'Yes',
            `ord` INT NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `u_user_module` (`user_id`,`layout`,`module_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Migration douce si l’ancienne table n’a pas de colonne layout ou l’index unique obsolète
    $cols = $pdo->query("SHOW COLUMNS FROM `" . TABLE_USER_MODULES . "`")->fetchAll(PDO::FETCH_COLUMN);
    if ($cols && !in_array('layout', $cols, true)) {
        $pdo->exec("ALTER TABLE `" . TABLE_USER_MODULES . "` ADD COLUMN `layout` TINYINT NOT NULL DEFAULT 3 AFTER `user_id`");
        $pdo->exec("UPDATE `" . TABLE_USER_MODULES . "` SET layout = 3");
    }

    $idxStmt = $pdo->query("SHOW INDEX FROM `" . TABLE_USER_MODULES . "` WHERE Key_name='u_user_module'");
    $hasLayoutUnique = false;
    $layoutIndexCols = [];
    foreach ($idxStmt->fetchAll() as $idxRow) {
        $layoutIndexCols[] = $idxRow['Column_name'];
    }
    if ($layoutIndexCols) {
        $hasLayoutUnique = in_array('layout', $layoutIndexCols, true) && in_array('module_key', $layoutIndexCols, true);
    }
    if (!$hasLayoutUnique) {
        try { $pdo->exec("ALTER TABLE `" . TABLE_USER_MODULES . "` DROP INDEX `u_user_module`"); } catch (Throwable $e) {}
        $pdo->exec("ALTER TABLE `" . TABLE_USER_MODULES . "` ADD UNIQUE KEY `u_user_module` (`user_id`,`layout`,`module_key`)");
    }

    // 2) Canon des modules + ordre par défaut pour le dashboard (correspond à index.php)
    $modules = [
        'pedometer' => 1,
        'steps'     => 2,
        'module-c'  => 3,
        'module-d'  => 4,
        'module-e'  => 5,
        'module-f'  => 6,
        'module-g'  => 7,
        'module-h'  => 8,
        'module-i'  => 9,
        'module-j'  => 10,
        'module-k'  => 11,
        'module-l'  => 12,
    ];
    $keys = array_keys($modules);

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    $layoutParam = $_GET['layout'] ?? null;
    $layoutAll   = is_string($layoutParam) && strtolower($layoutParam) === 'all';
    $layout      = $layoutAll ? null : normalize_layout($layoutParam, $ALLOWED_LAYOUTS);

    // ===== helpers =====
    $fetchAll = function(int $layout) use ($pdo, $uid, $modules){
        $st = $pdo->prepare("SELECT module_key, visible, ord FROM `".TABLE_USER_MODULES."` WHERE user_id=:uid AND layout=:l");
        $st->execute([':uid'=>$uid, ':l'=>$layout]);
        $out = [];
        foreach ($st->fetchAll() as $r) {
            $k = $r['module_key'];
            if (!array_key_exists($k, $modules)) {
                // On ignore les anciennes clés qui ne sont plus dans le canon
                continue;
            }
            $out[$k] = [
                'visible' => $r['visible'],
                'ord'     => is_null($r['ord']) ? null : (int)$r['ord'],
            ];
        }
        return $out;
    };
    $ensureSeed = function(int $layout) use ($pdo, $uid, $modules){
        // y a-t-il déjà des prefs (même anciennes) ?
        $st = $pdo->prepare("SELECT COUNT(*) c FROM `".TABLE_USER_MODULES."` WHERE user_id=:uid AND layout=:l");
        $st->execute([':uid'=>$uid, ':l'=>$layout]);
        $has = (int)$st->fetchColumn() > 0;
        if ($has) return;

        // seed : tous les nouveaux modules visibles avec l'ordre par défaut
        $pdo->beginTransaction();
        try {
            foreach ($modules as $k=>$ord) {
                $ins = $pdo->prepare("
                    INSERT INTO `".TABLE_USER_MODULES."` (user_id, layout, module_key, visible, ord)
                    VALUES (:uid, :l, :k, 'Yes', :o)
                ");
                $ins->execute([':uid'=>$uid, ':l'=>$layout, ':k'=>$k, ':o'=>$ord]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    };
    $ensureAllLayouts = function() use ($ALLOWED_LAYOUTS, $ensureSeed){
        foreach ($ALLOWED_LAYOUTS as $layoutValue) {
            $ensureSeed($layoutValue);
        }
    };
    $fillMissingModules = function(int $layout) use ($modules, $fetchAll, $pdo, $uid){
        $current = $fetchAll($layout);
        foreach ($modules as $k=>$ord) {
            if (!isset($current[$k])) {
                $ins = $pdo->prepare("INSERT INTO `".TABLE_USER_MODULES."` (user_id,layout,module_key,visible,ord) VALUES (:uid,:l,:k,'Yes',:o)");
                $ins->execute([':uid'=>$uid, ':l'=>$layout, ':k'=>$k, ':o'=>$ord]);
                $current[$k] = ['visible'=>'Yes', 'ord'=>$ord];
            }
        }
        return $current;
    };

    if ($method === 'GET' && $action === 'get') {
        if ($layoutAll) {
            $ensureAllLayouts();
            $layoutsOut = [];
            foreach ($ALLOWED_LAYOUTS as $layoutValue) {
                $layoutsOut[(string)$layoutValue] = $fillMissingModules($layoutValue);
            }
            echo json_encode(['ok'=>true, 'layouts'=>$layoutsOut], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!$layout) { echo json_encode(['ok'=>false,'reason'=>'bad_layout']); exit; }

        $ensureSeed($layout); // sème si vide
        $current = $fillMissingModules($layout);
        echo json_encode(['ok'=>true, 'layout'=>$layout, 'modules'=>$current], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        if (!is_array($json)) { echo json_encode(['ok'=>false,'reason'=>'bad_json']); exit; }

        $act = $json['action'] ?? '';
        if ($act === 'toggle') {
            $layout = normalize_layout($json['layout'] ?? null, $ALLOWED_LAYOUTS);
            if (!$layout) { echo json_encode(['ok'=>false,'reason'=>'bad_layout']); exit; }
            $key = $json['key'] ?? '';
            $vis = $json['visible'] ?? '';
            if (!in_array($key, $keys, true) || !in_array($vis,['Yes','No'],true)) {
                echo json_encode(['ok'=>false,'reason'=>'bad_args']); exit;
            }

            $ensureSeed($layout);

            // lit son ordre actuel
            $st = $pdo->prepare("SELECT visible, ord FROM `".TABLE_USER_MODULES."` WHERE user_id=:uid AND layout=:l AND module_key=:k LIMIT 1");
            $st->execute([':uid'=>$uid, ':l'=>$layout, ':k'=>$key]);
            $row = $st->fetch();
            if (!$row) {
                // crée la ligne si absente
                $pdo->prepare("INSERT INTO `".TABLE_USER_MODULES."` (user_id,layout,module_key,visible,ord) VALUES (:uid,:l,:k,'No',NULL)")
                    ->execute([':uid'=>$uid, ':l'=>$layout, ':k'=>$key]);
                $row = ['visible'=>'No','ord'=>null];
            }

            if ($vis === 'No' && $row['visible'] === 'Yes') {
                // MASQUER → ord des suivants -1
                $oldOrd = (int)$row['ord'];
                $pdo->beginTransaction();
                try{
                    $pdo->prepare("UPDATE `".TABLE_USER_MODULES."` SET visible='No', ord=NULL WHERE user_id=:uid AND layout=:l AND module_key=:k")
                        ->execute([':uid'=>$uid, ':l'=>$layout, ':k'=>$key]);
                    $pdo->prepare("UPDATE `".TABLE_USER_MODULES."` SET ord=ord-1 WHERE user_id=:uid AND layout=:l AND visible='Yes' AND ord>:o")
                        ->execute([':uid'=>$uid, ':l'=>$layout, ':o'=>$oldOrd]);
                    $pdo->commit();
                }catch(Throwable $e){ $pdo->rollBack(); throw $e; }
            } elseif ($vis === 'Yes' && $row['visible'] === 'No') {
                // AFFICHER → ord = max(ord_visible)+1
                $maxStmt = $pdo->prepare("SELECT COALESCE(MAX(ord),0) FROM `".TABLE_USER_MODULES."` WHERE user_id=:uid AND layout=:l AND visible='Yes'");
                $maxStmt->execute([':uid'=>$uid, ':l'=>$layout]);
                $max = (int)$maxStmt->fetchColumn();
                $new = $max + 1;
                $pdo->prepare("UPDATE `".TABLE_USER_MODULES."` SET visible='Yes', ord=:o WHERE user_id=:uid AND layout=:l AND module_key=:k")
                    ->execute([':uid'=>$uid, ':l'=>$layout, ':k'=>$key, ':o'=>$new]);
            }
            echo json_encode(['ok'=>true]); exit;
        }

        if ($act === 'reorder') {
            $layout = normalize_layout($json['layout'] ?? null, $ALLOWED_LAYOUTS);
            if (!$layout) { echo json_encode(['ok'=>false,'reason'=>'bad_layout']); exit; }
            $order = $json['order'] ?? null;
            if (!is_array($order) || empty($order)) { echo json_encode(['ok'=>false,'reason'=>'bad_order']); exit; }

            $ensureSeed($layout);

            // On ne renumérote que les modules visibles passés dans "order" (top→bottom)
            $pdo->beginTransaction();
            try{
                $i=1;
                $upd = $pdo->prepare("UPDATE `".TABLE_USER_MODULES."` SET ord=:o WHERE user_id=:uid AND layout=:l AND module_key=:k AND visible='Yes'");
                foreach ($order as $k) {
                    if (!in_array($k, $keys, true)) continue;
                    $upd->execute([':o'=>$i, ':uid'=>$uid, ':l'=>$layout, ':k'=>$k]);
                    $i++;
                }
                $pdo->commit();
            }catch(Throwable $e){ $pdo->rollBack(); throw $e; }

            echo json_encode(['ok'=>true]); exit;
        }

        echo json_encode(['ok'=>false,'reason'=>'unsupported']); exit;
    }

    echo json_encode(['ok'=>false,'reason'=>'unsupported']); exit;

} catch (Throwable $e) {
    echo json_encode(['ok'=>false,'reason'=>'server']); // 200 + JSON pour éviter les boucles côté client
}