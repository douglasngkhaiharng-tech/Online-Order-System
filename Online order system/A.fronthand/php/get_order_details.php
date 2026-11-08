<?php
ob_start();

require 'config.php';
require 'helpers.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $user = requireLogin();

    $order_id = (int)($_GET['order_id'] ?? 0);

    if ($order_id <= 0) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Order ID required'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM order_groups
        WHERE order_id = ?
        LIMIT 1
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Order not found'
        ]);
        exit;
    }

    if (($user['role'] ?? '') === 'customer' && (int)$order['customer_id'] !== (int)$user['user_id']) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Forbidden'
        ]);
        exit;
    }

    $itemStmt = $pdo->prepare("
        SELECT
            order_item_id,
            order_id,
            product_id,
            product_name,
            product_price,
            quantity,
            subtotal
        FROM order_items
        WHERE order_id = ?
        ORDER BY order_item_id ASC
    ");
    $itemStmt->execute([$order_id]);
    $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode($order, JSON_UNESCAPED_UNICODE);
    exit;
    
} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>