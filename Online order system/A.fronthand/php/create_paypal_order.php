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

    if ($local_order_id <= 0) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Local order ID is required.'
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
            'message' => 'Only unpaid orders can use PayPal payment.'
        ]);
        exit;
    }

    $amountValue = number_format((float)$order['total_amount'], 2, '.', '');
    $accessToken = getPayPalAccessToken();

    $payload = [
        'intent' => 'CAPTURE',
        'purchase_units' => [
            [
                'reference_id' => 'LOCAL_ORDER_' . $order['order_id'],
                'custom_id' => (string)$order['order_id'],
                'description' => 'Grouped order #' . $order['order_id'],
                'amount' => [
                    'currency_code' => PAYPAL_CURRENCY,
                    'value' => $amountValue
                ]
            ]
        ]
    ];

    $paypal = paypalApiRequest('POST', '/v2/checkout/orders', $payload, $accessToken);

    if ($paypal['http_code'] < 200 || $paypal['http_code'] >= 300 || empty($paypal['data']['id'])) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create PayPal order.',
            'paypal' => $paypal['data']
        ]);
        exit;
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'paypal_order_id' => $paypal['data']['id']
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Create PayPal order error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>