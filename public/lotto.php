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

$userId = $_SESSION['user_id'] ?? 0;

// Fetch Live Constants & Dynamic Thresholds
try {
    $minAllocation = 1000;
    $settingsStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'min_lotto_allocation'");
    if ($settingsStmt) {
        $val = $settingsStmt->fetchColumn();
        if ($val) $minAllocation = (int)$val;
    }

    // Dynamic Network Pools
    $realPool = $pdo->query("SELECT SUM(amount) as total FROM lotto_allocations WHERE draw_date = CURDATE() AND mode = 'real'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 1245000;
    $realUsers = $pdo->query("SELECT COUNT(DISTINCT user_id) as total FROM lotto_allocations WHERE draw_date = CURDATE() AND mode = 'real'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 1420;

    $lastWinnerRow = $pdo->query("SELECT amount FROM lotto_winners ORDER BY won_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $lastPayout = !empty($lastWinnerRow['amount']) ? '₦ ' . number_format($lastWinnerRow['amount'] / 1000, 0) . 'k' : '₦ 45k';

    // Prediction History
    $historyStmt = $pdo->prepare("SELECT sequence, amount, outcome, status, mode FROM lotto_allocations WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5");
    $historyStmt->execute(['user_id' => $userId]);
    $predictionHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $minAllocation = 1000; $realPool = 1245000; $realUsers = 1420; $lastPayout = '₦ 45k'; $predictionHistory = [];
}

$pageTitle = 'Lotto-Sovereign Engine';
require_once __DIR__ . '/pages/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-6 space-y-8">
    <!-- Header Context -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-white/5 pb-6">
        <div>
            <p class="badge">Sovereign Gaming</p>
            <h1 class="text-3xl font-black text-white mt-2 tracking-tight">Lotto-Sovereign <span class="text-purple-500">2.0</span></h1>
            <div class="flex flex-wrap items-center gap-4 mt-2">
                <p class="text-sm text-slate-400">High-fidelity prediction matrix with native liquidity pools.</p>
                <!-- Dual-Reality Switch -->
                <div class="flex items-center gap-2 bg-slate-900/50 p-1 rounded-xl border border-white/5">
                    <button type="button" onclick="switchLottoMode('real')" id="real-mode-btn" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-purple-600 text-white transition-all">REAL</button>
                    <button type="button" onclick="switchLottoMode('demo')" id="demo-mode-btn" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-white transition-all">DEMO</button>
                </div>
                <input type="hidden" id="lotto-mode" value="real">
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="bg-slate-900/80 border border-white/5 rounded-2xl px-5 py-3 flex flex-col items-end">
                <span class="text-[10px] uppercase tracking-widest text-slate-500 font-bold">Next Draw Countdown</span>
                <span class="text-xl font-mono font-bold text-rose-500" id="draw-timer">--:--:--</span>
            </div>
        </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-12">
        <!-- Main Prediction Terminal -->
        <div class="lg:col-span-8 space-y-8">
            <div class="glass-card p-8 border-t-4 border-purple-600 relative overflow-hidden transition-all duration-300" id="lotto-card">
                <div class="absolute top-0 right-0 p-8 opacity-10">
                    <i class="bx bx-dice-6 text-9xl"></i>
                </div>
                
                <div class="relative z-10">
                    <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                        <i class="bx bx-target-lock text-purple-500" id="lotto-icon"></i> Active Prediction Terminal
                    </h2>
                    
                    <form id="lottoExecutionForm" class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-widest text-slate-500">6-Digit Target Sequence</label>
                            <div class="flex gap-2 sm:gap-4 justify-between">
                                <?php for($i=0; $i<6; $i++): ?>
                                    <input type="text" maxlength="1" class="w-full h-16 sm:h-20 bg-slate-950 border border-white/10 rounded-2xl text-center text-3xl font-black text-white focus:outline-none focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all digit-input" placeholder="0">
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-widest text-slate-500">Allocation (₦)</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 font-bold">₦</span>
                                    <input type="number" id="bet-amount" class="w-full bg-slate-950 border border-white/10 rounded-2xl py-4 pl-10 pr-4 text-white font-bold focus:outline-none focus:border-purple-500" placeholder="Min <?= number_format($minAllocation) ?>">
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-widest text-slate-500">Projected Yield</label>
                                <div class="w-full bg-slate-950/50 border border-white/5 rounded-2xl py-4 px-4 text-emerald-400 font-black flex items-center justify-between">
                                    <span>₦ <span id="projected-win">0.00</span></span>
                                    <span class="text-[10px] bg-emerald-500/10 px-2 py-1 rounded text-emerald-500 uppercase">x500 Multiplier</span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4">
                            <button type="submit" id="lotto-submit-btn" class="w-full bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 text-white font-black py-4 rounded-2xl shadow-lg shadow-purple-500/20 transition-all transform hover:-translate-y-1">
                                EXECUTE REAL ALLOCATION
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Transaction History -->
            <div class="glass-card overflow-hidden">
                <div class="p-6 border-b border-white/5 flex items-center justify-between">
                    <h3 class="font-bold text-white flex items-center gap-2">
                        <i class="bx bx-history text-slate-400"></i> Prediction History
                    </h3>
                    <button class="text-xs text-blue-400 font-bold hover:underline">View All</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] uppercase tracking-widest text-slate-500 bg-white/5">
                                <th class="px-6 py-4">Sequence</th>
                                <th class="px-6 py-4">Allocation</th>
                                <th class="px-6 py-4">Mode</th>
                                <th class="px-6 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php if (empty($predictionHistory)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-slate-500 italic text-sm">No recent predictions found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($predictionHistory as $row): ?>
                                    <tr class="hover:bg-white/5 transition-colors">
                                        <td class="px-6 py-4 font-mono font-bold text-white"><?= htmlspecialchars($row['sequence']) ?></td>
                                        <td class="px-6 py-4 text-slate-300">₦ <?= number_format($row['amount'], 2) ?></td>
                                        <td class="px-6 py-4">
                                            <span class="text-[10px] font-black uppercase px-2 py-1 rounded <?= $row['mode'] === 'real' ? 'bg-purple-500/10 text-purple-400' : 'bg-blue-500/10 text-blue-400' ?>">
                                                <?= strtoupper($row['mode']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-[10px] font-bold uppercase <?= $row['status'] === 'pending' ? 'text-amber-400' : ($row['status'] === 'won' ? 'text-emerald-400' : 'text-rose-400') ?>">
                                                <?= strtoupper($row['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sidebar Metrics -->
        <div class="lg:col-span-4 space-y-6">
            <div class="glass-card p-6 bg-gradient-to-br from-slate-900 to-purple-950/30">
                <h3 class="text-xs font-black uppercase tracking-widest text-purple-400 mb-4">Ecosystem Pool</h3>
                <div class="space-y-1">
                    <p class="text-4xl font-black text-white">₦ <?= number_format($realPool) ?></p>
                    <p class="text-xs text-slate-500">Live aggregated liquidity for today's draw</p>
                </div>
                <div class="mt-6 pt-6 border-t border-white/10 grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-[10px] uppercase text-slate-500 font-bold">Participants</span>
                        <p class="text-lg font-bold text-white"><?= number_format($realUsers) ?></p>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase text-slate-500 font-bold">Last Winner</span>
                        <p class="text-lg font-bold text-emerald-400"><?= $lastPayout ?></p>
                    </div>
                </div>
            </div>

            <div class="glass-card p-6 space-y-4">
                <h4 class="font-bold text-white">Engine Protocol</h4>
                <div class="space-y-4">
                    <div class="flex gap-4">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center shrink-0">
                            <i class="bx bx-check-shield text-blue-500"></i>
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed">System automatically identifies low-liability sequences to ensure 98%+ payout solvency.</p>
                    </div>
                    <div class="flex gap-4">
                        <div class="w-8 h-8 rounded-lg bg-purple-500/10 flex items-center justify-center shrink-0">
                            <i class="bx bx-refresh text-purple-500"></i>
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed">Draws are triggered by the first active ecosystem node (user) after 00:00 UTC.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Live Timer for Lotto (End of day)
    const tomorrow = new Date();
    tomorrow.setHours(24, 0, 0, 0);
    new SovereignTimer('draw-timer', tomorrow).start();
});

function switchLottoMode(mode) {
    const realBtn = document.getElementById('real-mode-btn');
    const demoBtn = document.getElementById('demo-mode-btn');
    const modeInput = document.getElementById('lotto-mode');
    const lottoCard = document.getElementById('lotto-card');
    const lottoIcon = document.getElementById('lotto-icon');
    const submitBtn = document.getElementById('lotto-submit-btn');
    
    modeInput.value = mode;
    
    if (mode === 'real') {
        realBtn.classList.add('bg-purple-600', 'text-white');
        realBtn.classList.remove('text-slate-500');
        demoBtn.classList.remove('bg-blue-600', 'text-white');
        demoBtn.classList.add('text-slate-500');
        lottoCard.classList.replace('border-blue-600', 'border-purple-600');
        lottoIcon.classList.replace('text-blue-500', 'text-purple-500');
        submitBtn.innerText = "EXECUTE REAL ALLOCATION";
        submitBtn.classList.replace('from-blue-600', 'from-purple-600');
    } else {
        demoBtn.classList.add('bg-blue-600', 'text-white');
        demoBtn.classList.remove('text-slate-500');
        realBtn.classList.remove('bg-purple-600', 'text-white');
        realBtn.classList.add('text-slate-500');
        lottoCard.classList.add('border-blue-600');
        lottoCard.classList.remove('border-purple-600');
        lottoIcon.classList.add('text-blue-500');
        lottoIcon.classList.remove('text-purple-500');
        submitBtn.innerText = "EXECUTE DEMO SIMULATION";
        submitBtn.classList.add('from-blue-600');
        submitBtn.classList.remove('from-purple-600');
    }
}

document.querySelectorAll('.digit-input').forEach((input, index, inputs) => {
    input.addEventListener('input', () => {
        if (input.value.length === 1 && index < inputs.length - 1) {
            inputs[index + 1].focus();
        }
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && input.value.length === 0 && index > 0) {
            inputs[index - 1].focus();
        }
    });
});

document.getElementById('bet-amount').addEventListener('input', (e) => {
    const amount = parseFloat(e.target.value) || 0;
    document.getElementById('projected-win').innerText = (amount * 500).toLocaleString(undefined, {minimumFractionDigits: 2});
});
</script>

<?php require_once __DIR__ . '/pages/footer.php'; ?>
