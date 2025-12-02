<?php
if (!function_exists('renderDashboardLayout')) {
    function renderDashboardLayout(array $options = []): void
    {
        $title = $options['title'] ?? 'Life Tracker — Test';
        $bodyClass = trim('test-page ' . ($options['bodyClass'] ?? ''));
        $appClass = $options['appClass'] ?? 'app test-layout';
        $menuItems = $options['menuItems'] ?? [];
        $currentUsername = $options['currentUsername'] ?? 'Utilisateur';
        $children = $options['children'] ?? '';
        $styles = $options['styles'] ?? [];
        $deferScripts = $options['scripts'] ?? [];

        if (is_callable($children)) {
            $children = (string) $children();
        }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo esc($title); ?></title>
  <link rel="stylesheet" href="css/shell.css?v=<?php echo time(); ?>">
  <?php foreach ($styles as $style): ?>
    <link rel="stylesheet" href="<?php echo esc($style); ?>?v=<?php echo time(); ?>">
  <?php endforeach; ?>
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
<body class="<?php echo esc($bodyClass); ?>">
<div id="navOverlay" class="nav-overlay"></div>
<div id="topStackOverlay" class="topstack-overlay"></div>

<div class="<?php echo esc($appClass); ?>">
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
      <button id="dateNavBtn" class="date-nav" type="button" aria-label="Date du jour">
        <span class="date-nav-icon date-prev" data-dir="-1" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" focusable="false">
            <path d="M14 7l-5 5 5 5" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
          </svg>
        </span>
        <span id="dateLabel" class="date-label">--</span>
        <span class="date-nav-icon date-next" data-dir="1" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" focusable="false">
            <path d="M10 7l5 5-5 5" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
          </svg>
        </span>
      </button>
      <div id="layoutSwitcher" class="layout-switcher" role="group" aria-label="Disposition des modules">
        <button type="button" class="layout-btn icon-mini alerts-fab-btn" data-layout="4" title="Grille 4 colonnes">
          <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <rect x="2.5" y="5" width="4" height="14" rx="1"></rect>
            <rect x="7.5" y="5" width="4" height="14" rx="1"></rect>
            <rect x="12.5" y="5" width="4" height="14" rx="1"></rect>
            <rect x="17.5" y="5" width="4" height="14" rx="1"></rect>
          </svg>
        </button>
        <button type="button" class="layout-btn icon-mini alerts-fab-btn" data-layout="3" title="Grille 3 colonnes">
          <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <rect x="4" y="5" width="4" height="14" rx="1"></rect>
            <rect x="10" y="5" width="4" height="14" rx="1"></rect>
            <rect x="16" y="5" width="4" height="14" rx="1"></rect>
          </svg>
        </button>
        <button type="button" class="layout-btn icon-mini alerts-fab-btn" data-layout="2" title="Grille 2 colonnes">
          <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <rect x="6" y="5" width="5" height="14" rx="1"></rect>
            <rect x="13" y="5" width="5" height="14" rx="1"></rect>
          </svg>
        </button>
        <button type="button" class="layout-btn icon-mini alerts-fab-btn" data-layout="1" title="Vue smartphone">
          <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <rect x="7" y="3" width="10" height="18" rx="2"></rect>
            <circle cx="12" cy="17" r="1"></circle>
          </svg>
        </button>
      </div>
    </div>

    <div id="alertsFab" class="alerts-fab">
      <button id="editDashboardBtn" class="edit-dashboard-btn alerts-fab-btn" type="button" title="Modifier dashboard">Modifier
      dashboard</button>
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
      <?php echo $children; ?>
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

<nav class="mobile-bottom-nav" aria-label="Navigation mobile">
  <button type="button" class="mobile-nav-btn is-active" aria-label="Tableau de bord" data-nav-link="index.php">
    <span class="mobile-nav-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
        <path d="M3 10l9-7 9 7v9.5a1.5 1.5 0 0 1-1.5 1.5H4.5A1.5 1.5 0 0 1 3 19.5V10z" stroke-width="1.6" stroke-linejoin="round" />
        <path d="M9 21V12h6v9" stroke-width="1.6" stroke-linejoin="round" />
      </svg>
    </span>
    <span class="mobile-nav-label">Dashboard</span>
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
const editDashboardBtn = $('#editDashboardBtn');
const clockBar       = $('#topClockBar');
const clockEl        = $('#topClock');
const dateNavBtn     = $('#dateNavBtn');
const dateLabel      = $('#dateLabel');
const datePrev       = dateNavBtn ? dateNavBtn.querySelector('[data-dir="-1"]') : null;
const dateNext       = dateNavBtn ? dateNavBtn.querySelector('[data-dir="1"]') : null;
const reorderBtn     = $('#reorderBtn');
const rightColumn    = document.querySelector('.right-column');
const modulesGrid    = $('#modulesGrid');
const layoutSwitcher = $('#layoutSwitcher');

let currentNavDate = new Date();

function getDefaultLayoutForViewport(){
  const w = window.innerWidth || document.documentElement.clientWidth || screen.width || 0;
  if (w < 640) return '1';
  if (w < 1200) return '2';
  if (w >= 1600) return '4';
  return '3';
}

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
const MODULE_PREF_ENDPOINT = 'php/modules_prefs.php';
let modulePrefs = {}; // { layout: { key: {visible, ord} } }
let modulesReorder = false;
let activeViewLayout = getDefaultLayoutForViewport();
let editLayout = activeViewLayout;
const moduleCanon = modulesGrid
  ? [...modulesGrid.children].map((mod, idx) => ({ key: mod.dataset.moduleKey || `mod-${idx}`, ord: idx }))
  : [];

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
  updateDateNavVisibility();
}
window.addEventListener('resize', () => {
  const now = detectTablet();
  if (now !== IS_TABLET) { IS_TABLET = now; applyTabletMode(IS_TABLET); }
  else { updateTopStackMode(); updateClockPosition(); }
  syncActiveLayoutForViewport();
  updateDateNav();
  updateDateNavVisibility();
});

function isWindowMaximized(){
  if (document.fullscreenElement) return true;
  const minWidth  = 1280;
  const minHeight = 700;
  return (window.innerWidth >= minWidth && window.innerHeight >= minHeight);
}

function shouldHideEditButton(){
  const nonFull = !isWindowMaximized();
  const sheetOpen = topStack && topStack.classList.contains('open');
  return nonFull && sheetOpen;
}

function updateEditButtonVisibility(){
  if (!editDashboardBtn) return;
  editDashboardBtn.style.display = shouldHideEditButton() ? 'none' : '';
}

function shouldHideDateNav(){
  if (modulesReorder) return true;
  const nonFull = !isWindowMaximized();
  const sheetOpen = topStack && topStack.classList.contains('open');
  return nonFull && sheetOpen;
}

function updateDateNavVisibility(){
  if (!dateNavBtn) return;
  dateNavBtn.classList.toggle('hidden', shouldHideDateNav());
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

  updateEditButtonVisibility();

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
    if (topStack) {
      sheetOpen = topStack.classList.contains('open');
    }
    if (sheetOpen) {
      alertsFab.classList.add('with-right-stack');
      alertsFab.style.right = `${tabletGap + stackWidth}px`;
    }
  }
}

