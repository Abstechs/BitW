<?php
// public/admin/ecosystem.php
require_once __DIR__ . '/includes/admin_init.php';

$success = $_GET['success'] ?? '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Handle Asset Seeding
    if ($action === 'create_asset') {
        $name = trim($_POST['name'] ?? '');
        $ticker = strtoupper(trim($_POST['ticker'] ?? ''));
        $base_price = floatval($_POST['base_price'] ?? 0);
        $total_supply = floatval($_POST['total_supply'] ?? 0);

        if (!$name || !$ticker || $base_price <= 0 || $total_supply <= 0) {
            $errors[] = "Invalid structural metrics supplied for asset creation.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO trade_assets (name, ticker, base_price, current_price, total_supply) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $ticker, $base_price, $base_price, $total_supply]);
            
            // Seed initial record point in historical timeline matrix
            $assetId = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO asset_price_history (asset_id, price) VALUES (?, ?)")->execute([$assetId, $base_price]);
            
            header("Location: ecosystem.php?success=Asset initialized successfully");
            exit;
        }
    }

    // Handle User Flag Restrictions
    if ($action === 'restrict_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        $message = trim($_POST['custom_message'] ?? '');

        $stmt = $pdo->prepare("UPDATE users SET status = ?, status_custom_message = ? WHERE id = ?");
        $stmt->execute([$status, empty($message) ? null : $message, $user_id]);
        
        header("Location: ecosystem.php?success=User restriction status altered successfully");
        exit;
    }
}

// Fetch all elements for rendering panels
$assets = $pdo->query("SELECT * FROM trade_assets ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$users = $pdo->query("SELECT id, username, email, status, status_custom_message FROM users ORDER BY id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/admin_header.php';
?>

<main class="max-w-7xl mx-auto px-4 py-8 space-y-12">
    <div>
        <h1 class="text-3xl font-bold text-white mb-2">Ecosystem Architecture & Rules Control</h1>
        <p class="text-slate-400">Configure core virtual assets, dynamic multipliers, and lock down user restrictions platform-wide.</p>
    </div>

    <?php if ($success): ?>
        <div class="p-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 text-emerald-400 text-sm"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="p-4 rounded-xl border border-rose-500/20 bg-rose-500/10 text-rose-400 text-sm"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>

    <div class="grid gap-8 lg:grid-cols-2">
        <!-- PANEL A: LIVE ASSET INITIALIZER -->
        <div class="glass-card p-6 space-y-4">
            <h2 class="text-xl font-bold text-white"><i class="bx bx-planet text-blue-500"></i> Seed New Market Resource Asset</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create_asset">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-slate-400 uppercase mb-1">Asset Common Name</label>
                        <input type="text" name="name" placeholder="e.g., Mars Quartz" class="w-full bg-slate-900 border border-white/10 rounded-xl p-3 text-white focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 uppercase mb-1">Ticker ID</label>
                        <input type="text" name="ticker" placeholder="e.g., MQZ" class="w-full bg-slate-900 border border-white/10 rounded-xl p-3 text-white focus:outline-none" required>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-slate-400 uppercase mb-1">Initial Base Valuation ($)</label>
                        <input type="number" step="0.0001" name="base_price" placeholder="1.0000" class="w-full bg-slate-900 border border-white/10 rounded-xl p-3 text-white focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 uppercase mb-1">Total Fixed Ecosystem Pool Supply</label>
                        <input type="number" step="1" name="total_supply" placeholder="1000000" class="w-full bg-slate-900 border border-white/10 rounded-xl p-3 text-white focus:outline-none" required>
                    </div>
                </div>
                <button type="submit" class="action-button w-full justify-center bg-blue-600 border-none">Inject Asset Blueprint</button>
            </form>

            <div class="pt-4 border-t border-white/5 space-y-2">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active Assets Available</h3>
                <div class="max-h-40 overflow-y-auto space-y-2 pr-1">
                    <?php foreach ($assets as $a): ?>
                        <div class="flex items-center justify-between p-3 bg-slate-900/60 rounded-xl border border-white/5">
                            <div>
                                <span class="font-bold text-white"><?= htmlspecialchars($a['name']) ?></span>
                                <span class="text-xs font-mono text-slate-500 ml-1">[<?= htmlspecialchars($a['ticker']) ?>]</span>
                            </div>
                            <span class="font-mono text-emerald-400 font-semibold">$<?= number_format($a['current_price'], 4) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- PANEL B: USER STATUS INTEGRITY MATRIX -->
        <div class="glass-card p-6 space-y-4">
            <h2 class="text-xl font-bold text-white"><i class="bx bx-shield-quarter text-rose-500"></i> Active Access Modification Panel</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="restrict_user">
                <div>
                    <label class="block text-xs text-slate-400 uppercase mb-1">Target Account</label>
                    <select name="user_id" class="w-full bg-slate-900 border border-white/10 rounded-xl p-3 text-white focus:outline-none" required>
                        <option value="">-- Select a User Record --</option>
                        <?php foreach($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars($u['email']) ?>) [<?= $u['status'] ?>]</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-1">
                        <label class="block text-xs text-slate-400 uppercase mb-1">Status Enforcement Flag</label>
                        <select name="status" class="w-full bg-slate-900 border border-white/10 rounded-xl p-3 text-white focus:outline-none" required>
                            <option value="active">Active</option>
                            <option value="banned">Banned</option>
                            <option value="restricted">Restricted</option>
                            <option value="action_required">Action Required</option>
                            <option value="verification_required">Verification Required</option>
                            <option value="under_age">Under Age</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs text-slate-400 uppercase mb-1">Tweakable Display Message (Optional Override)</label>
                        <input type="text" name="custom_message" placeholder="Leave blank for generic rule message" class="w-full bg-slate-900 border border-white/10 rounded-xl p-3 text-white focus:outline-none">
                    </div>
                </div>
                <button type="submit" class="action-button w-full justify-center bg-rose-600 border-none">Enforce Restriction Parameters</button>
            </form>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>