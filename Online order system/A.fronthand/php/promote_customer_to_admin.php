<?php
ob_start();

require 'config.php';
require 'helpers.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $admin = requireRole('admin');
    $admin_id = (int)$admin['user_id'];

    $user_id = (int)($_POST['user_id'] ?? 0);

    if ($user_id <= 0) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'User ID is required.'
        ]);
        exit;
    }

    if ($user_id === $admin_id) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'You cannot promote yourself.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT user_id, role, full_name, email
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

    if (($user['role'] ?? '') !== 'customer') {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Only customer can be promoted to admin.'
        ]);
        exit;
    }

    // 这里只检查“当前有效”的 blacklist
    $blacklistStmt = $pdo->prepare("
        SELECT blacklist_id
        FROM user_blacklist
        WHERE LOWER(email) = LOWER(?)
          AND is_active = 1
        LIMIT 1
    ");
    $blacklistStmt->execute([$user['email']]);

    if ($blacklistStmt->fetch()) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Blacklisted customer cannot be promoted to admin. Please unblacklist first.'
        ]);
        exit;
    }

    $newAdminAvatar = function_exists('resolveAdminAvatarPath')
        ? resolveAdminAvatarPath()
        : (defined('ADMIN_DEFAULT_AVATAR') ? ADMIN_DEFAULT_AVATAR : 'uploads/default.png');

    $pdo->beginTransaction();

    $updateUser = $pdo->prepare("
        UPDATE users
        SET role = 'admin',
            avatar = ?
        WHERE user_id = ?
    ");
    $updateUser->execute([$newAdminAvatar, $user_id]);

    $checkAdmin = $pdo->prepare("
        SELECT admin_id
        FROM admins
        WHERE admin_id = ?
        LIMIT 1
    ");
    $checkAdmin->execute([$user_id]);

    if (!$checkAdmin->fetch()) {
        $insertAdmin = $pdo->prepare("
            INSERT INTO admins (admin_id, admin_name)
            VALUES (?, ?)
        ");
        $insertAdmin->execute([$user_id, $user['full_name']]);
    }

    $deleteCustomer = $pdo->prepare("
        DELETE FROM customers
        WHERE customer_id = ?
    ");
    $deleteCustomer->execute([$user_id]);

    $pdo->commit();

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Customer has been promoted to admin successfully.'
    ]);
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Promote customer error: ' . $e->getMessage()
    ]);
    exit;
}
?>