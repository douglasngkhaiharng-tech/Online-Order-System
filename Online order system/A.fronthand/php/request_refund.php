<?php
ob_start();

require 'config.php';
require 'helpers.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $user = requireRole('customer');
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

    if (!isset($_FILES['refund_proof']) || ($_FILES['refund_proof']['error'] ?? 4) !== 0) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Please upload refund proof.'
        ]);
        exit;
    }

    // ✅ 改成查 order_groups，不是旧 orders
    $stmt = $pdo->prepare("
        SELECT order_id, customer_id, status
        FROM order_groups
        WHERE order_id = ?
          AND customer_id = ?
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

    if ($order['status'] !== 'paid') {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Only paid orders can request refund.'
        ]);
        exit;
    }

    $uploadDir = dirname(__DIR__) . '/uploads/refunds';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $file = $_FILES['refund_proof'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed, true)) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Only JPG, JPEG, PNG, WEBP allowed.'
        ]);
        exit;
    }

    $filename = 'refund_' . $order_id . '_' . time() . '.' . $ext;
    $targetPath = $uploadDir . '/' . $filename;
    $dbPath = 'uploads/refunds/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to upload refund proof.'
        ]);
        exit;
    }

    $update = $pdo->prepare("
        UPDATE order_groups
        SET
            status = 'refund_pending',
            refund_proof_path = ?,
            refund_uploaded_at = NOW()
        WHERE order_id = ?
          AND customer_id = ?
    ");
    $update->execute([$dbPath, $order_id, $customer_id]);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Refund request submitted successfully.'
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Refund request error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>