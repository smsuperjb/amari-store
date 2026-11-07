<?php
// store/includes/auth.php
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool {
    return !empty($_SESSION['store_user_id']);
}

function currentUser(): ?array {
    return $_SESSION['store_user'] ?? null;
}

function loginUser(array $user): void {
    $_SESSION['store_user_id'] = $user['id'];
    $_SESSION['store_user']    = $user;
}

function logoutUser(): void {
    unset($_SESSION['store_user_id'], $_SESSION['store_user']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . STORE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}
