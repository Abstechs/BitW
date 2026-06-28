<?php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/marketplace.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$orders = getOrders($user_id, 50);
$pageTitle = 'Orders - ' . (AppConfig::get('APP_ALIAS') ?: 'BitW');
require_once __DIR__ . '/pages/header.php';
?>
<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="badge">Orders</p>
            <h1 class="text-4xl font-semibold tracking-tight mt-4">Track every order and action in your ecosystem</h1>
            <p class="mt-2 text-sm text-slate-400">See when a stone was bought, saved, or funded from a single historical view.</p>
        </div>
        <a href="dashboard.php" class="action-button" style="max-width: 220px;"><i class="bx bx-arrow-back"></i> Back to Dashboard</a>
    </div>

    <section class="glass-card p-6">
        <div class="space-y-3">
            <?php if (empty($orders)): ?>
                <p class="text-sm text-slate-400">No orders yet.</p>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="rounded-2xl border border-white/10 p-4 flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold"><?= htmlspecialchars($order['description'] ?: 'Order') ?></div>
                            <div class="text-sm text-slate-400"><?= htmlspecialchars(ucfirst($order['type'])) ?> • <?= htmlspecialchars($order['status']) ?></div>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold">₦<?= number_format($order['amount'], 2) ?></div>
                            <div class="text-sm text-slate-400"><?= htmlspecialchars($order['created_at']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php require_once __DIR__ . '/pages/footer.php'; ?>