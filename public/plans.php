<?php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/wallet.php';
require_once __DIR__ . '/../core/mining.php';
require_once __DIR__ . '/../core/marketplace.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user = getUser($user_id);
$wallet = getWallet($user_id);
$activeMinings = getActiveMinings($user_id);
$messages = [];

// Handle JSON Daily Earnings Claim via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'claim_yield') {
    header('Content-Type: application/json');
    $miningId = (int)($_POST['mining_id'] ?? 0);
    
    $result = claimMining($miningId);
    if ($result['success']) {
        $updatedWallet = getWallet($user_id);
        echo json_encode([
            'success' => true,
            'message' => 'Yield claimed successfully!',
            'new_balance' => '₦' . number_format($updatedWallet['balance'], 2)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message'] ?? 'Claim failed. Try again later.'
        ]);
    }
    exit;
}

$pageTitle = 'My Stone Portfolio - ' . (AppConfig::get('APP_ALIAS') ?: 'BitW');
require_once __DIR__ . '/pages/header.php';
?>

<div class="flex flex-col gap-6">
    <!-- Back to Dashboard Navigation link -->
    <div>
        <a href="dashboard.php" class="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-white transition-colors group">
            <i class="bx bx-arrow-back transition-transform group-hover:-translate-x-1"></i>
            Back to Dashboard
        </a>
    </div>

    <!-- Top Bar Section -->
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="badge">Vault Management</p>
            <h1 class="text-4xl font-semibold tracking-tight mt-2">Active Stone Portfolio</h1>
            <p class="mt-2 text-sm text-slate-400">Track contract lifespans, calculate remaining lifecycles, and claim your mining yields.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="glass-card px-4 py-2 flex items-center gap-2 border border-white/5">
                <i class="bx bx-wallet text-yellow-500"></i>
                <span class="text-sm text-slate-400">Balance:</span>
                <span id="global-wallet-balance" class="font-semibold text-white">₦<?= number_format($wallet['balance'] ?? 0, 2) ?></span>
            </div>
            <a href="mining.php" class="action-button" style="max-width: 200px;"><i class="bx bx-plus-circle"></i> Buy New Stone</a>
        </div>
    </div>

    <!-- Live Performance Metric Cards -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="glass-card p-5 border border-white/10">
            <p class="text-xs text-slate-400 uppercase tracking-wider">Active Operations</p>
            <div class="text-3xl font-semibold mt-2 text-emerald-400"><?= count($activeMinings) ?> Stones</div>
        </div>
        <div class="glass-card p-5 border border-white/10">
            <p class="text-xs text-slate-400 uppercase tracking-wider">Est. Combined Daily Yield</p>
            <div class="text-3xl font-semibold mt-2 text-white">
                <?php 
                $totalDaily = 0;
                foreach($activeMinings as $m) { $totalDaily += ($m['daily_earnings'] ?? $m['daily_earning'] ?? 0); }
                echo '₦' . number_format($totalDaily, 2);
                ?>
            </div>
        </div>
        <div class="glass-card p-5 border border-white/10">
            <p class="text-xs text-slate-400 uppercase tracking-wider">Network Connectivity</p>
            <div class="text-3xl font-semibold mt-2 text-sky-400 flex items-center gap-2">
                <span class="h-3 w-3 rounded-full bg-emerald-500 animate-pulse"></span> Synchronized
            </div>
        </div>
    </div>

    <!-- Active Purchases Management Console -->
    <section class="glass-card p-6">
        <div class="section-header mb-6">
            <div>
                <h2>Acquired Node Assets</h2>
                <p class="text-sm text-slate-400 mt-1">Real-time status tracking of running smart mining instances.</p>
            </div>
        </div>

        <div class="space-y-4">
            <?php if (empty($activeMinings)): ?>
                <div class="text-center py-12 rounded-2xl border border-dashed border-white/10 bg-slate-950/30">
                    <i class="bx bx-cube border border-white/10 p-4 rounded-2xl text-3xl text-slate-500 mb-3 block mx-auto w-fit"></i>
                    <p class="text-sm text-slate-400">No active stone packages found running in your architecture.</p>
                    <a href="mining.php" class="action-button mt-4 inline-block" style="max-width: 200px;">Explore Marketplace</a>
                </div>
            <?php else: ?>
                <?php foreach ($activeMinings as $mining): 
                    // Defend against missing/empty date columns gracefully
                    $rawDate = $mining['created_at'] ?? $mining['date'] ?? null;
                    $createdTime = $rawDate ? strtotime($rawDate) : time();
                    
                    $durationSeconds = (int)($mining['duration_days'] ?? 30) * 86400;
                    $expiryTime = $createdTime + $durationSeconds;
                    $timeLeft = $expiryTime - time();
                    $daysRemaining = max(0, ceil($timeLeft / 86400));
                    
                    // Progress Bar Percentage logic
                    $elapsed = time() - $createdTime;
                    $progressPercent = $durationSeconds > 0 ? min(100, max(0, ($elapsed / $durationSeconds) * 100)) : 100;

                    // Time lock verification for claims
                    $lastClaim = strtotime($mining['last_claim'] ?? '0000-00-00');
                    $canClaim = (time() - $lastClaim >= 86400);
                ?>
                    <div class="rounded-2xl border border-white/10 p-5 bg-slate-950/20 relative overflow-hidden group hover:border-white/20 transition-all">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between relative z-10">
                            
                            <!-- Left: Asset Info -->
                            <div class="flex items-start gap-4">
                                <?php if (!empty($mining['image'])): ?>
                                    <img src="<?= htmlspecialchars($mining['image']) ?>" class="w-14 h-14 rounded-xl object-cover border border-white/10 bg-slate-900 shadow-lg" alt="Stone preview">
                                <?php else: ?>
                                    <div class="w-14 h-14 rounded-xl border border-dashed border-slate-700 bg-slate-950 flex items-center justify-center text-yellow-500 text-2xl shadow-lg">
                                        <i class="bx bx-diamond"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-lg font-semibold text-white"><?= htmlspecialchars($mining['plan_name']) ?></h3>
                                        <span class="text-[10px] uppercase tracking-widest px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Running</span>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-0.5">Purchased on: <?= $rawDate ? date('M d, Y • H:i', $createdTime) : 'N/A' ?></p>
                                    
                                    <div class="grid grid-cols-2 gap-x-6 gap-y-1 mt-3 text-sm text-slate-300">
                                        <div><span class="text-slate-500">Principal Allocation:</span> ₦<?= number_format($mining['amount'] ?? 0, 2) ?></div>
                                        <div><span class="text-slate-500">Daily Accumulation:</span> ₦<?= number_format($mining['daily_earnings'] ?? $mining['daily_earning'] ?? 0, 2) ?></div>
                                        <div><span class="text-slate-500">Accrued to Date:</span> ₦<?= number_format($mining['total_earned'] ?? 0, 2) ?></div>
                                        <div><span class="text-slate-500">Contract Expiry:</span> <span class="text-rose-400 font-medium"><?= $daysRemaining ?> days left</span></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Claim Action -->
                            <div class="flex flex-col items-stretch lg:items-end gap-2 justify-center min-w-[200px]">
                                <button type="button" 
                                        data-claim-btn="<?= (int)$mining['id'] ?>"
                                        onclick="processDailyClaim(this, <?= (int)$mining['id'] ?>)" 
                                        class="action-button w-full transition active:scale-98 flex items-center justify-center gap-2 <?= !$canClaim ? 'opacity-50 pointer-events-none bg-slate-800' : '' ?>">
                                    <i class="bx bx-cube-alt"></i> 
                                    <span><?= $canClaim ? 'Claim Daily Yield' : 'Claimed Today' ?></span>
                                </button>
                                <?php if(!$canClaim): ?>
                                    <p class="text-[11px] text-slate-500 text-center lg:text-right"><i class="bx bx-time-five"></i> Ready in less than 24h</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Progress Bar Metrics for Contract Lifespan -->
                        <div class="mt-5 pt-3 border-t border-white/5 flex items-center gap-4 text-xs text-slate-400">
                            <span class="shrink-0">Lifespan Progress</span>
                            <div class="w-full bg-slate-900 rounded-full h-1.5 border border-white/5 overflow-hidden">
                                <div class="bg-gradient-to-r from-amber-500 to-yellow-400 h-1.5 rounded-full transition-all" style="width: <?= $progressPercent ?>%"></div>
                            </div>
                            <span class="shrink-0 text-right font-mono"><?= number_format($progressPercent, 1) ?>%</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- Snappy Global Alert Banner -->