function toggleNav(on){
  document.body.classList.toggle('nav-open', on);
  navOverlay.classList.toggle('visible', on);
}

if (toggleNavBtn){
  toggleNavBtn.addEventListener('click', ()=>toggleNav(!document.body.classList.contains('nav-open')));
}
if (navOverlay){
  navOverlay.addEventListener('click', ()=>toggleNav(false));
}

function enableDrag(enable){
  if (!sidebar) return;
  sidebar.classList.toggle('reorder', enable);
  if (!enable){
    sidebar.querySelectorAll('.dragging').forEach(el => el.classList.remove('dragging'));
  }
}

function setReorderMode(on){
  if (!sidebar || !menuTop) return;
  const items = $$('#menuTop .menu-item');
  items.forEach(li => {
    li.setAttribute('draggable', on ? 'true' : 'false');
    li.classList.toggle('grabbable', on);
  });
  menuTop.classList.toggle('reorder', on);
  modulesReorder = on;
  applyMenuPrefs();
}

function handleDragStart(e){
  if (!modulesReorder) return;
  e.target.classList.add('dragging');
  e.dataTransfer.effectAllowed = 'move';
}
function handleDragEnd(e){
  e.target.classList.remove('dragging');
}
function handleDragOver(e){
  if (!modulesReorder) return;
  e.preventDefault();
  const dragging = menuTop.querySelector('.dragging');
  const items = $$('#menuTop .menu-item:not(.dragging)');
  const y = e.clientY;
  let target = items.find(li => y <= li.getBoundingClientRect().top + li.offsetHeight / 2);
  if (!target) target = items[items.length-1];
  if (target && dragging) {
    menuTop.insertBefore(dragging, target);
  }
}

