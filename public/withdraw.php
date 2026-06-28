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
$wallet = getWallet($user_id);
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw_request'])) {
    $amount = (float) ($_POST['amount'] ?? 0);
    $method = trim($_POST['method'] ?? 'wallet');
    $result = requestWithdrawal($user_id, $amount, $method);
    $messages[] = ['type' => $result['success'] ? 'success' : 'error', 'text' => $result['message']];
    $wallet = getWallet($user_id);
}

$withdrawals = getWithdrawalRequests($user_id);
$pageTitle = 'Withdraw - ' . (AppConfig::get('APP_ALIAS') ?: 'BitW');
require_once __DIR__ . '/pages/header.php';
?>
<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="badge">Cashout</p>
            <h1 class="text-4xl font-semibold tracking-tight mt-4">Request a secure withdrawal</h1>
            <p class="mt-2 text-sm text-slate-400">Move your earned balance into a pending cashout request with a clean approval flow.</p>
        </div>
        <a href="dashboard.php" class="action-button" style="max-width: 220px;"><i class="bx bx-arrow-back"></i> Back to Dashboard</a>
    </div>

    <?php foreach ($messages as $message): ?>
        <div class="alert <?= $message['type'] === 'success' ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($message['text']) ?>
        </div>
    <?php endforeach; ?>

    <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
        <section class="glass-card p-6">
            <div class="section-header">
                <div>
                    <h2>Withdrawal request</h2>
                    <p class="text-sm text-slate-400 mt-1">Create a request from your available wallet balance.</p>
                </div>
            </div>
            <form method="POST" class="form-row">
                <input type="hidden" name="withdraw_request" value="1">
                <input type="number" step="0.01" name="amount" class="form-field" placeholder="Amount to withdraw">
                <select name="method" class="form-field">
                    <option value="wallet">Wallet transfer</option>
                    <option value="bank">Bank transfer</option>
                    <option value="cash">Cash pickup</option>
                </select>
                <button type="submit" class="action-button">Submit withdrawal</button>
            </form>
            <div class="mt-4 text-sm text-slate-400">Available balance: ₦<?= number_format($wallet['balance'] ?? 0, 2) ?></div>
        </section>

        <section class="glass-card p-6">
            <div class="section-header">
                <div>
                    <h2>Withdrawal history</h2>
                    <p class="text-sm text-slate-400 mt-1">Track pending and completed cashouts.</p>
                </div>
            </div>
            <div class="space-y-3">
                <?php if (empty($withdrawals)): ?>
                    <p class="text-sm text-slate-400">No withdrawals requested yet.</p>
                <?php else: ?>
                    <?php foreach ($withdrawals as $item): ?>
                        <div class="rounded-2xl border border-white/10 p-3 flex items-center justify-between gap-4">
                            <div>
                                <div class="font-semibold">₦<?= number_format($item['amount'], 2) ?></div>
                                <div class="text-sm text-slate-400"><?= htmlspecialchars($item['method']) ?></div>
                            </div>
                            <span class="status-pill <?= $item['status'] === 'completed' ? 'status-success' : 'status-muted' ?>"><?= htmlspecialchars(ucfirst($item['status'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
<?php require_once __DIR__ . '/pages/footer.php'; ?>