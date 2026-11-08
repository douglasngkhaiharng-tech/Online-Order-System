<?php
ob_start();

require 'config.php';
require 'helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    requireFields(['email'], $_POST);

    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("
        SELECT user_id, full_name, security_question
        FROM users
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Email not found.'
        ]);
        exit;
    }

    // ✅ 不再用 token / email reset
    // 👉 直接返回 security question

    if (empty($user['security_question'])) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'No security question set for this account.'
        ]);
        exit;
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'question' => $user['security_question']
    ]);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Forgot password error: ' . $e->getMessage()
    ]);
    exit;
}
?>