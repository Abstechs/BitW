<?php
require_once __DIR__ . '/includes/admin_init.php';
require_once __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-top">
    <div>
        <span class="badge">Admin Overview</span>
        <h2 class="text-3xl font-bold mt-4">Welcome back, admin</h2>
        <p class="text-slate-400 mt-2">Monitor the platform, review activity, and manage plans from one place.</p>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="admin-card">
        <h3 class="text-xl font-semibold mb-3">Quick actions</h3>
        <div class="space-y-3">
            <a href="plans.php" class="btn-secondary w-full">Manage Stone Plans</a>
            <a href="transactions.php" class="btn-secondary w-full">View Transactions</a>
            <a href="users.php" class="btn-secondary w-full">Manage Users</a>
            <a href="notifications.php" class="btn-secondary w-full bg-cyan-600/20 hover:bg-cyan-600/40 text-cyan-400 border border-cyan-500/30">Send System Notification</a>
        </div>
    </div>
    <div class="admin-card">
        <h3 class="text-xl font-semibold mb-3">Status</h3>
        <p class="text-slate-400">This dashboard is ready for production plan management and can be extended with analytics panels.</p>
    </div>
    <div class="admin-card">
        <h3 class="text-xl font-semibold mb-3">Stone plan count</h3>
        <p class="text-4xl font-bold mt-4"><?php
            $count = $pdo->query('SELECT COUNT(*) AS total FROM plans')->fetchColumn();
            echo intval($count);
        ?></p>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/admin_footer.php';
