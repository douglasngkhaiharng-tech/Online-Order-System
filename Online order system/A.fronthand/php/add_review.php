<?php
require 'config.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireRole('customer');

    $user_id = $_SESSION['user_id'] ?? null;
    $product_id = $_POST['product_id'] ?? '';
    $rating = $_POST['rating'] ?? '';
    $comment = trim($_POST['comment'] ?? '');

    if (!$user_id) {
        echo json_encode([
            'success' => false,
            'message' => 'User not logged in'
        ]);
        exit;
    }

    if ($product_id === '' || $rating === '' || $comment === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Missing fields'
        ]);
        exit;
    }

    $rating = (int)$rating;

    if ($rating < 1 || $rating > 5) {
        echo json_encode([
            'success' => false,
            'message' => 'Rating must be 1 to 5'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO product_reviews (user_id, product_id, rating, comment)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $product_id, $rating, $comment]);

    echo json_encode([
        'success' => true,
        'message' => 'Review submitted ⭐'
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>