<?php
require __DIR__.'/config.php';

/*
  Endpoints:
    GET  modules_prefs.php?action=get
      -> { ok:true, modules: { key:{visible:"Yes"|"No", ord:int|null}, ... } }

    POST modules_prefs.php  (JSON)
      { action:"toggle", key:"tasks", visible:"Yes"|"No" }
      { action:"reorder", order:["tasks","habits",... visible top→bottom] }
*/

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!defined('TABLE_USER_MODULES')) define('TABLE_USER_MODULES', 'user_module_prefs');

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
            `module_key` VARCHAR(32) NOT NULL,
            `visible` ENUM('Yes','No') NOT NULL DEFAULT 'Yes',
            `ord` INT NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `u_user_module` (`user_id`,`module_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 2) Canon des modules + ordre par défaut (Dashboard est fixe et exclu)
    $modules = [
        'tasks'    => 1,
        'habits'   => 2,
        'projects' => 3,
        'sport'    => 4,
        'food'     => 5,
        'calendar' => 6,
        'corps'    => 7,
        'finances' => 8,
        'clock'    => 9,
        'events'   => 10,
        'news'     => 11,
        'drive'    => 12,
    ];
    $keys = array_keys($modules);

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    // ===== helpers =====
    $fetchAll = function() use ($pdo, $uid){
        $st = $pdo->prepare("SELECT module_key, visible, ord FROM `".TABLE_USER_MODULES."` WHERE user_id=:uid");
        $st->execute([':uid'=>$uid]);
        $out = [];
        foreach ($st->fetchAll() as $r) {
            $out[$r['module_key']] = ['visible'=>$r['visible'], 'ord'=> is_null($r['ord'])? null : (int)$r['ord']];
        }
        return $out;
    };

    $ensureSeed = function() use ($pdo, $uid, $modules, $keys){
        // y a-t-il déjà des prefs ?
        $st = $pdo->prepare("SELECT COUNT(*) c FROM `".TABLE_USER_MODULES."` WHERE user_id=:uid");
        $st->execute([':uid'=>$uid]);
        $has = (int)$st->fetchColumn() > 0;
        if ($has) return;

        // seed : tout "Yes" et ord = mapping par défaut
        $pdo->beginTransaction();
        try {
            foreach ($modules as $k=>$ord) {
                $ins = $pdo->prepare("INSERT INTO `".TABLE_USER_MODULES."` (user_id,module_key,visible,ord) VALUES (:uid,:k,'Yes',:o)");
                $ins->execute([':uid'=>$uid, ':k'=>$k, ':o'=>$ord]);
            }
            $pdo->commit();
        } catch(Throwable $e){
            $pdo->rollBack();
            throw $e;
        }
    };

    if ($method === 'GET' && $action === 'get') {
        $ensureSeed(); // sème si vide (selon genre)

        // Renvoie l’état complet (tous les modules, même absents — on les crée si manque)
        $current = $fetchAll();

        // si une clé manque (schéma évolué), on l’insère avec defaults à la fin
        foreach ($modules as $k=>$ord) {
            if (!isset($current[$k])) {
                $ins = $pdo->prepare("INSERT INTO `".TABLE_USER_MODULES."` (user_id,module_key,visible,ord) VALUES (:uid,:k,'Yes',:o)");
                $ins->execute([':uid'=>$uid, ':k'=>$k, ':o'=>$ord]);
                $current[$k] = ['visible'=>'Yes', 'ord'=>$ord];
            }
        }

        // on ne garde que les modules canoniques
        $current = array_intersect_key($current, $modules);

        echo json_encode(['ok'=>true, 'modules'=>$current], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        if (!is_array($json)) { echo json_encode(['ok'=>false,'reason'=>'bad_json']); exit; }

        $act = $json['action'] ?? '';
        if ($act === 'toggle') {
            $key = $json['key'] ?? '';
            $vis = $json['visible'] ?? '';
            if (!in_array($key, $keys, true) || !in_array($vis,['Yes','No'],true)) {
                echo json_encode(['ok'=>false,'reason'=>'bad_args']); exit;
            }

            // lit son ordre actuel
            $st = $pdo->prepare("SELECT visible, ord FROM `".TABLE_USER_MODULES."` WHERE user_id=:uid AND module_key=:k LIMIT 1");
            $st->execute([':uid'=>$uid, ':k'=>$key]);
            $row = $st->fetch();
            if (!$row) {
                // crée la ligne si absente
                $pdo->prepare("INSERT INTO `".TABLE_USER_MODULES."` (user_id,module_key,visible,ord) VALUES (:uid,:k,'No',NULL)")
                    ->execute([':uid'=>$uid, ':k'=>$key]);
                $row = ['visible'=>'No','ord'=>null];
            }

            if ($vis === 'No' && $row['visible'] === 'Yes') {
                // MASQUER → ord des suivants -1
                $oldOrd = (int)$row['ord'];
                $pdo->beginTransaction();
                try{
                    $pdo->prepare("UPDATE `".TABLE_USER_MODULES."` SET visible='No', ord=NULL WHERE user_id=:uid AND module_key=:k")
                        ->execute([':uid'=>$uid, ':k'=>$key]);
                    $pdo->prepare("UPDATE `".TABLE_USER_MODULES."` SET ord=ord-1 WHERE user_id=:uid AND visible='Yes' AND ord>:o")
                        ->execute([':uid'=>$uid, ':o'=>$oldOrd]);
                    $pdo->commit();
                }catch(Throwable $e){ $pdo->rollBack(); throw $e; }
            } elseif ($vis === 'Yes' && $row['visible'] === 'No') {
                // AFFICHER → ord = max(ord_visible)+1
                $max = (int)$pdo->query("SELECT COALESCE(MAX(ord),0) FROM `".TABLE_USER_MODULES."` WHERE user_id=".$uid." AND visible='Yes'")->fetchColumn();
                $new = $max + 1;
                $pdo->prepare("UPDATE `".TABLE_USER_MODULES."` SET visible='Yes', ord=:o WHERE user_id=:uid AND module_key=:k")
                    ->execute([':uid'=>$uid, ':k'=>$key, ':o'=>$new]);
            }
            echo json_encode(['ok'=>true]); exit;
        }

        if ($act === 'reorder') {
            $order = $json['order'] ?? null;
            if (!is_array($order) || empty($order)) { echo json_encode(['ok'=>false,'reason'=>'bad_order']); exit; }

            // On ne renumérote que les modules visibles passés dans "order" (top→bottom)
            $pdo->beginTransaction();
            try{
                $i=1;
                $upd = $pdo->prepare("UPDATE `".TABLE_USER_MODULES."` SET ord=:o WHERE user_id=:uid AND module_key=:k AND visible='Yes'");
                foreach ($order as $k) {
                    if (!in_array($k, $keys, true)) continue;
                    $upd->execute([':o'=>$i, ':uid'=>$uid, ':k'=>$k]);
                    $i++;
                }
                $pdo->commit();
            }catch(Throwable $e){ $pdo->rollBack(); throw $e; }

            echo json_encode(['ok'=>true]); exit;
        }

        if ($act === 'reset_order') {
            $ensureSeed();
            $pdo->beginTransaction();
            try {
                $upd = $pdo->prepare("UPDATE `".TABLE_USER_MODULES."` SET ord=:o WHERE user_id=:uid AND module_key=:k");
                foreach ($modules as $k=>$ord) {
                    $upd->execute([':o'=>$ord, ':uid'=>$uid, ':k'=>$k]);
                }
                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
            echo json_encode(['ok'=>true]); exit;
        }

        echo json_encode(['ok'=>false,'reason'=>'unsupported']); exit;
    }

    echo json_encode(['ok'=>false,'reason'=>'unsupported']); exit;

} catch (Throwable $e) {
    echo json_encode(['ok'=>false,'reason'=>'server']); // 200 + JSON pour éviter les boucles côté client
}
