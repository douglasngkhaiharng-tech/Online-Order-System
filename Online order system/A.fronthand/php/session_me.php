<?php
ob_start();
require 'config.php';
require 'helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['user'])) {
        ob_end_clean();
        echo json_encode([
            'logged_in' => false,
            'default_avatar' => DEFAULT_AVATAR,
            'admin_default_avatar' => ADMIN_DEFAULT_AVATAR
        ]);
        exit;
    }

    $sessionUser = $_SESSION['user'];

    $stmt = $pdo->prepare("
        SELECT 
            user_id,
            role,
            full_name,
            email,
            phone,
            address,
            avatar
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$sessionUser['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION = [];
        session_destroy();

        ob_end_clean();
        echo json_encode([
            'logged_in' => false,
            'default_avatar' => DEFAULT_AVATAR,
            'admin_default_avatar' => ADMIN_DEFAULT_AVATAR
        ]);
        exit;
    }

    $managerAdminUserId = getManagerAdminUserId($pdo);
    $isManagerAdmin = (
        strtolower((string)$user['role']) === 'admin'
        && (int)$user['user_id'] === $managerAdminUserId
    );

    $user['avatar'] = normalizeAvatarPath($user['avatar'] ?? '', $user['role'] ?? 'customer');
    $user['address'] = trim((string)($user['address'] ?? ''));
    $user['is_manager_admin'] = $isManagerAdmin;
    $user['role_label'] = $isManagerAdmin ? MANAGER_ADMIN_LABEL : ($user['role'] === 'admin' ? 'Admin' : 'Customer');

    $_SESSION['user'] = $user;

    ob_end_clean();
    echo json_encode([
        'logged_in' => true,
        'user' => $user,
        'default_avatar' => DEFAULT_AVATAR,
        'admin_default_avatar' => ADMIN_DEFAULT_AVATAR
    ]);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'logged_in' => false,
        'message' => 'Session error: ' . $e->getMessage(),
        'default_avatar' => DEFAULT_AVATAR,
        'admin_default_avatar' => ADMIN_DEFAULT_AVATAR
    ]);
    exit;
}
?>