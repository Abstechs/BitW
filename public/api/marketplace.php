<?php
session_start();
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/plans.php';
require_once __DIR__ . '/../../core/marketplace.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';
$user_id = $_SESSION['user_id'];

if ($action === 'wishlist') {
    $planId = (int) ($input['plan_id'] ?? 0);
    if ($planId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid stone']);
        exit;
    }
    $result = addToWishlist($user_id, $planId);
    echo json_encode($result);
    exit;
}

if ($action === 'purchase') {
    $planId = (int) ($input['plan_id'] ?? 0);
    $amount = (float) ($input['amount'] ?? 0);
    if ($planId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid stone']);
        exit;
    }
    $result = purchasePlan($user_id, $planId, $amount);
    echo json_encode($result);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
