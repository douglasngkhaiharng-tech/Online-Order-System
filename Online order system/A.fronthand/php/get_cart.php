<?php
ob_start();

require 'config.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $user = requireRole('customer');
    $customer_id = (int)$user['user_id'];

    $stmt = $pdo->prepare("
        SELECT
            c.cart_id,
            c.product_id,
            c.quantity,
            p.product_name,
            p.product_price,
            p.product_stock,
            p.publish_date,
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
            ) AS cover_image
        FROM cart_items c
        JOIN products p ON c.product_id = p.product_id
        WHERE c.customer_id = ?
        ORDER BY c.cart_id DESC
    ");
    $stmt->execute([$customer_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_amount = 0;
    $total_items = 0;

    foreach ($rows as &$row) {
        $row['subtotal'] = (float)$row['product_price'] * (int)$row['quantity'];
        $total_amount += $row['subtotal'];
        $total_items += (int)$row['quantity'];
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'rows' => $rows,
        'summary' => [
            'total_amount' => number_format($total_amount, 2, '.', ''),
            'total_items' => $total_items
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'rows' => [],
        'summary' => [
            'total_amount' => '0.00',
            'total_items' => 0
        ],
        'message' => 'Get cart error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>