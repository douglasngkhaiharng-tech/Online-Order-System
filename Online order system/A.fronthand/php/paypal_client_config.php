<?php
require 'config.php';

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'success' => true,
    'client_id' => PAYPAL_CLIENT_ID,
    'currency' => PAYPAL_CURRENCY,
    'mode' => PAYPAL_MODE
], JSON_UNESCAPED_UNICODE);
exit;
?>