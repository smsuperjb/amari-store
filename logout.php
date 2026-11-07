<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
logoutUser();
session_destroy();
header('Location: ' . STORE_URL . '/login.php');
exit;