function persistMenuPrefs(){
  try { localStorage.setItem(MENU_PREF_KEY, JSON.stringify(menuPrefs)); }
  catch(err){ /* ignore */ }
}

function loadMenuPrefs(){
  try {
    const raw = localStorage.getItem(MENU_PREF_KEY);
    if (raw) menuPrefs = JSON.parse(raw) || {};
  } catch(err){ menuPrefs = {}; }
}

function applyMenuPrefs(){
  if (!menuTop) return;
  const entries = Object.entries(menuPrefs);
  entries.sort((a,b)=>{
    const ordA = a[1]?.ord ?? 0;
    const ordB = b[1]?.ord ?? 0;
    return ordA - ordB;
  });
  entries.forEach(([key, prefs]) => {
    const li = menuTop.querySelector(`[data-key="${key}"]`);
    if (!li) return;
    li.classList.toggle('hidden', prefs.visible === false || prefs.visible === 'No');
    if (prefs.ord !== undefined) {
      li.style.order = prefs.ord;
    }
  });

  const hiddenCount = entries.filter(([,prefs]) => prefs.visible === false || prefs.visible === 'No').length;
  document.body.classList.toggle('menu-has-hidden', hiddenCount > 0);
}

function toggleSubmenu(li, open){
  if (!li) return;
  const key = li.dataset.key;
  const willOpen = (typeof open === 'boolean') ? open : !li.classList.contains('open');
  li.classList.toggle('open', willOpen);
  if (willOpen) navOpenSections.add(key); else navOpenSections.delete(key);
  persistNavOpenSections();
}

function persistNavOpenSections(){
  const open = Array.from(navOpenSections);
  try{ localStorage.setItem('navOpenSections', JSON.stringify(open)); }
  catch(err){ /* ignore */ }
  fetch(NAV_PREF_ENDPOINT, {
    method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin',
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
      method:'POST',
      headers:{'Content-Type':'application/json'},
      credentials:'same-origin',
      body: JSON.stringify({ action:'reorder', order })
    });
  }catch(err){ /* silencieux */ }
}

function defaultModulePrefs(){
  const prefs = {};
  moduleCanon.forEach(({key, ord}) => {
    prefs[key] = { visible:true, ord };
  });
  return prefs;
}

function normalizePrefsMap(mods){
  const normalized = {};
  Object.entries(mods || {}).forEach(([k,v])=>{
    const visibleVal = v && typeof v.visible !== 'undefined' ? v.visible : true;
    const entry = {
      visible: visibleVal === false || visibleVal === 'No' ? false : true,
    };
    if (v && typeof v.ord !== 'undefined' && v.ord !== null) {
      entry.ord = Number(v.ord) || 0;
    }
    normalized[k] = entry;
  });
  return normalized;
}

function getLayoutPrefs(layout){
  if (!modulePrefs[layout]) modulePrefs[layout] = defaultModulePrefs();
  return modulePrefs[layout];
}

async function fetchModulePrefs(layout){
  const query = layout === 'all' ? '?action=get&layout=all' : `?action=get&layout=${layout}`;
  const res = await fetch(`${MODULE_PREF_ENDPOINT}${query}`, { credentials:'same-origin', cache:'no-store' });
  if (!res.ok) throw new Error('network');
  const data = await res.json();
  if (!data || data.ok !== true) throw new Error('server');

  if (data.layouts){
    Object.entries(data.layouts).forEach(([layoutKey, mods]) => {
      const base = defaultModulePrefs();
      modulePrefs[layoutKey] = Object.assign(base, normalizePrefsMap(mods));
    });
  } else if (data.modules && data.layout){
    const base = defaultModulePrefs();
    modulePrefs[String(data.layout)] = Object.assign(base, normalizePrefsMap(data.modules));
  }
}

