<?php
ob_start();

require 'config.php';
require 'require_auth.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $admin = requireRole('admin');

    $admin_id = (int)$admin['user_id'];
    $blacklist_id = (int)($_POST['blacklist_id'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    if ($blacklist_id <= 0) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Blacklist ID is required.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM user_blacklist
        WHERE blacklist_id = ?
          AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$blacklist_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Active blacklist record not found.'
        ]);
        exit;
    }

    $pdo->beginTransaction();

    $insertLog = $pdo->prepare("
        INSERT INTO user_blacklist_removals (
            blacklist_id, email, removed_by, note
        ) VALUES (?, ?, ?, ?)
    ");
    $insertLog->execute([
        $blacklist_id,
        $row['email'],
        $admin_id,
        $note === '' ? null : $note
    ]);

    // 直接删掉 active blacklist 记录
    $delete = $pdo->prepare("
        DELETE FROM user_blacklist
        WHERE blacklist_id = ?
          AND is_active = 1
    ");
    $delete->execute([$blacklist_id]);

    $pdo->commit();

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'User has been removed from blacklist.'
    ]);
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Unblacklist error: ' . $e->getMessage()
    ]);
    exit;
}
?>