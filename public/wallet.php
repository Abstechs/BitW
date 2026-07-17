<?php
// public/wallet.php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/wallet.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getUser($_SESSION['user_id']);
$wallet = getWallet($user['id']);

$pageTitle = 'Sovereign Wallet Hub';
require_once __DIR__ . '/pages/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-6 space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 border-b border-white/5 pb-8">
        <div>
            <p class="badge">Financial Core</p>
            <h1 class="text-4xl font-black text-white mt-2 tracking-tight">Sovereign <span class="text-blue-500">Wallet</span></h1>
            <p class="text-sm text-slate-400">Manage your liquid assets and convert funds between ecosystem modules.</p>
        </div>
        <div class="flex gap-3">
            <a href="deposit.php" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-6 py-3 rounded-2xl transition-all flex items-center gap-2">
                <i class="bx bx-plus-circle"></i> DEPOSIT
            </a>
            <a href="withdraw.php" class="bg-white/5 hover:bg-white/10 text-white font-bold px-6 py-3 rounded-2xl border border-white/5 transition-all flex items-center gap-2">
                <i class="bx bx-minus-circle"></i> WITHDRAW
            </a>
        </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-12">
        <!-- Balance Overview -->
        <div class="lg:col-span-4 space-y-6">
            <div class="glass-card p-8 bg-gradient-to-br from-slate-900 to-blue-950/30 border-t-4 border-blue-500">
                <h3 class="text-xs font-black uppercase tracking-widest text-blue-400 mb-4">Total Liquid Balance</h3>
                <h2 class="text-5xl font-black text-white mb-2">₦ <?= number_format($wallet['balance'], 2) ?></h2>
                <p class="text-xs text-slate-500">Verified & Secured by Sovereign Ledger</p>
            </div>

            <div class="glass-card p-6 space-y-4">
                <h4 class="font-bold text-white mb-4">Ecosystem Allocations</h4>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl border border-white/5">
                        <div class="flex items-center gap-3">
                            <i class="bx bx-bolt text-yellow-400 text-xl"></i>
                            <span class="text-sm font-bold">Mining Wallet</span>
                        </div>
                        <span class="font-mono text-sm">₦ 0.00</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl border border-white/5">
                        <div class="flex items-center gap-3">
                            <i class="bx bx-dice-6 text-purple-400 text-xl"></i>
                            <span class="text-sm font-bold">Lotto Wallet</span>
                        </div>
                        <span class="font-mono text-sm">₦ 0.00</span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-white/5 rounded-xl border border-white/5">
                        <div class="flex items-center gap-3">
                            <i class="bx bx-bullseye text-blue-400 text-xl"></i>
                            <span class="text-sm font-bold">Betting Wallet</span>
                        </div>
                        <span class="font-mono text-sm">₦ 0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unified Converter -->
        <div class="lg:col-span-8 space-y-8">
            <div class="glass-card p-8 border-t-4 border-emerald-500">
                <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                    <i class="bx bx-transfer-alt text-emerald-500"></i> Unified Fund Converter
                </h3>
                
                <form id="converterForm" class="space-y-6">
                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-widest text-slate-500">From Wallet</label>
                            <select class="w-full bg-slate-950 border border-white/10 rounded-2xl py-4 px-4 text-white font-bold focus:outline-none focus:border-emerald-500">
                                <option value="main">Main Balance</option>
                                <option value="mining">Mining Wallet</option>
                                <option value="lotto">Lotto Wallet</option>
                                <option value="betting">Betting Wallet</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-widest text-slate-500">To Wallet</label>
                            <select class="w-full bg-slate-950 border border-white/10 rounded-2xl py-4 px-4 text-white font-bold focus:outline-none focus:border-emerald-500">
                                <option value="mining">Mining Wallet</option>
                                <option value="lotto">Lotto Wallet</option>
                                <option value="betting">Betting Wallet</option>
                                <option value="main">Main Balance</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-bold uppercase tracking-widest text-slate-500">Amount to Convert</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 font-bold">₦</span>
                            <input type="number" class="w-full bg-slate-950 border border-white/10 rounded-2xl py-4 pl-10 pr-4 text-white font-bold focus:outline-none focus:border-emerald-500" placeholder="0.00">
                        </div>
                        <p class="text-[10px] text-slate-500 italic">A 1.0% conversion protocol fee applies to all internal swaps.</p>
                    </div>

                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-black py-4 rounded-2xl shadow-lg shadow-emerald-500/20 transition-all transform hover:-translate-y-1">
                        EXECUTE CONVERSION PIPELINE
                    </button>
                </form>
            </div>

            <!-- Recent Conversion History -->
            <div class="glass-card overflow-hidden">
                <div class="p-6 border-b border-white/5">
                    <h3 class="font-bold text-white flex items-center gap-2">
                        <i class="bx bx-history text-slate-400"></i> Recent Conversions
                    </h3>
                </div>
                <div class="p-8 text-center text-slate-500 italic text-sm">
                    No conversion history found in the current ledger cycle.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/pages/footer.php'; ?>
