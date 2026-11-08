<?php
ob_start();

require 'config.php';
require 'helpers.php';
require 'require_auth.php';
require 'paypal_api_helper.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $user = requireRole('customer');
    $customer_id = (int)$user['user_id'];

    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);

    $local_order_id = (int)($json['local_order_id'] ?? 0);
    $paypal_order_id = trim((string)($json['paypal_order_id'] ?? ''));

    if ($local_order_id <= 0 || $paypal_order_id === '') {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Missing local order ID or PayPal order ID.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM order_groups
        WHERE order_id = ?
          AND customer_id = ?
        LIMIT 1
    ");
    $stmt->execute([$local_order_id, $customer_id]);
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
            'message' => 'This order is no longer unpaid.'
        ]);
        exit;
    }

    $accessToken = getPayPalAccessToken();

    $paypal = paypalApiRequest(
        'POST',
        '/v2/checkout/orders/' . urlencode($paypal_order_id) . '/capture',
        new stdClass(),
        $accessToken
    );

    if ($paypal['http_code'] < 200 || $paypal['http_code'] >= 300) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to capture PayPal payment.',
            'paypal' => $paypal['data']
        ]);
        exit;
    }

    $paypalData = $paypal['data'];
    $status = $paypalData['status'] ?? '';

    $captureStatus = '';
    $capturedAmount = null;
    $paypalCaptureId = null;

    if (!empty($paypalData['purchase_units'][0]['payments']['captures'][0])) {
        $capture = $paypalData['purchase_units'][0]['payments']['captures'][0];
        $captureStatus = $capture['status'] ?? '';
        $capturedAmount = $capture['amount']['value'] ?? null;
        $paypalCaptureId = $capture['id'] ?? null;
    }

    if ($status !== 'COMPLETED' && $captureStatus !== 'COMPLETED') {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'PayPal payment is not completed.',
            'paypal' => $paypalData
        ]);
        exit;
    }

    $expectedAmount = number_format((float)$order['total_amount'], 2, '.', '');
    if ($capturedAmount !== null && number_format((float)$capturedAmount, 2, '.', '') !== $expectedAmount) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Captured amount does not match local order amount.'
        ]);
        exit;
    }

    $pdo->beginTransaction();

    $update = $pdo->prepare("
        UPDATE order_groups
        SET
            status = 'paid',
            payment_uploaded_at = NOW(),
            approved_at = NOW(),
            approved_by = NULL,
            payment_proof_path = NULL
        WHERE order_id = ?
    ");
    $update->execute([$local_order_id]);

    $pdo->commit();

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'PayPal payment completed successfully.',
        'paypal_capture_id' => $paypalCaptureId
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Capture PayPal payment error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>