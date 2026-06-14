<?php
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/wallet.php';
require_once __DIR__ . '/../../core/mining.php';
require_once __DIR__ . '/../../core/referral.php';

// Fetch stats
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
$total_users = $stmt->fetch()['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as active_miners FROM user_mining WHERE status = 'active'");
$active_miners = $stmt->fetch()['active_miners'];

$stmt = $pdo->query("SELECT SUM(balance) as total_balance FROM wallets");
$total_balance = $stmt->fetch()['total_balance'] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - BitW</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
    <?php include 'includes/header.php'; ?>
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        <div class="p-8 flex-1">
            <h1 class="text-4xl font-bold mb-8">Admin Dashboard</h1>
            <div class="grid grid-cols-4 gap-6">
                <div class="bg-gray-800 p-6 rounded-xl">
                    <h3>Total Users</h3>
                    <p class="text-5xl"><?= $total_users ?></p>
                </div>
                <div class="bg-gray-800 p-6 rounded-xl">
                    <h3>Active Miners</h3>
                    <p class="text-5xl"><?= $active_miners ?></p>
                </div>
                <div class="bg-gray-800 p-6 rounded-xl">
                    <h3>Total Balance</h3>
                    <p class="text-5xl">$<?= number_format($total_balance, 2) ?></p>
                </div>
            </div>
            <div class="mt-8">
                <a href="users.php" class="bg-blue-600 px-6 py-3 rounded">Manage Users</a>
                <a href="plans.php" class="bg-green-600 px-6 py-3 rounded">Plans</a>
                <a href="mining.php" class="bg-purple-600 px-6 py-3 rounded">Mining</a>
            </div>
        </div>
    </div>
</body>
</html>