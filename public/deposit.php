<?php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Deposit - ' . (AppConfig::get('APP_ALIAS') ?: 'BitW');
require_once __DIR__ . '/pages/header.php';
?>
<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="badge">Funding</p>
            <h1 class="text-4xl font-semibold tracking-tight mt-4">Top up your wallet securely</h1>
            <p class="mt-2 text-sm text-slate-400">Use a trusted gateway or manual transfer to bring funds into your wallet.</p>
        </div>
        <a href="dashboard.php" class="action-button" style="max-width: 220px;"><i class="bx bx-arrow-back"></i> Back to Dashboard</a>
    </div>

    <section class="glass-card p-6">
        <div class="section-header">
            <div>
                <h2>Deposit center</h2>
                <p class="text-sm text-slate-400 mt-1">Choose the funding route you prefer.</p>
            </div>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-2xl border border-white/10 p-4">
                <div class="font-semibold">Paystack</div>
                <div class="text-sm text-slate-400 mt-2">Card payments with live confirmation.</div>
                <a href="../api/paystack-initialize.php" class="action-button mt-4">Continue with Paystack</a>
            </div>
            <div class="rounded-2xl border border-white/10 p-4">
                <div class="font-semibold">Manual deposit</div>
                <div class="text-sm text-slate-400 mt-2">Upload a transfer proof in the admin panel.</div>
                <a href="manual-deposit.php" class="action-button mt-4">Use manual deposit</a>
            </div>
        </div>
    </section>
</div>
<?php require_once __DIR__ . '/pages/footer.php'; ?>