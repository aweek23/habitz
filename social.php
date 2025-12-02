<?php
$pageTitle = 'Social';

ob_start();
?>
<div class="section-title">
  <div>
    <div class="pill">Réseau</div>
    <h1>Social</h1>
  </div>
</div>

<div class="module-stack">
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
      <div class="profile-name">admin</div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();

include __DIR__ . '/php/layout.php';
