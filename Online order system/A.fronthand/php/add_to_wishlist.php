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

    $check = $pdo->prepare("
        SELECT id
        FROM wishlist
        WHERE user_id = ? AND product_id = ?
    ");
    $check->execute([$user_id, $product_id]);

    if ($check->fetch()) {
        echo json_encode([
            'success' => true,
            'message' => 'Already in wishlist'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO wishlist (user_id, product_id)
        VALUES (?, ?)
    ");
    $stmt->execute([$user_id, $product_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Added to wishlist ❤️'
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>