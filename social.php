<?php
require_once __DIR__ . '/php/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: php/auth.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Life Tracker — Social</title>
  <link rel="stylesheet" href="css/shell.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="css/index.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="css/social.css?v=<?php echo time(); ?>">
  <script>
  (function(){
    function goAuth(){ window.location.replace('php/auth.php'); }
    fetch('php/session_check.php?ts='+Date.now(),{credentials:'same-origin',cache:'no-store'})
      .then(r=>r.ok?r.json():{logged_in:false})
      .then(data=>{ if(!data || !data.logged_in) goAuth(); })
      .catch(()=>goAuth());
  })();
  </script>
</head>
<body class="social-page">
  <div class="social-layout">
    <header class="social-header">
      <a class="brand brand-link" href="index.php">Life Tracker</a>
    </header>

    <section class="top-panel social-panel">
      <div class="top-panel-tabs">
        <button type="button" class="top-tab active">For you</button>
        <button type="button" class="top-tab">Followings</button>
        <button type="button" class="top-tab">Discover</button>
      </div>

      <h2>Raccourcis rapides</h2>
      <p>Ajoute ici tes actions fréquentes, notifications ou rappels importants.</p>

      <button id="socialComposeBtn" class="top-panel-compose" type="button" title="Nouvelle action">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
          <path d="M5 19h14" stroke-width="1.8" stroke-linecap="round"></path>
          <path d="M8 15l9-9 3 3-9 9-4 1 1-4z" stroke-width="1.6" stroke-linejoin="round"></path>
        </svg>
      </button>
    </section>
  </div>

  <nav class="mobile-bottom-nav" aria-label="Navigation mobile">
    <button type="button" class="mobile-nav-btn" aria-label="Tableau de bord" data-nav-link="index.php">
      <span class="mobile-nav-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
          <path d="M3 10l9-7 9 7v9.5a1.5 1.5 0 0 1-1.5 1.5H4.5A1.5 1.5 0 0 1 3 19.5V10z" stroke-width="1.6" stroke-linejoin="round" />
          <path d="M9 21V12h6v9" stroke-width="1.6" stroke-linejoin="round" />
        </svg>
      </span>
      <span class="mobile-nav-label">Dashboard</span>
    </button>
    <button type="button" class="mobile-nav-btn is-active" aria-label="Social" data-nav-link="social.php">
      <span class="mobile-nav-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
          <path d="M7.5 10.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM19 12.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" stroke-width="1.6" />
          <path d="M3 21v-1a4 4 0 0 1 4-4h1" stroke-width="1.6" stroke-linecap="round" />
          <path d="M15 16h1a5 5 0 0 1 5 5" stroke-width="1.6" stroke-linecap="round" />
        </svg>
      </span>
      <span class="mobile-nav-label">Social</span>
    </button>
    <button type="button" class="mobile-nav-btn mobile-nav-add" aria-label="Ajouter">
      <span class="mobile-nav-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
          <path d="M12 5v14M5 12h14" stroke-width="1.8" stroke-linecap="round" />
        </svg>
      </span>
      <span class="mobile-nav-label">Ajouter</span>
    </button>
    <button type="button" class="mobile-nav-btn" aria-label="Modules">
      <span class="mobile-nav-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
          <rect x="4" y="4" width="6" height="6" rx="1.5" stroke-width="1.6" />
          <rect x="14" y="4" width="6" height="6" rx="1.5" stroke-width="1.6" />
          <rect x="4" y="14" width="6" height="6" rx="1.5" stroke-width="1.6" />
          <rect x="14" y="14" width="6" height="6" rx="1.5" stroke-width="1.6" />
        </svg>
      </span>
      <span class="mobile-nav-label">Modules</span>
    </button>
    <button type="button" class="mobile-nav-btn" aria-label="Profil">
      <span class="mobile-nav-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
          <circle cx="12" cy="8" r="3.5" stroke-width="1.6" />
          <path d="M5 20.5c0-3.5 3.134-6 7-6s7 2.5 7 6" stroke-width="1.6" stroke-linecap="round" />
        </svg>
      </span>
      <span class="mobile-nav-label">Profile</span>
    </button>
  </nav>

  <script>
  const $$ = (s)=>Array.from(document.querySelectorAll(s));
  function setupMobileNavLinks(){
    const navButtons = $$('.mobile-bottom-nav .mobile-nav-btn[data-nav-link]');
    navButtons.forEach(btn => {
      const target = btn.dataset.navLink;
      if (!target) return;
      btn.addEventListener('click', ()=>{ window.location.href = target; });
    });
  }
  window.addEventListener('load', setupMobileNavLinks);
  </script>
</body>
</html>
