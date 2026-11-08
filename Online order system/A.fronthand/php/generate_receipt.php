<?php
require 'config.php';
require 'require_auth.php';
require __DIR__ . '/fpdf/fpdf.php';

$user = requireRole('customer');
$customer_id = (int)$user['user_id'];

$order_id = (int)($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    die('Invalid order ID');
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
    die('Order not found');
}

$itemStmt = $pdo->prepare("
    SELECT
        product_name,
        product_price,
        quantity,
        subtotal
    FROM order_items
    WHERE order_id = ?
    ORDER BY order_item_id ASC
");
$itemStmt->execute([$order_id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

$pdf = new FPDF();
$pdf->AddPage();

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Douglas Online Order System Sdn. Bhd.', 0, 1, 'C');
$pdf->Cell(0, 10, 'E-Receipt', 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 8, 'Order ID:', 0, 0);
$pdf->Cell(0, 8, $order['order_id'], 0, 1);

$pdf->Cell(50, 8, 'Status:', 0, 0);
$pdf->Cell(0, 8, $order['status'], 0, 1);

$pdf->Cell(50, 8, 'Total Items:', 0, 0);
$pdf->Cell(0, 8, $order['total_items'], 0, 1);

$pdf->Cell(50, 8, 'Total Amount:', 0, 0);
$pdf->Cell(0, 8, 'RM ' . number_format((float)$order['total_amount'], 2), 0, 1);

$pdf->Cell(50, 8, 'Payment Time:', 0, 0);
$pdf->Cell(0, 8, $order['payment_uploaded_at'] ?? '-', 0, 1);

$pdf->Cell(50, 8, 'Approved Time:', 0, 0);
$pdf->Cell(0, 8, $order['approved_at'] ?? '-', 0, 1);

$pdf->Ln(8);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(80, 8, 'Product', 1, 0, 'C');
$pdf->Cell(30, 8, 'Price', 1, 0, 'C');
$pdf->Cell(30, 8, 'Qty', 1, 0, 'C');
$pdf->Cell(40, 8, 'Subtotal', 1, 1, 'C');

$pdf->SetFont('Arial', '', 11);

foreach ($items as $item) {
    $pdf->Cell(80, 8, $item['product_name'], 1, 0);
    $pdf->Cell(30, 8, 'RM ' . number_format((float)$item['product_price'], 2), 1, 0, 'R');
    $pdf->Cell(30, 8, $item['quantity'], 1, 0, 'C');
    $pdf->Cell(40, 8, 'RM ' . number_format((float)$item['subtotal'], 2), 1, 1, 'R');
}

$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Thank you for your purchase!', 0, 1, 'C');

$pdf->Output('D', 'receipt_' . $order_id . '.pdf');
exit;
?>