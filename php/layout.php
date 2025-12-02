<?php
$pageTitle = $pageTitle ?? 'Life Tracker';
$content = $content ?? '';
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
    <header class="topbar">
      <div class="logo-area">
        <div class="logo">LT</div>
        <span class="brand">Life Tracker</span>
      </div>
      <div class="top-actions">
        <button class="icon-btn" aria-label="Paramètres">
          <span class="dot"></span>
          <span class="dot"></span>
          <span class="dot"></span>
        </button>
        <button class="pill">Cubes</button>
        <button class="pill">Import</button>
        <button class="pill">Export</button>
        <div class="time-display">14:21:11</div>
      </div>
      <div class="profile-actions">
        <button class="icon-btn" aria-label="Profil">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20c1.7-3 4.1-4.5 8-4.5s6.3 1.5 8 4.5"/></svg>
        </button>
        <button class="icon-btn" aria-label="Notifications">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a6 6 0 0 0-6 6v3.5l-1.5 2.5h17L18 12.5V9a6 6 0 0 0-6-6Z"/><path d="M10 20a2 2 0 0 0 4 0"/></svg>
        </button>
      </div>
    </header>

    <div class="app">
      <aside class="sidebar">
        <nav class="menu">
          <div class="sidebar-header">
            <div class="menu-title">Life Tracker</div>
            <button class="icon-btn ghost" aria-label="Ouvrir">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <rect x="4" y="4" width="16" height="16" rx="4" ry="4" />
                <path d="M9 15h6V9" />
                <path d="M9 9h6v6" />
              </svg>
            </button>
          </div>

          <a class="menu-item" href="#">Tâches</a>
          <a class="menu-item" href="#">Habitudes</a>
          <a class="menu-item" href="#">Projets</a>
          <a class="menu-item with-caret" href="#">Sport</a>
          <a class="menu-item" href="#">Alimentation</a>
          <a class="menu-item" href="#">Calendrier</a>
          <a class="menu-item with-caret" href="#">Corps</a>
          <a class="menu-item with-caret" href="#">Finances</a>
          <a class="menu-item" href="#">Horloge</a>
          <a class="menu-item" href="#">Évènements</a>
          <a class="menu-item" href="#">Actualités, news, etc</a>
          <a class="menu-item" href="#">Drive</a>
        </nav>
      </aside>

      <main class="mid-column">
        <div class="main">
          <?= $content ?>
        </div>
      </main>

      <aside class="right-column">
        <div class="pane">
          <div class="pane-head">
            <h3>For you</h3>
            <div class="filters">
              <button class="filter active">You</button>
              <button class="filter">Following</button>
              <button class="filter">Discover</button>
            </div>
          </div>
          <div class="profile-card">
            <div class="avatar"></div>
            <div class="profile-infos">
              <div class="profile-name">Hi, @ktui you awake?</div>
              <div class="profile-meta">You • Habitz (Platform Team)</div>
              <div class="profile-date">Oct 2024, Today 8:24 AM</div>
            </div>
            <button class="more-btn" aria-label="Plus">
              <span class="dot"></span><span class="dot"></span><span class="dot"></span>
            </button>
          </div>
          <p class="hint">Ajustez vos actions importantes, notifications et réponses importantes.</p>
        </div>
      </aside>
    </div>
  </div>
</body>
</html>
