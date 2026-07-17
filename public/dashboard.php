<?php
// public/dashboard.php
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

// Fetch Portfolio Asset holdings details for aggregate values display
$portfolioStmt = $pdo->prepare("
    SELECT ua.units, ta.name, ta.ticker, ta.current_price 
    FROM user_assets ua
    JOIN trade_assets ta ON ua.asset_id = ta.id
    WHERE ua.user_id = ? AND ua.units > 0
");
$portfolioStmt->execute([$user['id']]);
$holdings = $portfolioStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate dynamic portfolio holding valuations
$totalAssetValue = 0;
foreach ($holdings as $h) {
    $totalAssetValue += ($h['units'] * $h['current_price']);
}
$cashBalance = floatval($wallet['balance'] ?? 0.00);
$netWorth = $cashBalance + $totalAssetValue;

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
$navSettings = $dashboardConfig['NAV_SETTINGS'] ?? 'Settings';

$useRankName = true; 
if ($useRankName) {
    $userRankLevel = $user['rank_level'] ?? 1;
    $rankQuery = $pdo->prepare("SELECT name FROM ranks WHERE level = ? LIMIT 1");
    $rankQuery->execute([$userRankLevel]);
    $rankRow = $rankQuery->fetch();
    $displayRank = $rankRow['name'] ?? 'Novice';
} else {
    $displayRank = htmlspecialchars($rankLevelPrefix) . ' ' . ($user['rank_level'] ?? 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($dashboardConfig['TITLE_PREFIX'] ?? 'Dashboard') ?> - <?= htmlspecialchars($appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        .glass { background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.08); }
        #sidebar { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar-hidden { transform: translateX(-100%); }
        .toastify-custom-success {
            background: linear-gradient(135deg, #059669, #10b981) !important;
            border-radius: 1rem !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3) !important;
        }
        .toastify-custom-error {
            background: linear-gradient(135deg, #dc2626, #f87171) !important;
            border-radius: 1rem !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3) !important;
        }
    </style>
    <style>
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .glass { background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.08); }
    #sidebar { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .sidebar-hidden { transform: translateX(-100%); }
</style>

</head>
<body class="bg-[#070a13] text-white min-h-screen flex antialiased graphic-smooth">

    <!-- ==================== CUSTOM CONFIRMATION MODAL ==================== -->
    <div id="confirmModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm hidden opacity-0 transition-all duration-300">
        <div class="glass max-w-sm w-full p-6 rounded-3xl shadow-2xl transform scale-95 transition-all duration-300">
            <div class="flex items-center gap-3 text-yellow-400 mb-4">
                <i class="fas fa-exclamation-circle text-2xl"></i>
                <h3 class="text-xl font-bold">Confirm Action</h3>
            </div>
            <p id="confirmModalMessage" class="text-gray-300 text-sm leading-relaxed mb-6"></p>
            <div class="flex items-center justify-end gap-3">
                <button id="confirmCancelBtn" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-sm rounded-xl transition font-medium">Cancel</button>
                <button id="confirmProceedBtn" class="px-5 py-2.5 bg-yellow-500 hover:bg-yellow-600 text-black text-sm rounded-xl transition font-semibold">Yes, Proceed</button>
            </div>
        </div>
    </div>

    <!-- DESKTOP FIXED SIDEBAR -->
    <aside id="sidebar" class="w-64 bg-slate-950/80 border-r border-white/5 h-screen fixed top-0 left-0 p-5 z-50 lg:block hidden">
        <div class="flex items-center gap-3 mb-8">
            <h1 class="text-2xl font-black text-yellow-400 tracking-wider"><?= htmlspecialchars($brand) ?></h1>
        </div>
        <nav class="space-y-1.5">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 bg-blue-600/10 text-blue-400 border border-blue-500/20 rounded-xl font-medium"><i class="bx bxs-dashboard text-lg"></i> <?= htmlspecialchars($navDashboard) ?></a>
            <a href="trading.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-slate-400 hover:text-white transition-all"><i class="bx bx-trending-up text-lg"></i> Exchange Matrix</a>
            <a href="plans.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-slate-400 hover:text-white transition-all"><i class="bx bx-folder text-lg"></i> My Portfolio</a>
            <a href="mining.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-slate-400 hover:text-white transition-all"><i class="bx bx-bolt text-lg"></i> <?= htmlspecialchars($navMining) ?></a>
            <a href="referrals.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-slate-400 hover:text-white transition-all"><i class="bx bx-user-plus text-lg"></i> <?= htmlspecialchars($navReferrals) ?></a>
            <a href="transactions.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-slate-400 hover:text-white transition-all"><i class="bx bx-receipt text-lg"></i> <?= htmlspecialchars($navTransactions) ?></a>
            <a href="withdraw.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-slate-400 hover:text-white transition-all"><i class="bx bx-money-withdraw text-lg"></i> <?= htmlspecialchars($navWithdraw) ?></a>
<a href="lotto.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-slate-400 hover:text-white transition-all"><i class="bx bx-dice-6 text-lg"></i> Lotto-Sovereign</a>
<a href="predictions.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-slate-400 hover:text-white transition-all"><i class="bx bx-bullseye text-lg"></i> Social Betting</a>
            <a href="settings.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-slate-400 hover:text-white transition-all"><i class="bx bx-cog text-lg"></i> <?= htmlspecialchars($navSettings) ?></a>
        </nav>
    </aside>

    <!-- MOBILE NAVIGATION OVERLAY SYSTEM -->
    <div id="mobileSidebar" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[90] hidden lg:hidden transition-all duration-300 opacity-0">
        <div class="w-64 bg-slate-950 h-full p-5 border-r border-white/5 transform -translate-x-full transition-transform duration-300">
            <div class="flex items-center justify-between mb-8">
                <h1 class="text-xl font-black text-yellow-400"><?= htmlspecialchars($brand) ?></h1>
                <button onclick="toggleMobileMenu()" class="text-slate-400 hover:text-white"><i class="bx bx-x text-2xl"></i></button>
            </div>
            <nav class="space-y-2">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 bg-blue-600/10 text-blue-400 border border-blue-500/20 rounded-xl"><i class="bx bxs-dashboard text-lg"></i> <?= htmlspecialchars($navDashboard) ?></a>
                <a href="trading.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-slate-300"><i class="bx bx-trending-up text-lg"></i> Exchange Matrix</a>
                <a href="plans.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-slate-300"><i class="bx bx-folder text-lg"></i> My Portfolio</a>
                <a href="mining.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-slate-300"><i class="bx bx-bolt text-lg"></i> <?= htmlspecialchars($navMining) ?></a>
                <a href="referrals.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-slate-300"><i class="bx bx-user-plus text-lg"></i> <?= htmlspecialchars($navReferrals) ?></a>
                <a href="transactions.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-slate-300"><i class="bx bx-receipt text-lg"></i> <?= htmlspecialchars($navTransactions) ?></a>
                <a href="withdraw.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-slate-300"><i class="bx bx-money-withdraw text-lg"></i> <?= htmlspecialchars($navWithdraw) ?></a>
<a href="lotto.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-slate-300"><i class="bx bx-dice-6 text-lg"></i> Lotto-Sovereign</a>
<a href="predictions.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-slate-300"><i class="bx bx-bullseye text-lg"></i> Social Betting</a>
                <a href="settings.php" class="flex items-center gap-3 px-4 py-3 hover:bg-white/5 rounded-xl text-slate-300"><i class="bx bx-cog text-lg"></i> <?= htmlspecialchars($navSettings) ?></a>
            </nav>
        </div>
    </div>

    <!-- MAIN VIEW CONTAINER CONTENT -->
    <div class="flex-1 min-w-0 flex flex-col lg:pl-64">
        
        <!-- MOBILE ROW TOP HEADER -->
        <header class="lg:hidden bg-slate-950 border-b border-white/5 p-4 flex items-center justify-between sticky top-0 z-40">
            <h1 class="text-lg font-black text-yellow-400 tracking-wider"><?= htmlspecialchars($brand) ?></h1>
            <button onclick="toggleMobileMenu()" class="p-2 bg-slate-900 border border-white/5 text-yellow-400 rounded-xl">
                <i class="bx bx-menu text-xl"></i>
            </button>
        </header>

        <main class="flex-1 p-4 md:p-6 lg:p-8 space-y-6">
            
            <!-- Context Welcoming Row -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-white/5 pb-4">
                <div>
                    <h1 class="text-2xl font-black text-white tracking-tight"><?= htmlspecialchars($welcomePrefix) ?>, <?= htmlspecialchars($user['username']) ?> 👋</h1>
                    <p class="text-xs text-slate-400 mt-1">Ref Identity: <span class="font-sans text-slate-300 font-bold"><?= $user['referral_code'] ?></span></p>
                </div>
                <div class="flex items-center gap-4 self-end sm:self-auto">
                    <div class="text-right hidden sm:block">
                        <p class="text-[10px] uppercase font-bold text-slate-500 tracking-wider"><?= htmlspecialchars($rankLabel) ?></p>
                        <p class="text-sm font-bold text-yellow-400"><?= htmlspecialchars($displayRank) ?></p>
                    </div>
                    <button onclick="logout()" class="px-4 py-2 bg-rose-600/10 hover:bg-rose-600 text-rose-400 hover:text-white rounded-xl text-xs font-bold border border-rose-500/20 transition-all">
                        <?= htmlspecialchars($logoutText) ?>
                    </button>
                </div>
            </div>

            <!-- Financial Capital Metrics Matrices Cards -->
            <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                
                <!-- Aggregate Collective Portfolio Capitalization worth -->
                <div class="glass rounded-2xl p-5 bg-gradient-to-br from-slate-900/60 via-slate-900/40 to-blue-950/20 shadow-lg relative overflow-hidden group">
                    <p class="text-xs font-semibold uppercase text-slate-400 tracking-wider">Account Valuation Net Worth</p>
                    <p class="text-3xl font-bold font-black text-white mt-2">$<?= number_format($netWorth, 2) ?></p>
                    <p class="text-[10px] text-blue-400 mt-1">Cash + Active Exchange Resource Inventory Stocks</p>
                    <i class="bx bx-line-chart text-7xl text-blue-500/5 absolute right-2 bottom-0 pointer-events-none group-hover:scale-110 transition-transform"></i>
                </div>

                <!-- Liquid Fiat Holding Balance Field -->
                <div class="glass rounded-2xl p-5 shadow-lg relative overflow-hidden group">
                    <p class="text-xs font-semibold uppercase text-slate-400 tracking-wider"><?= htmlspecialchars($walletLabel) ?></p>
                    <p class="text-3xl font-bold font-black text-emerald-400 mt-2"><?= htmlspecialchars($currencySymbol) ?><?= number_format($wallet['balance'] ?? 0, 2) ?></p>
                    <div class="mt-4 flex gap-2">
                        <a href="deposit.php" class="w-1/2 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl text-center shadow-md transition-all"><?= htmlspecialchars($depositText) ?></a>
                        <a href="withdraw.php" class="w-1/2 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold rounded-xl text-center border border-white/5 transition-all"><?= htmlspecialchars($withdrawText) ?></a>
                    </div>
                </div>

                <!-- Active Mining running stashes counting -->
                <div class="glass rounded-2xl p-5 shadow-lg flex flex-col justify-between sm:col-span-2 lg:col-span-1">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-400 tracking-wider"><?= htmlspecialchars($activeMiningLabel) ?></p>
                            <p class="text-3xl font-sans font-black text-yellow-400 mt-2"><?= count($activeMinings) ?></p>
                        </div>
                        <span class="text-[10px] font-sans bg-yellow-500/10 text-yellow-400 px-2 py-0.5 rounded border border-yellow-500/20 uppercase tracking-tight"><?= htmlspecialchars($plansRunningText) ?></span>
                    </div>
                    <div class="mt-4 pt-2 border-t border-white/5 flex justify-between items-center">
                        <p class="text-xs text-slate-400"><?= htmlspecialchars($referralLabel) ?></p>
                        <span class="font-sans text-sm font-bold text-emerald-400"><?= htmlspecialchars($currencySymbol) ?>0.00</span>
                    </div>
                </div>
            </div>

            <!-- Highly Organized Core Navigation Shortcut Pathways Grid Matrix -->
            <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                <a href="trading.php" class="glass p-4 rounded-2xl border border-white/5 hover:border-blue-500/30 bg-slate-900/10 hover:bg-slate-900/40 transition-all flex items-start gap-3 group">
                    <div class="p-2.5 bg-blue-500/10 rounded-xl text-blue-400 group-hover:bg-blue-600 group-hover:text-white transition-all"><i class="bx bx-trending-up text-xl"></i></div>
                    <div>
                        <h4 class="text-sm font-bold text-white">Resource Exchange Market</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5 leading-relaxed">Trade interplanetary stone allocations with real-time dynamic spreads and custom price adjustments.</p>
                    </div>
                </a>
                <a href="mining.php" class="glass p-4 rounded-2xl border border-white/5 hover:border-yellow-500/30 bg-slate-900/10 hover:bg-slate-900/40 transition-all flex items-start gap-3 group">
                    <div class="p-2.5 bg-yellow-500/10 rounded-xl text-yellow-400 group-hover:bg-yellow-500 group-hover:text-black transition-all"><i class="bx bx-chip text-xl"></i></div>
                    <div>
                        <h4 class="text-sm font-bold text-white">Staking & Mining Rigs</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5 leading-relaxed">Launch high-scarcity algorithmic mining configurations and secure recurring session payouts.</p>
                    </div>
                </a>
                <a href="referrals.php" class="glass p-4 rounded-2xl border border-white/5 hover:border-purple-500/30 bg-slate-900/10 hover:bg-slate-900/40 transition-all flex items-start gap-3 group sm:col-span-2 lg:col-span-1">
                    <div class="p-2.5 bg-purple-500/10 rounded-xl text-purple-400 group-hover:bg-purple-600 group-hover:text-white transition-all"><i class="bx bx-group text-xl"></i></div>
                    <div>
                        <h4 class="text-sm font-bold text-white">Affiliate Matrix Center</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5 leading-relaxed">Track downline team networks, claim bonus spreads, and extract secondary code dividends.</p>
                    </div>
                </a>
            </div>

            <!-- Main Dynamic Columns Layout Breakdown matrices -->
            <div class="grid gap-6 lg:grid-cols-3">
                
                <!-- Left 2 Cols: Stashes and Mining Pipelines -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Exchange Assets Inventory holdings Section -->
                    <div class="glass rounded-2xl p-5 shadow-xl">
                        <div class="flex items-center justify-between border-b border-white/5 pb-3 mb-4">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2"><i class="bx bx-cube text-blue-400 text-base"></i> Exchange Stock Holdings Inventory</h3>
                            <a href="trading.php" class="text-xs font-bold text-blue-400 hover:text-blue-300 transition-all flex items-center gap-0.5">Trade Spot <i class="bx bx-chevron-right"></i></a>
                        </div>
                        <?php if (empty($holdings)): ?>
                            <div class="text-center py-6 text-slate-500 text-xs">
                                <i class="bx bx-package text-2xl mb-1 opacity-40"></i>
                                <p>No registered trade stones acquired yet inside account ledger matrix.</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs whitespace-nowrap">
                                    <thead>
                                        <tr class="text-slate-400 border-b border-white/5 font-semibold">
                                            <th class="pb-2">Resource Stock Context</th>
                                            <th class="pb-2 text-right">Acquired Balance</th>
                                            <th class="pb-2 text-right">Market Rate</th>
                                            <th class="pb-2 text-right">Aggregate Worth</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-white/5 text-slate-300">
                                        <?php foreach ($holdings as $h): 
                                            $worth = $h['units'] * $h['current_price'];
                                        ?>
                                            <tr class="hover:bg-white/[0.02] transition-colors">
                                                <td class="py-3 font-bold text-white flex items-center gap-2">
                                                    <span class="text-[10px] bg-blue-500/10 text-blue-400 font-sans px-1.5 py-0.5 rounded"><?= $h['ticker'] ?></span>
                                                    <?= htmlspecialchars($h['name']) ?>
                                                </td>
                                                <td class="py-3 text-right font-sans font-medium"><?= number_format($h['units'], 4) ?></td>
                                                <td class="py-3 text-right font-sans text-slate-400">$<?= number_format($h['current_price'], 4) ?></td>
                                                <td class="py-3 text-right font-sans text-emerald-400 font-bold">$<?= number_format($worth, 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Active Mining Plans tracking fields -->
                    <div class="glass rounded-2xl p-5 shadow-xl">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-300 border-b border-white/5 pb-3 mb-4 flex items-center gap-2">
                            <i class="bx bx-bolt text-yellow-400 text-base"></i> <?= htmlspecialchars($plansLabel) ?>
                        </h2>
                        <?php if (empty($activeMinings)): ?>
                            <p class="text-xs text-slate-400 text-center py-4">
                                <?= htmlspecialchars($noActiveMining) ?>
                                <a href="mining.php" class="ml-1 text-yellow-400 font-semibold hover:underline"><?= htmlspecialchars($browsePlansText) ?></a>
                            </p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($activeMinings as $mining): ?>
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center p-3 rounded-xl bg-slate-950/40 border border-white/5 gap-3">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-xs font-bold text-white"><?= htmlspecialchars($mining['plan_name']) ?></h3>
                                        <p class="text-[11px] text-slate-400 mt-0.5">
                                            <?= htmlspecialchars($dailyEarningLabel) ?> Multiplier:
                                            <span class="text-emerald-400 font-sans font-semibold"><?= htmlspecialchars($currencySymbol) ?><?= number_format($mining['daily_earning'] ?? 0, 2) ?></span>
                                        </p>
                                    </div>
                                    <button onclick="askClaimConfirmation(<?= (int) $mining['id'] ?>)" class="w-full sm:w-auto px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-black text-xs font-black rounded-xl transition-all shadow-md shadow-yellow-500/10">
                                        <?= htmlspecialchars($claimText) ?>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right 1 Col: Recent Audit System logs Notifications panel -->
                <div class="glass rounded-2xl p-5 shadow-xl h-fit">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-300 border-b border-white/5 pb-3 mb-4 flex items-center gap-2"><i class="bx bx-bell text-slate-400 text-base"></i> Operational Audit Logs</h2>
                    <?php if (empty($notifications)): ?>
                        <p class="text-xs text-slate-400 text-center py-6"><?= htmlspecialchars($noNotifications) ?></p>
                    <?php else: ?>
                        <ul class="space-y-3">
                            <?php foreach ($notifications as $notif): ?>
                            <li class="border-b border-white/5 pb-3 last:border-b-0 last:pb-0">
                                <div class="flex items-start justify-between cursor-pointer group" onclick="document.getElementById('msg-<?= $notif['id'] ?>').classList.toggle('hidden')">
                                    <div class="flex gap-2 text-xs">
                                        <span class="<?= is_null($notif['user_id']) ? 'text-cyan-400' : 'text-yellow-400' ?> flex-shrink-0 mt-0.5">●</span>
                                        <div>
                                            <div class="font-bold text-white group-hover:text-yellow-400 transition-colors">
                                                <?= htmlspecialchars($notif['title']) ?>
                                                <?php if (is_null($notif['user_id'])): ?>
                                                    <span class="ml-1 text-[8px] bg-cyan-500/10 text-cyan-400 px-1.5 py-0.2 rounded font-sans uppercase">System</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-[10px] text-slate-500 font-sans mt-0.5"><?= $notif['created_at'] ?></div>
                                        </div>
                                    </div>
                                    <span class="text-slate-600 group-hover:text-slate-300 text-[10px] mt-0.5"><i class="fas fa-chevron-down"></i></span>
                                </div>
                                <div id="msg-<?= $notif['id'] ?>" class="hidden mt-2 ml-4 text-[11px] text-slate-400 bg-slate-950/60 p-2.5 rounded-xl border border-white/5 leading-relaxed">
                                    <?= nl2br(htmlspecialchars($notif['message'])) ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Script Dependencies Bundle setup -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        function toggleMobileMenu() {
            const container = document.getElementById('mobileSidebar');
            const drawer = container.querySelector('div');
            if(container.classList.contains('hidden')) {
                container.classList.remove('hidden');
                setTimeout(() => {
                    container.classList.remove('opacity-0');
                    drawer.classList.remove('-translate-x-full');
                }, 10);
            } else {
                container.classList.add('opacity-0');
                drawer.classList.add('-translate-x-full');
                setTimeout(() => container.classList.add('hidden'), 300);
            }
        }

        function showToastNotification(text, type = 'success') {
            let className = "toastify-custom-success";
            if (type === 'error') className = "toastify-custom-error";
            Toastify({
                text: text,
                duration: 3500,
                close: true,
                gravity: "top", 
                position: "right",
                className: className,
                stopOnFocus: true
            }).showToast();
        }

        // ========================================================
        // ELEGANT CUSTOM MODAL DISPATCH SYSTEM
        // ========================================================
        let activeMiningIdToClaim = null;
        const confirmModal = document.getElementById('confirmModal');
        const confirmModalMessage = document.getElementById('confirmModalMessage');
        const confirmProceedBtn = document.getElementById('confirmProceedBtn');
        const confirmCancelBtn = document.getElementById('confirmCancelBtn');

        function askClaimConfirmation(miningId) {
            activeMiningIdToClaim = miningId;
            confirmModalMessage.textContent = <?= json_encode($claimConfirmMessage) ?>;
            confirmModal.classList.remove('hidden');
            setTimeout(() => {
                confirmModal.classList.remove('opacity-0');
                confirmModal.querySelector('.glass').classList.remove('scale-95');
            }, 10);
        }

        function closeConfirmationModal() {
            confirmModal.classList.add('opacity-0');
            confirmModal.querySelector('.glass').classList.add('scale-95');
            setTimeout(() => {
                confirmModal.classList.add('hidden');
                activeMiningIdToClaim = null;
            }, 300);
        }

        confirmCancelBtn.addEventListener('click', closeConfirmationModal);
        confirmProceedBtn.addEventListener('click', () => {
            if (!activeMiningIdToClaim) return;
            const currentId = activeMiningIdToClaim;
            closeConfirmationModal();

            const claimSuccessMessage = <?= json_encode($claimSuccessMessage) ?>;
            const claimFailedMessage = <?= json_encode($claimFailedMessage) ?>;

            fetch('api/claim.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({mining_id: currentId})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToastNotification(data.message || claimSuccessMessage, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToastNotification(data.message || claimFailedMessage, 'error');
                }
            })
            .catch(error => {
                showToastNotification('An error occurred during communication.', 'error');
            });
        });

        function logout() {
            window.location.href = 'logout.php';
        }
    </script>
</body>
</html>