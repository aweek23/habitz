<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbStatusMessage = '';
$pdo = null;

require_once __DIR__ . '/php/active_tracking.php';

function checkDatabaseConnection(?PDO $pdo): array
{
    $start = microtime(true);
    if (!($pdo instanceof PDO)) {
        return [
            'id' => 'service-database',
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
            'id' => 'service-database',
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
            'id' => 'service-database',
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
            'id' => 'service-57131251210000',
            'key' => 'website',
            'name' => $name,
            'category' => 'Panel',
            'state' => $state,
            'online' => true,
            'latency_ms' => round($latency, 1),
            'message' => $state === 'slow' ? 'Temps de réponse élevé' : 'Réponse OK (' . $httpCode . ')',
            'checked_at' => date('c'),
        ];
    }

    return [
        'id' => 'service-57131251210000',
        'key' => 'website',
        'name' => $name,
        'category' => 'Panel',
        'state' => 'offline',
        'online' => false,
        'latency_ms' => $success ? round($latency, 1) : null,
        'message' => 'Aucune réponse',
        'checked_at' => date('c'),
    ];
}

function ensureUptimeTable(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS uptime_checks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            service_id VARCHAR(100) NOT NULL,
            service_name VARCHAR(255) NOT NULL,
            checked_at DATETIME NOT NULL,
            online TINYINT(1) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function logServiceCheck(PDO $pdo, array $service): void
{
    try {
        ensureUptimeTable($pdo);
        $stmt = $pdo->prepare(
            'INSERT INTO uptime_checks (service_id, service_name, checked_at, online)
             VALUES (:service_id, :service_name, :checked_at, :online)'
        );
        $stmt->execute([
            ':service_id' => $service['id'] ?? ($service['key'] ?? 'unknown'),
            ':service_name' => $service['name'] ?? 'Service',
            ':checked_at' => date('Y-m-d H:i:s', strtotime($service['checked_at'] ?? 'now')),
            ':online' => !empty($service['online']) ? 1 : 0,
        ]);
    } catch (Throwable $e) {
        // Les erreurs de journalisation ne doivent pas interrompre la réponse JSON.
    }
}

function getTotalUserCount(PDO $pdo): int
{
    $stmt = $pdo->query('SELECT COUNT(*) FROM users');
    return (int) $stmt->fetchColumn();
}

function fetchUserGrowthSeries(PDO $pdo, string $range): array
{
    $range = strtolower($range);

    if ($range !== 'day') {
        $range = 'day';
    }

    $start = new DateTime('now');
    $start->modify('-1 day');
    $startDate = $start->format('Y-m-d H:00:00');

    $baseStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE creation_date < :start');
    $baseStmt->execute([':start' => $startDate]);
    $runningTotal = (int) $baseStmt->fetchColumn();

    $stmt = $pdo->prepare(
        'SELECT DATE_FORMAT(creation_date, "%Y-%m-%d %H:00:00") AS bucket, COUNT(*) AS count
         FROM users
         WHERE creation_date >= :start
         GROUP BY bucket
         ORDER BY bucket'
    );
    $stmt->execute([':start' => $startDate]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $series = [];

    foreach ($rows as $row) {
        $runningTotal += (int) $row['count'];
        $series[] = [
            'label' => date('H\h', strtotime($row['bucket'])),
            'value' => $runningTotal,
            'date' => $row['bucket'],
        ];
    }

    return ['range' => $range, 'points' => $series];
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
        checkExternalService('http://57.131.25.12:10000', 'Virtualmin/Webmin'),
    ];

    if ($pdo instanceof PDO) {
        foreach ($services as $service) {
            logServiceCheck($pdo, $service);
        }
    }

    echo json_encode(['services' => $services]);
    exit;
}

if (isset($_GET['user_metrics'])) {
    header('Content-Type: application/json');

    if (!($pdo instanceof PDO)) {
        echo json_encode(['total' => 0, 'series' => ['range' => 'day', 'points' => []]]);
        exit;
    }

    $range = $_GET['range'] ?? 'day';
    echo json_encode([
        'total' => getTotalUserCount($pdo),
        'series' => fetchUserGrowthSeries($pdo, $range),
    ]);
    exit;
}

if (isset($_GET['active_metrics'])) {
    header('Content-Type: application/json');

    if (!($pdo instanceof PDO)) {
        echo json_encode(['active' => 0, 'series' => ['range' => 'day', 'points' => []]]);
        exit;
    }

    $range = $_GET['range'] ?? 'day';
    $activeUsers = countActiveUsers($pdo, 5);
    logActiveUserCount($pdo, $activeUsers);

    echo json_encode([
        'active' => $activeUsers,
        'series' => fetchActiveAverageSeries($pdo, $range),
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
$rightExtras = '';

ob_start();
?>
<div class="admin-grid">
  <div class="admin-col">
    <section class="metric-card" aria-labelledby="user-total-title">
      <div class="metric-chart" data-chart="users" aria-label="Évolution des utilisateurs">
        <div class="metric-overlay">
          <p class="metric-label">Utilisateurs</p>
          <h3 class="metric-value" id="user-total-title">0</h3>
        </div>
      </div>
    </section>
  </div>
  <div class="admin-col">
    <section class="metric-card" aria-labelledby="active-total-title">
      <div class="metric-chart" data-chart="active" aria-label="Moyenne des utilisateurs actifs">
        <div class="metric-overlay">
          <p class="metric-label">Utilisateurs actifs</p>
          <h3 class="metric-value" id="active-total-title">0</h3>
        </div>
      </div>
    </section>
  </div>
  <div class="admin-col"></div>
  <div class="admin-col">
    <div class="uptime-stack">
      <article class="uptime-card" data-service="database" data-service-id="service-database">
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

      <article class="uptime-card" data-service="website" data-service-id="service-57131251210000">
        <div class="uptime-card-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M2 12h20"></path>
            <path d="M12 2c2.5 2.9 4 6.4 4 10s-1.5 7.1-4 10"></path>
            <path d="M12 2C9.5 4.9 8 8.4 8 12s1.5 7.1 4 10"></path>
          </svg>
        </div>
        <div class="uptime-card-body">
          <p class="uptime-category">Panel</p>
          <div class="uptime-title-row">
            <h4>Virtualmin/Webmin</h4>
            <span class="uptime-status-dot" data-status="unknown" role="img" aria-label="Statut du site Virtualmin/Webmin"></span>
          </div>
        </div>
      </article>
    </div>
  </div>
</div>

<script>
  (function() {
    const userValueEl = document.getElementById('user-total-title');
    const activeValueEl = document.getElementById('active-total-title');
    const defaultRange = 'day';

    function renderLineChart(container, points) {
      const overlay = container.querySelector('.metric-overlay');
      if (overlay) {
        overlay.remove();
      }

      container.innerHTML = '';
      container.classList.remove('bars');
      container.classList.add('line');

      if (overlay) {
        container.appendChild(overlay);
      }

      if (!points || points.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'chart-empty';
        empty.textContent = 'Aucune donnée';
        container.appendChild(empty);
        return;
      }

      const bounds = container.getBoundingClientRect();
      const width = bounds.width || 320;
      const height = bounds.height || 220;
      const padding = 10;

      const maxValue = Math.max(...points.map(p => p.value), 1);
      const step = points.length > 1 ? (width - padding * 2) / (points.length - 1) : 0;

      const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
      svg.setAttribute('preserveAspectRatio', 'none');

      const pathPoints = points.map((point, index) => {
        const x = padding + step * index;
        const y = height - padding - (point.value / maxValue) * (height - padding * 2);
        return { x, y, label: point.label, value: point.value };
      });

      const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
      const d = pathPoints
        .map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x},${p.y}`)
        .join(' ');
      path.setAttribute('d', d);
      path.setAttribute('class', 'line-path');
      svg.appendChild(path);

      pathPoints.forEach(p => {
        const dot = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        dot.setAttribute('cx', p.x);
        dot.setAttribute('cy', p.y);
        dot.setAttribute('r', 3.5);
        dot.setAttribute('class', 'line-dot');
        dot.setAttribute('data-label', `${p.label} : ${p.value}`);
        dot.setAttribute('tabindex', '-1');
        svg.appendChild(dot);
      });

      container.appendChild(svg);
    }

    async function fetchUserMetrics(range = defaultRange) {
      try {
        const res = await fetch(`/admin_dashboard.php?user_metrics=1&range=${encodeURIComponent(range)}`, { credentials: 'same-origin' });
        const data = await res.json();
        userValueEl.textContent = data.total ?? 0;
        renderLineChart(document.querySelector('[data-chart="users"]'), data.series?.points || []);
      } catch (error) {
        console.error('Erreur utilisateurs', error);
      }
    }

    async function fetchActiveMetrics(range = defaultRange) {
      try {
        const res = await fetch(`/admin_dashboard.php?active_metrics=1&range=${encodeURIComponent(range)}`, { credentials: 'same-origin' });
        const data = await res.json();
        activeValueEl.textContent = data.active ?? 0;
        renderLineChart(document.querySelector('[data-chart="active"]'), data.series?.points || []);
      } catch (error) {
        console.error('Erreur utilisateurs actifs', error);
      }
    }

    fetchUserMetrics(defaultRange);
    fetchActiveMetrics(defaultRange);
    setInterval(() => fetchActiveMetrics(defaultRange), 5 * 60 * 1000);

    const STATUS_LABELS = {
      online: 'Connecté',
      slow: 'Lent',
      offline: 'Hors ligne',
      unknown: 'Inconnu'
    };

    function updateCard(service) {
      const lookupId = service.id || service.key;
      const card = document.querySelector(`.uptime-card[data-service-id="${lookupId}"]`) ||
                   document.querySelector(`.uptime-card[data-service="${service.key}"]`);
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
          id: card.dataset.serviceId,
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
