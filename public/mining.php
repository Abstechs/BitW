<?php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/wallet.php';
require_once __DIR__ . '/../core/plans.php';
require_once __DIR__ . '/../core/mining.php';
require_once __DIR__ . '/../core/notifications.php';
require_once __DIR__ . '/../core/marketplace.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user = getUser($user_id);
$wallet = getWallet($user_id);
$plans = getActivePlans();
$activeMinings = getActiveMinings($user_id);
$wishlist = getWishlist($user_id);
$orders = getOrders($user_id, 10);
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase_plan'])) {
    $planId = (int) ($_POST['plan_id'] ?? 0);
    $amount = (float) ($_POST['amount'] ?? 0);
    $result = purchasePlan($user_id, $planId, $amount);
    $messages[] = ['type' => $result['status'] ? 'success' : 'error', 'text' => $result['message']];
    if ($result['status']) {
        $activeMinings = getActiveMinings($user_id);
        $wallet = getWallet($user_id);
    }
}

// Comprehensive key extraction mapping strategy
$wishlistIds = [];
if (!empty($wishlist) && is_array($wishlist)) {
    foreach ($wishlist as $item) {
        if (isset($item['plan_id'])) {
            $wishlistIds[] = (int)$item['plan_id'];
        }
        if (isset($item['id'])) {
            $wishlistIds[] = (int)$item['id'];
        }
    }
}
$wishlistIds = array_unique(array_filter($wishlistIds));

// True Add/Remove backend toggle logic execution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wishlist_plan'])) {
    $planId = (int) ($_POST['plan_id'] ?? 0);
    
    if (in_array($planId, $wishlistIds)) {
        $result = removeFromWishlist($user_id, $planId);
    } else {
        $result = addToWishlist($user_id, $planId);
    }
    
    // Re-fetch clean dataset post-mutation
    $wishlist = getWishlist($user_id);
    
    $wishlistIds = [];
    if (!empty($wishlist) && is_array($wishlist)) {
        foreach ($wishlist as $item) {
            if (isset($item['plan_id'])) {
                $wishlistIds[] = (int)$item['plan_id'];
            }
            if (isset($item['id'])) {
                $wishlistIds[] = (int)$item['id'];
            }
        }
    }
    $wishlistIds = array_values(array_unique(array_filter($wishlistIds)));
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_with_status_payload($wishlist, $wishlistIds);
        exit;
    }
}

function json_with_status_payload($wishlist, $wishlistIds) {
    ob_start();
    if (empty($wishlist)): ?>
        <p class="text-sm text-slate-400">No saved stones yet.</p>
    <?php else: ?>
        <?php foreach ($wishlist as $item): ?>
            <div class="rounded-2xl border border-white/10 p-3 flex items-center gap-3">
                <?php if (!empty($item['image'])): ?>
                    <img src="<?= htmlspecialchars($item['image']) ?>" class="w-10 h-10 rounded-xl object-cover border border-white/5 bg-slate-900" alt="Saved stone">
                <?php else: ?>
                    <div class="w-10 h-10 rounded-xl border border-dashed border-slate-700 bg-slate-950 flex items-center justify-center text-yellow-500 text-sm">
                        <i class="bx bx-diamond"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <div class="font-semibold"><?= htmlspecialchars($item['name']) ?></div>
                    <div class="text-sm text-slate-400">Daily yield <?= (float) $item['daily_rate'] ?>% • <?= (int) $item['duration_days'] ?> days</div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif;
    $html = ob_get_clean();
    return json_encode(['html' => $html, 'ids' => $wishlistIds]);
}

