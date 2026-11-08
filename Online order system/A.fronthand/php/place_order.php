<?php
ob_start();

require 'config.php';
require 'helpers.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireRole('customer');

    $user = $_SESSION['user'];
    $customer_id = $user['user_id'];

    $product_id = $_POST['product_id'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 0);

    if ($product_id === '' || $quantity <= 0) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Missing product or invalid quantity.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
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

    if ((int)$product['product_stock'] < $quantity) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Not enough stock.'
        ]);
        exit;
    }

    $product_name = $product['product_name'];
    $product_price = (float)$product['product_price'];
    $total_amount = $product_price * $quantity;

    $pdo->beginTransaction();

    $insert = $pdo->prepare("
        INSERT INTO orders (
            customer_id,
            product_id,
            product_name,
            product_price,
            quantity,
            total_amount,
            status,
            ordered_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'unpaid', NOW())
    ");

    $insert->execute([
        $customer_id,
        $product_id,
        $product_name,
        $product_price,
        $quantity,
        $total_amount
    ]);

    $updateStock = $pdo->prepare("
        UPDATE products
        SET product_stock = product_stock - ?
        WHERE product_id = ?
    ");
    $updateStock->execute([$quantity, $product_id]);

    $pdo->commit();

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully.'
    ]);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    exit;
}
?>