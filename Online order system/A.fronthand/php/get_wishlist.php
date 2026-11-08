<?php
require 'config.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $user = requireRole('customer');
    $user_id = (int)$user['user_id'];

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
                'uploads/default.png'
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
        FROM wishlist w
        JOIN products p ON w.product_id = p.product_id
        WHERE w.user_id = ?
        ORDER BY w.id DESC
    ");
    $stmt->execute([$user_id]);

    echo json_encode([
        'success' => true,
        'rows' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'rows' => [],
        'message' => 'Wishlist error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>