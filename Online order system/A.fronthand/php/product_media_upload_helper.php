<?php

function saveProductMediaFiles(PDO $pdo, int $productId, array $files, string $projectRoot): array
{
    $saved = [];

    if (
        !isset($files['name']) ||
        !is_array($files['name']) ||
        count($files['name']) === 0
    ) {
        return $saved;
    }

    $uploadFolder = $projectRoot . '/uploads/product_media';
    $dbFolder = 'uploads/product_media';

    if (!is_dir($uploadFolder)) {
        mkdir($uploadFolder, 0777, true);
    }

    $allowedImageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedVideoExt = ['mp4', 'webm', 'ogg', 'mov'];

    $insert = $pdo->prepare("
        INSERT INTO product_media (product_id, media_path, media_type, sort_order)
        VALUES (?, ?, ?, ?)
    ");

    $sortOrder = 0;

    foreach ($files['name'] as $index => $originalName) {
        if (($files['error'][$index] ?? 1) !== 0) {
            continue;
        }

        $tmpName = $files['tmp_name'][$index] ?? '';
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            continue;
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mediaType = null;

        if (in_array($ext, $allowedImageExt, true)) {
            $mediaType = 'image';
        } elseif (in_array($ext, $allowedVideoExt, true)) {
            $mediaType = 'video';
        } else {
            continue;
        }

        $newName = uniqid('pm_', true) . '.' . $ext;
        $fullPath = $uploadFolder . '/' . $newName;
        $dbPath = $dbFolder . '/' . $newName;

        if (!move_uploaded_file($tmpName, $fullPath)) {
            continue;
        }

        $insert->execute([
            $productId,
            $dbPath,
            $mediaType,
            $sortOrder
        ]);

        $saved[] = [
            'media_path' => $dbPath,
            'media_type' => $mediaType,
            'sort_order' => $sortOrder
        ];

        $sortOrder++;
    }

    return $saved;
}