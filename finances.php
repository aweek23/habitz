<?php
session_start();

// Connexion PDO via ton config.php dans /php/config.php
require_once __DIR__ . '/php/config.php';

// On suppose que l'id utilisateur est dans $_SESSION['user_id']
if (!isset($_SESSION['user_id'])) {
    header('Location: php/auth.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

$currentUsername = 'Utilisateur';
try {
    if (isset($pdo)) {
        $stmt = $pdo->prepare('SELECT username FROM habitz WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();
        if ($row && isset($row['username'])) {
            $currentUsername = $row['username'];
        }
    }
} catch (Exception $e) {
    // En prod : log éventuel
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Life Tracker — Tableau de bord</title>
  <link rel="stylesheet" href="css/finances.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="css/shell.css?v=<?php echo time(); ?>">
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
<body class="dashboard-page">

<!-- Colonne droite : barre de recherche + cartes -->
<div id="topStack" class="top-stack sheet-mode">
  <div id="topSearch" class="top-search" role="search">
    <svg class="search-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
      <circle cx="11" cy="11" r="7" stroke-width="1.6"></circle>
      <line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="1.6" stroke-linecap="round"></line>
    </svg>
    <input id="globalSearch" type="search" placeholder="Rechercher…" autocomplete="off" />
  </div>

  <!-- Carte 1 : Raccourcis rapides -->
  <div id="topPanel" class="top-panel">
    <div class="top-panel-tabs">
      <button type="button" class="top-tab active">For you</button>
      <button type="button" class="top-tab">Followings</button>
      <button type="button" class="top-tab">Discover</button>
    </div>

    <h2>Raccourcis rapides</h2>
    <p>Ajoute ici tes actions fréquentes, notifications ou rappels importants.</p>

    <button id="quickComposeBtn" class="top-panel-compose" type="button" title="Nouvelle action">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
        <path d="M5 19h14" stroke-width="1.8" stroke-linecap="round"></path>
        <path d="M8 15l9-9 3 3-9 9-4 1 1-4z" stroke-width="1.6" stroke-linejoin="round"></path>
      </svg>
    </button>
  </div>

  <!-- Carte 2 : Profil utilisateur -->
  <div id="topUserPanel" class="top-panel top-panel-user">
    <div class="top-user-row">
      <!-- bloc gauche : avatar + pseudo -->
      <div class="top-user-main">
        <div class="top-user-avatar" role="img" aria-label="Photo de profil"></div>

        <span class="top-user-name trim-ellipsis">
          <?php echo htmlspecialchars($currentUsername, ENT_QUOTES, 'UTF-8'); ?>
        </span>
      </div>

      <!-- bloc droite : boutons sur la même ligne -->
      <div class="top-user-actions">
        <button id="topSettingsBtn" class="icon-mini" type="button" title="Paramètres">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"></path>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82 .33l-.06 .06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h-.09c-.3 0-.6 .06-.87 .18-.61 .24-1 .84-1 1.51z" stroke-width="1.4"></path>
          </svg>
        </button>

        <button id="topLogoutBtn" class="icon-mini" type="button" title="Se déconnecter">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            <path d="M10 17h-1.5A2.5 2.5 0 0 1 6 14.5v-5A2.5 2.5 0 0 1 8.5 7H10" stroke-width="1.6" stroke-linecap="round"></path>
            <path d="M14 7l5 5-5 5" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
            <path d="M19 12H9" stroke-width="1.6" stroke-linecap="round"></path>
          </svg>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Bouton rond pour ouvrir/fermer la colonne droite (iPad + desktop non plein écran) -->
<button id="topStackFab" class="topstack-fab" title="Panneau rapide">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
    <circle cx="12" cy="12" r="4" stroke-width="1.8"></circle>
    <path d="M4 12h4M16 12h4M12 4v4M12 16v4" stroke-width="1.8" stroke-linecap="round"></path>
  </svg>
</button>
<div id="topStackOverlay" class="topstack-overlay"></div>

<!-- Boutons flottants Messages + Notifications -->
<div id="alertsFab" class="alerts-fab">
  <button id="msgFab"   class="icon-mini alerts-fab-btn" type="button" title="Messages">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
      <path d="M4 6a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3H9l-4 4v-4H7a3 3 0 0 1-3-3V6z" stroke-width="1.6" stroke-linejoin="round"></path>
    </svg>
  </button>
  <button id="notifFab" class="icon-mini alerts-fab-btn" type="button" title="Notifications">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
      <path d="M18 10a6 6 0 0 0-12 0c0 4-2 6-2 6h16s-2-2-2-6z" stroke-width="1.6" stroke-linejoin="round"></path>
      <path d="M10 20a2 2 0 0 0 4 0" stroke-width="1.6" stroke-linecap="round"></path>
    </svg>
  </button>
</div>

<!-- Bouton hamburger (tablette) -->
<button id="toggleNavBtn" class="tablet-toggle" title="Menu">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
    <path d="M3 6h18M3 12h18M3 18h18" stroke-width="1.8" stroke-linecap="round"/>
  </svg>
</button>

<!-- Horloge HH:MM:SS + contrôles période/échelle -->
<div id="topClockBar" class="top-clock-bar">
  <div id="topClock" class="top-clock">--:--:--</div>
  <div class="top-clock-actions">
    <div class="period-cycle" aria-label="Changer de période">
      <button class="cycle-btn" type="button" aria-label="Période précédente">&#x276E;</button>
      <span class="label">Septembre 2025</span>
      <button class="cycle-btn" type="button" aria-label="Période suivante">&#x276F;</button>
    </div>
    <div id="scaleDropdown" class="scale-dropdown">
      <button id="scaleToggle" class="period-scale-btn" type="button" aria-haspopup="true" aria-expanded="false">
        Mensuel
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M7 10l5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </button>
      <div id="scaleMenu" class="scale-menu" role="menu" aria-label="Choisir l'échelle de temps">
        <button type="button" role="menuitem">Annuel</button>
        <button type="button" role="menuitem" class="active">Mensuel</button>
        <button type="button" role="menuitem">Hebdomadaire</button>
      </div>
    </div>
  </div>
</div>

<div id="navOverlay" class="nav-overlay"></div>

  <div class="app">
    <aside class="sidebar" id="sidebar">
      <div class="side-top">
        <div class="brand">Life Tracker</div>
        <button id="reorderBtn" class="icon-mini reorder-btn" type="button" title="Réorganiser les modules">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            <path d="M4 7h16M4 12h10M4 17h6" stroke-width="1.8" stroke-linecap="round"></path>
          </svg>
        </button>
      </div>

      <nav class="nav">
        <ul id="menuTop" class="menu">
  <!-- Tâches / Habitudes / Projets -->
  <li class="menu-item" data-key="tasks">
    <button class="item" type="button">Tâches</button>
  </li>

  <li class="menu-item" data-key="habits">
    <button class="item" type="button">Habitudes</button>
  </li>

  <li class="menu-item" data-key="projects">
    <button class="item" type="button">Projets</button>
  </li>

  <!-- Sport -->
  <li class="menu-item has-sub" data-key="sport">
    <button class="item has-sub-btn" type="button">
      <span>Sport</span>
      <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path d="M8 10l4 4 4-4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>
    <ul class="submenu">
      <li><button class="subitem" type="button">Entraînements</button></li>
      <li><button class="subitem" type="button">Pas</button></li>
    </ul>
  </li>

  <!-- Alimentation -->
  <li class="menu-item" data-key="food">
    <button class="item" type="button">Alimentation</button>
  </li>

  <!-- Calendrier -->
  <li class="menu-item" data-key="calendar">
    <button class="item" type="button">Calendrier</button>
  </li>

  <!-- Corps -->
  <li class="menu-item has-sub" data-key="body">
    <button class="item has-sub-btn" type="button">
      <span>Corps</span>
      <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path d="M8 10l4 4 4-4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>
    <ul class="submenu">
      <li><button class="subitem" type="button">Sommeil</button></li>
      <li><button class="subitem" type="button">Poids</button></li>
      <li><button class="subitem" type="button">Glycémie</button></li>
      <li><button class="subitem" type="button">Pression artérielle</button></li>
      <li><button class="subitem" type="button">Cycle menstruel</button></li>
      <li><button class="subitem" type="button">Composition corporelle</button></li>
    </ul>
  </li>

  <!-- Finances -->
  <li class="menu-item has-sub" data-key="finances">
    <button class="item has-sub-btn" type="button">
      <span>Finances</span>
      <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path d="M8 10l4 4 4-4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>
    <ul class="submenu">
      <li><button class="subitem" type="button">Budget</button></li>
      <li><button class="subitem" type="button">Patrimoine</button></li>
      <li><button class="subitem" type="button">Comptes</button></li>
    </ul>
  </li>

  <!-- Horloge -->
  <li class="menu-item" data-key="clock">
    <button class="item" type="button">Horloge</button>
  </li>

  <!-- Evènements -->
  <li class="menu-item" data-key="events">
    <button class="item" type="button">Evènements</button>
  </li>

  <!-- Actualités -->
  <li class="menu-item" data-key="news">
    <button class="item" type="button">Actualités, news, etc</button>
  </li>

  <!-- Drive -->
  <li class="menu-item" data-key="drive">
    <button class="item" type="button">Drive</button>
  </li>
</ul>
    </nav>
  </aside>

  <main class="main dashboard">
    <section class="dashboard-grid budget-grid">
      <div class="budget-module">
        <div class="budget-body">
          <div class="budget-stats">
            <article class="budget-stat-card">
              <p class="stat-label">Revenus prévus</p>
              <p class="stat-value">7 200 €</p>
              <p class="stat-detail">+ 300 € vs dernier mois</p>
            </article>
            <article class="budget-stat-card">
              <p class="stat-label">Dépenses prévues</p>
              <p class="stat-value">4 950 €</p>
              <p class="stat-detail">63 % du budget</p>
            </article>
            <article class="budget-stat-card">
              <p class="stat-label">Épargne projetée</p>
              <p class="stat-value">1 150 €</p>
              <p class="stat-detail">Taux 16 %</p>
            </article>
            <article class="budget-stat-card">
              <p class="stat-label">Reste à budgéter</p>
              <p class="stat-value">1 100 €</p>
              <p class="stat-detail">Inclut 350 € de marge</p>
            </article>
          </div>
        </div>
      </div>

      <div class="dashboard-module">
        <div class="dashboard-icon" aria-hidden="true">📊</div>
        <div class="dashboard-body">
          <h3 class="module-title">Tableau de bord</h3>
          <p class="module-desc">Visualise l’évolution de tes comptes et de tes catégories clés.</p>
          <div class="module-meta">
            <span class="module-pill">
              <span class="module-pill-dot" aria-hidden="true"></span>
              Vue globale
            </span>
          </div>
        </div>
      </div>

      <div class="cards-module">
        <div class="cards-icon" aria-hidden="true">💳</div>
        <div class="cards-body">
          <h3 class="module-title">Cartes & abonnements</h3>
          <p class="module-desc">Gère les paiements récurrents et les prochaines échéances.</p>
          <div class="module-meta">
            <span class="module-pill">
              <span class="module-pill-dot" aria-hidden="true"></span>
              12 abonnements
            </span>
          </div>
        </div>
      </div>

      <div class="income-module">
        <div class="income-icon" aria-hidden="true">💼</div>
        <div class="income-body">
          <h3 class="module-title">Revenus & salaires</h3>
          <p class="module-desc">Planifie les prochaines entrées d’argent et leurs répartitions.</p>
          <div class="module-meta">
            <span class="module-pill">
              <span class="module-pill-dot" aria-hidden="true"></span>
              Prévisions
            </span>
          </div>
        </div>
      </div>

      <div class="accounts-module">
        <div class="accounts-icon" aria-hidden="true">🏦</div>
        <div class="accounts-body">
          <h3 class="module-title">Comptes & banques</h3>
          <p class="module-desc">Synchronise ou saisis tes comptes courants, épargne et placements.</p>
          <div class="module-meta">
            <span class="module-pill">
              <span class="module-pill-dot" aria-hidden="true"></span>
              5 comptes
            </span>
          </div>
        </div>
      </div>

      <div class="goals-module">
        <div class="goals-icon" aria-hidden="true">🎯</div>
        <div class="goals-body">
          <h3 class="module-title">Objectifs</h3>
          <p class="module-desc">Fixe des objectifs d’épargne ou de remboursement et suis-les.</p>
          <div class="module-meta">
            <span class="module-pill">
              <span class="module-pill-dot" aria-hidden="true"></span>
              En cours
            </span>
          </div>
        </div>
      </div>
    </section>
  </main>
</div>

<script>
const $  = (s)=>document.querySelector(s);
const $$ = (s)=>Array.from(document.querySelectorAll(s));

const sidebar        = $('#sidebar');
const menuTop        = $('#menuTop');
const toggleNavBtn   = $('#toggleNavBtn');
const navOverlay     = $('#navOverlay');
const topStack       = $('#topStack');
const topStackFab    = $('#topStackFab');
const topStackOverlay= $('#topStackOverlay');
const alertsFab      = $('#alertsFab');
const clockBar       = $('#topClockBar');
const clockEl        = $('#topClock');
const reorderBtn     = $('#reorderBtn');
const scaleDropdown  = $('#scaleDropdown');
const scaleToggle    = $('#scaleToggle');
const scaleMenu      = $('#scaleMenu');

/* ===== Détection tablette / iPad ===== */
function detectTablet(){
  const ua = navigator.userAgent || navigator.vendor || window.opera || '';
  const low = ua.toLowerCase();
  const isIpad = (/\bipad\b/.test(low)) || (/\bmacintosh\b/.test(low) && 'ontouchend' in document);
  const isAndroidTablet = /\bandroid\b/.test(low) && !/\bmobile\b/.test(low);
  const coarse = window.matchMedia('(hover: none) and (pointer: coarse)').matches;
  const widthOK = (window.innerWidth >= 600 && window.innerWidth <= 1280);
  return isIpad || isAndroidTablet || (coarse && widthOK);
}
let IS_TABLET = detectTablet();

function applyTabletMode(on){
  document.body.classList.toggle('is-tablet', on);
  if(on){
    sidebar.classList.add('collapsed');
    setReorderMode(false);
    if (reorderBtn) reorderBtn.setAttribute('disabled','disabled');
    toggleNavBtn.style.display='grid';
  }else{
    sidebar.classList.remove('collapsed');
    if (reorderBtn) reorderBtn.removeAttribute('disabled');
    enableDrag(sidebar.classList.contains('reorder'));
    toggleNavBtn.style.display='none';
    document.body.classList.remove('nav-open');
  }
  updateTopStackMode();
  updateClockPosition();
}
window.addEventListener('resize', () => {
  const now = detectTablet();
  if (now !== IS_TABLET) { IS_TABLET = now; applyTabletMode(IS_TABLET); }
  else { updateTopStackMode(); updateClockPosition(); }
});

/* ===== Gestion de la colonne droite & overlays ===== */
function isWindowMaximized(){
  // Si le navigateur est vraiment en plein écran (F11, mode fullscreen)
  if (document.fullscreenElement) return true;

  // Sinon, on considère "desktop plein écran" si la fenêtre est grande
  const minWidth  = 1280;
  const minHeight = 700;
  return (window.innerWidth >= minWidth && window.innerHeight >= minHeight);
}

/* Position des boutons messages / notifs */
function updateAlertsFabPosition(){
  if (!alertsFab) return;

  const isMobile = window.innerWidth < 600;
  if (isMobile){
    alertsFab.style.display = 'none';
    return;
  }
  alertsFab.style.display = 'flex';

  alertsFab.classList.remove('with-right-stack');

  const fullDesktop = (!IS_TABLET && isWindowMaximized());

  if (fullDesktop){
    // Desktop plein écran
    let hasRightStack = false;
    if (topStack){
      hasRightStack =
        topStack.classList.contains('desktop-static') ||
        topStack.classList.contains('open');
    }
    if (hasRightStack){
      // À gauche de la colonne de droite
      alertsFab.classList.add('with-right-stack');
    }
  } else {
    // iPad ou desktop NON plein écran
    let sheetOpen = false;
    if (topStack){
      sheetOpen =
        topStack.classList.contains('sheet-mode') &&
        topStack.classList.contains('open');
    }
    if (sheetOpen){
      // Colonne ouverte : à gauche de la colonne
      alertsFab.classList.add('with-right-stack');
    }
    // sinon : top-right par défaut (via CSS)
  }
}

/* Position dynamique de l'horloge */
function updateClockPosition(){
  if(!clockBar) return;

  const isMobile = window.innerWidth < 600;
  if (isMobile){
    return; // cachée par CSS
  }


  // Desktop (non tablette) : à droite de la navbar
  if (!IS_TABLET){
    if (sidebar){
      const rect = sidebar.getBoundingClientRect();
      const gap  = 12;
      const left = rect.right + gap;
      clockBar.style.left = left + 'px';
    }
    return;
  }

  // iPad / tablette : à droite du burger (que la navbar soit ouverte ou non)
  if (toggleNavBtn){
    const rect = toggleNavBtn.getBoundingClientRect();
    const gap  = 12;
    const left = rect.right + gap;
    clockBar.style.left = left + 'px';
  }
}

function setupScaleDropdown(){
  if(!scaleDropdown || !scaleToggle || !scaleMenu) return;

  scaleToggle.addEventListener('click', (e)=>{
    e.stopPropagation();
    const willOpen = !scaleDropdown.classList.contains('open');
    scaleDropdown.classList.toggle('open', willOpen);
    scaleToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
  });

  document.addEventListener('click', (e)=>{
    if (!scaleDropdown.contains(e.target)){
      scaleDropdown.classList.remove('open');
      scaleToggle.setAttribute('aria-expanded','false');
    }
  });
}

function setClockControlsHidden(hide){
  if (!clockBar) return;
  clockBar.classList.toggle('hide-controls', !!hide);
}
function updateClockControlsVisibility(){
  if (!topStack){
    setClockControlsHidden(false);
    return;
  }
  const hideControls = topStack.classList.contains('sheet-mode') && topStack.classList.contains('open');
  setClockControlsHidden(hideControls);
}
function updateTopStackMode(){
  if (!topStack) return;
  const fullDesktop = (!IS_TABLET && isWindowMaximized());

  if (fullDesktop){
    // Mode desktop plein écran : colonne fixe, pas de bouton rond
    topStack.classList.add('desktop-static');
    topStack.classList.remove('sheet-mode','open');
    if (topStackFab) topStackFab.classList.remove('show','hidden');
    if (topStackOverlay) topStackOverlay.classList.remove('show');
    document.body.classList.add('has-desktop-stack');
  } else {
    // iPad + desktop non plein écran : panneau coulissant + bouton rond
    topStack.classList.add('sheet-mode');
    topStack.classList.remove('desktop-static','open');
    if (topStackFab) {
      topStackFab.classList.add('show');
      topStackFab.classList.remove('hidden'); // visible quand panneau fermé
    }
    if (topStackOverlay) topStackOverlay.classList.remove('show');
    document.body.classList.remove('has-desktop-stack');
  }

  updateAlertsFabPosition();
  updateClockPosition();
  updateClockControlsVisibility();
}
document.addEventListener('fullscreenchange', () => {
  updateTopStackMode();
  updateClockPosition();
});

/* Ouverture/fermeture du panneau coulissant */
function closeTopStackSheet(){
  if (!topStack) return;
  topStack.classList.remove('open');
  if (topStackOverlay) topStackOverlay.classList.remove('show');
  if (topStackFab) topStackFab.classList.remove('hidden'); // re-affiche le bouton
  setClockControlsHidden(false);
  updateAlertsFabPosition();
  updateClockPosition();
  updateClockControlsVisibility();
}
if (topStackFab){
  topStackFab.addEventListener('click', ()=>{
    if (!topStack.classList.contains('sheet-mode')) return;
    const willOpen = !topStack.classList.contains('open');
    topStack.classList.toggle('open', willOpen);
    if (topStackOverlay) topStackOverlay.classList.toggle('show', willOpen);
    if (topStackFab) topStackFab.classList.toggle('hidden', willOpen); // caché quand ouvert
    updateAlertsFabPosition();
    updateClockPosition();
    updateClockControlsVisibility();
  });
}
if (topStackOverlay){
  topStackOverlay.addEventListener('click', ()=>{
    // Fermer la colonne + la navbar (si on est en mode tablette)
    closeTopStackSheet();
    if (IS_TABLET) closeSidebar();
  });
}

/* ===== Onglets For you / Followings / Discover ===== */
$$('.top-tab').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    $$('.top-tab').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
  });
});

