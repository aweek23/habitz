<?php
require_once __DIR__ . '/php/page_bootstrap.php';
require_once __DIR__ . '/php/components/dashboard-layout.php';

ob_start();
?>
      <div class="test-grid" id="modulesGrid">
        <div class="test-module test-module-a" data-module-key="pedometer">
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
        <div class="test-module test-module-b" data-module-key="steps">
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
                <img src="assets/graphiques/steps_line.php" alt="Graphique de l'évolution des pas" class="steps-chart-img">
              </div>
          </div>
        </div>
        <div class="test-module test-module-c" data-module-key="module-c"></div>
        <div class="test-module test-module-d" data-module-key="module-d"></div>
        <div class="test-module test-module-e" data-module-key="module-e"></div>
        <div class="test-module test-module-f" data-module-key="module-f"></div>
        <div class="test-module test-module-d" data-module-key="module-g"></div>
        <div class="test-module test-module-e" data-module-key="module-h"></div>
        <div class="test-module test-module-f" data-module-key="module-i"></div>
        <div class="test-module test-module-d" data-module-key="module-j"></div>
        <div class="test-module test-module-e" data-module-key="module-k"></div>
        <div class="test-module test-module-f" data-module-key="module-l"></div>
      </div>
<?php
$mainContent = ob_get_clean();

renderDashboardLayout([
    'title' => 'Life Tracker — Test',
    'bodyClass' => 'test-page',
    'appClass' => 'app test-layout',
    'menuItems' => $menuItems,
    'currentUsername' => $currentUsername,
    'children' => $mainContent,
    'styles' => ['css/index.css'],
]);
