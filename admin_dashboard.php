<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbStatusMessage = '';
$pdo = null;

try {
    $pdo = require __DIR__ . '/config.php';
    $dbStatusMessage = 'Connexion à la base de données réussie.';
} catch (Throwable $e) {
    $dbStatusMessage = 'Erreur de connexion à la base de données : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

if ($pdo instanceof PDO) {
    try {
        $rankStmt = $pdo->prepare('SELECT rank FROM users WHERE id = :id LIMIT 1');
        $rankStmt->execute([':id' => $_SESSION['user_id']]);
        $freshRank = $rankStmt->fetchColumn();
        $_SESSION['rank'] = $freshRank !== false ? $freshRank : 'user';
    } catch (Throwable $e) {
        $_SESSION['rank'] = 'user';
    }
} else {
    $_SESSION['rank'] = 'user';
}

$isAdmin = ($_SESSION['rank'] ?? 'user') === 'admin';

if (!$isAdmin) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Accès refusé</title></head><body>';
    echo '<p style="font-family:Arial,sans-serif; margin:2rem;">Accès réservé aux administrateurs. Connectez-vous avec un compte "admin" pour ouvrir le tableau de bord.</p>';
    echo '</body></html>';
    exit;
}

$pageTitle = 'Admin Dashboard';
$menuItems = [
    ['label' => 'Dashboard', 'href' => '/admin_dashboard.php'],
    ['label' => 'Utilisateurs', 'href' => '/user.php'],
    ['label' => 'Signalements', 'href' => '#'],
    ['label' => 'Paramètres', 'href' => '#'],
];

ob_start();
?>
<div class="db-status-banner" role="status" aria-live="polite">
  <span class="db-dot <?php echo (strpos($dbStatusMessage, 'Erreur') !== false) ? 'error' : 'ok'; ?>" aria-hidden="true"></span>
  <span class="db-message"><?php echo $dbStatusMessage; ?></span>
</div>

<div class="cards-grid">
  <div class="hero-card">
    <span class="badge">Administration</span>
    <h2>Tableau de bord admin</h2>
    <p>Consultez les utilisateurs via l'onglet "Utilisateurs" ou accédez aux prochains modules du panneau d'administration.</p>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
      <a class="pill" href="/user.php">Accéder aux utilisateurs</a>
      <a class="pill" href="#">Voir les signalements</a>
    </div>
  </div>

  <div class="widget-card">
    <h3>État du système</h3>
    <ul style="margin:0; padding-left:1.1rem; display:grid; gap:8px;">
      <li>Statut base de données : <?= strpos($dbStatusMessage, 'Erreur') !== false ? 'Hors ligne' : 'En ligne' ?></li>
      <li>Dernière vérification de rôle : immédiate (à chaque chargement)</li>
      <li>Accès admin : <?= $isAdmin ? 'Autorisé' : 'Refusé' ?></li>
    </ul>
  </div>
</div>
<?php
$content = ob_get_clean();

include __DIR__ . '/php/layout.php';
