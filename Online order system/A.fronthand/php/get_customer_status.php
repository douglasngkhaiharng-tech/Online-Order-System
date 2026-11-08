<?php
ob_start();

require 'config.php';
require 'helpers.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireRole('customer');

    $user = $_SESSION['user'];
    $customer_id = (int)$user['user_id'];

    $stmt = $pdo->prepare("
        SELECT
            order_id,
            customer_id,
            total_amount,
            total_items,
            status,
            payment_uploaded_at,
            approved_at,
            approved_by,
            ordered_at
        FROM order_groups
        WHERE customer_id = ?
          AND status IN ('unpaid', 'pending')
        ORDER BY order_id DESC
    ");
    $stmt->execute([$customer_id]);
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
        'message' => 'Status error: ' . $e->getMessage()
    ]);
    exit;
}
?>