async function ensureLayoutPrefs(layout){
  if (modulePrefs[layout]) return;
  try {
    await fetchModulePrefs(layout);
  } catch(e) {
    modulePrefs[layout] = defaultModulePrefs();
  }
}

function getActiveLayoutKey(){
  return modulesReorder ? editLayout : activeViewLayout;
}

async function persistModuleOrder(layout){
  const prefs = getLayoutPrefs(layout);
  const order = [...modulesGrid.children]
    .map(mod => mod.dataset.moduleKey || '')
    .filter(key => key && prefs[key] && prefs[key].visible !== false);
  if (!order.length) return;
  try{
    await fetch(MODULE_PREF_ENDPOINT, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      credentials:'same-origin',
      body: JSON.stringify({ action:'reorder', layout:Number(layout), order })
    });
  }catch(err){ /* silencieux */ }
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
      try {
        await fetch(NAV_PREF_ENDPOINT, {
          method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin',
          body: JSON.stringify({ action:'toggle', key, visible: nextVisible ? 'Yes' : 'No' })
        });
        await loadMenuPrefs();
        applyMenuPrefs();
      } catch(err){ /* ignore */ }
    });
  });
}

function applyLayoutPrefs(layout){
  if (!modulesGrid) return;
  const prefs = getLayoutPrefs(layout);
  const ordered = Object.entries(prefs)
    .filter(([,entry]) => entry.visible !== false)
    .sort(([,a],[,b]) => (a.ord ?? 0) - (b.ord ?? 0))
    .map(([key]) => key);

  ordered.forEach(key => {
    const mod = modulesGrid.querySelector(`[data-module-key="${key}"]`);
    if (mod) modulesGrid.appendChild(mod);
  });

  [...modulesGrid.children].forEach(mod => {
    const key = mod.dataset.moduleKey;
    const pref = prefs[key] || {};
    const visible = pref.visible !== false;
    mod.classList.toggle('hidden', !visible);
  });

  const columns = Number(layout) || 1;
  modulesGrid.dataset.layout = layout;
  modulesGrid.style.setProperty('--columns', columns);
}

function handleLayoutSwitch(e){
  const btn = e.target.closest('.layout-btn');
  if (!btn) return;
  const layout = btn.dataset.layout;
  if (!layout) return;
  activeViewLayout = layout;
  syncLayoutButtons();
  applyLayoutPrefs(layout);
  updateAlertsFabPosition();
  persistActiveLayout(layout);
}

function syncLayoutButtons(){
  if (!layoutSwitcher) return;
  const activeLayoutKey = getActiveLayoutKey();
  $$('#layoutSwitcher .layout-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.layout === activeLayoutKey);
  });
}

function persistActiveLayout(layout){
  try {
    localStorage.setItem('activeLayout', layout);
  } catch(err){ /* ignore */ }
}

function loadActiveLayout(){
  try {
    const saved = localStorage.getItem('activeLayout');
    if (saved) activeViewLayout = saved;
  } catch(err){ /* ignore */ }
}

function syncActiveLayoutForViewport(){
  const layout = getDefaultLayoutForViewport();
  if (modulesReorder) return;
  if (activeViewLayout !== layout) {
    activeViewLayout = layout;
    applyLayoutPrefs(layout);
    syncLayoutButtons();
  }
}

function setupMenuHandlers(){
  if (!menuTop) return;
  menuTop.addEventListener('dragstart', handleDragStart);
  menuTop.addEventListener('dragend', handleDragEnd);
  menuTop.addEventListener('dragover', handleDragOver);

  $$('#menuTop .menu-item.has-sub').forEach(li => {
    const btn = li.querySelector('.has-sub-btn');
    btn?.addEventListener('click', () => toggleSubmenu(li));
  });
}

function initNavOpenState(){
  try {
    const raw = localStorage.getItem('navOpenSections');
    if (raw) {
      navOpenSections = new Set(JSON.parse(raw));
    }
  } catch(err){ navOpenSections = new Set(); }

  navOpenSections.forEach(key => {
    const li = menuTop?.querySelector(`[data-key="${key}"]`);
    if (li) li.classList.add('open');
  });
}

