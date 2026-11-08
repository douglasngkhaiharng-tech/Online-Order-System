<?php
ob_start();

require 'config.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireRole('admin');

    $stmt = $pdo->query("
        SELECT
            ub.blacklist_id,
            ub.user_id,
            ub.email,
            ub.reason,
            ub.blacklisted_by,
            ub.blacklisted_at,
            u.full_name
        FROM user_blacklist ub
        LEFT JOIN users u
            ON ub.user_id = u.user_id
        WHERE ub.is_active = 1
        ORDER BY ub.blacklisted_at DESC
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'rows' => $rows
    ]);
    exit;
} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'rows' => [],
        'message' => 'Load blacklist error: ' . $e->getMessage()
    ]);
    exit;
}
?>