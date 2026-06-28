<?php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/wallet.php';
require_once __DIR__ . '/../core/mining.php';
require_once __DIR__ . '/../core/notifications.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user = getUser($_SESSION['user_id']);
$wallet = getWallet($user['id']);
$activeMinings = getActiveMinings($user['id']);
$notifications = getNotifications($user['id'], 5);

$appName = AppConfig::get('APP_NAME') ?: 'BitWealthBuilder';
$appAlias = AppConfig::get('APP_ALIAS') ?: 'BitW';
$dashboardConfig = AppConfig::get('DASHBOARD') ?: [];

$brand = $dashboardConfig['BRAND'] ?? $appAlias;
$welcomePrefix = $dashboardConfig['WELCOME_PREFIX'] ?? 'Welcome back';
$rankLabel = $dashboardConfig['RANK_LABEL'] ?? 'Current Rank';
$rankLevelPrefix = $dashboardConfig['RANK_LEVEL_PREFIX'] ?? 'Level';
$walletLabel = $dashboardConfig['WALLET_BALANCE_LABEL'] ?? 'Wallet Balance';
$activeMiningLabel = $dashboardConfig['ACTIVE_MINING_LABEL'] ?? 'Active Mining';
$referralLabel = $dashboardConfig['REFERRAL_EARNINGS_LABEL'] ?? 'Referral Earnings';
$plansLabel = $dashboardConfig['ACTIVE_MINING_PLANS_LABEL'] ?? 'Active Mining Plans';
$activityLabel = $dashboardConfig['RECENT_ACTIVITY_LABEL'] ?? 'Recent Activity';
$noActiveMining = $dashboardConfig['NO_ACTIVE_MINING_TEXT'] ?? 'You have no active mining plans.';
$browsePlansText = $dashboardConfig['BROWSE_PLANS_TEXT'] ?? 'Browse Plans';
$noNotifications = $dashboardConfig['NO_NOTIFICATIONS_TEXT'] ?? 'No notifications yet.';
$viewReferralsText = $dashboardConfig['VIEW_REFERRALS_TEXT'] ?? 'View Referrals →';
$plansRunningText = $dashboardConfig['PLANS_RUNNING_TEXT'] ?? 'Plans Running';
$depositText = $dashboardConfig['DEPOSIT_TEXT'] ?? 'Deposit';
$withdrawText = $dashboardConfig['WITHDRAW_TEXT'] ?? 'Withdraw';
$logoutText = $dashboardConfig['LOGOUT_TEXT'] ?? 'Logout';
$claimText = $dashboardConfig['CLAIM_BUTTON_TEXT'] ?? 'Claim Today';
$claimConfirmMessage = $dashboardConfig['CLAIM_CONFIRM_MESSAGE'] ?? 'Claim mining earnings for this session?';
$claimSuccessMessage = $dashboardConfig['CLAIM_SUCCESS_MESSAGE'] ?? 'Claim successful!';
$claimFailedMessage = $dashboardConfig['CLAIM_FAILED_MESSAGE'] ?? 'Claim failed';
$dailyEarningLabel = $dashboardConfig['DAILY_EARNING_LABEL'] ?? 'Daily';
$currencySymbol = $dashboardConfig['CURRENCY_SYMBOL'] ?? '₦';
$navDashboard = $dashboardConfig['NAV_DASHBOARD'] ?? 'Dashboard';
$navMining = $dashboardConfig['NAV_MINING'] ?? 'Mining';
$navReferrals = $dashboardConfig['NAV_REFERRALS'] ?? 'Referrals';
$navTransactions = $dashboardConfig['NAV_TRANSACTIONS'] ?? 'Transactions';
$navWithdraw = $dashboardConfig['NAV_WITHDRAW'] ?? 'Withdraw';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($dashboardConfig['TITLE_PREFIX'] ?? 'Dashboard') ?> - <?= htmlspecialchars($appName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .glass { background: rgba(255,255,255,0.05); backdrop-filter: blur(12px); }
    </style>
</head>
<body class="bg-gray-950 text-white min-h-screen">

<div class="flex">
    <div class="w-72 bg-black h-screen fixed p-6">
        <div class="flex items-center gap-3 mb-10">
            <h1 class="text-3xl font-bold text-yellow-400"><?= htmlspecialchars($brand) ?></h1>
        </div>
        <nav class="space-y-2">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 bg-gray-800 rounded-xl"><i class="fas fa-home"></i> <?= htmlspecialchars($navDashboard) ?></a>
            <a href="mining.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 rounded-xl"><i class="fas fa-chart-line"></i> <?= htmlspecialchars($navMining) ?></a>
            <a href="referrals.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 rounded-xl"><i class="fas fa-users"></i> <?= htmlspecialchars($navReferrals) ?></a>
            <a href="transactions.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 rounded-xl"><i class="fas fa-history"></i> <?= htmlspecialchars($navTransactions) ?></a>
            <a href="withdraw.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 rounded-xl"><i class="fas fa-money-bill"></i> <?= htmlspecialchars($navWithdraw) ?></a>
        </nav>
    </div>

    <div class="ml-72 flex-1 p-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-4xl font-bold"><?= htmlspecialchars($welcomePrefix) ?>, <?= htmlspecialchars($user['username']) ?> 👋</h1>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm text-gray-400"><?= htmlspecialchars($rankLabel) ?></p>
                    <p class="text-xl font-semibold text-yellow-400"><?= htmlspecialchars($rankLevelPrefix) ?> <?= $user['rank_level'] ?? 1 ?></p>
                </div>
                <button onclick="logout()" class="px-6 py-2 bg-red-600 hover:bg-red-700 rounded-xl"><?= htmlspecialchars($logoutText) ?></button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="glass border border-gray-700 rounded-3xl p-6">
                <p class="text-gray-400"><?= htmlspecialchars($walletLabel) ?></p>
                <p class="text-5xl font-bold mt-2"><?= htmlspecialchars($currencySymbol) ?><?= number_format($wallet['balance'] ?? 0, 2) ?></p>
                <div class="mt-4 flex gap-3">
                    <a href="deposit.php" class="flex-1 text-center py-3 bg-green-600 hover:bg-green-700 rounded-2xl"><?= htmlspecialchars($depositText) ?></a>
                    <a href="withdraw.php" class="flex-1 text-center py-3 bg-yellow-600 hover:bg-yellow-700 rounded-2xl"><?= htmlspecialchars($withdrawText) ?></a>
                </div>
            </div>

            <div class="glass border border-gray-700 rounded-3xl p-6">
                <p class="text-gray-400"><?= htmlspecialchars($activeMiningLabel) ?></p>
                <p class="text-5xl font-bold mt-2"><?= count($activeMinings) ?></p>
                <p class="text-sm text-gray-400"><?= htmlspecialchars($plansRunningText) ?></p>
            </div>

            <div class="glass border border-gray-700 rounded-3xl p-6">
                <p class="text-gray-400"><?= htmlspecialchars($referralLabel) ?></p>
                <p class="text-5xl font-bold mt-2 text-green-400"><?= htmlspecialchars($currencySymbol) ?>0.00</p>
                <a href="referrals.php" class="text-yellow-400 text-sm hover:underline"><?= htmlspecialchars($viewReferralsText) ?></a>
            </div>
        </div>

        <div class="glass border border-gray-700 rounded-3xl p-8 mb-8">
            <h2 class="text-2xl font-semibold mb-6 flex items-center gap-3">
                <i class="fas fa-chart-line"></i> <?= htmlspecialchars($plansLabel) ?>
            </h2>

            <?php if (empty($activeMinings)): ?>
                <p class="text-gray-400"><?= htmlspecialchars($noActiveMining) ?> <a href="plans.php" class="text-yellow-400"><?= htmlspecialchars($browsePlansText) ?></a></p>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($activeMinings as $mining): ?>
                    <div class="flex justify-between items-center border-b border-gray-700 pb-6">
                        <div>
                            <h3 class="font-semibold"><?= htmlspecialchars($mining['plan_name']) ?></h3>
                            <p class="text-sm text-gray-400"><?= htmlspecialchars($dailyEarningLabel) ?>: <?= htmlspecialchars($currencySymbol) ?><?= number_format($mining['daily_earning'] ?? 0, 2) ?></p>
                        </div>
                        <button onclick="claimMining(<?= (int) $mining['id'] ?>)"
                                class="px-8 py-3 bg-yellow-500 hover:bg-yellow-600 text-black font-semibold rounded-2xl">
                            <?= htmlspecialchars($claimText) ?>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="glass border border-gray-700 rounded-3xl p-8">
            <h2 class="text-2xl font-semibold mb-6"><?= htmlspecialchars($activityLabel) ?></h2>
            <?php if (empty($notifications)): ?>
                <p class="text-gray-400"><?= htmlspecialchars($noNotifications) ?></p>
            <?php else: ?>
                <ul class="space-y-4">
                    <?php foreach ($notifications as $notif): ?>
                    <li class="flex gap-4 text-sm">
                        <span class="text-yellow-400">●</span>
                        <span><?= htmlspecialchars($notif['title']) ?></span>
                        <span class="ml-auto text-gray-500"><?= $notif['created_at'] ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function claimMining(miningId) {
    const claimConfirmMessage = <?= json_encode($claimConfirmMessage) ?>;
    const claimSuccessMessage = <?= json_encode($claimSuccessMessage) ?>;
    const claimFailedMessage = <?= json_encode($claimFailedMessage) ?>;

    if (confirm(claimConfirmMessage)) {
        fetch('api/claim.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({mining_id: miningId})
        })
        .then(r => r.json())
        .then(data => {
            const message = data.message || (data.success ? claimSuccessMessage : claimFailedMessage);
            alert(message);
            if (data.success) {
                location.reload();
            }
        });
    }
}

function logout() {
    window.location.href = 'logout.php';
}
</script>
</body>
</html>