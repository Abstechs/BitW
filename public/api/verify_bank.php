<?php
// public/api/verify_bank.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../core/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$account_number = trim($data['account_number'] ?? '');
$bank_code = trim($data['bank_code'] ?? '');

if (empty($account_number) || empty($bank_code)) {
    echo json_encode(['success' => false, 'message' => 'Account number and bank are required.']);
    exit;
}

$settings = include __DIR__ . '/../../config/settings.php';
$paystack_secret = $settings['PAYSTACK_SECRET'] ?? '';

$url = "https://api.paystack.co/bank/resolve?account_number=" . urlencode($account_number) . "&bank_code=" . urlencode($bank_code);

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "authorization: Bearer " . $paystack_secret,
        "cache-control: no-cache"
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    echo json_encode(['success' => false, 'message' => 'Communication error with Paystack.']);
    exit;
}

$result = json_decode($response, true);

if ($result && isset($result['status']) && $result['status'] === true) {
    echo json_encode([
        'success' => true,
        'account_name' => $result['data']['account_name']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $result['message'] ?? 'Could not resolve account details. Verify entries.'
    ]);
}