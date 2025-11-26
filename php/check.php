<?php
require __DIR__.'/config.php';
header('Content-Type: application/json; charset=utf-8');

$out = [
  'ok' => true,
  'username_available' => null,
  'email_checked' => false, 'email_used' => null,
  'phone_checked' => false, 'phone_used' => null,
];

try {
  // Vérif pseudo
  if (isset($_GET['u'])) {
    $u = trim($_GET['u']);
    if ($u !== '') {
      $st = $pdo->prepare("SELECT id FROM ".TABLE_USERS." WHERE username = :u LIMIT 1");
      $st->execute([':u' => $u]);
      $out['username_available'] = $st->fetch() ? false : true;
    }
  }

  // Vérif email
  if (isset($_GET['email'])) {
    $email = trim($_GET['email']);
    if ($email !== '') {
      $out['email_checked'] = true;
      $st = $pdo->prepare("SELECT id FROM ".TABLE_USERS." WHERE email = :e LIMIT 1");
      $st->execute([':e' => $email]);
      $out['email_used'] = $st->fetch() ? true : false;
    }
  }

  // Vérif téléphone (dial + phone_local)
  if (isset($_GET['phone'])) {
    $dial = trim($_GET['dial'] ?? '');
    $local = trim($_GET['phone'] ?? '');
    $digits = preg_replace('/\D+/', '', $dial.$local);
    if ($digits !== '') {
      $out['phone_checked'] = true;
      $e164 = '+' . $digits;
      $st = $pdo->prepare("SELECT id FROM ".TABLE_USERS." WHERE phone = :p LIMIT 1");
      $st->execute([':p' => $e164]);
      $out['phone_used'] = $st->fetch() ? true : false;
    }
  }

  echo json_encode($out);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'server']);
}
