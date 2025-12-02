<?php
require_once __DIR__ . '/php/page_bootstrap.php';
require_once __DIR__ . '/php/components/dashboard-layout.php';

ob_start();
?>
      <section class="test-page-content" aria-labelledby="testTitle">
        <header class="test-page-header">
          <div class="test-page-meta">
            <p class="eyebrow">Espace de test</p>
            <h1 id="testTitle">Nouvelle page de test</h1>
            <p class="lede">Utilise cet espace pour expérimenter des modules ou contenus sans perturber le tableau de bord principal.</p>
          </div>
          <div class="test-page-actions" role="group" aria-label="Actions rapides">
            <button type="button" class="ghost">Dupliquer</button>
            <button type="button" class="primary">Nouveau bloc</button>
          </div>
        </header>

        <div class="test-panels">
          <article class="test-panel">
            <h2>Zone centrale</h2>
            <p>Cette zone est réservée aux futurs contenus spécifiques de la page test. La structure principale (barre de navigation, horloge, boutons messages et notifications, colonne de droite) est conservée via le composant <strong>DashboardLayout</strong>.</p>
          </article>
          <article class="test-panel muted">
            <h3>Notes de mise en page</h3>
            <ul>
              <li>Le composant est prêt pour la responsive : ajoute simplement les règles CSS nécessaires plus tard.</li>
              <li>La zone centrale s'adapte à la largeur disponible et peut accueillir des grilles, des formulaires ou des graphiques.</li>
              <li>Les actions rapides ci-dessus peuvent être remplacées par des CTA propres à la page.</li>
            </ul>
          </article>
        </div>
      </section>
<?php
$mainContent = ob_get_clean();

renderDashboardLayout([
    'title' => 'Life Tracker — Page de test',
    'bodyClass' => 'test-page',
    'appClass' => 'app test-layout',
    'menuItems' => $menuItems,
    'currentUsername' => $currentUsername,
    'children' => $mainContent,
    'styles' => ['css/test.css'],
]);
