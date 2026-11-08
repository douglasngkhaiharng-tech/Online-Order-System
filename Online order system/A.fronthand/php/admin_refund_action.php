<?php
ob_start();

require 'config.php';
require 'helpers.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $admin = requireRole('admin');
    $admin_id = (int)$admin['user_id'];

    $order_id = (int)($_POST['order_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');

    if ($order_id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT order_id, status
        FROM order_groups
        WHERE order_id = ?
        LIMIT 1
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Order not found.'
        ]);
        exit;
    }

    if ($order['status'] !== 'refund_pending') {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'This order is not pending refund.'
        ]);
        exit;
    }

    if ($action === 'approve') {
        $update = $pdo->prepare("
            UPDATE order_groups
            SET
                status = 'refunded',
                approved_at = NOW(),
                approved_by = ?
            WHERE order_id = ?
        ");
        $update->execute([$admin_id, $order_id]);

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Refund approved successfully.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $update = $pdo->prepare("
        UPDATE order_groups
        SET
            status = 'paid'
        WHERE order_id = ?
    ");
    $update->execute([$order_id]);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Refund request rejected.'
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Refund action error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>