/* ===== Prefs modules (chargement / œil / drag desktop) ===== */
let serverModules = {};
function liOf(key){ return menuTop.querySelector(`.menu-item[data-key="${key}"]`); }
function applyPrefs() {
  Object.entries(serverModules).forEach(([k,v])=>{
    const li = liOf(k);
    const isOn = (v.visible === 'Yes');
    li.classList.toggle('disabled', !isOn);
    li.style.display = (!isOn && !sidebar.classList.contains('reorder')) ? 'none' : '';
  });
  const visible = Object.keys(serverModules)
    .filter(k => serverModules[k].visible === 'Yes')
    .sort((a,b) => (serverModules[a].ord||999) - (serverModules[b].ord||999));
  visible.forEach(k => menuTop.appendChild(liOf(k)));
  const hidden = Object.keys(serverModules).filter(k => serverModules[k].visible === 'No');
  hidden.forEach(k => menuTop.appendChild(liOf(k)));
  $$('.vis-toggle').forEach(b => b.style.display = sidebar.classList.contains('reorder') ? 'grid' : 'none');
}

function setReorderMode(on){
  const enable = !!on && !IS_TABLET;
  sidebar.classList.toggle('reorder', enable);
  enableDrag(enable);
  $$('.vis-toggle').forEach(b => b.style.display = enable ? 'grid' : 'none');
  if (reorderBtn){
    reorderBtn.classList.toggle('active', enable);
    reorderBtn.setAttribute('aria-pressed', enable ? 'true' : 'false');
  }
  applyPrefs();
}
async function loadPrefs(){
  const r=await fetch('php/modules_prefs.php?action=get',{credentials:'same-origin',cache:'no-store'});
  const data=r.ok?await r.json():{ok:false};
  if(!data.ok) return;
  serverModules=data.modules||{};
  ensureToggleButtons();
  applyPrefs();
}
function ensureToggleButtons(){
  $$('#menuTop .menu-item').forEach(li=>{
    if(li.querySelector('.vis-toggle')) return;
    const btn=document.createElement('button');
    btn.type='button'; btn.className='vis-toggle'; btn.title='Afficher / Masquer ce module';
    btn.innerHTML=`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
      <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
      <circle cx="12" cy="12" r="3"></circle>
    </svg>`;
    li.appendChild(btn);
    btn.addEventListener('click', async (e)=>{
      e.stopPropagation();
      const key = li.dataset.key;
      const target = (serverModules[key].visible === 'Yes') ? 'No' : 'Yes';
      await fetch('php/modules_prefs.php', {
        method:'POST', credentials:'same-origin', cache:'no-store',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'toggle', key, visible:target})
      });
      await loadPrefs();
    });
  });
}

