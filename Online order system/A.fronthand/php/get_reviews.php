<?php
ob_start();
require 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $product_id = (int)($_GET['product_id'] ?? 0);

    if ($product_id <= 0) {
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'rows' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT 
            pr.rating,
            pr.comment,
            pr.created_at,
            COALESCE(u.full_name, 'Customer') AS full_name
        FROM product_reviews pr
        LEFT JOIN users u ON pr.user_id = u.user_id
        WHERE pr.product_id = ?
        ORDER BY pr.created_at DESC
        LIMIT 3
    ");
    $stmt->execute([$product_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'rows' => $rows
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'rows' => [],
        'message' => 'Get reviews error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>