<?php
require_once __DIR__ . '/includes/admin_init.php';
require_once __DIR__ . '/../src/LottoDrawEngineService.php';

$engine = new LottoDrawEngineService($pdo);
$message = null;
$error = null;

// Place this POST handler near the top of admin/draws.php alongside the settlement trigger:

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_tiers') {
    $tiers = $_POST['tiers'] ?? [];
    try {
        $updStmt = $pdo->prepare("
            INSERT INTO lotto_tier_settings (drawing_length, match_count, multiplier) 
            VALUES (:length, :match_count, :multiplier)
            ON DUPLICATE KEY UPDATE multiplier = :multiplier
        ");
        
        foreach ($tiers as $key => $multiplier) {
            list($length, $matchCount) = explode('_', $key);
            $updStmt->execute([
                'length' => (int)$length,
                'match_count' => (int)$matchCount,
                'multiplier' => (float)$multiplier
            ]);
        }
        $message = "Payout tier multipliers updated successfully!";
    } catch (PDOException $e) {
        $error = "Failed to update tiers: " . $e->getMessage();
    }
}

// Fetch configured tiers for display
$tierStmt = $pdo->query("SELECT * FROM lotto_tier_settings ORDER BY drawing_length ASC, match_count DESC");
$configuredTiers = $tierStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Handle Manual Settlement Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'run_settlement') {
    $drawDate = trim($_POST['draw_date'] ?? date('Y-m-d'));
    $digitLength = (int)($_POST['digit_length'] ?? 6);

    $res = $engine->settleDrawForLength($drawDate, $digitLength, 'admin_manual');
    if ($res['status'] === 'success') {
        $message = "Draw settled! Lucky Number: <strong>{$res['lucky_number']}</strong>. Settled {$res['settled_positions']} entries with total payout of {$res['total_payout']} USD.";
    } else {
        $error = $res['message'];
    }
}

// Fetch historical draws
$drawsStmt = $pdo->query("SELECT * FROM lotto_draws ORDER BY draw_date DESC, drawing_length ASC LIMIT 30");
$recentDraws = $drawsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Fetch current pending positions summary
$pendingStmt = $pdo->query("
    SELECT CHAR_LENGTH(sequence) as length, COUNT(*) as pending_count, SUM(amount) as total_stake 
    FROM lotto_allocations 
    WHERE status = 'pending' 
    GROUP BY CHAR_LENGTH(sequence)
");
$pendingSummary = $pendingStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-top mb-6">
    <div>
        <span class="badge">Lotto Engine</span>
        <h2 class="text-3xl font-bold mt-2">Draw Management & Settlement</h2>
        <p class="text-slate-400 mt-1">Execute manual settlements, view pending pools, and inspect published winning numbers.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="p-4 mb-6 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-400">
        <?= $message ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="p-4 mb-6 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-400">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="grid gap-6 lg:grid-cols-3 mb-8">
    <!-- Manual Trigger Card -->
    <div class="admin-card lg:col-span-1">
        <h3 class="text-xl font-semibold mb-4">Manual Draw Trigger</h3>
        <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="action" value="run_settlement">
            
            <div>
                <label class="block text-sm text-slate-400 mb-1">Target Draw Date</label>
                <input type="date" name="draw_date" value="<?= date('Y-m-d') ?>" class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-white" required>
            </div>

            <div>
                <label class="block text-sm text-slate-400 mb-1">Digit Category</label>
                <select name="digit_length" class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-white">
                    <option value="4">4-Digit Pool</option>
                    <option value="6" selected>6-Digit Pool</option>
                    <option value="8">8-Digit Pool</option>
                </select>
            </div>

            <button type="submit" class="w-full btn-secondary bg-purple-600/30 hover:bg-purple-600/50 text-purple-300 border border-purple-500/40 py-2 rounded font-semibold">
                Execute Settlement
            </button>
        </form>
    </div>

    <!-- Active Pending Pools -->
    <div class="admin-card lg:col-span-2">
        <h3 class="text-xl font-semibold mb-4">Pending Pool Summary</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="border-b border-slate-700 text-slate-400 uppercase text-xs">
                    <tr>
                        <th class="py-2">Category</th>
                        <th class="py-2">Pending Allocations</th>
                        <th class="py-2">Total Staked Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <?php if (empty($pendingSummary)): ?>
                        <tr><td colspan="3" class="py-4 text-center text-slate-500">No pending allocations found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pendingSummary as $row): ?>
                            <tr>
                                <td class="py-3 font-semibold text-white"><?= intval($row['length']) ?>-Digit Pool</td>
                                <td class="py-3"><?= number_format($row['pending_count']) ?> positions</td>
                                <td class="py-3">$<?= number_format((float)$row['total_stake'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Published Draws History -->
<div class="admin-card">
    <h3 class="text-xl font-semibold mb-4">Recent Published Draws</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="border-b border-slate-700 text-slate-400 uppercase text-xs">
                <tr>
                    <th class="py-2">Draw Date</th>
                    <th class="py-2">Category</th>
                    <th class="py-2">Winning Sequence</th>
                    <th class="py-2">Status</th>
                    <th class="py-2">Published At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                <?php foreach ($recentDraws as $draw): ?>
                    <tr>
                        <td class="py-3"><?= htmlspecialchars($draw['draw_date']) ?></td>
                        <td class="py-3"><?= intval($draw['drawing_length']) ?> Digits</td>
                        <td class="py-3 font-mono font-bold text-emerald-400"><?= htmlspecialchars($draw['lucky_number'] ?? 'N/A') ?></td>
                        <td class="py-3">
                            <span class="px-2 py-1 rounded text-xs <?= $draw['is_released'] ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400' ?>">
                                <?= $draw['is_released'] ? 'Released' : 'Pending' ?>
                            </span>
                        </td>
                        <td class="py-3 text-slate-500"><?= htmlspecialchars($draw['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="admin-card mt-8">
    <h3 class="text-xl font-semibold mb-2">Configure Tier Payout Multipliers</h3>
    <p class="text-sm text-slate-400 mb-6">Set the payout multiplier ($Stake \times Multiplier$) awarded to users matching partial or full sequences.</p>
    
    <form method="POST" action="">
        <input type="hidden" name="action" value="update_tiers">
        <div class="grid gap-6 md:grid-cols-3">
            <?php 
            $groupedTiers = [];
            foreach ($configuredTiers as $t) {
                $groupedTiers[$t['drawing_length']][] = $t;
            }
            foreach ([4, 6, 8] as $len): 
            ?>
                <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/60">
                    <h4 class="font-bold text-emerald-400 border-b border-slate-700 pb-2 mb-3"><?= $len ?>-Digit Pool Tiers</h4>
                    <div class="space-y-3">
                        <?php foreach ($groupedTiers[$len] ?? [] as $tier): ?>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-300">Match <?= $tier['match_count'] ?> Digits:</span>
                                <div class="flex items-center gap-1">
                                    <input type="number" step="0.01" min="0" 
                                           name="tiers[<?= $tier['drawing_length'] ?>_<?= $tier['match_count'] ?>]" 
                                           value="<?= htmlspecialchars($tier['multiplier']) ?>" 
                                           class="w-24 bg-slate-900 border border-slate-700 rounded p-1 text-right text-white text-xs font-mono">
                                    <span class="text-slate-500 text-xs">x</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-6 text-right">
            <button type="submit" class="btn-secondary bg-emerald-600/30 hover:bg-emerald-600/50 text-emerald-300 border border-emerald-500/40 px-6 py-2 rounded font-semibold">
                Save Multiplier Configurations
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>