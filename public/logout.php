<?php
// public/logout.php
require_once __DIR__ . '/../core/session.php';

// Preferred session cleanup via Session helper
// Clear session array
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
$_SESSION = [];

// Delete session cookie if present
if (ini_get("session.use_cookies")) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000,
		$params['path'], $params['domain'], $params['secure'], $params['httponly']
	);
}

// Destroy session on server
session_destroy();

// Redirect back to the login page (relative path)
header('Location: login.php');
exit;
