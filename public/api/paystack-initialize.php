<?php
// api/paystack-initialize.php
// Initialize Paystack payment cleanly

session_start();
date_default_timezone_set('Africa/Lagos');
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/database.php';

// Validate AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Invalid request']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$amount = isset($_POST['amount']) && is_numeric($_POST['amount']) ? floatval($_POST['amount']) : 0;

if ($amount <= 0 || $amount > 999999999) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Invalid amount']);
    exit;
}

// Step back 2 levels to accurately hit the config folder in your project root
$settings = include __DIR__ . '/../../config/settings.php';
$paystack_secret = $settings['PAYSTACK_SECRET'] ?? null;
$paystack_public = $settings['PAYSTACK_PUBLIC'] ?? null;

if (!$paystack_secret || !$paystack_public) {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Paystack not configured']);
    exit;
}

$user = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
$user->execute([$user_id]);
$userRow = $user->fetch(PDO::FETCH_ASSOC);

if (!$userRow) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'User not found']);
    exit;
}

// Ensure a structurally pristine email parameter is always passed to Paystack
$email = trim($userRow['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // 1. Check if username is completely empty or blank string
    $rawUsername = (!empty($userRow['username']) && trim($userRow['username']) !== '') ? $userRow['username'] : 'user_' . $user_id;
    
    // 2. Remove any special characters or spaces
    $cleanUsername = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $rawUsername));
    
    // 3. Absolute safety safety fallback guard if string ends up blank
    if (empty($cleanUsername)) {
        $cleanUsername = 'user_' . $user_id;
    }
    
    $email = $cleanUsername . '@gmail.com'; 
}

// 1. Fetch the active app alias or name from configuration
$appAlias = class_exists('AppConfig') ? AppConfig::get('APP_ALIAS') : 'app';

// 2. Sanitize into a clean lowercase prefix format
$cleanPrefix = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $appAlias ?: 'app'));

// 3. Generate references dynamically
$paystack_reference = $cleanPrefix . '_' . time() . '_' . $user_id . '_' . substr(md5(uniqid()), 0, 6);
$internal_reference = 'ref_' . $user_id . '_' . date('dmYhis', time());

// Log entry into ledger as pending before gateway execution
$stmt = $pdo->prepare("
    INSERT INTO transactions (user_id, type, amount, reference, description, status, created_at)
    VALUES (?, 'deposit', ?, ?, ?, 'pending', NOW())
");
$stmt->execute([
    $user_id,
    $amount,
    $paystack_reference,
    "Paystack deposit - Internal Ref: {$internal_reference}"
]);

// Prepare Paystack request fields explicitly
$payload = [
    'amount' => intval($amount * 100), // Minor units (kobo)
    'email' => $email,
    'reference' => $paystack_reference,
    'metadata' => [
        'user_id' => $user_id,
        'username' => $userRow['username'] ?? 'User',
        'internal_reference' => $internal_reference,
        'custom_fields' => [
            [
                'display_name' => 'User ID',
                'variable_name' => 'user_id',
                'value' => $user_id
            ],
            [
                'display_name' => 'Internal Reference',
                'variable_name' => 'internal_ref',
                'value' => $internal_reference
            ]
        ]
    ]
];

// Initialize Paystack payment endpoint via cURL
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.paystack.co/transaction/initialize",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . trim($paystack_secret),
        "Content-Type: application/json",
    ),
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    error_log("Paystack init error: {$err}");
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Payment gateway error']);
    exit;
}

$result = json_decode($response, true);

if (isset($result['status']) && $result['status'] === true && isset($result['data']['authorization_url'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => true,
        'authorization_url' => $result['data']['authorization_url'],
        'access_code' => $result['data']['access_code'],
        'reference' => $paystack_reference,
        'internal_reference' => $internal_reference
    ]);
} else {
    http_response_code(400);
    // Write out complete API response context to aid debugging logs
    error_log("Paystack API Initialization Failure Context: " . $response);
    echo json_encode([
        'status' => false,
        'message' => $result['message'] ?? 'Payment initialization failed'
    ]);
}
