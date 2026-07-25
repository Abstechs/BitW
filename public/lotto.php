<?php
// public/lotto.php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userId = $_SESSION['user_id'] ?? 0;
$userRawName = $_SESSION['username'] ?? '';

// Fallback initializations only used if query execution yields nothing
$minAllocation = 200; 
$volatilityMultiplier = '1.0';
$gravityConstant = '0.0';
$realPool = 0.00; 
$realUsers = 0; 
$demoPool = 0.00; 
$demoUsers = 0;
$lastPayout = '₦ 0'; 
$predictionHistory = [];

$yesterday = date('Y-m-d', strtotime('-1 day'));
$yesterdayDraw = null;
$publicLedgerRows = [];
$userWonNotice = false;
$userWonAmount = 0.00;

function maskingUsername($raw) {
    if (strlen($raw) <= 4) return substr($raw, 0, 1) . '***' . substr($raw, -1);
    return substr($raw, 0, 3) . '***' . substr($raw, -2);
}

try {
    // 1. System configurations
    $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('min_lotto_allocation', 'global_volatility', 'mean_reversion_gravity')");
    if ($settingsStmt) {
        $settings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        if (isset($settings['min_lotto_allocation'])) $minAllocation = (int)$settings['min_lotto_allocation'];
        if (isset($settings['global_volatility'])) $volatilityMultiplier = $settings['global_volatility'];
        if (isset($settings['mean_reversion_gravity'])) $gravityConstant = $settings['mean_reversion_gravity'];
    }

    // 2. Pools calculations
    $poolStmt = $pdo->query("
        SELECT 
            SUM(CASE WHEN mode = 'real' THEN amount ELSE 0 END) as real_total,
            COUNT(DISTINCT CASE WHEN mode = 'real' THEN user_id END) as real_users,
            SUM(CASE WHEN mode = 'demo' THEN amount ELSE 0 END) as demo_total,
            COUNT(DISTINCT CASE WHEN mode = 'demo' THEN user_id END) as demo_users
        FROM lotto_allocations 
        WHERE draw_date = CURDATE()
    ");
    
    if ($poolStmt) {
        $pools = $poolStmt->fetch(PDO::FETCH_ASSOC);
        $realPool = (float)($pools['real_total'] ?? 0.00);
        $realUsers = (int)($pools['real_users'] ?? 0);
        $demoPool = (float)($pools['demo_total'] ?? 0.00);
        $demoUsers = (int)($pools['demo_users'] ?? 0);
    }

    // 3. Last distributed payout metrics
    $lastWinnerRow = $pdo->query("SELECT amount FROM lotto_winners ORDER BY won_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!empty($lastWinnerRow['amount'])) {
        $lastPayout = '₦ ' . number_format($lastWinnerRow['amount']);
    }

    // 4. History log collection
    $historyStmt = $pdo->prepare("SELECT sequence, amount, outcome, status, mode FROM lotto_allocations WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5");
    $historyStmt->execute(['user_id' => $userId]);
    $predictionHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Verification matrix mappings
    $drawQ = $pdo->prepare("SELECT lucky_number FROM lotto_draws WHERE draw_date = :draw_date AND is_released = 1 LIMIT 1");
    $drawQ->execute(['draw_date' => $yesterday]);
    $yesterdayDraw = $drawQ->fetch(PDO::FETCH_ASSOC);

    if ($yesterdayDraw) {
        $ledgerQ = $pdo->prepare("SELECT username, sequence, payout_amount, verified_hash FROM lotto_public_ledger WHERE draw_date = :draw_date ORDER BY payout_amount DESC");
        $ledgerQ->execute(['draw_date' => $yesterday]);
        $publicLedgerRows = $ledgerQ->fetchAll(PDO::FETCH_ASSOC);

        $maskedUser = maskingUsername($userRawName);
        foreach ($publicLedgerRows as $row) {
            if ($row['username'] === $maskedUser) {
                $userWonNotice = true;
                $userWonAmount = (float)$row['payout_amount'];
            }
        }
    }

} catch (PDOException $e) {
    error_log("Lotto Core Engine Exception Error: " . $e->getMessage());
}

$pageTitle = 'Lottery Play Dashboard';
require_once __DIR__ . '/pages/header.php';
?>

<!-- Inject Toastify Stylesheets Assets dynamically into page head context -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<!-- Inject Boxicons stylesheet asset library explicitly if not captured by the template header -->
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

<div class="max-w-7xl mx-auto px-4 py-6 space-y-8">
    
    <!-- Primary Page Top Header Assembly Layout Component -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-white/5 pb-6">
        <div>
            <p class="badge text-xs uppercase tracking-widest text-purple-400 font-bold bg-purple-500/10 px-3 py-1 rounded-md inline-block">Gaming Platform Module</p>
            <h1 class="text-3xl font-black text-white mt-2 tracking-tight">Lotto Matrix <span id="title-mode-badge" class="text-purple-500 transition-all">LIVE</span></h1>
            <div class="flex flex-wrap items-center gap-4 mt-2">
                <p class="text-sm text-slate-400">Select numbers, submit entry stakes, and track pool distribution status metrics.</p>
                <div class="flex items-center gap-2 bg-slate-950 p-1 rounded-xl border border-white/5">
                    <button type="button" onclick="switchLottoReality('real')" id="real-mode-btn" class="px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest bg-purple-600 text-white shadow-md transition-all">LIVE ENTRIES</button>
                    <button type="button" onclick="switchLottoReality('demo')" id="demo-mode-btn" class="px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-slate-300 transition-all">DEMO SANDBOX</button>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <!-- Context Navigation Link routing to Draw Results Matrix Page Component -->
            <a href="results.php" class="bg-slate-900 hover:bg-slate-850 border border-white/10 hover:border-purple-500/30 text-slate-200 hover:text-white px-4 py-3.5 rounded-2xl flex items-center gap-2 text-xs font-black uppercase tracking-wider transition-all shadow-md group">
                <i class="bx bx-trophy text-purple-400 group-hover:scale-110 transition-transform text-sm"></i>
                <span>Draw Results Board</span>
            </a>
            <div class="bg-slate-900/80 border border-white/5 rounded-2xl px-5 py-3 flex flex-col items-end shadow-inner">
                <span class="text-[10px] uppercase tracking-widest text-slate-500 font-bold">Time Until Next Pool Draw</span>
                <span class="text-2xl font-mono font-bold text-rose-500 tracking-wider" id="draw-timer">00:00:00</span>
            </div>
        </div>
    </div>

    <!-- Responsible Gaming Platform Protection Layer Frame -->
    <div class="bg-amber-500/5 border border-amber-500/10 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center shrink-0 border border-amber-500/20 text-amber-500 text-xl font-bold font-sans">18+</div>
            <div>
                <h4 class="text-xs font-black uppercase tracking-wider text-amber-500">Responsible Gaming Protection Notice</h4>
                <p class="text-xs text-slate-400 mt-0.5 leading-relaxed">
                    Lottery entry options involve explicit financial risk. Access is strictly forbidden to players under 18 years of age. Budget responsibly—never stake essential income resources or account capital you cannot afford to comfortably lose.
                </p>
            </div>
        </div>
        <div class="shrink-0">
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-500 border border-white/5 bg-slate-950 px-3 py-1.5 rounded-lg">MIN STAKE: ₦<?php echo number_format($minAllocation); ?></span>
        </div>
    </div>

    <?php if ($yesterdayDraw): ?>
    <div class="grid gap-6 md:grid-cols-12">
        <!-- Modified Draw Results Matrix deep-link component block panel -->
        <div class="md:col-span-4 bg-gradient-to-br from-slate-900 to-slate-950 border border-emerald-500/20 p-6 rounded-2xl flex flex-col justify-between shadow-xl relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/5 rounded-full blur-xl"></div>
            <div>
                <span class="text-[9px] font-black tracking-widest uppercase bg-emerald-500/10 text-emerald-400 px-2.5 py-1 rounded border border-emerald-500/20">Archive Drawing Result</span>
                <h3 class="text-sm font-bold text-slate-300 mt-3">Yesterday's Winning Sequence</h3>
                <p class="text-xs text-slate-500 mt-0.5">Drawn on date context: <?php echo htmlspecialchars($yesterday, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="my-4 py-2 bg-slate-950/60 rounded-xl border border-white/5 text-center">
                <span class="text-3xl font-mono font-black tracking-widest text-emerald-400"><?php echo htmlspecialchars($yesterdayDraw['lucky_number'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="space-y-3">
                <?php if ($userWonNotice): ?>
                    <div class="bg-emerald-500/10 border border-emerald-500/20 p-2.5 rounded-xl text-center">
                        <p class="text-xs font-black text-emerald-400 animate-bounce">✓ YOUR STAKE MATCHED! Payout of ₦<?php echo number_format($userWonAmount, 2); ?> transferred to your primary ledger wallet.</p>
                    </div>
                <?php else: ?>
                    <p class="text-[11px] text-slate-400 text-center italic mb-1">No matching combinations detected on this user account identifier.</p>
                <?php endif; ?>
                
                <!-- Explicit historical context validation deep-link parsing parameters -->
                <a href="results.php?date=<?php echo urlencode($yesterday); ?>" class="w-full bg-slate-950 hover:bg-slate-900 border border-white/5 hover:border-emerald-500/30 text-slate-400 hover:text-emerald-400 text-[10px] font-black uppercase tracking-widest py-2.5 rounded-xl flex items-center justify-center gap-1.5 transition-all">
                    <span>Verify Draw Metrics</span>
                    <i class="bx bx-right-arrow-alt text-sm transition-transform group-hover:translate-x-0.5"></i>
                </a>
            </div>
        </div>

        <div class="md:col-span-8 glass-card border border-white/5 overflow-hidden flex flex-col justify-between">
            <div class="p-4 border-b border-white/5 bg-slate-900/40 flex items-center justify-between">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-300 flex items-center gap-2">
                    <i class="bx bx-check-shield text-emerald-400"></i> Decentralized Validation Node Logs
                </h3>
            </div>
            <div class="overflow-x-auto grow max-h-48 overflow-y-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-950 text-slate-400 font-bold border-b border-white/5 text-[10px] uppercase tracking-wider sticky top-0">
                            <th class="p-3">Winner Account</th>
                            <th class="p-3">Sequence</th>
                            <th class="p-3">Distributed Return</th>
                            <th class="p-3 font-mono">Validation Sign</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 font-medium">
                        <?php if (!empty($publicLedgerRows)): ?>
                            <?php foreach ($publicLedgerRows as $ledgerRow): ?>
                                <tr class="hover:bg-white/[0.01]">
                                    <td class="p-3 text-slate-300"><?php echo htmlspecialchars($ledgerRow['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="p-3 font-mono font-bold text-white tracking-wider"><?php echo htmlspecialchars($ledgerRow['sequence'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="p-3 text-emerald-400 font-mono">₦ <?php echo number_format($ledgerRow['payout_amount']); ?></td>
                                    <td class="p-3 text-slate-600 font-mono text-[9px] truncate max-w-[120px]" title="<?php echo htmlspecialchars($ledgerRow['verified_hash'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($ledgerRow['verified_hash'], ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="p-4 text-center text-slate-500 italic">No drawing execution sequences ledger logs found for yesterday.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid gap-8 lg:grid-cols-12">
        <div class="lg:col-span-8 space-y-8">
            <div id="terminal-card" class="glass-card p-8 border-t-4 border-purple-600 relative overflow-hidden shadow-2xl transition-all duration-300">
                <div class="relative z-10">
                    <div class="flex justify-between items-start mb-6">
                        <h2 class="text-xl font-bold text-white flex items-center gap-3">
                            <i id="terminal-icon" class="bx bx-target-lock text-purple-500 transition-colors"></i> 
                            Number Configuration Entry (<span id="form-reality-label" class="text-purple-400 font-mono">Live Session</span>)
                        </h2>
                        <span id="risk-warning-badge" class="text-[9px] font-black tracking-widest uppercase px-2.5 py-1 bg-rose-500/10 text-rose-400 rounded border border-rose-500/20 animate-pulse">CAPITAL AT RISK</span>
                    </div>
                    
                    <form id="lottoExecutionForm" method="POST" action="api/submit-prediction.php" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="lotto_mode" id="lotto-mode-input" value="real">
                        
                        <div class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-widest text-slate-400">Select Matrix Length Option</label>
                            <div class="grid grid-cols-3 gap-2 bg-slate-950 p-1 rounded-xl border border-white/5 max-w-sm">
                                <button type="button" onclick="adjustDigitGridLength(4)" id="len-btn-4" class="py-2 rounded-lg text-xs font-bold text-slate-400 hover:text-white transition-all">4 Digits</button>
                                <button type="button" onclick="adjustDigitGridLength(6)" id="len-btn-6" class="py-2 rounded-lg text-xs font-bold bg-purple-600 text-white shadow transition-all">6 Digits</button>
                                <button type="button" onclick="adjustDigitGridLength(8)" id="len-btn-8" class="py-2 rounded-lg text-xs font-bold text-slate-400 hover:text-white transition-all">8 Digits</button>
                            </div>
                            <input type="hidden" name="prediction_length" id="prediction-len-input" value="6">
                        </div>

                        <div class="space-y-3">
                            <label class="text-xs font-bold uppercase tracking-widest text-slate-400 flex justify-between">
                                <span>Input Your Chosen Sequence Matrix</span>
                                <span class="text-slate-600 lowercase font-normal">Supports standard text pasting intercepts</span>
                            </label>
                            <div id="digit-grid-wrapper" class="flex gap-2 justify-between"></div>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-widest text-slate-400">Stake Allocation Amount</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 font-bold font-mono">₦</span>
                                    <input type="number" 
                                           name="bet_amount" 
                                           id="bet-amount" 
                                           min="<?php echo $minAllocation; ?>" 
                                           step="50" 
                                           required 
                                           class="w-full bg-slate-950 border border-white/10 rounded-2xl py-4 pl-10 pr-4 text-white font-mono font-bold focus:outline-none focus:border-purple-500 transition-colors" 
                                           placeholder="Min <?php echo number_format($minAllocation); ?>">
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-widest text-slate-400">Potential Wins Multiplier Return</label>
                                <div class="w-full bg-slate-950/80 border border-white/5 rounded-2xl py-4 px-4 text-emerald-400 font-mono font-black flex items-center justify-between shadow-inner">
                                    <span>₦ <span id="projected-win">0.00</span></span>
                                    <span class="text-[10px] bg-emerald-500/10 px-2.5 py-1 rounded-md text-emerald-400 uppercase font-sans font-bold tracking-wider"><span id="odds-payout-label">x500</span> Odds Payout</span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" id="submit-engine-btn" class="w-full bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 text-white font-black py-4 rounded-2xl shadow-xl shadow-purple-500/10 transition-all transform hover:-translate-y-0.5">
                                SUBMIT SYSTEM LEVEL ENTRY MATRIX
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="glass-card overflow-hidden shadow-xl">
                <div class="p-6 border-b border-white/5 flex items-center justify-between bg-slate-900/20">
                    <h3 class="font-bold text-white flex items-center gap-2 text-sm">
                        <i class="bx bx-notepad text-slate-400"></i> Personal Historical Game Ledger Logs
                    </h3>
                    <a href="history.php" class="text-xs text-blue-400 font-bold hover:text-blue-300 hover:underline transition-colors">View All Entries</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] uppercase tracking-widest text-slate-400 bg-slate-950/50 border-b border-white/5">
                                <th class="px-6 py-4">Session Context</th>
                                <th class="px-6 py-4">Selected Digits</th>
                                <th class="px-6 py-4">Staked Capital</th>
                                <th class="px-6 py-4">Draw Status Result</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php if (!empty($predictionHistory)): ?>
                                <?php foreach ($predictionHistory as $row): ?>
                                    <tr class="hover:bg-white/[0.02] transition-colors">
                                        <td class="px-6 py-4">
                                            <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded <?php echo $row['mode'] === 'real' ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'bg-blue-500/10 text-blue-400 border border-blue-500/20'; ?>">
                                                <?php echo htmlspecialchars($row['mode'] === 'real' ? 'Live Play' : 'Sandbox Demo', ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 font-mono font-bold text-white tracking-widest"><?php echo htmlspecialchars($row['sequence'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="px-6 py-4 text-slate-300 font-mono">₦ <?php echo number_format($row['amount'], 2); ?></td>
                                        <td class="px-6 py-4">
                                            <?php 
                                                $status = strtolower($row['status'] ?? 'pending');
                                                if ($status === 'settled' || $status === 'won') {
                                                    echo '<span class="text-[11px] font-bold text-emerald-400 bg-emerald-500/5 border border-emerald-500/10 px-2 py-0.5 rounded-md">Win Settled</span>';
                                                } elseif ($status === 'failed' || $status === 'lost') {
                                                    echo '<span class="text-[11px] font-bold text-rose-400 bg-rose-500/5 border border-rose-500/10 px-2 py-0.5 rounded-md">Lost Draw</span>';
                                                } else {
                                                    echo '<span class="text-[11px] font-bold text-amber-400 bg-amber-500/5 border border-amber-500/10 px-2 py-0.5 rounded-md animate-pulse">Awaiting Draw</span>';
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-sm text-slate-500 font-medium">No system entries found for this user account context.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="lg:col-span-4 space-y-6">
            <div class="glass-card p-6 bg-gradient-to-br from-slate-900 to-purple-950/20 shadow-xl border-l-2 border-purple-500">
                <h3 id="sidebar-pool-label" class="text-xs font-black uppercase tracking-widest text-purple-400 mb-4">Total Live Active Pool Stakes</h3>
                <div class="space-y-1">
                    <p class="text-4xl font-black text-white tracking-tight font-mono" id="sidebar-pool-amount">₦ <?php echo number_format($realPool); ?></p>
                    <p class="text-xs text-slate-500">Global active draw liquidity metrics</p>
                </div>
                <div class="mt-6 pt-6 border-t border-white/5 grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-[10px] uppercase text-slate-500 font-black tracking-wider">Active Players</span>
                        <p class="text-lg font-bold text-white font-mono" id="sidebar-participant-count"><?php echo number_format($realUsers); ?></p>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase text-slate-500 font-black tracking-wider">Last Big Winner</span>
                        <p class="text-lg font-bold text-emerald-400 font-mono"><?php echo htmlspecialchars($lastPayout, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </div>

            <div class="glass-card p-6 bg-slate-900/30 border border-white/5 space-y-4">
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-200 flex items-center gap-2">
                    <i class="bx bx-math text-blue-400"></i> Environmental Odds Constraints
                </h4>
                <div class="grid grid-cols-2 gap-4 border-t border-white/5 pt-4">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Volatility Skew</p>
                        <p class="text-sm font-mono font-black text-blue-400 mt-1"><?php echo htmlspecialchars($volatilityMultiplier, ENT_QUOTES, 'UTF-8'); ?>x</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Adjustment Gravity</p>
                        <p class="text-sm font-mono font-black text-purple-400 mt-1"><?php echo htmlspecialchars($gravityConstant, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Incorporate dynamic Toastify notification engine core library package scripts -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<script>
const ecosystemMetrics = {
    real: {
        pool: "₦ <?php echo number_format($realPool); ?>",
        users: "<?php echo number_format($realUsers); ?>"
    },
    demo: {
        pool: "₦ <?php echo number_format($demoPool); ?>",
        users: "<?php echo number_format($demoUsers); ?>"
    }
};

const oddsScales = {
    4: 150,
    6: 500,
    8: 2500
};

let currentLength = 6;
let currentRealityMode = 'real';

function switchLottoReality(mode) {
    currentRealityMode = mode;
    const realBtn = document.getElementById('real-mode-btn');
    const demoBtn = document.getElementById('demo-mode-btn');
    const modeInput = document.getElementById('lotto-mode-input');
    const formCard = document.getElementById('terminal-card');
    const submitBtn = document.getElementById('submit-engine-btn');
    const titleBadge = document.getElementById('title-mode-badge');
    const formLabel = document.getElementById('form-reality-label');
    const terminalIcon = document.getElementById('terminal-icon');
    const riskWarningBadge = document.getElementById('risk-warning-badge');
    
    const sidebarLabel = document.getElementById('sidebar-pool-label');
    const sidebarAmount = document.getElementById('sidebar-pool-amount');
    const sidebarCount = document.getElementById('sidebar-participant-count');
    const lenBtn = document.getElementById(`len-btn-${currentLength}`);

    if (!modeInput || !sidebarAmount || !sidebarCount) return;

    modeInput.value = mode;
    sidebarAmount.innerText = ecosystemMetrics[mode].pool;
    sidebarCount.innerText = ecosystemMetrics[mode].users;

    const primaryColorClass = mode === 'real' ? 'bg-purple-600' : 'bg-blue-600';
    document.querySelectorAll('[id^="len-btn-"]').forEach(btn => {
        btn.className = "py-2 rounded-lg text-xs font-bold text-slate-400 hover:text-white transition-all";
    });
    if(lenBtn) lenBtn.className = `py-2 rounded-lg text-xs font-bold ${primaryColorClass} text-white shadow transition-all`;

    if (mode === 'real') {
        realBtn.className = "px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest bg-purple-600 text-white shadow-md transition-all";
        demoBtn.className = "px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-slate-300 transition-all";
        formCard.style.borderTopColor = "#9333ea"; 
        submitBtn.className = "w-full bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 text-white font-black py-4 rounded-2xl shadow-xl shadow-purple-500/10 transition-all transform hover:-translate-y-0.5";
        submitBtn.innerText = "SUBMIT SYSTEM LEVEL ENTRY MATRIX";
        titleBadge.innerText = "LIVE";
        titleBadge.className = "text-purple-500 font-black";
        formLabel.innerText = "Live Session";
        terminalIcon.className = "bx bx-target-lock text-purple-500 transition-colors";
        sidebarLabel.innerText = "Total Live Active Pool Stakes";
        sidebarLabel.className = "text-xs font-black uppercase tracking-widest text-purple-400 mb-4";
        riskWarningBadge.innerText = "CAPITAL AT RISK";
        riskWarningBadge.className = "text-[9px] font-black tracking-widest uppercase px-2.5 py-1 bg-rose-500/10 text-rose-400 rounded border border-rose-500/20 animate-pulse";
    } else {
        demoBtn.className = "px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest bg-blue-600 text-white shadow-md transition-all";
        realBtn.className = "px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-slate-300 transition-all";
        formCard.style.borderTopColor = "#2563eb"; 
        submitBtn.className = "w-full bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 text-white font-black py-4 rounded-2xl shadow-xl shadow-blue-500/10 transition-all transform hover:-translate-y-0.5";
        submitBtn.innerText = "LAUNCH FREE DEMO SIMULATION ENTRY";
        titleBadge.innerText = "DEMO SIMULATION";
        titleBadge.className = "text-blue-400 font-black";
        formLabel.innerText = "Demo Simulation Environment";
        terminalIcon.className = "bx bx-joystick text-blue-500 transition-colors";
        sidebarLabel.innerText = "Demo Simulated Volume Pool";
        sidebarLabel.className = "text-xs font-black uppercase tracking-widest text-blue-400 mb-4";
        riskWarningBadge.innerText = "FINANCIAL RISKS DEACTIVATED";
        riskWarningBadge.className = "text-[9px] font-black tracking-widest uppercase px-2.5 py-1 bg-emerald-500/10 text-emerald-400 rounded border border-emerald-500/20";
    }

    Toastify({
        text: `Switched execution context to ${mode.toUpperCase()} environment successfully.`,
        duration: 3500,
        gravity: "top",
        position: "right",
        style: {
            background: mode === 'real' ? "linear-gradient(to right, #7c3aed, #4f46e5)" : "linear-gradient(to right, #2563eb, #06b6d4)",
            borderRadius: "14px",
            fontSize: "12px",
            fontWeight: "bold"
        }
    }).showToast();
}

function adjustDigitGridLength(targetLength) {
    currentLength = targetLength;
    const lenInput = document.getElementById('prediction-len-input');
    if (lenInput) lenInput.value = targetLength;

    const activeColor = currentRealityMode === 'real' ? 'bg-purple-600' : 'bg-blue-600';
    document.querySelectorAll('[id^="len-btn-"]').forEach(btn => {
        btn.className = "py-2 rounded-lg text-xs font-bold text-slate-400 hover:text-white transition-all";
    });
    const activeBtn = document.getElementById(`len-btn-${targetLength}`);
    if (activeBtn) activeBtn.className = `py-2 rounded-lg text-xs font-bold ${activeColor} text-white shadow transition-all`;

    const gridWrapper = document.getElementById('digit-grid-wrapper');
    gridWrapper.innerHTML = '';

    for (let i = 0; i < targetLength; i++) {
        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'sequence_digits[]';
        input.maxLength = 1;
        input.setAttribute('pattern', '[0-9]');
        input.setAttribute('inputmode', 'numeric');
        input.required = true;
        input.className = "w-full h-14 sm:h-20 bg-slate-950 border border-white/10 rounded-2xl text-center text-2xl sm:text-3xl font-black text-white focus:outline-none focus:ring-4 focus:ring-purple-500/10 transition-all digit-input";
        input.placeholder = "0";
        gridWrapper.appendChild(input);
    }

    const oddsLabel = document.getElementById('odds-payout-label');
    if (oddsLabel) oddsLabel.innerText = `x${oddsScales[targetLength]}`;
    calculateProjectedReturn();
    bindDynamicGridListeners();
}

function bindDynamicGridListeners() {
    const digitsInputs = document.querySelectorAll('.digit-input');
    digitsInputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/[^0-9]/g, '');
            if (input.value.length === 1 && index < digitsInputs.length - 1) {
                digitsInputs[index + 1].focus();
            }
        });
        
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && input.value.length === 0 && index > 0) {
                digitsInputs[index - 1].focus();
                digitsInputs[index - 1].value = '';
            }
        });

        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasteData = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
            let targetIdx = index;
            for (let char of pasteData) {
                if (targetIdx < digitsInputs.length) {
                    digitsInputs[targetIdx].value = char;
                    targetIdx++;
                }
            }
            const focusTarget = Math.min(targetIdx, digitsInputs.length - 1);
            if(digitsInputs[focusTarget]) digitsInputs[focusTarget].focus();
        });
    });
}

