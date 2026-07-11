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
$wishlist = getWishlist($user_id);
$pageTitle = 'Wishlist - ' . (AppConfig::get('APP_ALIAS') ?: 'BitW');
require_once __DIR__ . '/pages/header.php';
?>
<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="badge">Wishlist</p>
            <h1 class="text-4xl font-semibold tracking-tight mt-4">Your curated stone watchlist</h1>
            <p class="mt-2 text-sm text-slate-400">Keep your favorite stones close and purchase them when the time is right.</p>
        </div>
        <a href="dashboard.php" class="action-button" style="max-width: 220px;"><i class="bx bx-arrow-back"></i> Back to Dashboard</a>
    </div>

    <section class="glass-card p-6">
        <div class="space-y-3">
            <?php if (empty($wishlist)): ?>
                <p class="text-sm text-slate-400">No saved stones yet.</p>
            <?php else: ?>
                <?php foreach ($wishlist as $item): ?>
                    <div class="rounded-2xl border border-white/10 p-4 flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="text-sm text-slate-400">Daily yield <?= (float) $item['daily_rate'] ?>% • <?= (int) $item['duration_days'] ?> days</div>
                        </div>
                        <a href="mining.php" class="action-button" style="max-width: 180px;">View stone</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php require_once __DIR__ . '/pages/footer.php'; ?>