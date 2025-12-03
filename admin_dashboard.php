<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbStatusMessage = '';
$pdo = null;

try {
    $pdo = require __DIR__ . '/config.php';
    $dbStatusMessage = 'Connexion à la base de données réussie.';
} catch (Throwable $e) {
    $dbStatusMessage = 'Erreur de connexion à la base de données : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['user_id'])) {
    if (isset($_GET['ping'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['online' => false, 'message' => 'Non authentifié']);
        exit;
    }
    header('Location: auth.php');
    exit;
}

if ($pdo instanceof PDO) {
    try {
        $rankStmt = $pdo->prepare('SELECT rank FROM users WHERE id = :id LIMIT 1');
        $rankStmt->execute([':id' => $_SESSION['user_id']]);
        $freshRank = $rankStmt->fetchColumn();
        $_SESSION['rank'] = $freshRank !== false ? $freshRank : 'user';
    } catch (Throwable $e) {
        $_SESSION['rank'] = 'user';
    }
} else {
    $_SESSION['rank'] = 'user';
}

if (isset($_GET['ping'])) {
    header('Content-Type: application/json');
    $isOnline = $pdo instanceof PDO;
    $message = $isOnline ? 'Connexion à la base de données réussie.' : $dbStatusMessage;
    echo json_encode([
        'online' => $isOnline,
        'message' => $message,
        'checked_at' => date('Y-m-d H:i:s'),
    ]);
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

$pageTitle = 'Admin Dashboard';
$menuItems = [
    ['label' => 'Dashboard', 'href' => '/admin_dashboard.php'],
    ['label' => 'Utilisateurs', 'href' => '/user.php'],
    ['label' => 'Signalements', 'href' => '#'],
    ['label' => 'Paramètres', 'href' => '#'],
];
$rightExtras = <<<HTML
  <div class="right-module db-monitor-module" id="db-monitor">
    <div class="db-monitor-head">
      <div class="db-monitor-subtitle">Système</div>
      <div class="db-monitor-indicator" aria-hidden="true"></div>
    </div>
    <div class="db-monitor-title" id="db-monitor-title">Base de données</div>
    <p class="db-monitor-meta" id="db-monitor-meta">Vérification en cours…</p>
  </div>
HTML;

ob_start();
?>
<div class="db-status-banner" role="status" aria-live="polite">
  <span class="db-dot <?php echo (strpos($dbStatusMessage, 'Erreur') !== false) ? 'error' : 'ok'; ?>" aria-hidden="true"></span>
  <span class="db-message"><?php echo $dbStatusMessage; ?></span>
</div>

<div class="cards-grid">
  <div class="hero-card">
    <span class="badge">Administration</span>
    <h2>Tableau de bord admin</h2>
    <p>Consultez les utilisateurs via l'onglet "Utilisateurs" ou accédez aux prochains modules du panneau d'administration.</p>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a class="pill" href="/user.php">Accéder aux utilisateurs</a>
      <a class="pill" href="#">Voir les signalements</a>
    </div>
  </div>

  <div class="widget-card">
    <h3>État du système</h3>
    <ul style="margin:0; padding-left:1.1rem; display:grid; gap:8px;">
      <li>Statut base de données : <?= strpos($dbStatusMessage, 'Erreur') !== false ? 'Hors ligne' : 'En ligne' ?></li>
      <li>Dernière vérification de rôle : immédiate (à chaque chargement)</li>
      <li>Accès admin : <?= $isAdmin ? 'Autorisé' : 'Refusé' ?></li>
    </ul>
  </div>
</div>
<script>
  (function() {
    const indicator = document.getElementById('db-monitor')?.querySelector('.db-monitor-indicator');
    const titleEl = document.getElementById('db-monitor-title');
    const metaEl = document.getElementById('db-monitor-meta');

    async function refreshStatus() {
      if (!indicator || !titleEl || !metaEl) return;
      indicator.classList.remove('ok', 'error');
      indicator.classList.add('loading');
      metaEl.textContent = 'Vérification en cours…';

      try {
        const response = await fetch('/admin_dashboard.php?ping=1', { credentials: 'same-origin' });
        const payload = await response.json();
        const isOnline = Boolean(payload.online);
        indicator.classList.remove('loading');
        indicator.classList.add(isOnline ? 'ok' : 'error');
        titleEl.textContent = isOnline ? 'Base de données en ligne' : 'Base de données hors ligne';
        metaEl.textContent = payload.message + (payload.checked_at ? ` • ${payload.checked_at}` : '');
      } catch (error) {
        indicator.classList.remove('loading');
        indicator.classList.add('error');
        titleEl.textContent = 'Base de données hors ligne';
        metaEl.textContent = 'Impossible de vérifier le statut. ' + (error?.message || '');
      }
    }

    refreshStatus();
    setInterval(refreshStatus, 5 * 60 * 1000);
  })();
</script>
<?php
$content = ob_get_clean();

include __DIR__ . '/php/layout.php';
