<?php
ob_start();

require 'config.php';
require 'helpers.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireRole('customer');

    $user = $_SESSION['user'];
    $customer_id = (int)$user['user_id'];

    $order_id = (int)($_POST['order_id'] ?? 0);

    if ($order_id <= 0) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Order ID is required.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM order_groups
        WHERE order_id = ? AND customer_id = ?
        LIMIT 1
    ");
    $stmt->execute([$order_id, $customer_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Order not found.'
        ]);
        exit;
    }

    if ($order['status'] !== 'unpaid') {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Only unpaid orders can be cancelled.'
        ]);
        exit;
    }

    $itemsStmt = $pdo->prepare("
        SELECT product_id, quantity
        FROM order_items
        WHERE order_id = ?
    ");
    $itemsStmt->execute([$order_id]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    $pdo->beginTransaction();

    $restoreStock = $pdo->prepare("
        UPDATE products
        SET product_stock = product_stock + ?
        WHERE product_id = ?
    ");

    foreach ($items as $item) {
        $restoreStock->execute([
            $item['quantity'],
            $item['product_id']
        ]);
    }

    $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order_id]);
    $pdo->prepare("DELETE FROM order_groups WHERE order_id = ?")->execute([$order_id]);

    $pdo->commit();

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Order cancelled successfully.'
    ]);
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Cancel error: ' . $e->getMessage()
    ]);
    exit;
}
?>