<?php
require 'config.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $user = requireRole('customer');
    $user_id = (int)$user['user_id'];
    $product_id = (int)($_POST['product_id'] ?? 0);

    if ($product_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Product ID required'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        DELETE FROM wishlist
        WHERE user_id = ? AND product_id = ?
    ");
    $stmt->execute([$user_id, $product_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Removed from wishlist successfully'
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Wishlist remove error: ' . $e->getMessage()
    ]);
}
?>