<div id="snappy-toast" class="fixed bottom-6 right-6 z-50 transform translate-y-20 opacity-0 transition-all duration-300 pointer-events-none glass-card p-4 border max-w-sm flex items-center gap-3 shadow-2xl">
    <div id="toast-icon" class="w-8 h-8 rounded-xl flex items-center justify-center text-lg"></div>
    <p id="toast-text" class="text-sm font-medium text-white"></p>
</div>

<script>
function showSnappyAlert(text, isSuccess = true) {
    const toast = document.getElementById('snappy-toast');
    const icon = document.getElementById('toast-icon');
    const message = document.getElementById('toast-text');
    
    message.textContent = text;
    if (isSuccess) {
        toast.className = "fixed bottom-6 right-6 z-50 transition-all duration-300 glass-card p-4 border border-emerald-500/30 bg-emerald-950/80 text-white flex items-center gap-3 shadow-2xl";
        icon.className = "w-8 h-8 rounded-xl flex items-center justify-center text-lg bg-emerald-500/20 text-emerald-400";
        icon.innerHTML = "<i class='bx bx-check-circle'></i>";
    } else {
        toast.className = "fixed bottom-6 right-6 z-50 transition-all duration-300 glass-card p-4 border border-rose-500/30 bg-rose-950/80 text-white flex items-center gap-3 shadow-2xl";
        icon.className = "w-8 h-8 rounded-xl flex items-center justify-center text-lg bg-rose-500/20 text-rose-400";
        icon.innerHTML = "<i class='bx bx-error-circle'></i>";
    }
    
    // Animate view
    toast.classList.remove('translate-y-20', 'opacity-0');
    
    setTimeout(() => {
        toast.classList.add('translate-y-20', 'opacity-0');
    }, 4000);
}

