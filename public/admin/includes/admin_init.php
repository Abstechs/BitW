<?php
// admin/includes/admin_init.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../core/database.php';
require_once __DIR__ . '/../../../core/auth.php';
require_once __DIR__ . '/../../../core/config.php';
require_once __DIR__ . '/../../../core/settings.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php');
    exit;
}
