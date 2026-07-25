<?php
// public/predictions.php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Social Prediction Markets';
require_once __DIR__ . '/pages/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-6 space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 border-b border-white/5 pb-8">
        <div>
            <p class="badge">P2P Social Markets</p>
            <h1 class="text-4xl font-black text-white mt-2 tracking-tight">Oracle Prediction <span class="text-blue-500">Matrix</span></h1>
            <p class="text-sm text-slate-400">Join high-stakes social markets or launch your own verified prediction pools.</p>
        </div>
        <button class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-8 py-4 rounded-2xl shadow-lg shadow-blue-500/20 transition-all transform hover:-translate-y-1 flex items-center gap-3">
            <i class="bx bx-plus-circle text-xl"></i> LAUNCH NEW MARKET
        </button>
    </div>

    <!-- Market Filters -->
    <div class="flex items-center gap-4 overflow-x-auto pb-2 no-scrollbar">
        <button class="px-6 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold whitespace-nowrap">All Markets</button>
        <button class="px-6 py-2.5 bg-white/5 text-slate-400 hover:bg-white/10 rounded-xl text-sm font-bold whitespace-nowrap transition-all">Sports</button>
        <button class="px-6 py-2.5 bg-white/5 text-slate-400 hover:bg-white/10 rounded-xl text-sm font-bold whitespace-nowrap transition-all">Finance</button>
        <button class="px-6 py-2.5 bg-white/5 text-slate-400 hover:bg-white/10 rounded-xl text-sm font-bold whitespace-nowrap transition-all">Ecosystem</button>
        <button class="px-6 py-2.5 bg-white/5 text-slate-400 hover:bg-white/10 rounded-xl text-sm font-bold whitespace-nowrap transition-all">Verified Only</button>
    </div>

    <!-- Grid Layout -->
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <!-- Market Card -->
        <div class="glass-card flex flex-col h-full border-t-4 border-blue-500 group">
            <div class="p-6 flex-1 space-y-4">
                <div class="flex justify-between items-start">
                    <span class="text-[10px] font-black uppercase tracking-widest bg-blue-500/10 text-blue-400 px-2 py-1 rounded">Sports</span>
                    <div class="flex items-center gap-1 text-rose-500 animate-pulse">
                        <i class="bx bx-time-five"></i>
                        <span class="text-[10px] font-bold">ENDS IN 04:20:15</span>
                    </div>
                </div>
                
                <h3 class="text-xl font-bold text-white leading-tight group-hover:text-blue-400 transition-colors">Arsenal will win against Chelsea in the upcoming London Derby</h3>
                
                <div class="flex items-center gap-3 py-2">
                    <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center border border-white/10">
                        <i class="bx bx-user text-slate-400"></i>
                    </div>
                    <div class="text-xs">
                        <p class="text-white font-bold">@PremiumFounder</p>
                        <p class="text-slate-500">Verified Oracle</p>
                    </div>
                </div>

                <div class="space-y-3 pt-2">
                    <div class="flex justify-between text-[10px] font-black uppercase tracking-widest text-slate-500">
                        <span>Total Liquidity</span>
                        <span class="text-white">₦ 690,000.00</span>
                    </div>
                    <div class="h-3 w-full bg-slate-950 rounded-full overflow-hidden flex border border-white/5">
                        <div class="bg-emerald-500 h-full" style="width: 65%"></div>
                        <div class="bg-rose-500 h-full" style="width: 35%"></div>
                    </div>
                    <div class="flex justify-between text-[10px] font-bold">
                        <span class="text-emerald-400">AGREE: 65%</span>
                        <span class="text-rose-400">DISAGREE: 35%</span>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-white/5 border-t border-white/5 grid grid-cols-2 gap-3">
                <button class="py-3 rounded-xl bg-emerald-600/10 hover:bg-emerald-600/20 text-emerald-400 text-xs font-black border border-emerald-500/20 transition-all">AGREE</button>
                <button class="py-3 rounded-xl bg-rose-600/10 hover:bg-rose-600/20 text-rose-400 text-xs font-black border border-rose-500/20 transition-all">DISAGREE</button>
            </div>
        </div>

        <!-- Add more cards as needed -->
    </div>
</div>

<?php require_once __DIR__ . '/pages/footer.php'; ?>
