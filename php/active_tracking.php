<?php
if (!function_exists('ensureActiveTrackingTables')) {
    function ensureActiveTrackingTables(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS active_user_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                last_seen DATETIME NOT NULL,
                UNIQUE KEY uniq_active_user (user_id),
                KEY idx_last_seen (last_seen),
                CONSTRAINT fk_active_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS active_user_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                checked_at DATETIME NOT NULL,
                active_count INT NOT NULL,
                KEY idx_checked_at (checked_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }
}

if (!function_exists('markUserActive')) {
    function markUserActive(PDO $pdo, int $userId): void
    {
        ensureActiveTrackingTables($pdo);

        $stmt = $pdo->prepare(
            'INSERT INTO active_user_sessions (user_id, last_seen)
             VALUES (:user_id, NOW())
             ON DUPLICATE KEY UPDATE last_seen = VALUES(last_seen)'
        );
        $stmt->execute([':user_id' => $userId]);
    }
}

if (!function_exists('countActiveUsers')) {
    function countActiveUsers(PDO $pdo, int $minutes = 5): int
    {
        ensureActiveTrackingTables($pdo);

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM active_user_sessions WHERE last_seen >= (NOW() - INTERVAL :minutes MINUTE)'
        );
        $stmt->bindValue(':minutes', $minutes, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}

if (!function_exists('logActiveUserCount')) {
    function logActiveUserCount(PDO $pdo, int $count): void
    {
        ensureActiveTrackingTables($pdo);

        $stmt = $pdo->prepare(
            'INSERT INTO active_user_logs (checked_at, active_count) VALUES (NOW(), :count)'
        );
        $stmt->execute([':count' => $count]);
    }
}

if (!function_exists('fetchActiveAverageSeries')) {
function fetchActiveAverageSeries(PDO $pdo, string $range): array
{
    ensureActiveTrackingTables($pdo);

    $range = strtolower($range);

    if ($range !== 'day') {
        $range = 'day';
    }

    $start = new DateTime('now');
    $start->modify('-1 day');

    $stmt = $pdo->prepare(
        'SELECT DATE_FORMAT(checked_at, "%Y-%m-%d %H:00:00") AS bucket, AVG(active_count) AS avg_count
         FROM active_user_logs
         WHERE checked_at >= :start
         GROUP BY bucket
         ORDER BY bucket'
    );
    $stmt->execute([':start' => $start->format('Y-m-d H:00:00')]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $averages = [];
    foreach ($rows as $row) {
        $averages[$row['bucket']] = round((float) $row['avg_count'], 2);
    }

    $series = [];
    $cursor = new DateTime($start->format('Y-m-d H:00:00'));
    $end = new DateTime('now');
    $lastValue = 0.0;

    while ($cursor <= $end) {
        $bucket = $cursor->format('Y-m-d H:00:00');
        if (array_key_exists($bucket, $averages)) {
            $lastValue = $averages[$bucket];
        }

        $series[] = [
            'label' => $cursor->format('H\h'),
            'value' => $lastValue,
            'date' => $bucket,
        ];

        $cursor->modify('+1 hour');
    }

    return ['range' => $range, 'points' => $series];
}
}
