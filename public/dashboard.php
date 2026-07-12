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
$navTrading = $dashboardConfig['NAV_TRADING'] ?? 'Trading';
$navReferrals = $dashboardConfig['NAV_REFERRALS'] ?? 'Referrals';
$navTransactions = $dashboardConfig['NAV_TRANSACTIONS'] ?? 'Transactions';
$navWithdraw = $dashboardConfig['NAV_WITHDRAW'] ?? 'Withdraw';
$navSettings = $dashboardConfig['NAV_SETTINGS'] ?? 'Settings';

// ========================================================
// SIMPLE RANK DISPLAY TOGGLE SWITCH
// ========================================================
$useRankName = true; 

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
    
    <!-- Toastify CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <style>
        .glass { background: rgba(255,255,255,0.05); backdrop-filter: blur(12px); }
        #sidebar { transition: transform 0.3s ease; }
        .sidebar-hidden { transform: translateX(-100%); }
        * { transition: all 0.2s ease; }
        .btn-transition:hover { opacity: 0.9; }
        @media (max-width: 768px) {
            #sidebar { width: 60vw; max-width: 240px; }
        }
        
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
</head>
<body class="bg-gray-950 text-white min-h-screen flex flex-col">

    <!-- ==================== CUSTOM CONFIRMATION MODAL ==================== -->
    <div id="confirmModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm hidden opacity-0 transition-all duration-300">
        <div class="glass border border-gray-700 max-w-sm w-full p-6 rounded-3xl shadow-2xl transform scale-95 transition-all duration-300">
            <div class="flex items-center gap-3 text-yellow-400 mb-4">
                <i class="fas fa-exclamation-circle text-2xl"></i>
                <h3 class="text-xl font-bold">Confirm Action</h3>
            </div>
            <p id="confirmModalMessage" class="text-gray-300 text-sm leading-relaxed mb-6"></p>
            <div class="flex items-center justify-end gap-3">
                <button id="confirmCancelBtn" class="px-5 py-2.5 bg-gray-800 hover:bg-gray-700 text-sm rounded-xl transition font-medium">
                    Cancel
                </button>
                <button id="confirmProceedBtn" class="px-5 py-2.5 bg-yellow-500 hover:bg-yellow-600 text-black text-sm rounded-xl transition font-semibold">
                    Yes, Proceed
                </button>
            </div>
        </div>
    </div>
    <!-- =================================================================== -->

    <!-- DESKTOP SIDEBAR -->
    <div id="sidebar" class="w-72 bg-black h-screen fixed top-0 left-0 p-6 z-50 md:block lg:block hidden">
        <div class="flex items-center gap-3 mb-10">
            <h1 class="text-3xl font-bold text-yellow-400"><?= htmlspecialchars($brand) ?></h1>
        </div>
        <nav class="space-y-2">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 bg-gray-800 rounded-xl text-white"><i class="fas fa-home"></i> <?= htmlspecialchars($navDashboard) ?></a>
            <a href="plans.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 rounded-xl text-gray-400 hover:text-white"><i class="fas fa-folder-open"></i> My Portfolio</a>
            <a href="mining.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 rounded-xl text-gray-400 hover:text-white"><i class="fas fa-microchip"></i> <?= htmlspecialchars($navMining) ?></a>
            <a href="trading.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 rounded-xl text-gray-400 hover:text-white"><i class="fas fa-chart-line"></i> <?= htmlspecialchars($navTrading) ?></a>
            <a href="referrals.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 rounded-xl text-gray-400 hover:text-white"><i class="fas fa-users"></i> <?= htmlspecialchars($navReferrals) ?></a>
            <a href="transactions.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 rounded-xl text-gray-400 hover:text-white"><i class="fas fa-history"></i> <?= htmlspecialchars($navTransactions) ?></a>
            <a href="withdraw.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 rounded-xl text-gray-400 hover:text-white"><i class="fas fa-money-bill"></i> <?= htmlspecialchars($navWithdraw) ?></a>
            <a href="settings.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 rounded-xl text-gray-400 hover:text-white"><i class="fas fa-cog"></i> <?= htmlspecialchars($navSettings) ?></a>
        </nav>
    </div>

    <!-- MOBILE NAVIGATION HEADER -->
    <div class="md:hidden lg:hidden flex items-center justify-between p-4 bg-black z-50 relative border-b border-gray-900">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-yellow-400"><?= htmlspecialchars($brand) ?></h1>
        </div>
        <button id="menuToggle" class="p-2 text-yellow-400 hover:text-yellow-300 focus:outline-none">
            <i class="fas fa-bars"></i>
        </button>

        <!-- MOBILE FLOATING DROPDOWN MENU -->
        <div id="mobileMenu" class="hidden absolute top-full left-0 w-full bg-black border-b border-gray-800 p-4 space-y-2 shadow-xl">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 bg-gray-900 rounded-xl text-white"><i class="fas fa-home"></i> <?= htmlspecialchars($navDashboard) ?></a>
            <a href="trading.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-900 rounded-xl text-gray-300"><i class="fas fa-chart-line"></i> <?= htmlspecialchars($navTrading) ?></a>
            <a href="plans.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-900 rounded-xl text-gray-300"><i class="fas fa-folder-open"></i> My Portfolio</a>
            <a href="mining.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-900 rounded-xl text-gray-300"><i class="fas fa-microchip"></i> <?= htmlspecialchars($navMining) ?></a>
            <a href="referrals.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-900 rounded-xl text-gray-300"><i class="fas fa-users"></i> <?= htmlspecialchars($navReferrals) ?></a>
            <a href="transactions.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-900 rounded-xl text-gray-300"><i class="fas fa-history"></i> <?= htmlspecialchars($navTransactions) ?></a>
            <a href="withdraw.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-900 rounded-xl text-gray-300"><i class="fas fa-money-bill"></i> <?= htmlspecialchars($navWithdraw) ?></a>
            <a href="settings.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-900 rounded-xl text-gray-300"><i class="fas fa-cog"></i> <?= htmlspecialchars($navSettings) ?></a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-8 md:ml-72 lg:ml-72">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6">
            <h1 class="text-3xl font-bold mb-4 md:mb-0">
                <?= htmlspecialchars($welcomePrefix) ?>, <?= htmlspecialchars($user['username']) ?> 👋
            </h1>
            <div class="flex flex-col md:flex-row md:items-center md:space-x-6">
                <div class="text-center md:text-right">
                    <p class="text-sm text-gray-400"><?= htmlspecialchars($rankLabel) ?></p>
                    <p class="text-xl font-semibold text-yellow-400">
                        <?= htmlspecialchars($displayRank) ?>
                    </p>
                </div>
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
                    <a href="mining.php" class="ml-2 text-yellow-400 hover:underline">
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
                        <button onclick="askClaimConfirmation(<?= (int) $mining['id'] ?>)" class="mt-3 md:mt-0 self-align-end px-5 py-2 bg-yellow-500 hover:bg-yellow-600 text-black font-semibold rounded-2xl">
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
                    <li class="border-b border-gray-800/60 pb-3 last:border-b-0">
                        <!-- Toggle Header Block -->
                        <div class="flex items-start justify-between cursor-pointer group" onclick="document.getElementById('msg-<?= $notif['id'] ?>').classList.toggle('hidden')">
                            <div class="flex gap-3 text-sm">
                                <span class="<?= is_null($notif['user_id']) ? 'text-cyan-400' : 'text-yellow-400' ?> flex-shrink-0 mt-1">●</span>
                                <div>
                                    <div class="font-medium group-hover:text-yellow-400 transition-colors">
                                        <?= htmlspecialchars($notif['title']) ?>
                                        <?php if (is_null($notif['user_id'])): ?>
                                            <span class="ml-2 text-[10px] bg-cyan-500/20 text-cyan-400 px-2 py-0.5 rounded-full uppercase tracking-wider font-semibold">Broadcast</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-xs text-gray-400 mt-0.5"><?= $notif['created_at'] ?></div>
                                </div>
                            </div>
                            <span class="text-gray-500 group-hover:text-gray-300 text-xs mt-1"><i class="fas fa-chevron-down"></i></span>
                        </div>
                        
                        <!-- Collapsible Body Content -->
                        <div id="msg-<?= $notif['id'] ?>" class="hidden mt-2 ml-6 text-sm text-gray-300 bg-black/40 p-3 rounded-xl border border-gray-800 leading-relaxed">
                            <?= nl2br(htmlspecialchars($notif['message'])) ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>

    <!-- Toastify JavaScript -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <script>
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.getElementById('menuToggle');
        const mobileMenu = document.getElementById('mobileMenu');

        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                // For desktop view handling adjustments
                if (window.innerWidth > 768) {
                    sidebar.classList.toggle('hidden');
                    document.querySelector('main').classList.toggle('ml-0');
                    document.querySelector('main').classList.toggle('ml-72');
                } else {
                    // For mobile menu layout handling
                    mobileMenu.classList.toggle('hidden');
                }
            });
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
            
            // Show modal cleanly with styles
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
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToastNotification(data.message || claimFailedMessage, 'error');
                }
            })
            .catch(error => {
                showToastNotification('An error occurred during communication.', 'error');
            });
        });
        // ========================================================

        function logout() {
            window.location.href = 'logout.php';
        }
    </script>
</body>
</html>