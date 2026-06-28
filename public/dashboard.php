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

// ========================================================
// FIXED TRANSACTION VERIFICATION (ENUM STATUS ALIGNMENT)
// ========================================================
if (isset($_GET['payment']) && $_GET['payment'] === 'success' && isset($_GET['ref'])) {
    $reference = filter_input(INPUT_GET, 'ref', FILTER_SANITIZE_SPECIAL_CHARS);
    
    global $pdo; 
    
    // 1. Check if transaction exists
    $stmt = $pdo->prepare("SELECT id, status FROM transactions WHERE reference = ? LIMIT 1");
    $stmt->execute([$reference]);
    $existingTx = $stmt->fetch();

    // Proceed if it's new OR if it is sitting as 'pending'
    if (!$existingTx || (isset($existingTx['status']) && $existingTx['status'] === 'pending')) {
        $settings = include __DIR__ . '/../config/settings.php';
        $paystack_secret = $settings['PAYSTACK_SECRET'] ?? '';

        // 2. Direct verification with Paystack
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "authorization: Bearer " . $paystack_secret,
                "cache-control: no-cache"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if (!$err) {
            $result = json_decode($response, true);
            
            if ($result && isset($result['status']) && $result['status'] === true && $result['data']['status'] === 'success') {
                $amountPaid = $result['data']['amount'] / 100; // Convert kobo to Naira
                $currentUserId = (int)$user['id']; 

                // 3. Credit User's wallet directly
                $updateWallet = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
                $updateWallet->execute([$amountPaid, $currentUserId]);

                // 4. Update or Insert Transaction (Using 'completed' to match your ENUM options)
                if ($existingTx) {
                    $logTx = $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE reference = ?");
                    $logTx->execute([$reference]);
                } else {
                    $logTx = $pdo->prepare("INSERT INTO transactions (user_id, reference, amount, type, status, gateway, created_at) VALUES (?, ?, ?, 'deposit', 'completed', 'paystack', NOW())");
                    $logTx->execute([$currentUserId, $reference, $amountPaid]);
                }

                // 5. Add Dashboard Notification
                try {
                    $addNotif = $pdo->prepare("INSERT INTO notifications (user_id, title, created_at) VALUES (?, ?, NOW())");
                    $addNotif->execute([$currentUserId, "Successfully deposited ₦" . number_format($amountPaid, 2)]);
                } catch (PDOException $e) {}
                
                // Redirect back out to display the updated balance cleanly
                header("Location: dashboard.php");
                exit;
            }
        }
    }
}
// ========================================================

// Continue with standard page data fetching
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

// ========================================================
// SIMPLE RANK DISPLAY TOGGLE SWITCH
// ========================================================
$useRankName = true; // TRUE = Shows Rank Name ("Builder"), FALSE = Shows Rank Level ("Level 1")

if ($useRankName) {
    global $pdo;
    $userRankLevel = $user['rank_level'] ?? 1;
    $rankQuery = $pdo->prepare("SELECT name FROM ranks WHERE level = ? LIMIT 1");
    $rankQuery->execute([$userRankLevel]);
    $rankRow = $rankQuery->fetch();
    $displayRank = $rankRow['name'] ?? 'Novice';
} else {
    $displayRank = htmlspecialchars($rankLevelPrefix) . ' ' . ($user['rank_level'] ?? 1);
}
// ========================================================
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
        #sidebar { transition: transform 0.3s ease; }
        .sidebar-hidden { transform: translateX(-100%); }
        * { transition: all 0.2s ease; }
        .btn-transition:hover { opacity: 0.9; }
        @media (max-width: 768px) {
            #sidebar { width: 60vw; max-width: 240px; }
        }
    </style>
