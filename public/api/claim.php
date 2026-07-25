<?php
session_start();
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/mining.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$miningId = (int) ($input['mining_id'] ?? 0);

if ($miningId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid mining session']);
    exit;
}

$result = claimMining($miningId);
echo json_encode($result);
