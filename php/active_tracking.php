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

if (!function_exists('fetchActiveLogSeries')) {
function fetchActiveLogSeries(PDO $pdo, string $range): array
{
    ensureActiveTrackingTables($pdo);

    $range = strtolower($range);
    if ($range !== 'day') {
        $range = 'day';
    }

    $start = new DateTime('now');
    $start->modify('-1 day');
    $start->setTime((int) $start->format('H'), (int) (floor((int) $start->format('i') / 5) * 5), 0);

    $baselineStmt = $pdo->prepare(
        'SELECT active_count FROM active_user_logs WHERE checked_at < :start ORDER BY checked_at DESC LIMIT 1'
    );
    $baselineStmt->execute([':start' => $start->format('Y-m-d H:i:s')]);
    $lastValue = (int) ($baselineStmt->fetchColumn() ?: 0);

    $stmt = $pdo->prepare(
        'SELECT checked_at, active_count
         FROM active_user_logs
         WHERE checked_at >= :start
         ORDER BY checked_at'
    );
    $stmt->execute([':start' => $start->format('Y-m-d H:i:s')]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $series = [];
    $cursor = clone $start;
    $end = new DateTime('now');
    $index = 0;

    while ($cursor <= $end) {
        while ($index < count($rows) && strtotime($rows[$index]['checked_at']) <= $cursor->getTimestamp()) {
            $lastValue = (int) $rows[$index]['active_count'];
            $index++;
        }

        $series[] = [
            'label' => $cursor->format('H:i'),
            'value' => $lastValue,
            'date' => $cursor->format('Y-m-d H:i:s'),
        ];

        $cursor->modify('+5 minutes');
    }

    return ['range' => $range, 'points' => $series];
}
}
