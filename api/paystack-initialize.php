<?php
// api/paystack-initialize.php
// Initialize Paystack payment

session_start();
date_default_timezone_set('Africa/Lagos');
require_once "../core/config.php";
require_once "../core/auth.php";
require_once "../core/database.php";

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

$settings = include __DIR__ . '/../config/settings.php';
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

// Validate email
$email = filter_var($userRow['email'], FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Invalid email address']);
    exit;
}

// Generate dual references: external (Paystack) and internal
$paystack_reference = 'bitw_' . time() . '_' . $user_id . '_' . substr(md5(uniqid()), 0, 6);
$internal_reference = 'ref_' . $user_id . '_' . date('dmYhis', time());

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

// Initialize Paystack payment
$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.paystack.co/transaction/initialize",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode([
        'amount' => intval($amount * 100), // Paystack expects amount in kobo
        'email' => $email,
        'reference' => $paystack_reference,
        'metadata' => [
            'user_id' => $user_id,
            'username' => $userRow['username'],
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
    ]),
    CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer {$paystack_secret}",
        "Content-Type: application/json",
    ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    // Log error
    error_log("Paystack init error: {$err}");
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Payment gateway error']);
    exit;
}

$result = json_decode($response, true);

if ($result['status'] === true && isset($result['data']['authorization_url'])) {
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
    error_log("Paystack error: " . json_encode($result));
    echo json_encode([
        'status' => false,
        'message' => $result['message'] ?? 'Payment initialization failed'
    ]);
}
