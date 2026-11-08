<?php
ob_start();

require 'config.php';
require 'helpers.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $user = requireRole('customer');
    $user_id = (int)$user['user_id'];

    $product_id = (int)($_POST['product_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($product_id <= 0) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Product ID is required.'
        ]);
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Rating must be between 1 and 5.'
        ]);
        exit;
    }

    if ($comment === '') {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Please write a comment.'
        ]);
        exit;
    }

    if (mb_strlen($comment) > 1000) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Comment is too long.'
        ]);
        exit;
    }

    $productStmt = $pdo->prepare("
        SELECT product_id
        FROM products
        WHERE product_id = ?
        LIMIT 1
    ");
    $productStmt->execute([$product_id]);

    if (!$productStmt->fetch()) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Product not found.'
        ]);
        exit;
    }

    $pdo->beginTransaction();

    $existingStmt = $pdo->prepare("
        SELECT 1
        FROM product_reviews
        WHERE user_id = ? AND product_id = ?
        LIMIT 1
    ");
    $existingStmt->execute([$user_id, $product_id]);
    $exists = $existingStmt->fetchColumn();

    if ($exists) {
        $updateStmt = $pdo->prepare("
            UPDATE product_reviews
            SET rating = ?, comment = ?, created_at = NOW()
            WHERE user_id = ? AND product_id = ?
        ");
        $updateStmt->execute([$rating, $comment, $user_id, $product_id]);
    } else {
        $insertStmt = $pdo->prepare("
            INSERT INTO product_reviews (product_id, user_id, rating, comment, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $insertStmt->execute([$product_id, $user_id, $rating, $comment]);
    }

    $pdo->commit();

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Review submitted successfully.'
    ]);
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Submit review error: ' . $e->getMessage()
    ]);
    exit;
}
?>