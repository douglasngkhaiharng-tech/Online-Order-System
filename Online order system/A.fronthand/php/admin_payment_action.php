<?php
ob_start();

require 'config.php';
require 'helpers.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireRole('admin');

    $user = $_SESSION['user'];
    $admin_id = $user['user_id'];

    $order_id = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($order_id <= 0 || $action === '') {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM order_groups WHERE order_id = ?");
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

    if ($order['status'] !== 'pending') {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Only pending orders can be processed.'
        ]);
        exit;
    }

    if ($action === 'approve') {
        $update = $pdo->prepare("
            UPDATE order_groups
            SET
                status = 'paid',
                approved_at = NOW(),
                approved_by = ?
            WHERE order_id = ?
        ");
        $update->execute([$admin_id, $order_id]);

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Payment approved successfully.'
        ]);
        exit;
    }

    if ($action === 'reject') {
        if (!empty($order['payment_proof_path'])) {
            $fullPath = dirname(__DIR__) . '/' . $order['payment_proof_path'];
            deleteFileIfExists($fullPath);
        }

        $update = $pdo->prepare("
            UPDATE order_groups
            SET
                status = 'unpaid',
                payment_proof_path = NULL,
                payment_uploaded_at = NULL,
                approved_at = NULL,
                approved_by = NULL
            WHERE order_id = ?
        ");
        $update->execute([$order_id]);

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Payment rejected and proof deleted.'
        ]);
        exit;
    }

    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action.'
    ]);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Admin payment action error: ' . $e->getMessage()
    ]);
    exit;
}
?>