<?php
ob_start();

require 'config.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $admin = requireRole('admin');

    $admin_id = (int)$admin['user_id'];
    $user_id = (int)($_POST['user_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    if ($user_id <= 0) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'User ID is required.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT user_id, email, role
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'User not found.'
        ]);
        exit;
    }

    if ($user['role'] === 'admin') {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Admin account cannot be blacklisted here.'
        ]);
        exit;
    }

    $email = strtolower(trim($user['email']));

    $check = $pdo->prepare("
        SELECT blacklist_id
        FROM user_blacklist
        WHERE LOWER(email) = LOWER(?)
          AND is_active = 1
        LIMIT 1
    ");
    $check->execute([$email]);

    if ($check->fetch()) {
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'This user is already blacklisted.'
        ]);
        exit;
    }

    $insert = $pdo->prepare("
        INSERT INTO user_blacklist (
            user_id, email, reason, is_active, blacklisted_by
        ) VALUES (?, ?, ?, 1, ?)
    ");
    $insert->execute([
        $user_id,
        $email,
        $reason === '' ? null : $reason,
        $admin_id
    ]);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'User has been blacklisted successfully.'
    ]);
    exit;
} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Blacklist user error: ' . $e->getMessage()
    ]);
    exit;
}
?>