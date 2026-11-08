<?php
ob_start();

require 'config.php';
require 'helpers.php';
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
            p.product_stock
        FROM cart_items c
        JOIN products p ON c.product_id = p.product_id
        WHERE c.customer_id = ?
        ORDER BY c.cart_id ASC
    ");
    $stmt->execute([$customer_id]);
    $cartRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$cartRows) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Your cart is empty.'
        ]);
        exit;
    }

    $totalAmount = 0;
    $totalItems = 0;

    foreach ($cartRows as $item) {
        if ((int)$item['quantity'] <= 0) {
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Invalid quantity found in cart.'
            ]);
            exit;
        }

        if ((int)$item['product_stock'] < (int)$item['quantity']) {
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Not enough stock for ' . $item['product_name']
            ]);
            exit;
        }

        $subtotal = (float)$item['product_price'] * (int)$item['quantity'];
        $totalAmount += $subtotal;
        $totalItems += (int)$item['quantity'];
    }

    $pdo->beginTransaction();

    $insertOrder = $pdo->prepare("
        INSERT INTO order_groups (
            customer_id,
            total_amount,
            total_items,
            status,
            ordered_at
        ) VALUES (?, ?, ?, 'unpaid', NOW())
    ");
    $insertOrder->execute([
        $customer_id,
        number_format($totalAmount, 2, '.', ''),
        $totalItems
    ]);

    $orderId = (int)$pdo->lastInsertId();

    $insertItem = $pdo->prepare("
        INSERT INTO order_items (
            order_id,
            product_id,
            product_name,
            product_price,
            quantity,
            subtotal
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    $updateStock = $pdo->prepare("
        UPDATE products
        SET product_stock = product_stock - ?
        WHERE product_id = ?
    ");

    foreach ($cartRows as $item) {
        $subtotal = (float)$item['product_price'] * (int)$item['quantity'];

        $insertItem->execute([
            $orderId,
            $item['product_id'],
            $item['product_name'],
            $item['product_price'],
            $item['quantity'],
            number_format($subtotal, 2, '.', '')
        ]);

        $updateStock->execute([
            $item['quantity'],
            $item['product_id']
        ]);
    }

    $clearCart = $pdo->prepare("DELETE FROM cart_items WHERE customer_id = ?");
    $clearCart->execute([$customer_id]);

    $pdo->commit();

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Checkout successful.',
        'order_id' => $orderId
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Checkout cart error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>