$pageTitle = 'Mining - ' . (AppConfig::get('APP_ALIAS') ?: 'BitW');
require_once __DIR__ . '/pages/header.php';
?>
<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="badge">Futuristic Mining</p>
            <h1 class="text-4xl font-semibold tracking-tight mt-4">Acquire, monitor, and grow your stone portfolio</h1>
            <p class="mt-2 text-sm text-slate-400">Every stone comes with a live daily yield, detailed lore, and a purchase flow built for modern dashboards.</p>
        </div>
        <a href="dashboard.php" class="action-button" style="max-width: 220px;"><i class="bx bx-arrow-back"></i> Back to Dashboard</a>
    </div>

    <?php foreach ($messages as $message): ?>
        <div class="alert <?= $message['type'] === 'success' ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($message['text']) ?>
        </div>
    <?php endforeach; ?>

    <div class="grid gap-6 lg:grid-cols-[1.4fr_0.8fr]">
        <section class="glass-card p-6">
            <div class="section-header">
                <div>
                    <h2>Stone marketplace</h2>
                    <p class="text-sm text-slate-400 mt-1">Buy premium stones, inspect their earnings rate, and review their lore.</p>
                </div>
                <span class="badge"><i class="bx bx-diamond"></i> Live plans</span>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <?php foreach ($plans as $plan): ?>
                    <div class="glass-card p-5 border border-white/10 flex flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <div class="flex items-center gap-3">
                                    <?php if (!empty($plan['image'])): ?>
                                        <img src="<?= htmlspecialchars($plan['image']) ?>" class="w-12 h-12 rounded-xl object-cover border border-white/10 bg-slate-900" alt="<?= htmlspecialchars($plan['name']) ?>">
                                    <?php else: ?>
                                        <div class="w-12 h-12 rounded-xl border border-dashed border-slate-700 bg-slate-900 flex items-center justify-center text-yellow-500 text-xl">
                                            <i class="bx bx-diamond"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h3 class="text-lg font-semibold"><?= htmlspecialchars($plan['name']) ?></h3>
                                        <p class="text-xs text-slate-400"><?= htmlspecialchars($plan['description'] ?? 'Premium stone plan') ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="text-2xl font-semibold mb-3">₦<?= number_format($plan['min_amount'], 2) ?><?php if ($plan['max_amount']): ?> – ₦<?= number_format($plan['max_amount'], 2) ?><?php endif; ?></div>
                            <div class="text-sm text-slate-400 mb-2">Daily yield: <?= (float) $plan['daily_rate'] ?>%</div>
                            <div class="text-sm text-slate-400 mb-4">Duration: <?= (int) $plan['duration_days'] ?> days</div>
                            <?php if (!empty($plan['background_story'])): ?>
                                <p class="text-sm text-slate-400 mb-4"><?= htmlspecialchars(substr($plan['background_story'], 0, 120)) ?>...</p>
                            <?php endif; ?>
                        </div>
                        <div class="flex gap-2 mt-3">
                            <form method="POST" class="flex-1">
                                <input type="hidden" name="purchase_plan" value="1">
                                <input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
                                <input type="number" step="0.01" min="<?= (float) $plan['min_amount'] ?>" name="amount" class="form-field mb-2" value="<?= number_format((float) $plan['min_amount'], 2, '.', '') ?>" placeholder="Purchase amount">
                                <button type="submit" class="action-button">Purchase stone</button>
                            </form>
                            
                            <button type="button" data-wishlist-btn="<?= (int)$plan['id'] ?>" onclick="executeWishlistToggle(this, <?= (int)$plan['id'] ?>)" class="action-button transition active:scale-95 flex items-center justify-center" style="max-width: 52px; padding: 0.95rem;">
                                <?php if (in_array($plan['id'], $wishlistIds)): ?>
                                    <i class="bx bxs-heart text-rose-500 transition-colors"></i>
                                <?php else: ?>
                                    <i class="bx bx-heart transition-colors"></i>
                                <?php endif; ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="flex flex-col gap-6">
            <section class="glass-card p-6">
                <div class="section-header">
                    <div>
                        <h2>Wallet</h2>
                        <p class="text-sm text-slate-400 mt-1">Your available balance for future stone purchases.</p>
                    </div>
                    <span class="badge"><i class="bx bx-wallet"></i> Ready</span>
                </div>
                <div class="text-4xl font-semibold">₦<?= number_format($wallet['balance'] ?? 0, 2) ?></div>
            </section>

            <section class="glass-card p-6">
                <div class="section-header">
                    <div>
                        <h2>Wishlist</h2>
                        <p class="text-sm text-slate-400 mt-1">Save stones for later and revisit them anytime.</p>
                    </div>
                </div>
                <div id="dynamic-wishlist-container" class="space-y-3">
                    <?php if (empty($wishlist)): ?>
                        <p class="text-sm text-slate-400">No saved stones yet.</p>
                    <?php else: ?>
                        <?php foreach ($wishlist as $item): ?>
                            <div class="rounded-2xl border border-white/10 p-3 flex items-center gap-3">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?= htmlspecialchars($item['image']) ?>" class="w-10 h-10 rounded-xl object-cover border border-white/5 bg-slate-900" alt="Saved stone">
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-xl border border-dashed border-slate-700 bg-slate-950 flex items-center justify-center text-yellow-500 text-sm">
                                        <i class="bx bx-diamond"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="font-semibold"><?= htmlspecialchars($item['name']) ?></div>
                                    <div class="text-sm text-slate-400">Daily yield <?= (float) $item['daily_rate'] ?>% • <?= (int) $item['duration_days'] ?> days</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </aside>
    </div>

    <!-- Active Stone Section -->
    <section class="glass-card p-6">
        <div class="section-header">
            <div>
                <h2>Active stone purchases</h2>
                <p class="text-sm text-slate-400 mt-1">Follow each active mining contract with yield and performance.</p>
            </div>
        </div>
        <div class="space-y-4">
            <?php if (empty($activeMinings)): ?>
                <p class="text-sm text-slate-400">No active mining stones yet.</p>
            <?php else: ?>
                <?php foreach ($activeMinings as $mining): ?>
                    <div class="rounded-2xl border border-white/10 p-4">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div class="flex items-center gap-4">
                                <?php if (!empty($mining['image'])): ?>
                                    <img src="<?= htmlspecialchars($mining['image']) ?>" class="w-12 h-12 rounded-xl object-cover border border-white/10 bg-slate-900" alt="Active item preview">
                                <?php else: ?>
                                    <div class="w-12 h-12 rounded-xl border border-dashed border-slate-700 bg-slate-950 flex items-center justify-center text-yellow-500 text-lg">
                                        <i class="bx bx-diamond"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="font-semibold"><?= htmlspecialchars($mining['plan_name']) ?></div>
                                    <div class="text-sm text-slate-400">Invested: ₦<?= number_format($mining['amount'], 2) ?> • Daily earnings: ₦<?= number_format($mining['daily_earnings'] ?? 0, 2) ?></div>
                                    <div class="text-sm text-slate-400">Status: <?= htmlspecialchars($mining['status']) ?></div>
                                    <?php if (!empty($mining['description'])): ?>
                                        <div class="text-sm text-slate-400 mt-2"><?= htmlspecialchars($mining['description']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($mining['read_more_link'])): ?>
                                <a href="<?= htmlspecialchars($mining['read_more_link']) ?>" target="_blank" class="dashboard-link" style="max-width: 220px;">Read more</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Recent Orders Section -->
    <section class="glass-card p-6">
        <div class="section-header">
            <div>
                <h2>Recent orders</h2>
                <p class="text-sm text-slate-400 mt-1">Track your recent purchases and wallet actions.</p>
            </div>
        </div>
        <div class="space-y-3">
            <?php if (empty($orders)): ?>
                <p class="text-sm text-slate-400">No orders yet.</p>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="rounded-2xl border border-white/10 p-3 flex items-center justify-between gap-4">
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

