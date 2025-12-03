<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

$pageTitle = 'Life Tracker';

$dbStatusMessage = '';

try {
    $pdo = require __DIR__ . '/config.php';
    $dbStatusMessage = 'Connexion à la base de données réussie.';
} catch (Throwable $e) {
    $dbStatusMessage = 'Erreur de connexion à la base de données : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

ob_start();
?>
<div class="db-status-banner" role="status" aria-live="polite">
  <span class="db-dot <?php echo (strpos($dbStatusMessage, 'Erreur') !== false) ? 'error' : 'ok'; ?>" aria-hidden="true"></span>
  <span class="db-message"><?php echo $dbStatusMessage; ?></span>
</div>
<div class="section-title">
  <div>
    <div class="pill">Today</div>
    <h1>Dashboard</h1>
  </div>
</div>

<div class="cards-grid">
  <div class="hero-card">
    <span class="badge">Habitz Central</span>
    <h2>Organisez vos habitudes et suivez votre progression</h2>
    <p>Réduisez les rechargements de page en gardant ce layout fixe et en remplaçant seulement le contenu du centre.</p>
    <button class="pill">Ajouter une habitude</button>
  </div>
  <div class="widget-card">
    <h3>Actions rapides</h3>
    <div class="widget-list">
      <div class="widget-row">
        <span class="status"></span>
        <div>
          <div class="label">Nouvelle tâche</div>
          <div class="meta">Créer une tâche pour aujourd'hui</div>
        </div>
        <button class="icon-btn" aria-label="Ajouter">
          <span class="dot"></span>
          <span class="dot"></span>
          <span class="dot"></span>
        </button>
      </div>
      <div class="widget-row">
        <span class="status"></span>
        <div>
          <div class="label">Routine du matin</div>
          <div class="meta">Mettre à jour les habitudes complétées</div>
        </div>
        <button class="icon-btn" aria-label="Éditer">
          <span class="dot"></span>
          <span class="dot"></span>
          <span class="dot"></span>
        </button>
      </div>
      <div class="widget-row">
        <span class="status"></span>
        <div>
          <div class="label">Rapport</div>
          <div class="meta">Exporter les données de la semaine</div>
        </div>
        <button class="icon-btn" aria-label="Exporter">
          <span class="dot"></span>
          <span class="dot"></span>
          <span class="dot"></span>
        </button>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();

include __DIR__ . '/php/layout.php';