/* Drag & drop (desktop uniquement) */
let dragSrc=null;
function enableDrag(on){
  menuTop.querySelectorAll('.menu-item').forEach(li=>{
    li.draggable=on; li.classList.toggle('draggable',on);
  });
}
menuTop.addEventListener('dragstart',e=>{
  if (IS_TABLET) return;
  dragSrc=e.target.closest('.menu-item'); if(!dragSrc) return;
  e.dataTransfer.effectAllowed='move'; dragSrc.classList.add('dragging');
});
menuTop.addEventListener('dragend',async ()=>{
  if (dragSrc) dragSrc.classList.remove('dragging');
  const hadDrag = !!dragSrc; dragSrc=null;
  if (IS_TABLET || !hadDrag) return;
  const order = [...menuTop.children]
    .map(li => li.dataset.key)
    .filter(k => serverModules[k].visible === 'Yes');
  await fetch('php/modules_prefs.php', {
    method:'POST', credentials:'same-origin', cache:'no-store',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({action:'reorder', order})
  });
  await loadPrefs();
});
menuTop.addEventListener('dragover',e=>{
  if(!dragSrc || IS_TABLET || !sidebar.classList.contains('reorder')) return; e.preventDefault();
  const over=e.target.closest('.menu-item'); if(!over||over===dragSrc) return;
  if (serverModules[over.dataset.key]?.visible !== 'Yes') return;
  const r=over.getBoundingClientRect(); const before=e.clientY<r.top+r.height/2;
  menuTop.insertBefore(dragSrc, before?over:over.nextSibling);
});

