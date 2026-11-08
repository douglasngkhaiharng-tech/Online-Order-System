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

    $order_id = $_POST['order_id'] ?? '';

    if ($order_id === '') {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Order ID is required.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT * FROM orders
        WHERE order_id = ? AND customer_id = ?
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
            'message' => 'Only unpaid orders can upload payment proof.'
        ]);
        exit;
    }

    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== 0) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Please upload payment proof image.'
        ]);
        exit;
    }

    $projectRoot = dirname(__DIR__);
    $paymentUploadFolder = $projectRoot . '/uploads/payments';
    $paymentDbFolder = 'uploads/payments';

    $uploadResult = uploadFile($_FILES['payment_proof'], $paymentUploadFolder, $paymentDbFolder);

    if (!$uploadResult['success']) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => $uploadResult['message']
        ]);
        exit;
    }

    $paymentPath = $uploadResult['path'];

    $update = $pdo->prepare("
        UPDATE orders
        SET payment_proof_path = ?, payment_uploaded_at = NOW(), status = 'pending'
        WHERE order_id = ?
    ");
    $update->execute([$paymentPath, $order_id]);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Payment proof uploaded. Status changed to pending.'
    ]);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Submit payment error: ' . $e->getMessage()
    ]);
    exit;
}
?>