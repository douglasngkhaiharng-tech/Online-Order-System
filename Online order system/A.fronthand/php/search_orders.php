<?php
require 'config.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $keyword = trim($_GET['keyword'] ?? '');
    $scope = $_GET['scope'] ?? 'customer';

    if ($keyword === '') {
        echo json_encode([
            'success' => false,
            'rows' => [],
            'message' => 'Please enter keyword'
        ]);
        exit;
    }

    if ($scope === 'customer') {
        $user = requireRole('customer');
        $user_id = (int)$user['user_id'];

        $stmt = $pdo->prepare("
            SELECT
                og.order_id,
                og.customer_id,
                og.total_items,
                og.total_amount,
                og.status,
                og.ordered_at
            FROM order_groups og
            WHERE og.customer_id = ?
              AND (
                    CAST(og.order_id AS CHAR) LIKE ?
                 OR CAST(og.customer_id AS CHAR) LIKE ?
              )
            ORDER BY og.order_id DESC
        ");
        $stmt->execute([
            $user_id,
            "%$keyword%",
            "%$keyword%"
        ]);
    } else {
        requireRole('admin');

        $stmt = $pdo->prepare("
            SELECT
                og.order_id,
                og.customer_id,
                og.total_items,
                og.total_amount,
                og.status,
                og.ordered_at,
                og.payment_uploaded_at,
                og.approved_at,
                og.approved_by,
                COALESCE(u.full_name, '-') AS customer_name
            FROM order_groups og
            LEFT JOIN users u ON og.customer_id = u.user_id
            WHERE
                CAST(og.customer_id AS CHAR) LIKE ?
                OR u.full_name LIKE ?
                OR CAST(og.approved_by AS CHAR) LIKE ?
                OR CAST(og.order_id AS CHAR) LIKE ?
            ORDER BY og.order_id DESC
        ");
        $stmt->execute([
            "%$keyword%",
            "%$keyword%",
            "%$keyword%",
            "%$keyword%"
        ]);
    }

    echo json_encode([
        'success' => true,
        'rows' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'rows' => [],
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>