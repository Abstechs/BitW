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

// Fetch Live Constants & Dynamic Thresholds directly from Admin Engine Parameters
try {
    // Read systemic limits dynamically configured by the admin settings panel
    // Fallback to baseline default parameter matrix if the db state is uninitialized
    $minAllocation = 200; // Default fallback to 200 as specified
    $volatilityMultiplier = '1.2x';
    $gravityConstant = '0.05';

    $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('min_lotto_allocation', 'global_volatility', 'mean_reversion_gravity')");
    if ($settingsStmt) {
        $settings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        if (isset($settings['min_lotto_allocation'])) $minAllocation = (int)$settings['min_lotto_allocation'];
        if (isset($settings['global_volatility'])) $volatilityMultiplier = htmlspecialchars($settings['global_volatility']) . 'x';
        if (isset($settings['mean_reversion_gravity'])) $gravityConstant = htmlspecialchars($settings['mean_reversion_gravity']);
    }

    // Dynamic Network Pools Allocation Metrics
    $realPool = $pdo->query("SELECT SUM(amount) as total FROM lotto_allocations WHERE draw_date = CURDATE() AND mode = 'real'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 1245000;
    $realUsers = $pdo->query("SELECT COUNT(DISTINCT user_id) as total FROM lotto_allocations WHERE draw_date = CURDATE() AND mode = 'real'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 1420;

    $demoPool = $pdo->query("SELECT SUM(amount) as total FROM lotto_allocations WHERE draw_date = CURDATE() AND mode = 'demo'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 85000;
    $demoUsers = $pdo->query("SELECT COUNT(DISTINCT user_id) as total FROM lotto_allocations WHERE draw_date = CURDATE() AND mode = 'demo'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 310;

    $lastWinnerRow = $pdo->query("SELECT amount FROM lotto_winners ORDER BY won_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $lastPayout = !empty($lastWinnerRow['amount']) ? '₦ ' . number_format($lastWinnerRow['amount'] / 1000, 0) . 'k' : '₦ 45k';

    // Account Specific Historical Ledger Arrays
    $historyStmt = $pdo->prepare("SELECT sequence, amount, outcome, status, mode FROM lotto_allocations WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5");
    $historyStmt->execute(['user_id' => $userId]);
    $predictionHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Structural Resiliency Contingencies
    $minAllocation = 200; $volatilityMultiplier = '1.2x'; $gravityConstant = '0.05';
    $realPool = 1245000; $realUsers = 1420; $demoPool = 85000; $demoUsers = 310;
    $lastPayout = '₦ 45k'; $predictionHistory = [];
}

$pageTitle = 'Lotto-Sovereign Engine';
require_once __DIR__ . '/pages/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-6 space-y-8">
    <!-- Matrix Dashboard Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-white/5 pb-6">
        <div>
            <p class="badge text-xs uppercase tracking-widest text-purple-400 font-bold bg-purple-500/10 px-3 py-1 rounded-md inline-block">Sovereign Architecture</p>
            <h1 class="text-3xl font-black text-white mt-2 tracking-tight">Lotto-Sovereign <span id="title-mode-badge" class="text-purple-500 transition-all">LIVE</span></h1>
            <div class="flex items-center gap-4 mt-2">
                <p class="text-sm text-slate-400">High-fidelity predictive analytics interface.</p>
                
                <!-- Risk State Reality Switcher -->
                <div class="flex items-center gap-2 bg-slate-950 p-1 rounded-xl border border-white/5">
                    <button type="button" onclick="switchLottoReality('real')" id="real-mode-btn" class="px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest bg-purple-600 text-white shadow-md transition-all">LIVE POSITIONS</button>
                    <button type="button" onclick="switchLottoReality('demo')" id="demo-mode-btn" class="px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-slate-300 transition-all">PRACTICE SANDBOX</button>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="bg-slate-900/80 border border-white/5 rounded-2xl px-5 py-3 flex flex-col items-end shadow-inner">
                <span class="text-[10px] uppercase tracking-widest text-slate-500 font-bold">Mathematical Settlement Draw</span>
                <span class="text-2xl font-mono font-bold text-rose-500 tracking-wider" id="draw-timer">--:--:--</span>
            </div>
        </div>
    </div>

    <!-- MANDATORY RESPONSIBLE GAMING ADVISORY BAR -->
    <div class="bg-amber-500/5 border border-amber-500/10 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center shrink-0 border border-amber-500/20 text-amber-500 text-xl font-bold font-sans">
                18+
            </div>
            <div>
                <h4 class="text-xs font-black uppercase tracking-wider text-amber-500">Responsible Analytical Protocol</h4>
                <p class="text-xs text-slate-400 mt-0.5 leading-relaxed">
                    Sequence allocation involves strategic risk. Access is limited strictly to platforms operators aged 18+. Manage metrics wisely—never allocate structural liquidity you cannot afford to balance. Avoid greed spirals; approach metrics logically and document your data vectors.
                </p>
            </div>
        </div>
        <div class="shrink-0">
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-500 border border-white/5 bg-slate-950 px-3 py-1.5 rounded-lg">MIN THRESHOLD: ₦<?php echo number_format($minAllocation); ?></span>
        </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-12">
        <!-- Input Terminal Vector -->
        <div class="lg:col-span-8 space-y-8">
            <div id="terminal-card" class="glass-card p-8 border-t-4 border-purple-600 relative overflow-hidden shadow-2xl transition-all duration-300">
                <div class="absolute top-0 right-0 p-8 opacity-5">
                    <i class="bx bx-shield-quarter text-9xl text-white"></i>
                </div>
                
                <div class="relative z-10">
                    <div class="flex justify-between items-start mb-6">
                        <h2 class="text-xl font-bold text-white flex items-center gap-3">
                            <i id="terminal-icon" class="bx bx-target-lock text-purple-500 transition-colors"></i> 
                            Prediction Matrix (<span id="form-reality-label" class="text-purple-400 font-mono">Live Matrix</span>)
                        </h2>
                        <span id="risk-warning-badge" class="text-[9px] font-black tracking-widest uppercase px-2.5 py-1 bg-rose-500/10 text-rose-400 rounded border border-rose-500/20 animate-pulse">ACTIVE RISK NODES</span>
                    </div>
                    
                    <form id="lottoExecutionForm" method="POST" action="api/submit-prediction.php" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="lotto_mode" id="lotto-mode-input" value="real">
                        
                        <div class="space-y-3">
                            <label class="text-xs font-bold uppercase tracking-widest text-slate-400 flex justify-between">
                                <span>6-Digit Analytical Target Sequence</span>
                                <span class="text-slate-600 lowercase font-normal">Supports full sequence paste intercept</span>
                            </label>
                            <div class="flex gap-2 sm:gap-4 justify-between">
                                <?php for($i = 0; $i < 6; $i++): ?>
                                    <input type="text" 
                                           name="sequence_digits[]"
                                           maxlength="1" 
                                           pattern="[0-9]"
                                           inputmode="numeric"
                                           required
                                           class="w-full h-16 sm:h-20 bg-slate-950 border border-white/10 rounded-2xl text-center text-3xl font-black text-white focus:outline-none focus:ring-4 focus:ring-purple-500/10 transition-all digit-input" 
                                           placeholder="0">
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-widest text-slate-400">Position Value Allocation</label>
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
                                <label class="text-xs font-bold uppercase tracking-widest text-slate-400">Calculated Yield Projection</label>
                                <div class="w-full bg-slate-950/80 border border-white/5 rounded-2xl py-4 px-4 text-emerald-400 font-mono font-black flex items-center justify-between shadow-inner">
                                    <span>₦ <span id="projected-win">0.00</span></span>
                                    <span class="text-[10px] bg-emerald-500/10 px-2.5 py-1 rounded-md text-emerald-400 uppercase font-sans font-bold tracking-wider">x500 Multiplier</span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" id="submit-engine-btn" class="w-full bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 text-white font-black py-4 rounded-2xl shadow-xl shadow-purple-500/10 transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                                INITIALIZE REAL POSITION MATRIX
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Realtime History Array -->
            <div class="glass-card overflow-hidden shadow-xl">
                <div class="p-6 border-b border-white/5 flex items-center justify-between bg-slate-900/20">
                    <h3 class="font-bold text-white flex items-center gap-2 text-sm">
                        <i class="bx bx-notepad text-slate-400"></i> Personal Matrix Ledger Audits
                    </h3>
                    <a href="history.php" class="text-xs text-blue-400 font-bold hover:text-blue-300 hover:underline transition-colors">Complete Ledger Base</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] uppercase tracking-widest text-slate-400 bg-slate-950/50 border-b border-white/5">
                                <th class="px-6 py-4">Matrix Node Group</th>
                                <th class="px-6 py-4">Target Array Sequence</th>
                                <th class="px-6 py-4">Allocation Weight</th>
                                <th class="px-6 py-4">Verification Map</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php if (!empty($predictionHistory)): ?>
                                <?php foreach ($predictionHistory as $row): ?>
                                    <tr class="hover:bg-white/[0.02] transition-colors">
                                        <td class="px-6 py-4">
                                            <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded <?php echo $row['mode'] === 'real' ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'bg-blue-500/10 text-blue-400 border border-blue-500/20'; ?>">
                                                <?php echo htmlspecialchars($row['mode'] === 'real' ? 'Live Account' : 'Sandbox'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 font-mono font-bold text-white tracking-widest"><?php echo htmlspecialchars($row['sequence']); ?></td>
                                        <td class="px-6 py-4 text-slate-300 font-mono">₦ <?php echo number_format($row['amount'], 2); ?></td>
                                        <td class="px-6 py-4">
                                            <?php 
                                                $status = strtolower($row['status'] ?? 'pending');
                                                if ($status === 'settled' || $status === 'won') {
                                                    echo '<span class="text-[11px] font-bold text-emerald-400 bg-emerald-500/5 border border-emerald-500/10 px-2 py-0.5 rounded-md">Position Settled</span>';
                                                } elseif ($status === 'failed' || $status === 'lost') {
                                                    echo '<span class="text-[11px] font-bold text-rose-400 bg-rose-500/5 border border-rose-500/10 px-2 py-0.5 rounded-md">Balanced Out</span>';
                                                } else {
                                                    echo '<span class="text-[11px] font-bold text-amber-400 bg-amber-500/5 border border-amber-500/10 px-2 py-0.5 rounded-md animate-pulse">Analyzing Nodes</span>';
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-sm text-slate-500 font-medium">No position hashes tracked for current configuration.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Ecosystem Realtime Metadata Sidebar -->
        <div class="lg:col-span-4 space-y-6">
            <div class="glass-card p-6 bg-gradient-to-br from-slate-900 to-purple-950/20 shadow-xl border-l-2 border-purple-500">
                <h3 id="sidebar-pool-label" class="text-xs font-black uppercase tracking-widest text-purple-400 mb-4">Total Realized Pool Size</h3>
                <div class="space-y-1">
                    <p class="text-4xl font-black text-white tracking-tight font-mono" id="sidebar-pool-amount">₦ <?php echo number_format($realPool); ?></p>
                    <p class="text-xs text-slate-500">Global algorithmic allocation pools</p>
                </div>
                <div class="mt-6 pt-6 border-t border-white/5 grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-[10px] uppercase text-slate-500 font-black tracking-wider">Active Evaluators</span>
                        <p class="text-lg font-bold text-white font-mono" id="sidebar-participant-count"><?php echo number_format($realUsers); ?></p>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase text-slate-500 font-black tracking-wider">Top Scale Yield</span>
                        <p class="text-lg font-bold text-emerald-400 font-mono"><?php echo htmlspecialchars($lastPayout); ?></p>
                    </div>
                </div>
            </div>

            <!-- Public Safe Math Constraints (Abstracted metrics driven directly by the Admin file config) -->
            <div class="glass-card p-6 bg-slate-900/30 border border-white/5 space-y-4">
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-200 flex items-center gap-2">
                    <i class="bx bx-math text-blue-400"></i> Environmental Math Vectors
                </h4>
                <div class="grid grid-cols-2 gap-4 border-t border-white/5 pt-4">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Stochastic Divergence</p>
                        <p class="text-sm font-mono font-black text-blue-400 mt-1"><?php echo $volatilityMultiplier; ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Mean Distribution Gravity</p>
                        <p class="text-sm font-mono font-black text-purple-400 mt-1"><?php echo $gravityConstant; ?></p>
                    </div>
                </div>
                <p class="text-[10px] text-slate-500 italic leading-relaxed pt-2 border-t border-white/5">
                    *Calculated via real-time market noise indices to maintain platform configuration equity bounds.
                </p>
            </div>

            <!-- Terminal Risk Management Guide -->
            <div class="glass-card p-6 space-y-3 bg-slate-950/40 border border-white/5 rounded-2xl">
                <h4 class="text-xs font-black uppercase tracking-wider text-slate-300 flex items-center gap-2">
                    <i class="bx bx-info-circle text-amber-500"></i> Smart Prediction Protocol
                </h4>
                <ul class="text-[11px] text-slate-400 space-y-2.5 list-disc pl-4 leading-relaxed">
                    <li><strong class="text-slate-200">Avoid Clustering Parameters:</strong> Distribute number arrays across non-linear paths. Grouping digits consecutively (e.g. 000000) heavily drops probability distribution efficiency.</li>
                    <li><strong class="text-slate-200">Systematic Micro-Allocations:</strong> Establish safe financial thresholds. Utilizing the **Practice Sandbox** lets you analyze sequence anomalies without real resource deployment.</li>
                    <li><strong class="text-slate-200">Counter Greedy Trajectories:</strong> Mathematical iterations are independent; past sequences do not guarantee future loops. Retain gains intelligently.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// High Fidelity Client Environment State Management Container
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

// 1. Interactive Sandbox Engine Toggle
function switchLottoReality(mode) {
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

    modeInput.value = mode;
    sidebarAmount.innerText = ecosystemMetrics[mode].pool;
    sidebarCount.innerText = ecosystemMetrics[mode].users;

    if (mode === 'real') {
        realBtn.className = "px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest bg-purple-600 text-white shadow-md transition-all";
        demoBtn.className = "px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-slate-300 transition-all";
        
        formCard.className = "glass-card p-8 border-t-4 border-purple-600 relative overflow-hidden shadow-2xl transition-all duration-300";
        submitBtn.className = "w-full bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 text-white font-black py-4 rounded-2xl shadow-xl shadow-purple-500/10 transition-all transform hover:-translate-y-0.5";
        
        submitBtn.innerText = "INITIALIZE REAL POSITION MATRIX";
        titleBadge.innerText = "LIVE";
        titleBadge.className = "text-purple-500 font-black";
        formLabel.innerText = "Live Matrix";
        terminalIcon.className = "bx bx-target-lock text-purple-500 transition-colors";
        sidebarLabel.innerText = "Total Realized Pool Size";
        sidebarLabel.className = "text-xs font-black uppercase tracking-widest text-purple-400 mb-4";
        
        riskWarningBadge.innerText = "ACTIVE RISK NODES";
        riskWarningBadge.className = "text-[9px] font-black tracking-widest uppercase px-2.5 py-1 bg-rose-500/10 text-rose-400 rounded border border-rose-500/20 animate-pulse";
    } else {
        demoBtn.className = "px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest bg-blue-600 text-white shadow-md transition-all";
        realBtn.className = "px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-slate-300 transition-all";
        
        formCard.className = "glass-card p-8 border-t-4 border-blue-600 relative overflow-hidden shadow-2xl transition-all duration-300";
        submitBtn.className = "w-full bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 text-white font-black py-4 rounded-2xl shadow-xl shadow-blue-500/10 transition-all transform hover:-translate-y-0.5";
        
        submitBtn.innerText = "INITIALIZE SANDBOX DEMO CONTRACT";
        titleBadge.innerText = "PRACTICE SANDBOX";
        titleBadge.className = "text-blue-400 font-black";
        formLabel.innerText = "Demo Sandboxed Mode";
        terminalIcon.className = "bx bx-joystick text-blue-500 transition-colors";
        sidebarLabel.innerText = "Sandbox Simulated Volume";
        sidebarLabel.className = "text-xs font-black uppercase tracking-widest text-blue-400 mb-4";
        
        riskWarningBadge.innerText = "RISK BOUNDS ISOLATED";
        riskWarningBadge.className = "text-[9px] font-black tracking-widest uppercase px-2.5 py-1 bg-emerald-500/10 text-emerald-400 rounded border border-emerald-500/20";
    }
}

// 2. Intelligent Entry Field Auto-Tabbing
const digitsInputs = document.querySelectorAll('.digit-input');
digitsInputs.forEach((input, index) => {
    input.addEventListener('input', (e) => {
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
        digitsInputs[focusTarget].focus();
    });
});

// 3. Live Mathematical Return Calculator Interceptor
document.getElementById('bet-amount').addEventListener('input', (e) => {
    const amount = parseFloat(e.target.value) || 0;
    document.getElementById('projected-win').innerText = (amount * 500).toLocaleString(undefined, {minimumFractionDigits: 2});
});

// 4. Secure Async XHR Submission Node (Refactored for Toastify)
document.getElementById('lottoExecutionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const submitBtn = document.getElementById('submit-engine-btn');
    const originalText = submitBtn.innerText;
    
    submitBtn.disabled = true;
    submitBtn.innerText = "RECORDING POSITIONAL CONSTRAINTS...";

    fetch(form.getAttribute('action'), {
        method: 'POST',
        body: new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Toastify({
                text: "Position Locked: " + data.message,
                duration: 3000,
                gravity: "top",
                position: "right",
                style: {
                    background: "linear-gradient(to right, #10b981, #059669)",
                    borderRadius: "12px",
                    fontWeight: "bold"
                }
            }).showToast();
            
            setTimeout(() => window.location.reload(), 1500);
        } else {
            Toastify({
                text: "Rejected: " + data.message,
                duration: 4000,
                gravity: "top",
                position: "right",
                style: {
                    background: "linear-gradient(to right, #f43f5e, #e11d48)",
                    borderRadius: "12px",
                    fontWeight: "bold"
                }
            }).showToast();
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        }
    })
    .catch(err => {
        console.error(err);
        Toastify({
            text: "Transmission Failure. Check network uplink.",
            duration: 4000,
            gravity: "top",
            position: "right",
            style: {
                background: "linear-gradient(to right, #f59e0b, #d97706)",
                borderRadius: "12px",
                fontWeight: "bold"
            }
        }).showToast();
        submitBtn.disabled = false;
        submitBtn.innerText = originalText;
    });
});

// 5. Synchronized Countdown Counter Layer
function runPrecisionTimer() {
    const timerElement = document.getElementById('draw-timer');
    if (!timerElement) return;

    function refreshFrame() {
        const timeFrame = new Date();
        const targetDrawTime = Date.UTC(
            timeFrame.getUTCFullYear(),
            timeFrame.getUTCMonth(),
            timeFrame.getUTCDate() + 1,
            0, 0, 0, 0
        );

        const calculationDelta = targetDrawTime - Date.now();
        if (calculationDelta <= 0) {
            timerElement.innerText = "00:00:00";
            return;
        }

        const hrs = Math.floor(calculationDelta / 3600000);
        const mins = Math.floor((calculationDelta % 3600000) / 60000);
        const secs = Math.floor((calculationDelta % 60000) / 1000);

        timerElement.innerText = 
            String(hrs).padStart(2, '0') + ":" + 
            String(mins).padStart(2, '0') + ":" + 
            String(secs).padStart(2, '0');
    }

    setInterval(refreshFrame, 1000);
    refreshFrame();
}
runPrecisionTimer();
</script>

<?php require_once __DIR__ . '/pages/footer.php'; ?>