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
    <div class="app">
      <aside class="sidebar">
        <nav class="menu">
          <div class="sidebar-header">
            <div class="menu-title">Life Tracker</div>
          </div>

          <a class="menu-item" href="#">Tâches</a>
          <a class="menu-item" href="#">Habitudes</a>
          <a class="menu-item" href="#">Projets</a>
          <a class="menu-item" href="#">Sport</a>
          <a class="menu-item" href="#">Alimentation</a>
          <a class="menu-item" href="#">Calendrier</a>
          <a class="menu-item" href="#">Corps</a>
          <a class="menu-item" href="#">Finances</a>
          <a class="menu-item" href="#">Horloge</a>
          <a class="menu-item" href="#">Évènements</a>
          <a class="menu-item" href="#">Actualités, news, etc</a>
          <a class="menu-item" href="#">Drive</a>
        </nav>
      </aside>

      <main class="mid-column">
        <div class="mid-header">
          <div class="profile-actions">
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
        <div class="right-module search-module">
          <div class="search-section">
            <div class="search-bar" role="search">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="11" cy="11" r="6" />
                <path d="m15.5 15.5 3.5 3.5" />
              </svg>
              <input type="search" placeholder="Rechercher…" aria-label="Rechercher" />
            </div>
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
            <div class="profile-name">admin</div>
          </div>
        </div>
      </aside>
    </div>
  </div>
</body>
</html>
