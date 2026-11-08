<?php
ob_start();

require 'config.php';
require 'helpers.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireRole('admin');

    $stmt = $pdo->query("
        SELECT
            order_id,
            customer_id,
            total_amount,
            total_items,
            status,
            payment_proof_path,
            payment_uploaded_at,
            approved_at,
            approved_by,
            ordered_at
        FROM order_groups
        WHERE status IN ('unpaid', 'pending')
        ORDER BY order_id DESC
    ");

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'rows' => $orders
    ]);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'rows' => [],
        'message' => 'Admin order status error: ' . $e->getMessage()
    ]);
    exit;
}
?>