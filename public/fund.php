<?php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/wallet.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$wallet = getWallet($user_id);
$pageTitle = 'Fund Wallet - ' . (AppConfig::get('APP_ALIAS') ?: 'BitW');
require_once __DIR__ . '/pages/header.php';
?>
<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="badge">Funding</p>
            <h1 class="text-4xl font-semibold tracking-tight mt-4">Fund your wallet for future stone purchases</h1>
            <p class="mt-2 text-sm text-slate-400">Use the built-in deposit flow to bring funds into your wallet and keep mining active.</p>
        </div>
        <a href="dashboard.php" class="action-button" style="max-width: 220px;"><i class="bx bx-arrow-back"></i> Back to Dashboard</a>
    </div>

    <section class="glass-card p-6">
        <div class="section-header">
            <div>
                <h2>Deposit options</h2>
                <p class="text-sm text-slate-400 mt-1">Choose the method that suits your pace.</p>
            </div>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border border-white/10 p-4">
                <div class="font-semibold">Paystack</div>
                <div class="text-sm text-slate-400 mt-2">Secure card and bank transfer funding with instant confirmation.</div>
                <a href="deposit.php" class="action-button mt-4">Fund with Paystack</a>
            </div>
            <div class="rounded-2xl border border-white/10 p-4">
                <div class="font-semibold">Manual bank transfer</div>
                <div class="text-sm text-slate-400 mt-2">Use the manual deposit process to complete offline funding.</div>
                <a href="deposit.php" class="action-button mt-4">Use manual deposit</a>
            </div>
        </div>
        <div class="mt-6 text-sm text-slate-400">Current wallet balance: ₦<?= number_format($wallet['balance'] ?? 0, 2) ?></div>
    </section>
</div>
<?php require_once __DIR__ . '/pages/footer.php'; ?>