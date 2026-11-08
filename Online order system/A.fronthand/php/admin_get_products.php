<?php
ob_start();
require 'config.php';
require 'helpers.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireRole('admin');

    $stmt = $pdo->query("
        SELECT 
            p.product_id,
            p.product_name,
            p.product_price,
            p.product_rating,
            p.product_stock,
            p.publish_date,
            p.image_path,
            p.category,
            COALESCE(
                (
                    SELECT pm.media_path
                    FROM product_media pm
                    WHERE pm.product_id = p.product_id
                      AND pm.media_type = 'image'
                    ORDER BY pm.sort_order ASC, pm.media_id ASC
                    LIMIT 1
                ),
                p.image_path
            ) AS cover_image
        FROM products p
        ORDER BY p.product_id DESC
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode($rows);
    exit;
} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Admin products error: ' . $e->getMessage()
    ]);
    exit;
}
?>