<?php
require 'config.php';
require 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    jsonResponse([
        'success' => false,
        'message' => 'Not logged in.'
    ]);
}

jsonResponse([
    'success' => true,
    'user_id' => $_SESSION['user_id'],
    'role' => $_SESSION['role'] ?? ''
]);