function processDailyClaim(button, miningId) {
    if (button.classList.contains('pointer-events-none')) return;
    
    button.classList.add('pointer-events-none', 'opacity-60');
    const originalContent = button.innerHTML;
    button.innerHTML = "<i class='bx bx-loader-alt animate-spin'></i> Syncing Vault...";
    
    const data = new FormData();
    data.append('action', 'claim_yield');
    data.append('mining_id', miningId);
    
    fetch('plans.php', {
        method: 'POST',
        body: data,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => {
        if (!res.ok) throw new Error();
        return res.json();
    })
    .then(payload => {
        if (payload.success) {
            showSnappyAlert(payload.message, true);
            // Dynamic UI updates without full page reloads
            document.getElementById('global-wallet-balance').textContent = payload.new_balance;
            button.className = "action-button w-full opacity-50 pointer-events-none bg-slate-800 flex items-center justify-center gap-2";
            button.innerHTML = "<i class='bx bx-cube-alt'></i> <span>Claimed Today</span>";
        } else {
            showSnappyAlert(payload.message, false);
            button.classList.remove('pointer-events-none', 'opacity-60');
            button.innerHTML = originalContent;
        }
    })
    .catch(() => {
        showSnappyAlert('Network communication failure. Could not parse node connection.', false);
        button.classList.remove('pointer-events-none', 'opacity-60');
        button.innerHTML = originalContent;
    });
}
</script>

<?php require_once __DIR__ . '/pages/footer.php'; ?>