</head>
<body class="bg-gray-950 text-white min-h-screen flex flex-col">

    <!-- ==================== MOBILE TOPBAR ==================== -->
    <header class="hidden md:block lg:block"></header>

    <div id="sidebar" class="w-72 bg-black h-screen fixed top-0 left-0 p-6 z-50 md:block lg:block hidden">
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

    <!-- MOBILE HAMBURGER -->
    <div class="md:hidden lg:hidden flex items-center justify-between p-4 bg-black z-50">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-yellow-400"><?= htmlspecialchars($brand) ?></h1>
        </div>
        <button id="menuToggle" class="p-2 text-yellow-400 hover:text-yellow-300">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-8 md:ml-72 lg:ml-72">
        <!-- Header (welcome + rank + logout) -->
        <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
            <h1 class="text-3xl font-bold mb-4 md:mb-0">
                <?= htmlspecialchars($welcomePrefix) ?>, <?= htmlspecialchars($user['username']) ?> 👋
            </h1>
            <div class="flex flex-col md:flex-row md:items-center md:space-x-6">
                <!-- Rank Config Switch Integration -->
                <div class="text-center md:text-right">
                    <p class="text-sm text-gray-400"><?= htmlspecialchars($rankLabel) ?></p>
                    <p class="text-xl font-semibold text-yellow-400">
                        <?= htmlspecialchars($displayRank) ?>
                    </p>
                </div>
                <!-- Logout button -->
                <button onclick="logout()" class="mt-3 md:mt-0 px-6 py-2 bg-red-600 hover:bg-red-700 rounded-xl">
                    <?= htmlspecialchars($logoutText) ?>
                </button>
            </div>
        </div>

        <!-- Stats cards -->
        <div class="grid gap-6 mb-8 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Wallet -->
            <div class="glass border border-gray-700 rounded-3xl p-6">
                <p class="text-gray-400"><?= htmlspecialchars($walletLabel) ?></p>
                <p class="text-4xl font-bold mt-2">
                    <?= htmlspecialchars($currencySymbol) ?><?= number_format($wallet['balance'] ?? 0, 2) ?>
                </p>
                <div class="mt-4 flex flex-col sm:flex-row gap-3">
                    <a href="deposit.php" class="w-full text-center py-3 bg-green-600 hover:bg-green-700 rounded-2xl">
                        <?= htmlspecialchars($depositText) ?>
                    </a>
                    <a href="withdraw.php" class="w-full text-center py-3 bg-yellow-600 hover:bg-yellow-700 rounded-2xl">
                        <?= htmlspecialchars($withdrawText) ?>
                    </a>
                </div>
            </div>

            <!-- Active Mining -->
            <div class="glass border border-gray-700 rounded-3xl p-6">
                <p class="text-gray-400"><?= htmlspecialchars($activeMiningLabel) ?></p>
                <p class="text-4xl font-bold mt-2"><?= count($activeMinings) ?></p>
                <p class="text-sm text-gray-400"><?= htmlspecialchars($plansRunningText) ?></p>
            </div>

            <!-- Referral Earnings -->
            <div class="glass border border-gray-700 rounded-3xl p-6">
                <p class="text-gray-400"><?= htmlspecialchars($referralLabel) ?></p>
                <p class="text-4xl font-bold mt-2 text-green-400">
                    <?= htmlspecialchars($currencySymbol) ?>0.00
                </p>
                <a href="referrals.php" class="mt-3 inline-block text-yellow-400 text-sm hover:underline">
                    <?= htmlspecialchars($viewReferralsText) ?>
                </a>
            </div>
        </div>

        <!-- Active Mining Plans -->
        <div class="glass border border-gray-700 rounded-3xl p-8 mb-8">
            <h2 class="text-2xl font-semibold mb-6 flex items-center gap-3">
                <i class="fas fa-chart-line"></i> <?= htmlspecialchars($plansLabel) ?>
            </h2>

            <?php if (empty($activeMinings)): ?>
                <p class="text-gray-400 text-center">
                    <?= htmlspecialchars($noActiveMining) ?>
                    <a href="plans.php" class="ml-2 text-yellow-400 hover:underline">
                        <?= htmlspecialchars($browsePlansText) ?>
                    </a>
                </p>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($activeMinings as $mining): ?>
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-gray-700 pb-6">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold"><?= htmlspecialchars($mining['plan_name']) ?></h3>
                            <p class="text-sm text-gray-400 mt-1">
                                <?= htmlspecialchars($dailyEarningLabel) ?>:
                                <?= htmlspecialchars($currencySymbol) ?><?= number_format($mining['daily_earning'] ?? 0, 2) ?>
                            </p>
                        </div>
                        <button onclick="claimMining(<?= (int) $mining['id'] ?>)" class="mt-3 md:mt-0 self-align-end px-5 py-2 bg-yellow-500 hover:bg-yellow-600 text-black font-semibold rounded-2xl">
                            <?= htmlspecialchars($claimText) ?>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Activity / Notifications -->
        <div class="glass border border-gray-700 rounded-3xl p-8">
            <h2 class="text-2xl font-semibold mb-6"><?= htmlspecialchars($activityLabel) ?></h2>

            <?php if (empty($notifications)): ?>
                <p class="text-gray-400"><?= htmlspecialchars($noNotifications) ?></p>
            <?php else: ?>
                <ul class="space-y-4">
                    <?php foreach ($notifications as $notif): ?>
                    <li class="flex gap-3 text-sm">
                        <span class="text-yellow-400 flex-shrink-0">●</span>
                        <div class="flex-1">
                            <div class="font-medium"><?= htmlspecialchars($notif['title']) ?></div>
                            <div class="text-xs text-gray-400"><?= $notif['created_at'] ?></div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>

    <script>
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.getElementById('menuToggle');

        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
                document.querySelector('main').classList.toggle('ml-0');
                document.querySelector('main').classList.toggle('ml-72');
            });
        }

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