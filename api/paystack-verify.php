<?php
// api/paystack-verify.php
// Verify Paystack payment and redirect to dashboard

session_start();
require_once "../core/config.php";
require_once "../core/auth.php";
require_once "../core/database.php";
require_once "../core/wallet.php";

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

$reference = $_GET['reference'] ?? null;

if (!$reference) {
    header("Location: ../public/dashboard.php?error=Invalid reference");
    exit;
}

$settings = include __DIR__ . '/../config/settings.php';
$paystack_secret = $settings['PAYSTACK_SECRET'] ?? null;

// Verify with Paystack
$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/{$reference}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer {$paystack_secret}",
        "Content-Type: application/json",
    ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    header("Location: ../public/dashboard.php?error=Payment verification failed");
    exit;
}

$result = json_decode($response, true);

if ($result['status'] === true && $result['data']['status'] === 'success') {
    // Payment successful, transaction should be marked completed via webhook
    // Just redirect to dashboard
    header("Location: ../public/dashboard.php?success=Payment completed successfully");
    exit;
} else {
    header("Location: ../public/dashboard.php?error=Payment verification failed");
    exit;
}