<script>
function executeWishlistToggle(button, planId) {
    if (button.classList.contains('pointer-events-none')) return;
    
    const icon = button.querySelector('i');
    const isCurrentlyAdded = icon.classList.contains('bxs-heart');
    
    button.classList.add('pointer-events-none', 'opacity-60');
    icon.className = 'bx bx-loader-alt animate-spin text-slate-400';

    const data = new FormData();
    data.append('wishlist_plan', '1');
    data.append('plan_id', planId);

    fetch('mining.php', {
        method: 'POST',
        body: data,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => {
        if(!res.ok) throw new Error();
        return res.json();
    })
    .then(payload => {
        document.getElementById('dynamic-wishlist-container').innerHTML = payload.html;
        
        document.querySelectorAll('[data-wishlist-btn]').forEach(btn => {
            const id = parseInt(btn.getAttribute('data-wishlist-btn'));
            const btnIcon = btn.querySelector('i');
            
            if(payload.ids.includes(id)) {
                btnIcon.className = 'bx bxs-heart text-rose-500 transition-colors';
            } else {
                btnIcon.className = 'bx bx-heart transition-colors';
            }
        });
    })
    .catch(() => {
        if (isCurrentlyAdded) {
            icon.className = 'bx bxs-heart text-rose-500 transition-colors';
        } else {
            icon.className = 'bx bx-heart transition-colors';
        }
    })
    .finally(() => {
        button.classList.remove('pointer-events-none', 'opacity-60');
    });
}
</script>

<?php require_once __DIR__ . '/pages/footer.php'; ?>