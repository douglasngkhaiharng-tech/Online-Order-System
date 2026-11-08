<?php
ob_start();
require 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $product_id = $_GET['product_id'] ?? '';

    if ($product_id === '') {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Product ID is required.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT media_path, media_type, sort_order
        FROM product_media
        WHERE product_id = ?
        ORDER BY sort_order ASC, media_id ASC
    ");
    $stmt->execute([$product_id]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'rows' => $rows
    ]);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Media error: ' . $e->getMessage()
    ]);
    exit;
}
?>