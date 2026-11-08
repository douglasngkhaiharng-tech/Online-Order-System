<?php
require 'config.php';
require 'helpers.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireRole('admin');

    $stmt = $pdo->query("
        SELECT
            u.user_id,
            u.role,
            u.full_name,
            u.email,
            u.phone,
            COALESCE(NULLIF(u.avatar, ''), 'uploads/default.png') AS avatar,
            u.created_at,
            CASE
                WHEN ub.blacklist_id IS NULL THEN 0
                ELSE 1
            END AS is_blacklisted,
            ub.blacklist_id,
            ub.reason AS blacklist_reason,
            ub.created_at AS blacklisted_at
        FROM users u
        LEFT JOIN user_blacklist ub
            ON LOWER(ub.email) = LOWER(u.email)
           AND ub.is_active = 1
        ORDER BY u.user_id DESC
    ");

    echo json_encode([
        'success' => true,
        'rows' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'rows' => [],
        'message' => 'Load users error: ' . $e->getMessage()
    ]);
}
?>