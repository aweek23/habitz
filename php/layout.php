<?php
$pageTitle = $pageTitle ?? 'Life Tracker';
$content = $content ?? '';
$defaultMenuItems = [
    ['label' => 'Tâches', 'href' => '#'],
    ['label' => 'Habitudes', 'href' => '#'],
    ['label' => 'Projets', 'href' => '#'],
    ['label' => 'Sport', 'href' => '#'],
    ['label' => 'Alimentation', 'href' => '#'],
    ['label' => 'Calendrier', 'href' => '#'],
    ['label' => 'Corps', 'href' => '#'],
    ['label' => 'Finances', 'href' => '#'],
    ['label' => 'Horloge', 'href' => '#'],
];
$menuItems = $menuItems ?? $defaultMenuItems;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$homeUrl = defined('APP_HOME') ? APP_HOME : '/index.php';
$isAuthenticated = !empty($_SESSION['user_id']);

require_once __DIR__ . '/active_tracking.php';

$navIconLibrary = [
    'home' => '<path d="M4 11 12 4l8 7v8a1 1 0 0 1-1 1h-4v-5H9v5H5a1 1 0 0 1-1-1z" />',
    'tasks' => '<path d="M6 8h12" /><path d="M6 12h12" /><path d="M6 16h12" /><path d="m9 16 1.5-1.5L12 16l1.5-1.5L15 16" />',
    'layers' => '<rect x="5" y="5" width="14" height="14" rx="3" /><path d="M9 9h6v6H9z" />',
    'activity' => '<path d="M4 12h3l2 6 4-12 2 6h3" />',
    'nutrition' => '<path d="M9 5c-2.5 2.5-2.5 6.5 0 9l3 3c2.5-2.5 2.5-6.5 0-9l-3-3Z" /><path d="M9 5v14" />',
    'calendar' => '<rect x="4" y="6" width="16" height="14" rx="2" /><path d="M16 2v4" /><path d="M8 2v4" /><path d="M4 10h16" />',
    'body' => '<circle cx="12" cy="7" r="3" /><path d="M5 21c1-3.5 3.5-6 7-6s6 2.5 7 6" />',
    'finance' => '<path d="M5 8h14" /><path d="M7 12h10" /><path d="M10 16h4" /><rect x="4" y="5" width="16" height="14" rx="2" />',
    'clock' => '<circle cx="12" cy="12" r="7" /><path d="M12 9v4l3 2" />',
    'event' => '<path d="M6 10h12" /><path d="M8 14h8" /><rect x="4" y="5" width="16" height="14" rx="3" />',
    'news' => '<path d="M7 5h10v14H7z" /><path d="M7 9h10" /><path d="M10 13h4" /><path d="M4 7v10a2 2 0 0 0 2 2h10" />',
    'drive' => '<path d="M9 4h6l5 9-3 6H7L4 13Z" /><path d="m4 13 5-9" /><path d="m20 13-5-9" />',
];
$navIconOrder = array_keys($navIconLibrary);
$hasActiveMenuItem = false;
foreach ($menuItems as $item) {
    if (!empty($item['active'])) {
        $hasActiveMenuItem = true;
        break;
    }
}

$pdoForRole = null;
try {
    $pdoForRole = require __DIR__ . '/../config.php';
} catch (Throwable $e) {
    // Si la connexion échoue, on désactive les options admin par sécurité.
    $_SESSION['rank'] = 'user';
}

if ($pdoForRole instanceof PDO && $isAuthenticated) {
    try {
        $rankStmt = $pdoForRole->prepare('SELECT rank FROM users WHERE id = :id LIMIT 1');
        $rankStmt->execute([':id' => $_SESSION['user_id']]);
        $dbRank = $rankStmt->fetchColumn();
        $_SESSION['rank'] = $dbRank !== false ? $dbRank : 'user';

        markUserActive($pdoForRole, (int) $_SESSION['user_id']);
    } catch (Throwable $e) {
        $_SESSION['rank'] = 'user';
    }
}

