<?php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/wallet.php';
require_once __DIR__ . '/../core/marketplace.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user = getUser($user_id);
$wallet = getWallet($user_id);
$summary = getReferralSummary($user_id);
$referralLink = 'https://' . $_SERVER['HTTP_HOST'] . '/public/register.php?ref=' . $user['referral_code'];
$pageTitle = 'Referrals - ' . (AppConfig::get('APP_ALIAS') ?: 'BitW');
require_once __DIR__ . '/pages/header.php';
?>
<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="badge">Referral engine</p>
            <h1 class="text-4xl font-semibold tracking-tight mt-4">Grow your network and earn referral rewards</h1>
            <p class="mt-2 text-sm text-slate-400">Share your unique link and turn each signup into a passive bonus stream.</p>
        </div>
        <a href="dashboard.php" class="action-button" style="max-width: 220px;"><i class="bx bx-arrow-back"></i> Back to Dashboard</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_0.8fr]">
        <section class="glass-card p-6">
            <div class="section-header">
                <div>
                    <h2>Your referral link</h2>
                    <p class="text-sm text-slate-400 mt-1">Copy and share your personal launch code with new members.</p>
                </div>
            </div>
            <div class="rounded-2xl border border-white/10 p-4 bg-black/20">
                <div class="text-sm text-slate-400 mb-2">Referral code</div>
                <div class="text-2xl font-semibold"><?= htmlspecialchars($user['referral_code']) ?></div>
                <div class="mt-4 text-sm text-slate-400 break-all"><?= htmlspecialchars($referralLink) ?></div>
            </div>
        </section>

        <section class="glass-card p-6">
            <div class="section-header">
                <div>
                    <h2>Referral summary</h2>
                    <p class="text-sm text-slate-400 mt-1">Your current referral performance at a glance.</p>
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-white/10 p-4">
                    <div class="text-sm text-slate-400">Total referrals</div>
                    <div class="text-3xl font-semibold mt-2"><?= (int) ($summary['total_referrals'] ?? 0) ?></div>
                </div>
                <div class="rounded-2xl border border-white/10 p-4">
                    <div class="text-sm text-slate-400">Referral earnings</div>
                    <div class="text-3xl font-semibold mt-2">₦<?= number_format($summary['total_bonus'] ?? 0, 2) ?></div>
                </div>
            </div>
        </section>
    </div>
</div>
<?php require_once __DIR__ . '/pages/footer.php'; ?>
