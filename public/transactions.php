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
$transactions = getTransactions($user_id);
$pageTitle = 'Transactions - ' . (AppConfig::get('APP_ALIAS') ?: 'BitW');
require_once __DIR__ . '/pages/header.php';
?>
<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="badge">Ledger</p>
            <h1 class="text-4xl font-semibold tracking-tight mt-4">Your on-chain-style transaction history</h1>
            <p class="mt-2 text-sm text-slate-400">Review deposits, claims, withdrawals, and referral rewards in a single stream.</p>
        </div>
        <a href="dashboard.php" class="action-button" style="max-width: 220px;"><i class="bx bx-arrow-back"></i> Back to Dashboard</a>
    </div>

    <section class="glass-card p-6">
        <div class="dashboard-table-wrapper overflow-x-auto">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="5" class="text-slate-400">No transactions yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $txn): ?>
                            <tr>
                                <td><?= htmlspecialchars($txn['created_at']) ?></td>
                                <td><?= htmlspecialchars(ucfirst($txn['type'])) ?></td>
                                <td><?= htmlspecialchars($txn['description'] ?: 'Wallet activity') ?></td>
                                <td>₦<?= number_format($txn['amount'], 2) ?></td>
                                <td><span class="status-pill <?= $txn['status'] === 'completed' ? 'status-success' : 'status-muted' ?>"><?= htmlspecialchars(ucfirst($txn['status'] ?? 'completed')) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php require_once __DIR__ . '/pages/footer.php'; ?>