const betAmountInput = document.getElementById('bet-amount');
function calculateProjectedReturn() {
    if (!betAmountInput) return;
    const amount = parseFloat(betAmountInput.value) || 0;
    const oddsMultiplier = oddsScales[currentLength] || 500;
    const projectedField = document.getElementById('projected-win');
    if (projectedField) {
        projectedField.innerText = (amount * oddsMultiplier).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
}

function initializePoolCountdown() {
    const timerDisplay = document.getElementById('draw-timer');
    if (!timerDisplay) return;

    setInterval(() => {
        const now = new Date();
        const tomorrow = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1);
        const difference = tomorrow - now;

        const hours = Math.floor((difference / (1000 * 60 * 60)) % 24);
        const minutes = Math.floor((difference / 1000 / 60) % 60);
        const seconds = Math.floor((difference / 1000) % 60);

        const format = (num) => String(num).padStart(2, '0');
        timerDisplay.innerText = `${format(hours)}:${format(minutes)}:${format(seconds)}`;
    }, 1000);
}

// Global programmatic intercept layer processing active matrix stake submissions 
document.getElementById('lottoExecutionForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const minConfiguredStake = <?php echo $minAllocation ?? 100; ?>;
    const betVal = parseFloat(betAmountInput ? betAmountInput.value : 0) || 0;

    if (betVal < minConfiguredStake) {
        Toastify({
            text: `Validation Error: Minimum system stake configuration constraints require at least ₦${minConfiguredStake}.`,
            duration: 4000,
            gravity: "top",
            position: "right",
            style: { background: "#f43f5e", borderRadius: "12px" }
        }).showToast();
        return false;
    }

    // Capture the active form elements natively (natively packages sequence_digits[])
    const formData = new FormData(this);
    
    // Explicitly enforce state overrides
    formData.set('lotto_mode', currentRealityMode);
    formData.set('prediction_length', currentLength);

    // Build human-readable visual display string for notifications
    let sequenceStr = '';
    document.querySelectorAll('.digit-input').forEach(input => {
        sequenceStr += input.value;
    });

    fetch(this.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Toastify({
                text: `Success! Allocation entry [${sequenceStr}] successfully broadcasted!`,
                duration: 5000,
                gravity: "top",
                position: "right",
                style: { background: "linear-gradient(to right, #10b981, #059669)", borderRadius: "12px" }
            }).showToast();

            if (data.new_balance !== undefined && currentRealityMode === 'real') {
                const navWallet = document.getElementById('nav-wallet-display');
                if (navWallet) {
                    navWallet.textContent = `₦${parseFloat(data.new_balance).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                }
            }

            if (betAmountInput) betAmountInput.value = '';
            const projectedField = document.getElementById('projected-win');
            if (projectedField) projectedField.innerText = '0.00';
            
            adjustDigitGridLength(currentLength); 
        } else {
            Toastify({
                text: `Execution Failure: ${data.message || 'Verification rejected by backend rules.'}`,
                duration: 5000,
                gravity: "top",
                position: "right",
                style: { background: "#ef4444", borderRadius: "12px" }
            }).showToast();
        }
    })
    .catch(err => {
        console.error("Payload transmission exception detail window:", err);
        Toastify({
            text: "Critical connection malfunction handling operational requirements.",
            duration: 5000,
            gravity: "top",
            position: "right",
            style: { background: "#ef4444", borderRadius: "12px" }
        }).showToast();
    });
});

if (betAmountInput) {
    betAmountInput.addEventListener('input', calculateProjectedReturn);
}

document.addEventListener('DOMContentLoaded', () => {
    adjustDigitGridLength(6);
    initializePoolCountdown();
});
</script>

<?php 
require_once __DIR__ . '/pages/footer.php';
?>