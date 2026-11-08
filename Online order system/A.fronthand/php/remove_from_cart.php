<?php
ob_start();

require 'config.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $user = requireRole('customer');
    $customer_id = (int)$user['user_id'];

    $cart_id = (int)($_POST['cart_id'] ?? 0);

    if ($cart_id <= 0) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Cart ID is required.'
        ]);
        exit;
    }

    $delete = $pdo->prepare("
        DELETE FROM cart_items
        WHERE cart_id = ? AND customer_id = ?
    ");
    $delete->execute([$cart_id, $customer_id]);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Item removed from cart.'
    ]);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Remove cart item error: ' . $e->getMessage()
    ]);
    exit;
}
?>