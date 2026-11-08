<?php
ob_start();

require 'config.php';
require 'helpers.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $user = requireLogin();

    $user_id = (int)$user['user_id'];
    $role = $user['role'];

    $full_name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? ''); // ✅ 新增
    $current_password = $_POST['current_password'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($full_name === '' || $email === '' || $phone === '' || $current_password === '') {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all required fields.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingUser) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'User not found.'
        ]);
        exit;
    }

    $storedHash = $existingUser['password_hash'] ?? ($existingUser['password'] ?? '');

    if ($storedHash === '' || !password_verify($current_password, $storedHash)) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Current password is incorrect.'
        ]);
        exit;
    }

    $checkEmail = $pdo->prepare("
        SELECT user_id
        FROM users
        WHERE LOWER(email) = LOWER(?)
          AND user_id <> ?
        LIMIT 1
    ");
    $checkEmail->execute([$email, $user_id]);

    if ($checkEmail->fetch()) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'This email is already used by another account.'
        ]);
        exit;
    }

    $newAvatarPath = normalizeAvatarPath($existingUser['avatar'] ?? '');

    if (isset($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? 4) === 0) {
        $projectRoot = dirname(__DIR__);
        $uploadFolder = $projectRoot . '/uploads/avatars';
        $dbFolder = 'uploads/avatars';

        $uploadResult = uploadFile($_FILES['avatar'], $uploadFolder, $dbFolder);

        if (!$uploadResult['success']) {
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'message' => $uploadResult['message']
            ]);
            exit;
        }

        $newAvatarPath = $uploadResult['path'];

        if (!empty($existingUser['avatar']) && !isDefaultAvatarPath($existingUser['avatar'])) {
            $oldFullPath = dirname(__DIR__) . '/' . ltrim($existingUser['avatar'], '/\\');
            deleteFileIfExists($oldFullPath);
        }
    }

    $pdo->beginTransaction();

    if ($password !== '' || $confirm_password !== '') {
        if ($password !== $confirm_password) {
            $pdo->rollBack();
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'message' => 'New password and confirm password do not match.'
            ]);
            exit;
        }

        if (strlen($password) < 6) {
            $pdo->rollBack();
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'message' => 'New password must be at least 6 characters.'
            ]);
            exit;
        }

        $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $update = $pdo->prepare("
                UPDATE users
                SET
                    full_name = ?,
                    email = ?,
                    phone = ?,
                    address = ?, -- ✅ 新增
                    avatar = ?,
                    password_hash = ?
                WHERE user_id = ?
            ");
            $update->execute([
                $full_name,
                $email,
                $phone,
                $address, // ✅ 新增
                $newAvatarPath,
                $newPasswordHash,
                $user_id
            ]);
        } catch (Throwable $e) {
            $update = $pdo->prepare("
                UPDATE users
                SET
                    full_name = ?,
                    email = ?,
                    phone = ?,
                    address = ?, -- ✅ 新增
                    avatar = ?,
                    password = ?
                WHERE user_id = ?
            ");
            $update->execute([
                $full_name,
                $email,
                $phone,
                $address, // ✅ 新增
                $newAvatarPath,
                $newPasswordHash,
                $user_id
            ]);
        }
    } else {
        $update = $pdo->prepare("
            UPDATE users
            SET
                full_name = ?,
                email = ?,
                phone = ?,
                address = ?, -- ✅ 新增
                avatar = ?
            WHERE user_id = ?
        ");
        $update->execute([
            $full_name,
            $email,
            $phone,
            $address, // ✅ 新增
            $newAvatarPath,
            $user_id
        ]);
    }

    if ($role === 'admin') {
        try {
            $syncAdmin = $pdo->prepare("
                UPDATE admins
                SET admin_name = ?
                WHERE admin_id = ?
            ");
            $syncAdmin->execute([$full_name, $user_id]);
        } catch (Throwable $e) {}
    }

    if ($role === 'customer') {
        try {
            $syncCustomer = $pdo->prepare("
                UPDATE customers
                SET customer_name = ?
                WHERE customer_id = ?
            ");
            $syncCustomer->execute([$full_name, $user_id]);
        } catch (Throwable $e) {}
    }

    $reload = $pdo->prepare("
        SELECT
            user_id,
            role,
            full_name,
            email,
            phone,
            address, -- ✅ 新增
            avatar
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");
    $reload->execute([$user_id]);
    $updatedUser = $reload->fetch(PDO::FETCH_ASSOC);

    if (!$updatedUser) {
        $pdo->rollBack();
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to reload updated user.'
        ]);
        exit;
    }

    $updatedUser['avatar'] = normalizeAvatarPath($updatedUser['avatar'] ?? '');

    $_SESSION['user'] = $updatedUser;

    $pdo->commit();

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully.',
        'user' => $updatedUser,
        'default_avatar' => resolveDefaultAvatarPath()
    ]);
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Update profile error: ' . $e->getMessage()
    ]);
    exit;
}
?>