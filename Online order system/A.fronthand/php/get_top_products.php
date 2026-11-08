<?php
ob_start();
require 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $defaultAvatar = defined('DEFAULT_AVATAR') ? DEFAULT_AVATAR : 'uploads/default.png';

    $stmt = $pdo->prepare("
        SELECT 
            p.product_id,
            p.product_name,
            p.product_price,
            p.product_rating,
            p.product_stock,
            p.publish_date,
            p.image_path,
            p.category,
            COUNT(o.product_id) AS total_sold,
            COALESCE(
                (
                    SELECT pm.media_path
                    FROM product_media pm
                    WHERE pm.product_id = p.product_id
                      AND pm.media_type = 'image'
                    ORDER BY pm.sort_order ASC, pm.media_id ASC
                    LIMIT 1
                ),
                NULLIF(p.image_path, ''),
                ?
            ) AS cover_image,
            COALESCE((
                SELECT AVG(pr.rating)
                FROM product_reviews pr
                WHERE pr.product_id = p.product_id
            ), 0) AS avg_user_rating,
            COALESCE((
                SELECT COUNT(*)
                FROM product_reviews pr2
                WHERE pr2.product_id = p.product_id
            ), 0) AS review_count
        FROM products p
        LEFT JOIN orders o ON p.product_id = o.product_id
        GROUP BY 
            p.product_id,
            p.product_name,
            p.product_price,
            p.product_rating,
            p.product_stock,
            p.publish_date,
            p.image_path,
            p.category
        ORDER BY total_sold DESC, p.product_id DESC
        LIMIT 5
    ");

    $stmt->execute([$defaultAvatar]);
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
        'message' => 'Top products error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>