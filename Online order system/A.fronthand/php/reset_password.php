<?php
require 'config.php';
require 'helpers.php';

header('Content-Type: application/json; charset=utf-8');

$email = trim($_POST['email'] ?? '');
$answer = trim($_POST['security_answer'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $answer === '' || $password === '') {
    echo json_encode([
        'success' => false,
        'message' => 'All fields are required.'
    ]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 6 characters.'
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT security_answer
    FROM users
    WHERE email = ?
    LIMIT 1
");
$stmt->execute([$email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode([
        'success' => false,
        'message' => 'User not found'
    ]);
    exit;
}

// 验证答案
if (!password_verify($answer, $user['security_answer'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Wrong answer'
    ]);
    exit;
}

// 更新密码
$stmt = $pdo->prepare("
    UPDATE users
    SET password_hash = ?
    WHERE email = ?
");

$stmt->execute([
    password_hash($password, PASSWORD_DEFAULT),
    $email
]);

echo json_encode([
    'success' => true,
    'message' => 'Password reset successful'
]);
?>