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

function checkExternalService(
    string $url,
    string $name = 'Service distant',
    string $id = 'service-external',
    string $category = 'Panel'
): array
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
            'id' => $id,
            'key' => 'website',
            'name' => $name,
            'category' => $category,
            'state' => $state,
            'online' => true,
            'latency_ms' => round($latency, 1),
            'message' => $state === 'slow' ? 'Temps de réponse élevé' : 'Réponse OK (' . $httpCode . ')',
            'checked_at' => date('c'),
        ];
    }

    return [
        'id' => $id,
        'key' => 'website',
        'name' => $name,
        'category' => $category,
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
    if (!in_array($range, ['day', 'week', 'month', 'ytd'], true)) {
        $range = 'week';
    }

    $now = new DateTime('now');
    $start = new DateTime('now');
    $intervalSpec = 'P1D';
    $labelFormat = 'd/m';
    $groupFormat = '%Y-%m-%d';

    switch ($range) {
        case 'day':
            $start->modify('-23 hours');
            $start->setTime((int) $start->format('H'), 0, 0);
            $now->setTime((int) $now->format('H'), 0, 0);
            $intervalSpec = 'PT1H';
            $labelFormat = 'H\h';
            $groupFormat = '%Y-%m-%d %H:00:00';
            break;
        case 'week':
            $start = new DateTime('today');
            $start->modify('-6 days');
            $now = new DateTime('today');
            $intervalSpec = 'P1D';
            $labelFormat = 'd/m';
            $groupFormat = '%Y-%m-%d';
            break;
        case 'month':
            $start = new DateTime('today');
            $start->modify('-29 days');
            $now = new DateTime('today');
            $intervalSpec = 'P1D';
            $labelFormat = 'd/m';
            $groupFormat = '%Y-%m-%d';
            break;
        case 'ytd':
            $start = new DateTime('first day of January ' . $now->format('Y'));
            $start->setTime(0, 0, 0);
            $now = new DateTime('today');
            $intervalSpec = 'P1D';
            $labelFormat = 'd/m';
            $groupFormat = '%Y-%m-%d';
            break;
    }

    $startDate = $start->format('Y-m-d H:i:s');

    $baseStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE creation_date < :start');
    $baseStmt->execute([':start' => $startDate]);
    $runningTotal = (int) $baseStmt->fetchColumn();

    $stmt = $pdo->prepare(
        'SELECT DATE_FORMAT(creation_date, :group_format) AS bucket, COUNT(*) AS count
         FROM users
         WHERE creation_date >= :start
         GROUP BY bucket
         ORDER BY bucket'
    );
    $stmt->execute([
        ':start' => $startDate,
        ':group_format' => $groupFormat,
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $increments = [];
    foreach ($rows as $row) {
        $increments[$row['bucket']] = (int) $row['count'];
    }

    $series = [];
    $cursor = new DateTime($startDate);
    $interval = new DateInterval($intervalSpec);

    while ($cursor <= $now) {
        $bucket = $range === 'day'
            ? $cursor->format('Y-m-d H:00:00')
            : $cursor->format('Y-m-d');

        $runningTotal += $increments[$bucket] ?? 0;
        $series[] = [
            'label' => $cursor->format($labelFormat),
            'value' => $runningTotal,
            'date' => $bucket,
        ];
        $cursor->add($interval);
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
        checkExternalService('http://57.131.25.12:10000', 'Virtualmin/Webmin', 'service-57131251210000', 'Panel'),
        checkExternalService('http://57.131.25.12', 'Habitz', 'service-habitz', 'Site Web'),
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
        echo json_encode(['total' => 0, 'series' => ['range' => 'week', 'points' => []]]);
        exit;
    }

    $range = $_GET['range'] ?? 'week';
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
        'series' => fetchActiveLogSeries($pdo, $range),
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
        <div class="metric-actions" data-actions="users">
          <button class="range-btn" data-chart="users" data-range="day" type="button">1d</button>
          <button class="range-btn active" data-chart="users" data-range="week" type="button">1w</button>
          <button class="range-btn" data-chart="users" data-range="month" type="button">1m</button>
          <button class="range-btn" data-chart="users" data-range="ytd" type="button">YTD</button>
        </div>
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
        <div class="metric-actions" data-actions="active">
          <button class="range-btn active" data-chart="active" data-range="day" type="button">1d</button>
          <button class="range-btn" data-chart="active" data-range="week" type="button">1w</button>
          <button class="range-btn" data-chart="active" data-range="month" type="button">1m</button>
          <button class="range-btn" data-chart="active" data-range="ytd" type="button">YTD</button>
        </div>
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

      <article class="uptime-card" data-service="habitz" data-service-id="service-habitz">
        <div class="uptime-card-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <path d="M3 7a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7Z"></path>
            <path d="M3 9h18"></path>
            <path d="M7 6h.01"></path>
            <path d="M11 6h.01"></path>
            <path d="M15 6h.01"></path>
          </svg>
        </div>
        <div class="uptime-card-body">
          <p class="uptime-category">Site Web</p>
          <div class="uptime-title-row">
            <h4>Habitz</h4>
            <span class="uptime-status-dot" data-status="unknown" role="img" aria-label="Statut du site Habitz"></span>
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
    let userRange = 'week';
    let activeRange = 'day';
    const root = document.documentElement;

    function syncMetricHeights() {
      const stack = document.querySelector('.uptime-stack');
      if (!stack) return;

      const stackHeight = stack.getBoundingClientRect().height;
      if (stackHeight > 0) {
        root.style.setProperty('--uptime-stack-height', `${stackHeight}px`);
        document.querySelectorAll('.metric-card').forEach(card => {
          card.style.maxHeight = `${stackHeight}px`;
        });
      }
    }

    const uptimeStack = document.querySelector('.uptime-stack');
    if (uptimeStack && typeof ResizeObserver !== 'undefined') {
      const observer = new ResizeObserver(() => syncMetricHeights());
      observer.observe(uptimeStack);
    }

    window.addEventListener('resize', () => {
      requestAnimationFrame(syncMetricHeights);
    });

    syncMetricHeights();

    function renderLineChart(container, points) {
      const overlay = container.querySelector('.metric-overlay');
      const actions = container.querySelector('.metric-actions');
      if (overlay) overlay.remove();
      if (actions) actions.remove();

      container.innerHTML = '';
      container.classList.remove('bars');
      container.classList.add('line');

      if (actions) container.appendChild(actions);
      if (overlay) container.appendChild(overlay);

      const tooltip = document.createElement('div');
      tooltip.className = 'chart-tooltip';
      tooltip.textContent = '';
      container.appendChild(tooltip);

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

      const gradientId = `line-grad-${Math.random().toString(36).slice(2)}`;

      const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
      const gradient = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
      gradient.setAttribute('id', gradientId);
      gradient.setAttribute('x1', '0%');
      gradient.setAttribute('y1', '0%');
      gradient.setAttribute('x2', '0%');
      gradient.setAttribute('y2', '100%');

      const stopTop = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
      stopTop.setAttribute('offset', '0%');
      stopTop.setAttribute('stop-color', '#5c7dff');
      stopTop.setAttribute('stop-opacity', '0.38');

      const stopBottom = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
      stopBottom.setAttribute('offset', '100%');
      stopBottom.setAttribute('stop-color', '#5c7dff');
      stopBottom.setAttribute('stop-opacity', '0');

      gradient.appendChild(stopTop);
      gradient.appendChild(stopBottom);
      defs.appendChild(gradient);
      svg.appendChild(defs);

      const pathPoints = points.map((point, index) => {
        const x = padding + step * index;
        const y = height - padding - (point.value / maxValue) * (height - padding * 2);
        return { x, y, label: point.label, value: point.value };
      });

      const baselineY = height - padding;

      const area = document.createElementNS('http://www.w3.org/2000/svg', 'path');
      const areaPath = [
        `M${pathPoints[0].x},${baselineY}`,
        `L${pathPoints[0].x},${pathPoints[0].y}`,
        ...pathPoints.slice(1).map(p => `L${p.x},${p.y}`),
        `L${pathPoints[pathPoints.length - 1].x},${baselineY}`,
        'Z'
      ].join(' ');
      area.setAttribute('d', areaPath);
      area.setAttribute('class', 'line-area');
      area.setAttribute('fill', `url(#${gradientId})`);
      svg.appendChild(area);

      const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
      const d = pathPoints
        .map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x},${p.y}`)
        .join(' ');
      path.setAttribute('d', d);
      path.setAttribute('class', 'line-path');
      svg.appendChild(path);

      const hoverLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
      hoverLine.setAttribute('class', 'hover-line');
      hoverLine.setAttribute('x1', '0');
      hoverLine.setAttribute('x2', '0');
      hoverLine.setAttribute('y1', padding.toString());
      hoverLine.setAttribute('y2', (height - padding).toString());
      hoverLine.style.opacity = '0';
      svg.appendChild(hoverLine);

      container.appendChild(svg);

      function showTooltip(point) {
        tooltip.textContent = `${point.value} utilisateurs`;
        tooltip.style.opacity = '1';
        tooltip.style.left = `${point.x + 8}px`;
        tooltip.style.top = `${point.y + 8}px`;
        hoverLine.setAttribute('x1', point.x.toString());
        hoverLine.setAttribute('x2', point.x.toString());
        hoverLine.style.opacity = '1';
      }

      function hideTooltip() {
        tooltip.style.opacity = '0';
        hoverLine.style.opacity = '0';
      }

      function handleMove(event) {
        const rect = container.getBoundingClientRect();
        const x = event.clientX - rect.left;
        let closest = pathPoints[0];
        let smallest = Math.abs(pathPoints[0].x - x);

        for (let i = 1; i < pathPoints.length; i++) {
          const diff = Math.abs(pathPoints[i].x - x);
          if (diff < smallest) {
            smallest = diff;
            closest = pathPoints[i];
          }
        }

        showTooltip(closest);
      }

      container.onmousemove = handleMove;
      container.onmouseleave = hideTooltip;
    }

    function setRangeButtons(chart, range) {
      document.querySelectorAll(`.range-btn[data-chart="${chart}"]`).forEach(btn => {
        btn.classList.toggle('active', btn.dataset.range === range);
      });
    }

    async function fetchUserMetrics(range = userRange) {
      try {
        const res = await fetch(`/admin_dashboard.php?user_metrics=1&range=${encodeURIComponent(range)}`, { credentials: 'same-origin' });
        const data = await res.json();
        userValueEl.textContent = data.total ?? 0;
        renderLineChart(document.querySelector('[data-chart="users"]'), data.series?.points || []);
        setRangeButtons('users', range);
      } catch (error) {
        console.error('Erreur utilisateurs', error);
      }
    }

    async function fetchActiveMetrics(range = activeRange) {
      try {
        const res = await fetch(`/admin_dashboard.php?active_metrics=1&range=${encodeURIComponent(range)}`, { credentials: 'same-origin' });
        const data = await res.json();
        activeValueEl.textContent = data.active ?? 0;
        renderLineChart(document.querySelector('[data-chart="active"]'), data.series?.points || []);
        setRangeButtons('active', range);
      } catch (error) {
        console.error('Erreur utilisateurs actifs', error);
      }
    }

    document.querySelectorAll('.range-btn[data-chart="users"]').forEach(btn => {
      btn.addEventListener('click', () => {
        userRange = btn.dataset.range || 'week';
        fetchUserMetrics(userRange);
      });
    });

    document.querySelectorAll('.range-btn[data-chart="active"]').forEach(btn => {
      btn.addEventListener('click', () => {
        activeRange = btn.dataset.range || 'day';
        fetchActiveMetrics(activeRange);
      });
    });

    fetchUserMetrics(userRange);
    fetchActiveMetrics(activeRange);
    setInterval(() => fetchActiveMetrics(activeRange), 5 * 60 * 1000);

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
