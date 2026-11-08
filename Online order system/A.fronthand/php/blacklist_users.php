<?php
require 'config.php';
require 'helpers.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $admin = requireRole('admin');

    $user_id = (int)($_POST['user_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? 'Blacklisted by admin.');

    if ($user_id <= 0) {
        jsonResponse([
            'success' => false,
            'message' => 'User ID is required.'
        ]);
    }

    if ($user_id === (int)$admin['user_id']) {
        jsonResponse([
            'success' => false,
            'message' => 'You cannot blacklist your own account.'
        ]);
    }

    $userStmt = $pdo->prepare("
        SELECT user_id, email, full_name
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");
    $userStmt->execute([$user_id]);
    $targetUser = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$targetUser) {
        jsonResponse([
            'success' => false,
            'message' => 'User not found.'
        ]);
    }

    $email = strtolower(trim($targetUser['email']));

    $checkStmt = $pdo->prepare("
        SELECT blacklist_id
        FROM user_blacklist
        WHERE LOWER(email) = LOWER(?)
        LIMIT 1
    ");
    $checkStmt->execute([$email]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $updateStmt = $pdo->prepare("
            UPDATE user_blacklist
            SET reason = ?, is_active = 1, created_at = NOW()
            WHERE blacklist_id = ?
        ");
        $updateStmt->execute([$reason, $existing['blacklist_id']]);
    } else {
        $insertStmt = $pdo->prepare("
            INSERT INTO user_blacklist (email, reason, is_active, created_at)
            VALUES (?, ?, 1, NOW())
        ");
        $insertStmt->execute([$email, $reason]);
    }

    jsonResponse([
        'success' => true,
        'message' => 'User blacklisted successfully.'
    ]);

} catch (Throwable $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Blacklist error: ' . $e->getMessage()
    ]);
}
?>