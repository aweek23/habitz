<?php
$pdo = require __DIR__ . '/config.php';

if (empty($_SESSION['user_id']) || ($_SESSION['rank'] ?? 'user') !== 'admin') {
    header('Location: auth.php');
    exit;
}

$searchTerm = trim($_GET['q'] ?? '');
$users = [];
$error = '';

if ($searchTerm !== '') {
    try {
        $like = '%' . $searchTerm . '%';
        $conditions = [
            'username LIKE :term',
            'email LIKE :term',
            'phone_number LIKE :term',
            'ip LIKE :term',
        ];
        $params = [
            ':term' => $like,
        ];

        if (ctype_digit($searchTerm)) {
            $conditions[] = 'id = :idExact';
            $params[':idExact'] = (int) $searchTerm;
        }

        $sql = 'SELECT id, username, email, phone_number, birthdate, gender, password, rank, creation_date, ip '
             . 'FROM users '
             . 'WHERE ' . implode(' OR ', $conditions)
             . ' ORDER BY creation_date DESC LIMIT 100';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = 'Erreur lors de la recherche : ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration — Utilisateurs</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; background: #f5f6fa; }
        h1 { margin-bottom: 1rem; }
        form { margin-bottom: 1.5rem; }
        input[type="text"] { padding: 0.5rem; width: 320px; max-width: 100%; }
        button { padding: 0.5rem 1rem; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { padding: 0.75rem; border: 1px solid #e0e0e0; text-align: left; font-size: 0.95rem; }
        th { background: #f0f0f0; }
        .notice { color: #777; font-size: 0.9rem; }
        .error { color: #b00020; margin-top: 0.5rem; }
    </style>
</head>
<body>
    <h1>Tableau de bord administrateur</h1>
    <p class="notice">Recherchez par identifiant, pseudo, email, téléphone ou adresse IP. Jusqu'à 100 résultats récents s'affichent.</p>

    <form method="get" action="admin_dashboard.php">
        <label for="q">Recherche utilisateur :</label>
        <input type="text" id="q" name="q" value="<?= htmlspecialchars($searchTerm, ENT_QUOTES) ?>" placeholder="Pseudo, email, téléphone, IP ou ID" />
        <button type="submit">Rechercher</button>
    </form>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($searchTerm !== ''): ?>
        <?php if (empty($users)): ?>
            <p>Aucun utilisateur trouvé pour "<?= htmlspecialchars($searchTerm) ?>".</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Birthdate</th>
                        <th>Gender</th>
                        <th>Password</th>
                        <th>Rank</th>
                        <th>Creation date</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= (int) $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone_number'] ?? '') ?></td>
                            <td><?= htmlspecialchars($user['birthdate']) ?></td>
                            <td><?= htmlspecialchars($user['gender'] ?? '') ?></td>
                            <td style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;">
                                <?= htmlspecialchars($user['password']) ?>
                            </td>
                            <td><?= htmlspecialchars($user['rank']) ?></td>
                            <td><?= htmlspecialchars($user['creation_date']) ?></td>
                            <td><?= htmlspecialchars($user['ip'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php else: ?>
        <p class="notice">Saisissez un terme de recherche pour voir les utilisateurs correspondants.</p>
    <?php endif; ?>
</body>
</html>
