<?php
ob_start();

require 'config.php';
require 'helpers.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $user = requireRole('customer');
    $customer_id = (int)$user['user_id'];

    $cart_id = (int)($_POST['cart_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);

    if ($cart_id <= 0) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Cart ID is required.'
        ]);
        exit;
    }

    if ($quantity <= 0) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Quantity must be at least 1.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT c.cart_id, c.product_id, p.product_stock
        FROM cart_items c
        JOIN products p ON c.product_id = p.product_id
        WHERE c.cart_id = ? AND c.customer_id = ?
        LIMIT 1
    ");
    $stmt->execute([$cart_id, $customer_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Cart item not found.'
        ]);
        exit;
    }

    if ($quantity > (int)$row['product_stock']) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Quantity exceeds stock.'
        ]);
        exit;
    }

    $update = $pdo->prepare("
        UPDATE cart_items
        SET quantity = ?, updated_at = NOW()
        WHERE cart_id = ? AND customer_id = ?
    ");
    $update->execute([$quantity, $cart_id, $customer_id]);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Cart quantity updated.'
    ]);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Update cart error: ' . $e->getMessage()
    ]);
    exit;
}
?>