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
    ['label' => 'Évènements', 'href' => '#'],
    ['label' => 'Actualités, news, etc', 'href' => '#'],
    ['label' => 'Drive', 'href' => '#'],
];
$menuItems = $menuItems ?? $defaultMenuItems;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$homeUrl = defined('APP_HOME') ? APP_HOME : '/index.php';
$isAdminUser = ($_SESSION['rank'] ?? '') === 'admin';
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$isAdminPage = $currentScript === 'admin_dashboard.php';
$adminLinkLabel = $isAdminPage ? 'User dashboard' : 'Admin dashboard';
$adminLinkHref = $isAdminPage ? $homeUrl : '/admin_dashboard.php';
$displayUsername = $_SESSION['username'] ?? 'Invité';
$isAuthenticated = !empty($_SESSION['user_id']);
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
      <aside class="sidebar">
        <nav class="menu">
          <div class="sidebar-header">
            <div class="menu-title">Life Tracker</div>
          </div>

          <?php foreach ($menuItems as $menuItem): ?>
            <?php
              $label = htmlspecialchars($menuItem['label'] ?? '', ENT_QUOTES, 'UTF-8');
              $href = htmlspecialchars($menuItem['href'] ?? '#', ENT_QUOTES, 'UTF-8');
            ?>
            <a class="menu-item" href="<?= $href ?>"><?= $label ?></a>
          <?php endforeach; ?>
        </nav>
      </aside>

      <main class="mid-column">
        <div class="mid-header">
          <div class="profile-actions">
            <?php if ($isAdminUser): ?>
              <a class="pill" href="<?= htmlspecialchars($adminLinkHref, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($adminLinkLabel, ENT_QUOTES, 'UTF-8') ?>
              </a>
            <?php endif; ?>
            <button class="icon-btn ghost" aria-label="Lien">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M10 14a3 3 0 0 1 0-4l3.5-3.5a3 3 0 0 1 4.2 4.2l-.7.7" />
                <path d="M14 10a3 3 0 0 1 0 4l-3.5 3.5a3 3 0 0 1-4.2-4.2l.7-.7" />
              </svg>
            </button>
            <button class="icon-btn ghost" aria-label="Réglages">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="3" />
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1 1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c0 .69.4 1.3 1 1.58.19.09.4.14.61.14H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z" />
              </svg>
            </button>
          </div>
        </div>

        <div class="main">
          <?= $content ?>
        </div>
      </main>

      <aside class="right-column">
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
              <form class="logout-form" action="/logout.php" method="post">
                <button type="submit" class="icon-btn ghost" aria-label="Se déconnecter">
                  <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M16 17l5-5-5-5" />
                    <path d="M21 12H9" />
                    <path d="M12 19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5a2 2 0 0 1 2 2" />
                  </svg>
                </button>
              </form>
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
  </div>
</body>
</html>
