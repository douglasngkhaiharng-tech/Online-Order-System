<?php
require 'config.php';
require 'helpers.php';

requireFields(
    [
        'full_name',
        'email',
        'phone',
        'password',
        'confirm_password',
        'security_question',
        'security_answer',
        'address'
    ],
    $_POST
);

$full_name = trim($_POST['full_name']);
$email = strtolower(trim($_POST['email']));
$phone = trim($_POST['phone']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$security_question = trim($_POST['security_question']);
$security_answer = trim($_POST['security_answer']);
$address = trim($_POST['address']);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse([
        'success' => false,
        'message' => 'Invalid email.'
    ]);
}

if ($password !== $confirm_password) {
    jsonResponse([
        'success' => false,
        'message' => 'Passwords do not match.'
    ]);
}

if (strlen($password) < 6) {
    jsonResponse([
        'success' => false,
        'message' => 'Password must be at least 6 characters.'
    ]);
}

if ($security_question === '' || $security_answer === '') {
    jsonResponse([
        'success' => false,
        'message' => 'Security question and answer are required.'
    ]);
}

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
        'message' => 'This email has been blacklisted and cannot register.'
    ]);
}

$check = $pdo->prepare("SELECT user_id FROM users WHERE LOWER(email) = LOWER(?)");
$check->execute([$email]);
if ($check->fetch()) {
    jsonResponse([
        'success' => false,
        'message' => 'Email already registered.'
    ]);
}

if (!isset($_FILES['avatar']) || ($_FILES['avatar']['error'] ?? 4) !== 0) {
    jsonResponse([
        'success' => false,
        'message' => 'Profile photo is required.'
    ]);
}

$projectRoot = dirname(__DIR__);
$avatarUploadFolder = $projectRoot . '/uploads/avatars';
$avatarDbFolder = 'uploads/avatars';

$uploadResult = uploadFile($_FILES['avatar'], $avatarUploadFolder, $avatarDbFolder);

if (!$uploadResult['success']) {
    jsonResponse([
        'success' => false,
        'message' => $uploadResult['message']
    ]);
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);
$security_answer_hash = password_hash($security_answer, PASSWORD_DEFAULT);
$avatarPath = $uploadResult['path'];

// 注册统一先当 customer
$role = 'customer';

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO users (
            role,
            full_name,
            email,
            phone,
            password_hash,
            avatar,
            security_question,
            security_answer,
            address
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $role,
        $full_name,
        $email,
        $phone,
        $password_hash,
        $avatarPath,
        $security_question,
        $security_answer_hash,
        $address
    ]);

    $user_id = (int)$pdo->lastInsertId();

    $stmt2 = $pdo->prepare("
        INSERT INTO customers (customer_id, customer_name)
        VALUES (?, ?)
    ");
    $stmt2->execute([$user_id, $full_name]);

    $pdo->commit();

    jsonResponse([
        'success' => true,
        'message' => 'Sign up successful. Please login. '
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    jsonResponse([
        'success' => false,
        'message' => 'Sign up failed: ' . $e->getMessage()
    ]);
}
?>