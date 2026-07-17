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

$pageTitle = 'Lotto-Sovereign Engine';
require_once __DIR__ . '/pages/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-6 space-y-8">
    <!-- Header Context -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-white/5 pb-6">
        <div>
            <p class="badge">Sovereign Gaming</p>
            <h1 class="text-3xl font-black text-white mt-2 tracking-tight">Lotto-Sovereign <span class="text-purple-500">2.0</span></h1>
            <div class="flex items-center gap-4 mt-2">
                <p class="text-sm text-slate-400">High-fidelity prediction matrix with native liquidity pools.</p>
                <!-- Dual-Reality Switch -->
                <div class="flex items-center gap-2 bg-slate-900/50 p-1 rounded-xl border border-white/5">
                    <button id="real-mode-btn" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-purple-600 text-white transition-all">REAL</button>
                    <button id="demo-mode-btn" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-white transition-all">DEMO</button>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="bg-slate-900/80 border border-white/5 rounded-2xl px-5 py-3 flex flex-col items-end">
                <span class="text-[10px] uppercase tracking-widest text-slate-500 font-bold">Next Draw Countdown</span>
                <span class="text-xl font-mono font-bold text-rose-500" id="draw-timer">23:59:59</span>
            </div>
        </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-12">
        <!-- Main Prediction Terminal -->
        <div class="lg:col-span-8 space-y-8">
            <div class="glass-card p-8 border-t-4 border-purple-600 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-10">
                    <i class="bx bx-dice-6 text-9xl"></i>
                </div>
                
                <div class="relative z-10">
                    <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                        <i class="bx bx-target-lock text-purple-500"></i> Active Prediction Terminal
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
                                    <input type="number" id="bet-amount" class="w-full bg-slate-950 border border-white/10 rounded-2xl py-4 pl-10 pr-4 text-white font-bold focus:outline-none focus:border-purple-500" placeholder="Min 1,000">
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-widest text-slate-500">Projected Yield</label>
                                <div class="w-full bg-slate-950/50 border border-white/5 rounded-2xl py-4 px-4 text-emerald-400 font-black flex items-center justify-between">
                                    <span>₦ <span id="projected-win">0.00</span></span>
                                    <span class="text-[10px] bg-emerald-500/10 px-2 py-1 rounded text-emerald-500">x500 Multiplier</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4 pt-4">
                            <button type="submit" class="flex-1 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 text-white font-black py-4 rounded-2xl shadow-lg shadow-purple-500/20 transition-all transform hover:-translate-y-1">
                                EXECUTE REAL ALLOCATION
                            </button>
                            <button type="button" class="sm:w-1/3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold py-4 rounded-2xl border border-white/5 transition-all">
                                DEMO SIMULATION
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
                                <th class="px-6 py-4">Outcome</th>
                                <th class="px-6 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4 font-mono font-bold text-white">215874</td>
                                <td class="px-6 py-4 text-slate-300">₦ 5,000.00</td>
                                <td class="px-6 py-4 text-slate-500">Pending</td>
                                <td class="px-6 py-4">
                                    <span class="status-pill status-warning">Processing</span>
                                </td>
                            </tr>
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
                    <p class="text-4xl font-black text-white">₦ 1,245,000</p>
                    <p class="text-xs text-slate-500">Live aggregated liquidity for today's draw</p>
                </div>
                <div class="mt-6 pt-6 border-t border-white/10 grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-[10px] uppercase text-slate-500 font-bold">Participants</span>
                        <p class="text-lg font-bold text-white">1,420</p>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase text-slate-500 font-bold">Last Winner</span>
                        <p class="text-lg font-bold text-emerald-400">₦ 45k</p>
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
