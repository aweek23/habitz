<?php
require_once __DIR__ . '/php/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: php/auth.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function fetch_username(PDO $pdo, int $userId): string
{
    try {
        $stmt = $pdo->prepare('SELECT username FROM ' . TABLE_USERS . ' WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();

        return $row['username'] ?? 'Utilisateur';
    } catch (Throwable $exception) {
        return 'Utilisateur';
    }
}

$menuItems = [
    ['key' => 'tasks', 'label' => 'Tâches'],
    ['key' => 'habits', 'label' => 'Habitudes'],
    ['key' => 'projects', 'label' => 'Projets'],
    [
        'key' => 'sport',
        'label' => 'Sport',
        'submenu' => [
            ['label' => 'Entraînements'],
            ['label' => 'Pas'],
        ],
    ],
    ['key' => 'food', 'label' => 'Alimentation'],
    ['key' => 'calendar', 'label' => 'Calendrier'],
    [
        'key' => 'body',
        'label' => 'Corps',
        'submenu' => [
            ['label' => 'Sommeil', 'href' => 'sleep.php'],
            ['label' => 'Poids'],
            ['label' => 'Glycémie'],
            ['label' => 'Pression artérielle'],
            ['label' => 'Cycle menstruel'],
            ['label' => 'Composition corporelle'],
        ],
    ],
    [
        'key' => 'finances',
        'label' => 'Finances',
        'submenu' => [
            ['label' => 'Budget', 'href' => 'finances.php'],
            ['label' => 'Patrimoine'],
            ['label' => 'Comptes'],
        ],
    ],
    ['key' => 'clock', 'label' => 'Horloge'],
    ['key' => 'events', 'label' => 'Evènements'],
    ['key' => 'news', 'label' => 'Actualités, news, etc'],
    ['key' => 'drive', 'label' => 'Drive'],
];

$currentUsername = fetch_username($pdo, $userId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Life Tracker — Test</title>
  <link rel="stylesheet" href="css/shell.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="css/sport.css?v=<?php echo time(); ?>">
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
<body class="test-page">
<div id="navOverlay" class="nav-overlay"></div>
<div id="topStackOverlay" class="topstack-overlay"></div>

<div class="app test-layout">
  <aside class="sidebar" id="sidebar">
    <div class="side-top">
      <a class="brand brand-link" href="index.php">Life Tracker</a>
      <button id="reorderBtn" class="icon-mini reorder-btn" type="button" title="Réorganiser les modules">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
          <path d="M4 7h16M4 12h10M4 17h6" stroke-width="1.8" stroke-linecap="round"></path>
        </svg>
      </button>
    </div>
    <nav class="nav">
      <ul id="menuTop" class="menu">
        <?php foreach ($menuItems as $item): ?>
          <?php $hasSubmenu = !empty($item['submenu']); ?>
          <li
            class="menu-item<?php echo $hasSubmenu ? ' has-sub' : ''; ?>"
            data-key="<?php echo esc($item['key']); ?>"
          >
            <?php if ($hasSubmenu): ?>
              <button class="item has-sub-btn" type="button">
                <span><?php echo esc($item['label']); ?></span>
                <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path d="M8 10l4 4 4-4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>
              <ul class="submenu">
                <?php foreach ($item['submenu'] as $subItem): ?>
                  <li>
                    <?php if (!empty($subItem['href'])): ?>
                      <a class="subitem" href="<?php echo esc($subItem['href']); ?>">
                        <?php echo esc($subItem['label']); ?>
                      </a>
                    <?php else: ?>
                      <button class="subitem" type="button">
                        <?php echo esc($subItem['label']); ?>
                      </button>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <button class="item" type="button"><?php echo esc($item['label']); ?></button>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </aside>

  <div class="mid-column">
    <button id="toggleNavBtn" class="tablet-toggle" title="Menu">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
        <path d="M3 6h18M3 12h18M3 18h18" stroke-width="1.8" stroke-linecap="round"/>
      </svg>
    </button>

    <div id="topClockBar" class="top-clock-bar simple-clock-bar">
      <div id="topClock" class="top-clock">--:--:--</div>
    </div>

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

    <div class="main dashboard">
      <div class="test-grid">
        <div class="test-module test-module-a">
          <div class="pedometer-card">
            <div class="pedometer-header">
              <h3 class="pedometer-title">Podomètre</h3>
            </div>

            <div class="pedometer-stats">
              <div class="steps-current">8 540</div>
              <div class="steps-goal">/10 000</div>
            </div>

            <div class="pedometer-progress" role="presentation" aria-hidden="true">
              <div class="pedometer-progress-bar" style="width:85%;"></div>
            </div>

            <div class="pedometer-metrics">
              <div class="metric">
                <div class="metric-value">6,4 km</div>
                <div class="metric-label">Distance</div>
              </div>
              <div class="metric">
                <div class="metric-value">320 kcal</div>
                <div class="metric-label">Brûlées</div>
              </div>
            </div>
          </div>
        </div>
        <div class="test-module test-module-b">
          <div class="steps-evolution">
            <div class="steps-evolution-header">
              <h3 class="steps-evolution-title">Evolution du nombre de pas</h3>
              <div class="steps-filters" role="group" aria-label="Période du graphique">
                <button type="button" class="steps-filter-btn">7d</button>
                <button type="button" class="steps-filter-btn">1m</button>
                <button type="button" class="steps-filter-btn">1y</button>
              </div>
            </div>
              <div class="steps-chart" role="img" aria-label="Graphique de l'évolution des pas">
                <img src="php/steps_line.php" alt="Graphique de l'évolution des pas" class="steps-chart-img">
              </div>
          </div>
        </div>
        <div class="test-module test-module-c"></div>
        <div class="test-module test-module-d"></div>
        <div class="test-module test-module-e"></div>
        <div class="test-module test-module-f"></div>
        <div class="test-module test-module-d"></div>
        <div class="test-module test-module-e"></div>
        <div class="test-module test-module-f"></div>
        <div class="test-module test-module-d"></div>
        <div class="test-module test-module-e"></div>
        <div class="test-module test-module-f"></div>
      </div>
    </div>

    <button id="topStackFab" class="topstack-fab" title="Panneau rapide">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
        <circle cx="12" cy="12" r="4" stroke-width="1.8"></circle>
        <path d="M4 12h4M16 12h4M12 4v4M12 16v4" stroke-width="1.8" stroke-linecap="round"></path>
      </svg>
    </button>
  </div>

  <div class="right-column">
    <div id="topStack" class="top-stack sheet-mode">
      <div id="topSearch" class="top-search" role="search">
        <svg class="search-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
          <circle cx="11" cy="11" r="7" stroke-width="1.6"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65" stroke-width="1.6" stroke-linecap="round"></line>
        </svg>
        <input id="globalSearch" type="search" placeholder="Rechercher…" autocomplete="off" />
      </div>

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

      <div id="topUserPanel" class="top-panel top-panel-user">
        <div class="top-user-row">
          <div class="top-user-main">
            <div class="top-user-avatar" role="img" aria-label="Photo de profil"></div>

            <span class="top-user-name trim-ellipsis">
              <?php echo esc($currentUsername); ?>
            </span>
          </div>

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
  </div>
</div>

<script>
const $  = (s)=>document.querySelector(s);
const $$ = (s)=>Array.from(document.querySelectorAll(s));

const appLayout      = document.querySelector('.app.test-layout');
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
const rightColumn    = document.querySelector('.right-column');

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
const MENU_PREF_KEY = 'testMenuPrefs';
const NAV_PREF_ENDPOINT = 'php/nav_prefs.php';
let menuPrefs = {};
let navOpenSections = new Set();

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

function isWindowMaximized(){
  if (document.fullscreenElement) return true;
  const minWidth  = 1280;
  const minHeight = 700;
  return (window.innerWidth >= minWidth && window.innerHeight >= minHeight);
}

function updateAlertsFabPosition(){
  if (!alertsFab) return;

  const isMobile = window.innerWidth < 600;
  if (isMobile){
    alertsFab.style.display = 'none';
    return;
  }
  alertsFab.style.display = 'flex';

  alertsFab.classList.remove('with-right-stack');
  alertsFab.style.right = '';

  const fullDesktop = (!IS_TABLET && isWindowMaximized());
  const tabletGap   = IS_TABLET ? 12 : 16;
  const stackWidth  = rightColumn ? Math.round(rightColumn.getBoundingClientRect().width) : 320;
  document.documentElement.style.setProperty('--right-stack-width', `${stackWidth}px`);

  if (fullDesktop){
    let hasRightStack = false;
    if (topStack){
      hasRightStack =
        topStack.classList.contains('desktop-static') ||
        topStack.classList.contains('open');
    }
    if (hasRightStack){
      alertsFab.classList.add('with-right-stack');
    }
  } else {
    let sheetOpen = false;
    if (topStack){
      sheetOpen =
        topStack.classList.contains('sheet-mode') &&
        topStack.classList.contains('open');
    }
    if (sheetOpen){
      alertsFab.classList.add('with-right-stack');
      alertsFab.style.right = `calc(${stackWidth}px + ${tabletGap + 12}px)`;
    } else {
      alertsFab.style.right = `${tabletGap}px`;
    }
  }
}

function updateClockPosition(){
  const clockTarget = clockBar || clockEl;
  if(!clockTarget) return;

  const isMobile = window.innerWidth < 600;
  if (isMobile){
    return;
  }

  if (!IS_TABLET){
    if (sidebar){
      const rect = sidebar.getBoundingClientRect();
      const gap  = 12;
      clockTarget.style.left = (rect.right + gap) + 'px';
    }
    return;
  }

  if (toggleNavBtn){
    const rect = toggleNavBtn.getBoundingClientRect();
    const gap  = 12;
    clockTarget.style.left = (rect.right + gap) + 'px';
  }
}
function updateTopStackMode(){
  if (!topStack) return;
  const fullDesktop = (!IS_TABLET && isWindowMaximized());
  const sheetOpen   = topStack.classList.contains('open');

  if (fullDesktop){
    if (appLayout) appLayout.classList.remove('compact-right','sheet-overlay','sheet-open');
    if (rightColumn) rightColumn.classList.remove('sheet-open');
    topStack.classList.add('desktop-static');
    topStack.classList.remove('sheet-mode');
    if (topStackFab) topStackFab.classList.remove('show','hidden');
    if (topStackOverlay) topStackOverlay.classList.remove('show');
  } else {
    topStack.classList.add('sheet-mode');
    topStack.classList.remove('desktop-static');
    if (appLayout) {
      appLayout.classList.add('sheet-overlay');
      appLayout.classList.remove('compact-right');
      appLayout.classList.toggle('sheet-open', sheetOpen);
    }
    if (rightColumn) rightColumn.classList.toggle('sheet-open', sheetOpen);
    if (topStackFab) {
      topStackFab.classList.add('show');
      topStackFab.classList.toggle('hidden', sheetOpen);
    }
    if (topStackOverlay) topStackOverlay.classList.toggle('show', sheetOpen);
  }

  updateAlertsFabPosition();
}

function toggleTopStack(open){
  if (!topStack) return;
  const shouldOpen = typeof open === 'boolean' ? open : !topStack.classList.contains('open');
  if (shouldOpen){
    topStack.classList.add('open');
  } else {
    topStack.classList.remove('open');
  }
  updateTopStackMode();
}

if (topStackFab) topStackFab.addEventListener('click', ()=>toggleTopStack(true));
if (topStackOverlay) topStackOverlay.addEventListener('click', ()=>toggleTopStack(false));

function liOf(key){ return menuTop ? menuTop.querySelector(`.menu-item[data-key="${key}"]`) : null; }

function defaultMenuPrefs(){
  const prefs = {};
  if (!menuTop) return prefs;
  [...menuTop.children].forEach((li, idx)=>{
    const key = li.dataset.key;
    prefs[key] = { visible:true, ord: idx + 1 };
  });
  return prefs;
}

function normalizeMenuOrders(){
  const entries = Object.entries(menuPrefs);
  entries
    .sort((a,b)=>((a[1].ord||0)-(b[1].ord||0)))
    .forEach(([k,v], idx)=>{
      if (!v) menuPrefs[k] = { visible:true, ord: idx + 1 };
      else menuPrefs[k].ord = idx + 1;
    });
}

async function loadMenuPrefs(){
  menuPrefs = defaultMenuPrefs();
  navOpenSections = new Set();

  try{
    const res = await fetch(`${NAV_PREF_ENDPOINT}?action=get&ts=${Date.now()}`, { credentials:'same-origin', cache:'no-store' });
    if (res.ok){
      const data = await res.json();
      if (data && data.ok){
        const items = data.items || {};
        Object.entries(items).forEach(([k,v])=>{
          if (!menuPrefs[k]) menuPrefs[k] = { visible:true, ord: 0 };
          menuPrefs[k].visible = v && v.visible === 'No' ? false : true;
          if (v && typeof v.ord !== 'undefined' && v.ord !== null) {
            menuPrefs[k].ord = Number(v.ord) || 0;
          }
        });

        const openSections = Array.isArray(data.open_sections) ? data.open_sections : [];
        navOpenSections = new Set(openSections.filter(k => menuPrefs[k]));
      }
    }
  } catch(e){
    try{
      const cached = JSON.parse(localStorage.getItem(MENU_PREF_KEY) || '{}');
      if (cached && cached.prefs){
        menuPrefs = cached.prefs;
      }
      if (cached && Array.isArray(cached.open)){
        navOpenSections = new Set(cached.open);
      }
    }catch(err){ /* ignore */ }
  }

  normalizeMenuOrders();
  persistMenuPrefs(false);
}

function persistMenuPrefs(saveRemote = true){
  const open = [...navOpenSections];

  try{ localStorage.setItem(MENU_PREF_KEY, JSON.stringify({ prefs: menuPrefs, open })); }catch(e){}

  if (!saveRemote) return;
  fetch(NAV_PREF_ENDPOINT, {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    credentials:'same-origin',
    body: JSON.stringify({ action:'open_sections', open_sections: open })
  }).catch(()=>{});
}

async function persistNavOrder(){
  if (!menuTop) return;
  const order = [...menuTop.children]
    .map(li => li.dataset.key || '')
    .filter(k => k && menuPrefs[k]);
  if (!order.length) return;
  try{
    await fetch(NAV_PREF_ENDPOINT, {
      method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin',
      body: JSON.stringify({ action:'reorder', order })
    });
  }catch(err){ /* ignore */ }
}

function ensureToggleButtons(){
  if (!menuTop) return;
  $$('#menuTop .menu-item').forEach(li => {
    if (li.querySelector('.vis-toggle')) return;
    const btn = document.createElement('button');
    btn.type='button'; btn.className='vis-toggle'; btn.title='Afficher / masquer ce module';
    btn.innerHTML=`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
      <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path>
      <circle cx="12" cy="12" r="3"></circle>
    </svg>`;
    li.appendChild(btn);
    btn.addEventListener('click', async (e)=>{
      e.stopPropagation();
      const key = li.dataset.key;
      if (!menuPrefs[key]) menuPrefs[key] = { visible:true, ord: [...menuTop.children].indexOf(li) + 1 };
      const currentVisible = menuPrefs[key].visible !== false;
      const nextVisible = !currentVisible;
      menuPrefs[key].visible = nextVisible;
      applyMenuPrefs();
      persistMenuPrefs();
      try{
        await fetch(NAV_PREF_ENDPOINT, {
          method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin',
          body: JSON.stringify({ action:'toggle', key, visible: nextVisible ? 'Yes' : 'No' })
        });
        await loadMenuPrefs();
        applyMenuPrefs();
      }catch(err){ /* ignore */ }
    });
  });
}

function applyMenuPrefs(){
  if (!menuTop) return;
  const entries = Object.entries(menuPrefs);
  entries
    .sort((a,b)=>(a[1].ord||0)-(b[1].ord||0))
    .forEach(([k,v], idx)=>{
      const li = liOf(k); if(!li) return;
      if (!v) menuPrefs[k] = { visible:true, ord: idx + 1 };
      li.classList.toggle('disabled', v && v.visible === false);
      li.style.display = (v && v.visible === false && !sidebar.classList.contains('reorder')) ? 'none' : '';
      if (v) menuPrefs[k].ord = idx + 1;
      menuTop.appendChild(li);
    });

  $$('.vis-toggle').forEach(b => b.style.display = sidebar.classList.contains('reorder') ? 'grid' : 'none');

  $$('#menuTop .menu-item.has-sub').forEach(li => {
    const key = li.dataset.key;
    li.classList.toggle('open', navOpenSections.has(key));
  });
}

function enableDrag(on){
  if (!menuTop) return;
  menuTop.querySelectorAll('.menu-item').forEach(li=>{
    li.draggable = on;
    li.classList.toggle('draggable', on);
  });
}

function setReorderMode(on){
  const enable = !!on && !IS_TABLET;
  if (!sidebar) return;
  sidebar.classList.toggle('reorder', enable);
  enableDrag(enable);
  if(reorderBtn){
    reorderBtn.setAttribute('aria-pressed', enable ? 'true' : 'false');
    reorderBtn.classList.toggle('active', enable);
  }
  $$('.vis-toggle').forEach(b => b.style.display = enable ? 'grid' : 'none');
  applyMenuPrefs();
}
if (reorderBtn){
  reorderBtn.addEventListener('click', ()=>{
    const next = !sidebar.classList.contains('reorder');
    setReorderMode(next);
  });
}

let dragSrc=null;
if (menuTop){
  menuTop.addEventListener('dragstart', (e)=>{
    if (IS_TABLET || !sidebar.classList.contains('reorder')) return;
    const li = e.target.closest('.menu-item');
    if(!li || menuPrefs[li.dataset.key]?.visible === false) return;
    dragSrc = li;
    e.dataTransfer.effectAllowed='move';
    li.classList.add('dragging');
  });
  menuTop.addEventListener('dragend', ()=>{
    if (dragSrc) dragSrc.classList.remove('dragging');
    if (dragSrc){
      [...menuTop.children].forEach((li, idx)=>{
        const key = li.dataset.key;
        if(menuPrefs[key]) menuPrefs[key].ord = idx + 1;
      });
      applyMenuPrefs();
      persistMenuPrefs();
      persistNavOrder();
    }
    dragSrc=null;
  });
  menuTop.addEventListener('dragover',(e)=>{
    if(!dragSrc || IS_TABLET || !sidebar.classList.contains('reorder')) return;
    const over = e.target.closest('.menu-item');
    if(!over || over === dragSrc) return;
    if(menuPrefs[over.dataset.key]?.visible === false) return;
    e.preventDefault();
    const r = over.getBoundingClientRect();
    const before = e.clientY < r.top + r.height/2;
    menuTop.insertBefore(dragSrc, before ? over : over.nextSibling);
  });
}

if (menuTop){
  menuTop.addEventListener('click', (evt)=>{
    const btn = evt.target.closest('.has-sub-btn');
    if(!btn) return;
    const item = btn.closest('.menu-item');
    if(!item) return;
    item.classList.toggle('open');
    const key = item.dataset.key;
    if (item.classList.contains('open')) navOpenSections.add(key);
    else navOpenSections.delete(key);
    persistMenuPrefs();
  });
}

if (toggleNavBtn){
  const setNavOpen = (open)=>{
    const shouldOpen = open !== undefined ? open : !document.body.classList.contains('nav-open');
    document.body.classList.toggle('nav-open', shouldOpen);
    if (sidebar){
      sidebar.classList.toggle('collapsed', !shouldOpen);
    }
    if (navOverlay){
      navOverlay.classList.toggle('show', shouldOpen);
    }
    requestAnimationFrame(updateClockPosition);
  };

  toggleNavBtn.addEventListener('click', ()=> setNavOpen());
}
if (navOverlay){
  navOverlay.addEventListener('click', ()=>{
    document.body.classList.remove('nav-open');
    navOverlay.classList.remove('show');
    if (sidebar){
      sidebar.classList.add('collapsed');
    }
    requestAnimationFrame(updateClockPosition);
  });
}

function startClock(){
  if (!clockEl) return;
  const update=()=>{
    const now = new Date();
    const h = String(now.getHours()).padStart(2,'0');
    const m = String(now.getMinutes()).padStart(2,'0');
    const s = String(now.getSeconds()).padStart(2,'0');
    clockEl.textContent = `${h}:${m}:${s}`;
  };
  update();
  setInterval(update, 1000);
}

async function init(){
  await loadMenuPrefs();
  ensureToggleButtons();
  applyMenuPrefs();
  applyTabletMode(IS_TABLET);
  startClock();
  updateAlertsFabPosition();
  updateTopStackMode();
}
window.addEventListener('load', ()=>{ init().catch(()=>{}); });
</script>
</body>
</html>