if (reorderBtn){
  reorderBtn.addEventListener('click', ()=>{
    const willEnable = !sidebar.classList.contains('reorder');
    setReorderMode(willEnable);
  });
}

/* Sous-menus */
$$('.has-sub .has-sub-btn').forEach(btn=>{
  btn.addEventListener('click',()=>btn.closest('.has-sub').classList.toggle('open'));
});

/* Hamburger + overlay (tablette) */
function openSidebar(){
  sidebar.classList.remove('collapsed');
  navOverlay.classList.add('show');
  document.body.classList.add('nav-open');
  updateClockPosition();
}
function closeSidebar(){
  sidebar.classList.add('collapsed');
  navOverlay.classList.remove('show');
  document.body.classList.remove('nav-open');
  updateClockPosition();
}
toggleNavBtn.addEventListener('click', ()=>{
  if (sidebar.classList.contains('collapsed')) openSidebar();
  else closeSidebar();
});
navOverlay.addEventListener('click', ()=>{
  // Fermer navbar + colonne de droite si ouvertes
  if (IS_TABLET) closeSidebar();
  closeTopStackSheet();
});

/* Déconnexion uniquement dans la colonne droite */
function attachLogoutHandler(btn){
  if(!btn) return;
  btn.addEventListener('click', async ()=>{
    try{
      await fetch('php/logout.php', {
        method:'POST',
        credentials:'same-origin',
        cache:'no-store'
      });
    }catch(e){}
    window.location.href='php/auth.php';
  });
}

/* Ouverture des paramètres (icône dans la carte profil) */
function openSettings(){
  alert('Ouvrir paramètres…');
}

/* Horloge HH:MM:SS (heure du PC) */
function updateClock(){
  if (!clockEl) return;
  const now = new Date();
  const hh = String(now.getHours()).padStart(2,'0');
  const mm = String(now.getMinutes()).padStart(2,'0');
  const ss = String(now.getSeconds()).padStart(2,'0');
  clockEl.textContent = hh + ':' + mm + ':' + ss;
}

/* Init */
(async function init(){
  IS_TABLET = detectTablet();
  applyTabletMode(IS_TABLET);
  setReorderMode(false);
  await loadPrefs();

  attachLogoutHandler($('#topLogoutBtn'));

  const topSettingsBtn = $('#topSettingsBtn');
  if(topSettingsBtn) topSettingsBtn.addEventListener('click', openSettings);

  updateTopStackMode();
  updateClockPosition();
  setupScaleDropdown();

  if (clockEl){
    updateClock();
    setInterval(updateClock, 1000);
  }
})();
</script>
</body>
</html>
