<?php
// api/paystack-initialize.php
// Initialize Paystack payment

session_start();
require_once "../core/config.php";
require_once "../core/auth.php";
require_once "../core/database.php";

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Invalid amount']);
    exit;
}

$settings = include __DIR__ . '/../config/settings.php';
$paystack_secret = $settings['PAYSTACK_SECRET'] ?? null;

if (!$paystack_secret) {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Paystack not configured']);
    exit;
}

$user = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$user->execute([$user_id]);
$userRow = $user->fetch(PDO::FETCH_ASSOC);

if (!$userRow) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'User not found']);
    exit;
}

// Store transaction reference
$reference = 'bitw_' . time() . '_' . $user_id;

$stmt = $pdo->prepare("
    INSERT INTO transactions (user_id, type, amount, reference, status, description, created_at)
    VALUES (?, 'deposit', ?, ?, 'pending', 'Paystack deposit', NOW())
");
$stmt->execute([$user_id, $amount, $reference]);

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
        'email' => $userRow['email'],
        'reference' => $reference,
        'metadata' => [
            'user_id' => $user_id,
            'custom_fields' => [
                [
                    'display_name' => 'User ID',
                    'variable_name' => 'user_id',
                    'value' => $user_id
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
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Payment gateway error']);
    exit;
}

$result = json_decode($response, true);

if ($result['status'] === true) {
    echo json_encode([
        'status' => true,
        'authorization_url' => $result['data']['authorization_url'],
        'access_code' => $result['data']['access_code'],
        'reference' => $reference
    ]);
} else {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => $result['message'] ?? 'Payment initialization failed']);
}
