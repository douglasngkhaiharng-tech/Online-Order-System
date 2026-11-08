<?php
require 'config.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $user = requireRole('customer');
    $customer_id = (int)$user['user_id'];

    $stmt = $pdo->prepare("
        SELECT
            order_id,
            total_amount,
            total_items,
            status,
            ordered_at,
            approved_at
        FROM order_groups
        WHERE customer_id = ?
        ORDER BY order_id DESC
    ");

    $stmt->execute([$customer_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'rows' => $rows
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'rows' => [],
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>