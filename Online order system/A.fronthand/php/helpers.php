<?php

function jsonResponse($data)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function requireFields($fields, $source)
{
    foreach ($fields as $field) {
        if (!isset($source[$field]) || trim((string)$source[$field]) === '') {
            jsonResponse([
                'success' => false,
                'message' => "Missing field: {$field}"
            ]);
        }
    }
}

function ensureDirectory($dir)
{
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function getProjectRootPath()
{
    return dirname(__DIR__);
}

function resolveDefaultAvatarPath()
{
    $projectRoot = getProjectRootPath();

    $candidates = [
        DEFAULT_AVATAR,
        'uploads/default.jpg',
        'uploads/default.jpeg',
        'uploads/default.webp',
        'uploads/default.gif',
        'uploads/default'
    ];

    foreach ($candidates as $candidate) {
        $fullPath = $projectRoot . '/' . $candidate;
        if (is_file($fullPath)) {
            return str_replace('\\', '/', $candidate);
        }
    }

    return DEFAULT_AVATAR;
}

function resolveAdminAvatarPath()
{
    $projectRoot = getProjectRootPath();

    $candidates = [
        ADMIN_DEFAULT_AVATAR,
        'uploads/admin-default.jpg',
        'uploads/admin-default.jpeg',
        'uploads/admin-default.webp',
        'uploads/admin-default.gif'
    ];

    foreach ($candidates as $candidate) {
        $fullPath = $projectRoot . '/' . $candidate;
        if (is_file($fullPath)) {
            return str_replace('\\', '/', $candidate);
        }
    }

    return resolveDefaultAvatarPath();
}

function normalizeRelativePath($path)
{
    $path = trim((string)$path);
    $path = str_replace('\\', '/', $path);
    $path = preg_replace('/\?.*$/', '', $path);
    $path = ltrim($path, '/');

    return $path;
}

function isDefaultAvatarPath($path)
{
    $path = strtolower(normalizeRelativePath($path));

    $defaultCandidates = [
        strtolower(normalizeRelativePath(DEFAULT_AVATAR)),
        'uploads/default.jpg',
        'uploads/default.jpeg',
        'uploads/default.webp',
        'uploads/default.gif',
        strtolower(normalizeRelativePath(ADMIN_DEFAULT_AVATAR)),
        'uploads/admin-default.jpg',
        'uploads/admin-default.jpeg',
        'uploads/admin-default.webp',
        'uploads/admin-default.gif'
    ];

    return $path === '' || in_array($path, $defaultCandidates, true);
}

function isValidUserUploadedAvatar($path)
{
    $path = normalizeRelativePath($path);

    if ($path === '') {
        return false;
    }

    if (stripos($path, 'uploads/avatars/') !== 0) {
        return false;
    }

    $fullPath = getProjectRootPath() . '/' . $path;

    return is_file($fullPath);
}

function normalizeAvatarPath($path, $role = 'customer')
{
    $path = normalizeRelativePath($path);
    $role = strtolower(trim((string)$role));

    if ($path === '') {
        return $role === 'admin' ? resolveAdminAvatarPath() : resolveDefaultAvatarPath();
    }

    if (isValidUserUploadedAvatar($path)) {
        return $path;
    }

    if (isDefaultAvatarPath($path)) {
        return $role === 'admin' ? resolveAdminAvatarPath() : resolveDefaultAvatarPath();
    }

    return $role === 'admin' ? resolveAdminAvatarPath() : resolveDefaultAvatarPath();
}

function uploadFile(
    $file,
    $uploadDir,
    $dbPathPrefix,
    array $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'],
    $maxSize = 2 * 1024 * 1024
) {
    if (!isset($file) || !is_array($file)) {
        return [
            'success' => false,
            'message' => 'No file uploaded.'
        ];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => 'File upload error.'
        ];
    }

    if (($file['size'] ?? 0) <= 0) {
        return [
            'success' => false,
            'message' => 'Uploaded file is empty.'
        ];
    }

    if (($file['size'] ?? 0) > $maxSize) {
        return [
            'success' => false,
            'message' => 'File too large (max 2MB).'
        ];
    }

    $tmpPath = $file['tmp_name'] ?? '';
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return [
            'success' => false,
            'message' => 'Invalid uploaded file.'
        ];
    }

    $mimeType = mime_content_type($tmpPath);
    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        return [
            'success' => false,
            'message' => 'Only JPG, PNG, WEBP allowed.'
        ];
    }

    ensureDirectory($uploadDir);

    $ext = strtolower(pathinfo($file['name'] ?? 'file', PATHINFO_EXTENSION));
    if ($ext === '') {
        $ext = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'bin'
        };
    }

    $fileName = uniqid('img_', true) . '.' . $ext;
    $fullPath = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($tmpPath, $fullPath)) {
        return [
            'success' => false,
            'message' => 'Failed to move uploaded file.'
        ];
    }

    return [
        'success' => true,
        'path' => rtrim(str_replace('\\', '/', $dbPathPrefix), '/\\') . '/' . $fileName
    ];
}

function deleteFileIfExists($filePath)
{
    if ($filePath && file_exists($filePath) && is_file($filePath)) {
        unlink($filePath);
    }
}

function getManagerAdminUserId(PDO $pdo): int
{
    $stmt = $pdo->query("
        SELECT user_id
        FROM users
        WHERE role = 'admin'
        ORDER BY user_id ASC
        LIMIT 1
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? (int)$row['user_id'] : 0;
}
?>