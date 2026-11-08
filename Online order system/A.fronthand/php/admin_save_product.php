<?php
ob_start();

require 'config.php';
require 'helpers.php';
require 'require_auth.php';
require 'product_media_upload_helper.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireRole('admin');

    $product_id = $_POST['product_id'] ?? '';
    $product_name = trim($_POST['product_name'] ?? '');
    $product_price = $_POST['product_price'] ?? '';
    $product_rating = $_POST['product_rating'] ?? '';
    $product_stock = $_POST['product_stock'] ?? '';
    $publish_date = $_POST['publish_date'] ?? '';
    $category = trim($_POST['category'] ?? '');

    if (
        $product_name === '' ||
        $product_price === '' ||
        $product_rating === '' ||
        $product_stock === '' ||
        $publish_date === '' ||
        $category === ''
    ) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all required fields.'
        ]);
        exit;
    }

    $product_rating = floatval($product_rating);
    if ($product_rating < 0.1 || $product_rating > 9.9) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Rating must be between 0.1 and 9.9'
        ]);
        exit;
    }

    $projectRoot = dirname(__DIR__);
    $pdo->beginTransaction();

    if ($product_id !== '') {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            $pdo->rollBack();
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Product not found.'
            ]);
            exit;
        }

        $update = $pdo->prepare("
            UPDATE products
            SET product_name = ?, product_price = ?, product_rating = ?, product_stock = ?, publish_date = ?, category = ?
            WHERE product_id = ?
        ");
        $update->execute([
            $product_name,
            $product_price,
            $product_rating,
            $product_stock,
            $publish_date,
            $category,
            $product_id
        ]);

        if (isset($_FILES['media_files']) && !empty($_FILES['media_files']['name'][0])) {
            saveProductMediaFiles($pdo, (int)$product_id, $_FILES['media_files'], $projectRoot);
        }

        $coverStmt = $pdo->prepare("
            SELECT media_path
            FROM product_media
            WHERE product_id = ? AND media_type = 'image'
            ORDER BY sort_order ASC, media_id ASC
            LIMIT 1
        ");
        $coverStmt->execute([$product_id]);
        $cover = $coverStmt->fetch(PDO::FETCH_ASSOC);

        if ($cover && !empty($cover['media_path'])) {
            $coverUpdate = $pdo->prepare("UPDATE products SET image_path = ? WHERE product_id = ?");
            $coverUpdate->execute([$cover['media_path'], $product_id]);
        }

        $pdo->commit();

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Product updated successfully.'
        ]);
        exit;
    }

    $insert = $pdo->prepare("
        INSERT INTO products (
            product_name,
            product_price,
            product_rating,
            product_stock,
            publish_date,
            image_path,
            category
        ) VALUES (?, ?, ?, ?, ?, '', ?)
    ");
    $insert->execute([
        $product_name,
        $product_price,
        $product_rating,
        $product_stock,
        $publish_date,
        $category
    ]);

    $newProductId = (int)$pdo->lastInsertId();

    $savedMedia = [];
    if (isset($_FILES['media_files']) && !empty($_FILES['media_files']['name'][0])) {
        $savedMedia = saveProductMediaFiles($pdo, $newProductId, $_FILES['media_files'], $projectRoot);
    }

    $coverImage = '';
    foreach ($savedMedia as $media) {
        if ($media['media_type'] === 'image') {
            $coverImage = $media['media_path'];
            break;
        }
    }

    if ($coverImage !== '') {
        $coverUpdate = $pdo->prepare("UPDATE products SET image_path = ? WHERE product_id = ?");
        $coverUpdate->execute([$coverImage, $newProductId]);
    }

    $pdo->commit();

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Product added successfully.'
    ]);
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    exit;
}
?>