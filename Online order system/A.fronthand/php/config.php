<?php
// ===== DATABASE =====
$host = 'localhost';
$db   = 'online_order_system';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// ===== CONNECT =====
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Throwable $e) {
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]));
}

// ===== TIMEZONE =====
date_default_timezone_set('Asia/Kuala_Lumpur');

// ===== SESSION =====
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    );

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_path', '/');

    session_name('ONLINE_ORDER_SESSID');
    session_start();
}

// ===== APP CONFIG =====
define('DEFAULT_AVATAR', 'uploads/default.png');
define('ADMIN_DEFAULT_AVATAR', 'uploads/admin-default.png');
define('AVATAR_FOLDER', 'uploads/avatars/');
define('MANAGER_ADMIN_LABEL', 'Manager Admin');

// ===== PAYPAL CONFIG =====
define('PAYPAL_MODE', 'sandbox'); // sandbox or live
define('PAYPAL_CLIENT_ID', 'AZjZUSH4_CaJ00meU3ue1nvtImb8JNAfhNI6nD0QL_7Vpc-vzkYoJVZWLG_bXoSI-T6t9ZBx_S00Nc0l');
define('PAYPAL_CLIENT_SECRET', 'EP8dtS6IKnSZ9bsR2-yt245FNx8bsKa5dqz1Vh0S_2EsH4BtdXo-rddFlCZmQHa7Gy3Cn-ZqMZvV17LC');
define('PAYPAL_CURRENCY', 'MYR');

define(
    'PAYPAL_API_BASE',
    PAYPAL_MODE === 'live'
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com'
);
?>