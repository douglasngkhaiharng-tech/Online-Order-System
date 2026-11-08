<?php
require_once 'helpers.php';

function requireLogin()
{
    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
        jsonResponse([
            'success' => false,
            'message' => 'Not logged in.'
        ]);
    }

    return $_SESSION['user'];
}

function requireRole($role)
{
    $user = requireLogin();

    if (($user['role'] ?? '') !== $role) {
        jsonResponse([
            'success' => false,
            'message' => 'Access denied.'
        ]);
    }

    return $user;
}