$isAdminUser = ($_SESSION['rank'] ?? 'user') === 'admin';
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$isAdminPage = $currentScript === 'admin_dashboard.php';
$adminLinkLabel = $isAdminPage ? 'User dashboard' : 'Admin dashboard';
$adminLinkHref = $isAdminPage ? $homeUrl : '/admin_dashboard.php';
$displayUsername = $_SESSION['username'] ?? 'Invité';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/layout.css">
</head>
<body>
  <div class="page">
    <div class="app">
      <main class="mid-column">
        <div class="mid-header">
          <div class="topbar" role="navigation" aria-label="Navigation principale">
            <div class="topbar-left">
              <div class="brand-pill" aria-hidden="true">
                <span class="brand-initials">LT</span>
              </div>
              <nav class="topbar-menu">
                <a href="#" class="topbar-link active">Dashboard</a>
                <a href="#" class="topbar-link">Orders</a>
                <a href="#" class="topbar-link">Products</a>
                <a href="#" class="topbar-link">Customers</a>
                <div class="topbar-dropdown">
                  <button class="topbar-link" type="button" aria-haspopup="true" aria-expanded="false">
                    Settings
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                      <path d="m6 9 6 6 6-6" />
                    </svg>
                  </button>
                  <div class="dropdown-panel" role="menu">
                    <a href="#" role="menuitem">General</a>
                    <a href="#" role="menuitem">Store</a>
                    <a href="#" role="menuitem">Privacy</a>
                    <a href="#" role="menuitem">API</a>
                  </div>
                </div>
              </nav>
            </div>
            <div class="topbar-right">
              <button class="pill icon-only" aria-label="Rechercher">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <circle cx="11" cy="11" r="6" />
                  <path d="m15.5 15.5 3.5 3.5" />
                </svg>
              </button>
              <a class="pill upgrade" href="#">Upgrade</a>
              <div class="topbar-avatar" aria-hidden="true"></div>
            </div>
          </div>

          <div class="profile-actions">
            <?php if ($isAdminUser): ?>
              <a class="pill" href="<?= htmlspecialchars($adminLinkHref, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($adminLinkLabel, ENT_QUOTES, 'UTF-8') ?>
              </a>
            <?php endif; ?>
            <button class="pill icon-only" aria-label="Messages">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 5h16a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H8l-4 3V6a1 1 0 0 1 1-1Z" />
                <path d="M7 9h10" />
                <path d="M7 13h6" />
              </svg>
            </button>
            <button class="pill icon-only" aria-label="Notifications">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 8-3 8h18s-3-1-3-8Z" />
                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
              </svg>
            </button>
          </div>
        </div>

        <div class="main">
          <?= $content ?>
        </div>
      </main>

      <aside class="right-column">
        <?php if (!empty($rightExtras)): ?>
          <?= $rightExtras ?>
        <?php endif; ?>

        <div class="right-module search-bar-module">
          <div class="search-bar" role="search">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="11" cy="11" r="6" />
              <path d="m15.5 15.5 3.5 3.5" />
            </svg>
            <input type="search" placeholder="Rechercher…" aria-label="Rechercher" />
          </div>
        </div>

        <div class="right-module suggestion-module">
          <div class="tabs" role="tablist" aria-label="Suggestions">
            <button class="tab active" role="tab" aria-selected="true">For you</button>
            <button class="tab" role="tab" aria-selected="false">Followings</button>
            <button class="tab" role="tab" aria-selected="false">Discover</button>
          </div>

          <div class="quick-card">
            <h4>Raccourcis rapides</h4>
            <p>Ajoute ici tes actions fréquentes, notifications ou rappels importants.</p>
          </div>

          <button class="floating-action" aria-label="Ajouter un raccourci">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 5v14" />
              <path d="M5 12h14" />
            </svg>
          </button>
        </div>

        <div class="right-module profile-module">
          <div class="bottom-profile">
            <div class="avatar"></div>
            <div class="profile-name"><?= htmlspecialchars($displayUsername, ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ($isAuthenticated): ?>
              <div class="profile-actions-inline">
                <a class="pill icon-only" href="/user.php" aria-label="Paramètres">
                  <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="3" />
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1 1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c0 .69.4 1.3 1 1.58.19.09.4.14.61.14H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z" />
                  </svg>
                </a>
                <form class="logout-form" action="/logout.php" method="post">
                  <button type="submit" class="pill icon-only" aria-label="Se déconnecter">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                      <path d="M16 17l5-5-5-5" />
                      <path d="M21 12H9" />
                      <path d="M12 19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5a2 2 0 0 1 2 2" />
                    </svg>
                  </button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </aside>
    </div>

    <nav class="mobile-nav" aria-label="Navigation principale">
      <div class="mobile-nav-surface">
        <button class="active" type="button" aria-label="Dashboard">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="m4 11 8-7 8 7v8a1 1 0 0 1-1 1h-4v-5H9v5H5a1 1 0 0 1-1-1v-8Z" />
          </svg>
          <span class="sr-only">Dashboard</span>
        </button>
        <button type="button" aria-label="Social" onclick="window.location.href='social.php'">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M7 7a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
            <path d="M17 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
            <path d="M7 17a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
            <path d="m9.6 7.5 4.8 2.3" />
            <path d="m9.6 13.1 4.7-2.2" />
          </svg>
          <span class="sr-only">Social</span>
        </button>
        <button class="primary" type="button" aria-label="Ajouter">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 5v14" />
            <path d="M5 12h14" />
          </svg>
          <span class="sr-only">Ajouter</span>
        </button>
        <button type="button" aria-label="Modules">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="3" width="7" height="7" rx="2" />
            <rect x="14" y="3" width="7" height="7" rx="2" />
            <rect x="3" y="14" width="7" height="7" rx="2" />
            <rect x="14" y="14" width="7" height="7" rx="2" />
          </svg>
          <span class="sr-only">Modules</span>
        </button>
        <button type="button" aria-label="Profil">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" />
            <path d="M4 20a8 8 0 0 1 16 0" />
          </svg>
          <span class="sr-only">Profil</span>
        </button>
      </div>
    </nav>
    <?php if ($isAdminUser): ?>
      <a class="mobile-admin-fab pill" href="<?= htmlspecialchars($adminLinkHref, ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($adminLinkLabel, ENT_QUOTES, 'UTF-8') ?>
      </a>
    <?php endif; ?>
  </div>
</body>
</html>