function setupDateNav(){
  if (!dateNavBtn) return;
  datePrev?.addEventListener('click', ()=>shiftDate(-1));
  dateNext?.addEventListener('click', ()=>shiftDate(1));
  updateDateNav();
}

function updateDateNav(){
  if (!dateLabel) return;
  const formatter = new Intl.DateTimeFormat('fr-FR', { weekday:'short', day:'numeric', month:'short' });
  dateLabel.textContent = formatter.format(currentNavDate);
}

function shiftDate(delta){
  currentNavDate.setDate(currentNavDate.getDate() + delta);
  updateDateNav();
}

function updateClock(){
  if (!clockEl) return;
  const now = new Date();
  const time = now.toLocaleTimeString('fr-FR', { hour12:false });
  clockEl.textContent = time;
}

function updateClockPosition(){
  if (!clockBar) return;
  const hideDate = window.innerWidth < 960;
  clockBar.classList.toggle('compact', hideDate);
}

function setupClock(){
  updateClock();
  setInterval(updateClock, 1000);
  updateClockPosition();
}

function setupTopStackFab(){
  if (!topStackFab || !topStack) return;
  topStackFab.addEventListener('click', ()=>{
    topStack.classList.toggle('open');
    topStackOverlay.classList.toggle('visible', topStack.classList.contains('open'));
    updateAlertsFabPosition();
    updateDateNavVisibility();
  });
  topStackOverlay?.addEventListener('click', ()=>{
    topStack.classList.remove('open');
    topStackOverlay.classList.remove('visible');
    updateAlertsFabPosition();
    updateDateNavVisibility();
  });
}

function setupMobileNav(){
  const buttons = $$('.mobile-bottom-nav .mobile-nav-btn');
  buttons.forEach(btn => {
    btn.addEventListener('click', () => {
      buttons.forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');
      const dest = btn.dataset.navLink;
      if (dest) {
        window.location.href = dest;
      }
    });
  });
}

function setupReorderButton(){
  if (!reorderBtn) return;
  reorderBtn.addEventListener('click', () => {
    const next = !modulesReorder;
    setReorderMode(next);
    if (!next) {
      persistNavOrder();
    }
  });
}

function setupLayoutSwitcher(){
  layoutSwitcher?.addEventListener('click', handleLayoutSwitch);
}

function setupModuleDrag(){
  if (!modulesGrid) return;
  let dragItem = null;
  modulesGrid.addEventListener('dragstart', (e)=>{
    if (!modulesReorder) return;
    dragItem = e.target.closest('[data-module-key]');
    if (!dragItem) return;
    dragItem.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
  });
  modulesGrid.addEventListener('dragend', ()=>{
    if (!dragItem) return;
    dragItem.classList.remove('dragging');
    dragItem = null;
  });
  modulesGrid.addEventListener('dragover', (e)=>{
    if (!modulesReorder) return;
    e.preventDefault();
    const target = e.target.closest('[data-module-key]');
    if (!target || target === dragItem) return;
    const rect = target.getBoundingClientRect();
    const before = e.clientY < rect.top + rect.height / 2;
    modulesGrid.insertBefore(dragItem, before ? target : target.nextSibling);
  });
}

async function initLayout(){
  applyTabletMode(IS_TABLET);
  loadMenuPrefs();
  ensureToggleButtons();
  applyMenuPrefs();
  setupMenuHandlers();
  initNavOpenState();
  setupDateNav();
  setupClock();
  setupTopStackFab();
  setupMobileNav();
  setupReorderButton();
  setupLayoutSwitcher();
  setupModuleDrag();
  updateAlertsFabPosition();
  loadActiveLayout();
  await ensureLayoutPrefs('all');
  applyLayoutPrefs(getActiveLayoutKey());
  syncLayoutButtons();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initLayout);
} else {
  initLayout();
}
</script>
<?php foreach ($deferScripts as $script): ?>
  <script src="<?php echo esc($script); ?>?v=<?php echo time(); ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
<?php
    }
}
