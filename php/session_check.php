<?php
require __DIR__.'/config.php';

/*
  - Toujours 200 + JSON {logged_in, user_id, username, gender}
  - No-cache pour éviter les réponses périmées
*/
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$logged = !empty($_SESSION['user_id']);
$out = [
  'logged_in' => $logged,
  'user_id'   => $logged ? (int)($_SESSION['user_id']) : null,
  'username'  => $logged ? ($_SESSION['username'] ?? null) : null,
  'gender'    => null,
];

if ($logged) {
    try {
        // On lit le genre depuis la BDD (plus fiable que la session)
        $st = $pdo->prepare("SELECT gender FROM ".TABLE_USERS." WHERE id = :id LIMIT 1");
        $st->execute([':id' => (int)$_SESSION['user_id']]);
        $row = $st->fetch();
        if ($row && isset($row['gender'])) {
            $out['gender'] = $row['gender'];
        }
    } catch (Throwable $e) {
        // ignore, on renverra null
    }
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);
exit;
