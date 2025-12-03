<?php
$pdo = require __DIR__ . '/config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

$isAdmin = ($_SESSION['rank'] ?? 'user') === 'admin';

if (!$isAdmin) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Accès refusé</title></head><body>';
    echo '<p style="font-family:Arial,sans-serif; margin:2rem;">Accès réservé aux administrateurs. Connectez-vous avec un compte "admin" pour ouvrir le tableau de bord.</p>';
    echo '</body></html>';
    exit;
}

$searchTerm = trim($_GET['q'] ?? '');
$users = [];
$error = '';
$ipLogsByUser = [];
$primaryIpByUser = [];

if ($searchTerm !== '') {
    try {
        $like = '%' . $searchTerm . '%';
        $conditions = [
            'username LIKE :termUser',
            'email LIKE :termEmail',
            'phone_number LIKE :termPhone',
            'ip LIKE :termIp',
        ];
        $params = [
            ':termUser' => $like,
            ':termEmail' => $like,
            ':termPhone' => $like,
            ':termIp' => $like,
        ];

        if (ctype_digit($searchTerm)) {
            $conditions[] = 'id = :idExact';
            $params[':idExact'] = (int) $searchTerm;
        }

        $sql = 'SELECT id, username, email, phone_number, birthdate, gender, password, rank, creation_date, ip '
             . 'FROM users '
             . 'WHERE ' . implode(' OR ', $conditions)
             . ' ORDER BY creation_date DESC LIMIT 100';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        if (!empty($users)) {
            $userIds = array_column($users, 'id');
            $placeholders = implode(', ', array_fill(0, count($userIds), '?'));

            $logStmt = $pdo->prepare("SELECT user_id, ip_address, context, created_at FROM user_ips WHERE user_id IN ($placeholders) ORDER BY created_at DESC");
            $logStmt->execute($userIds);
            while ($row = $logStmt->fetch()) {
                $ipLogsByUser[$row['user_id']][] = $row;
            }

            $countStmt = $pdo->prepare("SELECT user_id, ip_address, COUNT(*) AS cnt FROM user_ips WHERE user_id IN ($placeholders) GROUP BY user_id, ip_address");
            $countStmt->execute($userIds);
            while ($row = $countStmt->fetch()) {
                $userId = $row['user_id'];
                $primaryIpByUser[$userId] = $primaryIpByUser[$userId] ?? ['ip_address' => $row['ip_address'], 'cnt' => (int) $row['cnt']];
                if ((int) $row['cnt'] > $primaryIpByUser[$userId]['cnt']) {
                    $primaryIpByUser[$userId] = ['ip_address' => $row['ip_address'], 'cnt' => (int) $row['cnt']];
                }
            }
        }
    } catch (PDOException $e) {
        $error = 'Erreur lors de la recherche : ' . $e->getMessage();
    }
}

$pageTitle = 'Admin Dashboard';
$menuItems = [
    ['label' => 'Dashboard', 'href' => '#'],
    ['label' => 'Utilisateurs', 'href' => '#'],
    ['label' => 'Signalements', 'href' => '#'],
    ['label' => 'Paramètres', 'href' => '#'],
];

ob_start();
?>
<div class="section-title">
  <div>
    <div class="pill">Admin</div>
    <h1>Dashboard</h1>
  </div>
</div>

<div class="cards-grid">
  <div class="widget-card">
    <h3>Recherche utilisateurs</h3>
    <form method="get" action="admin_dashboard.php" class="auth-form" style="gap: 10px;">
      <label for="q">Pseudo, email, téléphone, IP ou ID</label>
      <input type="text" id="q" name="q" value="<?= htmlspecialchars($searchTerm, ENT_QUOTES) ?>" placeholder="Rechercher un utilisateur" />
      <button type="submit" class="pill">Rechercher</button>
    </form>

    <?php if ($error): ?>
      <p class="error" style="margin-top: 8px; color: #ff9f0f; font-weight: 600;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($searchTerm === ''): ?>
      <p class="notice">Saisissez un terme de recherche pour voir les utilisateurs correspondants.</p>
    <?php endif; ?>
  </div>

  <?php if ($searchTerm !== ''): ?>
    <div class="widget-card" style="overflow-x: auto;">
      <?php if (empty($users)): ?>
        <p>Aucun utilisateur trouvé pour "<?= htmlspecialchars($searchTerm) ?>".</p>
      <?php else: ?>
        <table style="width: 100%; border-collapse: collapse;">
          <thead>
            <tr>
              <th style="text-align:left; padding:8px;">ID</th>
              <th style="text-align:left; padding:8px;">Username</th>
              <th style="text-align:left; padding:8px;">Email</th>
              <th style="text-align:left; padding:8px;">Phone</th>
              <th style="text-align:left; padding:8px;">Birthdate</th>
              <th style="text-align:left; padding:8px;">Gender</th>
              <th style="text-align:left; padding:8px;">Password</th>
              <th style="text-align:left; padding:8px;">Rank</th>
              <th style="text-align:left; padding:8px;">Creation date</th>
              <th style="text-align:left; padding:8px;">IP</th>
              <th style="text-align:left; padding:8px;">Historique des IP</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $user): ?>
              <tr style="border-top: 1px solid rgba(255,255,255,0.08);">
                <td style="padding:8px;"><?= (int) $user['id'] ?></td>
                <td style="padding:8px;"><?= htmlspecialchars($user['username']) ?></td>
                <td style="padding:8px;"><?= htmlspecialchars($user['email']) ?></td>
                <td style="padding:8px;"><?= htmlspecialchars($user['phone_number'] ?? '') ?></td>
                <td style="padding:8px;"><?= htmlspecialchars($user['birthdate']) ?></td>
                <td style="padding:8px;"><?= htmlspecialchars($user['gender'] ?? '') ?></td>
                <td style="padding:8px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;">
                  <?= htmlspecialchars($user['password']) ?>
                </td>
                <td style="padding:8px;"><?= htmlspecialchars($user['rank']) ?></td>
                <td style="padding:8px;"><?= htmlspecialchars($user['creation_date']) ?></td>
                <td style="padding:8px;"><?= htmlspecialchars($user['ip'] ?? '') ?></td>
                <td style="padding:8px;">
                  <?php
                    $logs = $ipLogsByUser[$user['id']] ?? [];
                    $primaryIp = $primaryIpByUser[$user['id']]['ip_address'] ?? null;
                  ?>
                  <?php if (empty($logs)): ?>
                    <span class="notice">Aucun historique</span>
                  <?php else: ?>
                    <ul style="margin:0; padding-left:1.25rem;">
                      <?php foreach ($logs as $log): ?>
                        <?php
                          $label = $log['context'];
                          if ($primaryIp !== null && $log['ip_address'] === $primaryIp) {
                              $label .= ' / Principale';
                          }
                        ?>
                        <li>
                          <strong><?= htmlspecialchars($log['ip_address']) ?></strong>
                          (<?= htmlspecialchars($label) ?>)
                          — <?= htmlspecialchars($log['created_at']) ?>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
<?php
$content = ob_get_clean();

include __DIR__ . '/php/layout.php';
