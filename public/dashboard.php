<?php
session_start();
// require_once "../core/session.php";
require_once "../core/auth.php";
require_once "../core/wallet.php";
require_once "../core/plans.php";
//require_once "../core/mining.php";

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

//$user_id = Session::get('user_id');
$user_id = $_SESSION['user_id'] ?? null;
$user = getUser($user_id);
$wallet = getWallet($user_id);
$plans = getActivePlans();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BitW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: linear-gradient(to bottom, #0a0f1c, #02040a); color: #e0f0ff; font-family: system-ui; }
        .card { background: rgba(17, 24, 39, 0.95); border: 1px solid #374151; }
    </style>
</head>
<body class="min-h-screen p-8">
<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-10">
        <div>
            <h1 class="text-4xl font-bold tracking-tight">BitW Dashboard</h1>
            <p class="text-emerald-400">Welcome back, <?= htmlspecialchars($user['username']) ?></p>
        </div>
        <a href="logout.php" class="px-6 py-2 bg-red-500/20 text-red-400 hover:bg-red-500/30 rounded-2xl transition">Logout</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Wallet Card -->
        <div class="card p-8 rounded-3xl">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl">Wallet</h2>
                <span class="text-xs uppercase tracking-widest text-emerald-400">NGN</span>
            </div>
            <div class="text-6xl font-mono font-bold text-white mb-2">₦<?= number_format($wallet['balance'] ?? 0, 2) ?></div>
            <p class="text-sm text-gray-400">Available Balance</p>
        </div>

        <!-- Mining -->
        <div class="card p-8 rounded-3xl">
            <h2 class="text-2xl mb-6">Daily Mining</h2>
            <button onclick="claimReward()" class="w-full py-4 bg-gradient-to-r from-cyan-400 to-purple-500 text-black font-semibold rounded-2xl hover:scale-105 transition">CLAIM TODAY'S YIELD</button>
            <p class="text-xs text-center mt-4 text-gray-500">Log in daily to mine</p>
        </div>

        <!-- Plans -->
        <!-- Plans -->
<div class="card p-8 rounded-3xl">
    <h2 class="text-2xl mb-6">Stone Plans</h2>
    <?php foreach ($plans as $p): ?>
    <div class="p-4 border border-gray-700 rounded-2xl mb-4">
        <div class="font-semibold"><?= htmlspecialchars($p['name']) ?></div>
        <div class="text-sm text-gray-400">
            ₦<?= number_format($p['min_amount']) ?> 
            <?php if ($p['max_amount']): ?>
                - ₦<?= number_format($p['max_amount']) ?>
            <?php else: ?>
                +
            <?php endif; ?>
        </div>
        <div class="text-sm text-gray-400">
            <?= $p['daily_rate'] ?>% daily • <?= $p['duration_days'] ?> days
        </div>
    </div>
    <?php endforeach; ?>
</div>
    </div>
</div>

<script>
function claimReward() {
    alert('Mining reward claimed! (Demo)');
    // TODO: AJAX to mining endpoint
}
</script>
</body>
</html>