<?php
ob_start();

require 'config.php';
require 'helpers.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $user = requireRole('customer');
    $customer_id = (int)$user['user_id'];

    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);

    if ($product_id <= 0) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Product ID is required.'
        ]);
        exit;
    }

    if ($quantity <= 0) {
        $quantity = 1;
    }

    $stmt = $pdo->prepare("
        SELECT product_id, product_name, product_stock
        FROM products
        WHERE product_id = ?
        LIMIT 1
    ");
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

    if ((int)$product['product_stock'] <= 0) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'This product is out of stock.'
        ]);
        exit;
    }

    $check = $pdo->prepare("
        SELECT cart_id, quantity
        FROM cart_items
        WHERE customer_id = ? AND product_id = ?
        LIMIT 1
    ");
    $check->execute([$customer_id, $product_id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $newQty = (int)$existing['quantity'] + $quantity;

        if ($newQty > (int)$product['product_stock']) {
            $newQty = (int)$product['product_stock'];
        }

        $update = $pdo->prepare("
            UPDATE cart_items
            SET quantity = ?, updated_at = NOW()
            WHERE cart_id = ?
        ");
        $update->execute([$newQty, $existing['cart_id']]);

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Cart updated successfully.'
        ]);
        exit;
    }

    if ($quantity > (int)$product['product_stock']) {
        $quantity = (int)$product['product_stock'];
    }

    $insert = $pdo->prepare("
        INSERT INTO cart_items (customer_id, product_id, quantity)
        VALUES (?, ?, ?)
    ");
    $insert->execute([$customer_id, $product_id, $quantity]);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Added to cart successfully.'
    ]);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Add to cart error: ' . $e->getMessage()
    ]);
    exit;
}
?>