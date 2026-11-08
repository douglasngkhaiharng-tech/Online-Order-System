<?php
ob_start();

require 'config.php';
require 'helpers.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireRole('admin');

    $managerAdminUserId = getManagerAdminUserId($pdo);

    $stmt = $pdo->query("
        SELECT
            u.user_id,
            u.role,
            u.full_name,
            u.email,
            u.phone,
            u.avatar,
            ub.blacklist_id,
            CASE
                WHEN ub.blacklist_id IS NULL THEN 0
                ELSE 1
            END AS is_blacklisted
        FROM users u
        LEFT JOIN user_blacklist ub
            ON LOWER(ub.email) = LOWER(u.email)
           AND ub.is_active = 1
        ORDER BY u.user_id DESC
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $role = strtolower((string)($row['role'] ?? 'customer'));
        $row['avatar'] = normalizeAvatarPath($row['avatar'] ?? '', $role);
        $row['is_manager_admin'] = ($role === 'admin' && (int)$row['user_id'] === $managerAdminUserId) ? 1 : 0;
        $row['role_display'] = $row['is_manager_admin'] ? MANAGER_ADMIN_LABEL : ucfirst($role);
        $row['blacklist_id'] = isset($row['blacklist_id']) && $row['blacklist_id'] !== null ? (int)$row['blacklist_id'] : 0;
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'rows' => $rows
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'rows' => [],
        'message' => 'Load users error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>