<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbStatusMessage = '';
$pdo = null;

function checkDatabaseConnection(?PDO $pdo): array
{
    $start = microtime(true);
    if (!($pdo instanceof PDO)) {
        return [
            'key' => 'database',
            'name' => 'Base de données',
            'category' => 'Infrastructure',
            'state' => 'offline',
            'online' => false,
            'latency_ms' => null,
            'message' => 'Connexion indisponible',
            'checked_at' => date('c'),
        ];
    }

    try {
        $pdo->query('SELECT 1');
        $latency = (microtime(true) - $start) * 1000;
        $state = $latency > 2000 ? 'slow' : 'online';

        return [
            'key' => 'database',
            'name' => 'Base de données',
            'category' => 'Infrastructure',
            'state' => $state,
            'online' => true,
            'latency_ms' => round($latency, 1),
            'message' => $state === 'slow' ? 'Réponse lente' : 'Connexion OK',
            'checked_at' => date('c'),
        ];
    } catch (Throwable $e) {
        return [
            'key' => 'database',
            'name' => 'Base de données',
            'category' => 'Infrastructure',
            'state' => 'offline',
            'online' => false,
            'latency_ms' => null,
            'message' => 'Erreur : ' . $e->getMessage(),
            'checked_at' => date('c'),
        ];
    }
}

function checkExternalService(string $url, string $name = 'Service distant'): array
{
    $start = microtime(true);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $success = curl_exec($ch) !== false;
    $httpCode = $success ? curl_getinfo($ch, CURLINFO_HTTP_CODE) : 0;
    $latency = (microtime(true) - $start) * 1000;
    curl_close($ch);

    if ($success && $httpCode >= 200 && $httpCode < 400) {
        $state = $latency > 2500 ? 'slow' : 'online';
        return [
            'key' => 'website',
            'name' => $name,
            'category' => 'Sites web',
            'state' => $state,
            'online' => true,
            'latency_ms' => round($latency, 1),
            'message' => $state === 'slow' ? 'Temps de réponse élevé' : 'Réponse OK (' . $httpCode . ')',
            'checked_at' => date('c'),
        ];
    }

    return [
        'key' => 'website',
        'name' => $name,
        'category' => 'Sites web',
        'state' => 'offline',
        'online' => false,
        'latency_ms' => $success ? round($latency, 1) : null,
        'message' => 'Aucune réponse',
        'checked_at' => date('c'),
    ];
}

try {
    $pdo = require __DIR__ . '/config.php';
    $dbStatusMessage = 'Connexion à la base de données réussie.';
} catch (Throwable $e) {
    $dbStatusMessage = 'Erreur de connexion à la base de données : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['user_id'])) {
    if (isset($_GET['ping']) || isset($_GET['uptime_check'])) {
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

if (isset($_GET['uptime_check'])) {
    header('Content-Type: application/json');
    $services = [
        checkDatabaseConnection($pdo),
        checkExternalService('http://57.131.25.12:10000', '57.131.25.12:10000'),
    ];
    echo json_encode(['services' => $services]);
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
$rightExtras = '';

ob_start();
?>
<div class="admin-grid">
  <div class="admin-col"></div>
  <div class="admin-col"></div>
  <div class="admin-col">
    <div class="uptime-stack">
      <article class="uptime-card" data-service="database">
        <div class="uptime-card-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <ellipse cx="12" cy="5" rx="7" ry="3.5"></ellipse>
            <path d="M5 5v6c0 1.93 3.13 3.5 7 3.5s7-1.57 7-3.5V5"></path>
            <path d="M5 11v6c0 1.93 3.13 3.5 7 3.5s7-1.57 7-3.5v-6"></path>
          </svg>
        </div>
        <div class="uptime-card-body">
          <p class="uptime-category">Infrastructure</p>
          <div class="uptime-title-row">
            <h4>Base de données</h4>
            <span class="uptime-status-dot" data-status="unknown" role="img" aria-label="Statut de la base de données"></span>
          </div>
        </div>
      </article>

      <article class="uptime-card" data-service="website">
        <div class="uptime-card-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M2 12h20"></path>
            <path d="M12 2c2.5 2.9 4 6.4 4 10s-1.5 7.1-4 10"></path>
            <path d="M12 2C9.5 4.9 8 8.4 8 12s1.5 7.1 4 10"></path>
          </svg>
        </div>
        <div class="uptime-card-body">
          <p class="uptime-category">Sites web</p>
          <div class="uptime-title-row">
            <h4>57.131.25.12:10000</h4>
            <span class="uptime-status-dot" data-status="unknown" role="img" aria-label="Statut du site 57.131.25.12:10000"></span>
          </div>
        </div>
      </article>
    </div>
  </div>
</div>

<script>
  (function() {
    const STATUS_LABELS = {
      online: 'Connecté',
      slow: 'Lent',
      offline: 'Hors ligne',
      unknown: 'Inconnu'
    };

    function updateCard(service) {
      const card = document.querySelector(`.uptime-card[data-service="${service.key}"]`);
      if (!card) return;

      const statusDot = card.querySelector('.uptime-status-dot');

      const state = service.state || 'unknown';
      statusDot.setAttribute('data-status', state);
      statusDot.setAttribute('aria-label', `Statut : ${STATUS_LABELS[state] || state}`);
    }

    function markAllOffline() {
      document.querySelectorAll('.uptime-card').forEach(card => {
        updateCard({
          key: card.dataset.service,
          state: 'offline',
          checked_at: new Date().toISOString(),
          latency_ms: null
        });
      });
    }

    async function fetchStatuses() {
      try {
        const response = await fetch('/admin_dashboard.php?uptime_check=1', { credentials: 'same-origin' });
        if (!response.ok) throw new Error('Requête échouée');
        const data = await response.json();
        (data.services || []).forEach(updateCard);
      } catch (error) {
        console.error('Erreur de surveillance', error);
        markAllOffline();
      }
    }

    fetchStatuses();
    setInterval(fetchStatuses, 5 * 60 * 1000);
  })();
</script>
<?php
$content = ob_get_clean();

include __DIR__ . '/php/layout.php';
