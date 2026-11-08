<?php
require 'config.php';
require 'helpers.php';

requireFields(['email', 'password'], $_POST);

$email = strtolower(trim($_POST['email']));
$password = $_POST['password'];

// 黑名单检查
$blacklistStmt = $pdo->prepare("
    SELECT blacklist_id
    FROM user_blacklist
    WHERE LOWER(email) = LOWER(?)
      AND is_active = 1
    LIMIT 1
");
$blacklistStmt->execute([$email]);

if ($blacklistStmt->fetch()) {
    jsonResponse([
        'success' => false,
        'message' => 'This email has been blacklisted and cannot log in.'
    ]);
}

// 只用 email 查用户，不再看 role
$stmt = $pdo->prepare("
    SELECT *
    FROM users
    WHERE LOWER(email) = LOWER(?)
    LIMIT 1
");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    jsonResponse([
        'success' => false,
        'message' => 'Account not found.'
    ]);
}

$storedHash = $user['password_hash'] ?? '';

if ($storedHash === '') {
    jsonResponse([
        'success' => false,
        'message' => 'Password record not found.'
    ]);
}

if (!password_verify($password, $storedHash)) {
    jsonResponse([
        'success' => false,
        'message' => 'Wrong password.'
    ]);
}

// 第一个成功登录的人自动升级成 Manager Admin
$adminCountStmt = $pdo->query("
    SELECT COUNT(*) AS total_admin
    FROM users
    WHERE role = 'admin'
");
$adminCountRow = $adminCountStmt->fetch(PDO::FETCH_ASSOC);
$totalAdmin = (int)($adminCountRow['total_admin'] ?? 0);

if ($totalAdmin === 0 && $user['role'] !== 'admin') {
    $newAdminAvatar = resolveAdminAvatarPath();

    $pdo->beginTransaction();

    $updateUser = $pdo->prepare("
        UPDATE users
        SET role = 'admin',
            avatar = ?
        WHERE user_id = ?
    ");
    $updateUser->execute([$newAdminAvatar, $user['user_id']]);

    $checkAdmin = $pdo->prepare("
        SELECT admin_id
        FROM admins
        WHERE admin_id = ?
        LIMIT 1
    ");
    $checkAdmin->execute([$user['user_id']]);

    if (!$checkAdmin->fetch()) {
        $insertAdmin = $pdo->prepare("
            INSERT INTO admins (admin_id, admin_name)
            VALUES (?, ?)
        ");
        $insertAdmin->execute([$user['user_id'], $user['full_name']]);
    }

    $pdo->commit();

    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$user['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

session_regenerate_id(true);

$managerAdminUserId = getManagerAdminUserId($pdo);
$isManagerAdmin = (
    strtolower((string)($user['role'] ?? '')) === 'admin'
    && (int)$user['user_id'] === $managerAdminUserId
);

$_SESSION['user'] = [
    'user_id' => (int)$user['user_id'],
    'role' => $user['role'],
    'is_manager_admin' => $isManagerAdmin,
    'role_label' => $isManagerAdmin ? MANAGER_ADMIN_LABEL : ($user['role'] === 'admin' ? 'Admin' : 'Customer'),
    'full_name' => $user['full_name'],
    'email' => $user['email'],
    'phone' => $user['phone'] ?? '',
    'avatar' => normalizeAvatarPath($user['avatar'] ?? '', $user['role'] ?? 'customer')
];

session_write_close();

jsonResponse([
    'success' => true,
    'message' => $isManagerAdmin ? 'Login successful. You are Manager Admin.' : 'Login successful.',
    'role' => $user['role']
]);
?>