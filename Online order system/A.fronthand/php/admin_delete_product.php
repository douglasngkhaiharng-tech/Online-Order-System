<?php
ob_start();

require 'config.php';
require 'helpers.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireRole('admin');

    $product_id = (int)($_POST['product_id'] ?? 0);

    if ($product_id <= 0) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Product ID is required.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ? LIMIT 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Product not found.'
        ]);
        exit;
    }

    $mediaStmt = $pdo->prepare("
        SELECT media_path
        FROM product_media
        WHERE product_id = ?
    ");
    $mediaStmt->execute([$product_id]);
    $mediaRows = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

    $pdo->beginTransaction();

    $pdo->prepare("DELETE FROM product_media WHERE product_id = ?")->execute([$product_id]);
    $pdo->prepare("DELETE FROM wishlist WHERE product_id = ?")->execute([$product_id]);
    $pdo->prepare("DELETE FROM product_reviews WHERE product_id = ?")->execute([$product_id]);

    $delete = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
    $delete->execute([$product_id]);

    $pdo->commit();

    if (!empty($product['image_path'])) {
        deleteFileIfExists(dirname(__DIR__) . '/' . $product['image_path']);
    }

    foreach ($mediaRows as $row) {
        if (!empty($row['media_path'])) {
            deleteFileIfExists(dirname(__DIR__) . '/' . $row['media_path']);
        }
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Product deleted successfully.'
    ]);
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Delete product error: ' . $e->getMessage()
    ]);
    exit;
}
?>