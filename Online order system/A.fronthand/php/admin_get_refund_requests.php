<?php
ob_start();

require 'config.php';
require 'helpers.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireRole('admin');

    $stmt = $pdo->query("
        SELECT
            order_id,
            customer_id,
            total_amount,
            total_items,
            refund_uploaded_at,
            refund_proof_path,
            status
        FROM order_groups
        WHERE status = 'refund_pending'
        ORDER BY refund_uploaded_at DESC, order_id DESC
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'rows' => $rows
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'rows' => [],
        'message' => 'Refund requests error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>