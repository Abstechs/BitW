<?php
// admin/lotto-control.php
session_start();
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/auth.php';

if (!isAdminLoggedIn()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access Denied');
}

$targetDate = date('Y-m-d');
$activeLength = (int)($_GET['len'] ?? 6);
if(!in_array($activeLength, [4, 6, 8])) { $activeLength = 6; }

// Dynamic Multiplier Configuration Odds System
$oddsMap = [4 => 100, 6 => 500, 8 => 2500];
$currentMultiplier = $oddsMap[$activeLength];

$audit = ['real_pool' => 0.00, 'demo_pool' => 0.00, 'total_tickets' => 0];

try {
    // 1. Structural Pre-Draw Asset Tracking Matrix Analysis Query
    $auditStmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN mode = 'real' THEN amount ELSE 0 END) as real_total,
            SUM(CASE WHEN mode = 'demo' THEN amount ELSE 0 END) as demo_total,
            COUNT(id) as total_entries
        FROM lotto_allocations 
        WHERE DATE(draw_date) = :target_date AND prediction_length = :len
    ");
    $auditStmt->execute(['target_date' => $targetDate, 'len' => $activeLength]);
    $auditData = $auditStmt->fetch(PDO::FETCH_ASSOC);
    if ($auditData) {
        $audit['real_pool'] = (float)($auditData['real_total'] ?? 0);
        $audit['demo_pool'] = (float)($auditData['demo_total'] ?? 0);
        $audit['total_tickets'] = (int)$auditData['total_entries'];
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
}

// 2. Settlement Execution Loop Action Sequence Intercept
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $luckySequence = trim($_POST['lucky_number'] ?? '');
    
    if (empty($luckySequence) || strlen($luckySequence) !== $activeLength) {
        $msg = "Error: Input sequence string parameter configuration length must equal precisely matches setting constraints ({$activeLength} digits).";
    } else {
        try {
            $pdo->beginTransaction();

            // Set drawing status values globally across targets records parameters maps
            $drawStmt = $pdo->prepare("
                INSERT INTO lotto_draws (draw_date, lucky_number, drawing_length, total_pool_real, total_pool_demo, is_released)
                VALUES (:d, :n, :l, :r, :dm, 1)
                ON DUPLICATE KEY UPDATE lucky_number = :n, total_pool_real = :r, total_pool_demo = :dm, is_released = 1
            ");
            $drawStmt->execute([
                'd' => $targetDate, 'n' => $luckySequence, 'l' => $activeLength,
                'r' => $audit['real_pool'], 'dm' => $audit['demo_pool']
            ]);

            // Clear previous configurations ledger strings safely
            $clearLedger = $pdo->prepare("DELETE FROM lotto_public_ledger WHERE draw_date = :d AND CHAR_LENGTH(sequence) = :l");
            $clearLedger->execute(['d' => $targetDate, 'l' => $activeLength]);

            // Query dynamic records context matching targets elements parameters sequences rules entries indices
            $entriesStmt = $pdo->prepare("SELECT * FROM lotto_allocations WHERE draw_date = :d AND prediction_length = :l FOR UPDATE");
            $entriesStmt->execute(['d' => $targetDate, 'l' => $activeLength]);
            $allEntries = $entriesStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($allEntries as $entry) {
                $isWinner = ($entry['sequence'] === $luckySequence);
                $status = $isWinner ? 'won' : 'lost';
                $payout = $isWinner ? ($entry['amount'] * $currentMultiplier) : 0.00;

                // Update state matrix context metrics safely
                $upd = $pdo->prepare("UPDATE lotto_allocations SET status = :s, payout_generated = :p WHERE id = :id");
                $upd->execute(['s' => $status, 'p' => $payout, 'id' => $entry['id']]);

                if ($isWinner && $entry['mode'] === 'real') {
                    // Inject won profits back directly inside the real wallet primary balance tracks securely
                    $credit = $pdo->prepare("UPDATE user_wallets SET balance = balance + :payout WHERE user_id = :uid");
                    $credit->execute(['payout' => $payout, 'uid' => $entry['user_id']]);

                    // Log tracking record transaction node
                    $tLog = $pdo->prepare("INSERT INTO wallet_transactions (user_id, amount, type, reference) VALUES (:u, :a, 'credit', 'lotto_win_payout')");
                    $tLog->execute(['u' => $entry['user_id'], 'a' => $payout]);
                    
                    // Public node registration lines visibility write indices logs
                    $uName = $pdo->query("SELECT username FROM users WHERE id = {$entry['user_id']}")->fetchColumn();
                    $masked = substr($uName, 0, 3) . '***' . substr($uName, -2);
                    $hash = hash('sha256', $masked . $luckySequence . $payout . time());
                    
                    $insL = $pdo->prepare("INSERT INTO lotto_public_ledger (draw_date, username, sequence, amount_staked, payout_amount, is_dummy, verified_hash) VALUES (:d, :u, :s, :as, :pa, 0, :h)");
                    $insL->execute(['d' => $targetDate, 'u' => $masked, 's' => $luckySequence, 'as' => $entry['amount'], 'pa' => $payout, 'h' => $hash]);
                }
            }

            // High Frequency Random Dynamic Dummy Profiles injections sequence loops rules parameters
            $dummyVolume = random_int(2, 5);
            for ($i = 0; $i < $dummyVolume; $i++) {
                $prefixes = ['Volt', 'Bet', 'Apex', 'Naira', 'Coin', 'Max', 'Prime', 'Solid'];
                $suffixes = ['_Win', 'Genius', 'Guru', 'Play', 'Boss', 'Elite'];
                $dummyUser = $prefixes[array_rand($prefixes)] . $suffixes[array_rand($suffixes)] . random_int(10, 99);
                $dummyMask = substr($dummyUser, 0, 3) . '***' . substr($dummyUser, -2);
                
                $dummyStake = random_int(250, 1200);
                $dummyPayout = $dummyStake * $currentMultiplier;
                $hash = hash('sha256', $dummyMask . $luckySequence . $dummyPayout . time());

                $insL = $pdo->prepare("INSERT INTO lotto_public_ledger (draw_date, username, sequence, amount_staked, payout_amount, is_dummy, verified_hash) VALUES (:d, :u, :s, :as, :pa, 1, :h)");
                $insL->execute(['d' => $targetDate, 'u' => $dummyMask, 's' => $luckySequence, 'as' => $dummyStake, 'pa' => $dummyPayout, 'h' => $hash]);
            }

            $pdo->commit();
            $msg = "Success: Drawing allocations variable fields updated and balance ledger settlements committed successfully.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "Critical Transaction Exception: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html class="bg-slate-950 text-white font-sans">
<head><title>System Operations Configuration Terminal</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="p-4 sm:p-8 max-w-5xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b border-white/10 pb-4 gap-4">
        <div>
            <h1 class="text-xl font-black text-rose-500 tracking-widest uppercase">Lotto Risk Management & Settlement Panel</h1>
            <p class="text-xs text-slate-400">Perform exhaustive pooled transaction capital audits prior to triggering winning sequences declarations updates rules parameters.</p>
        </div>
        <!-- Length Config Filter Bar Controls -->
        <div class="flex gap-2 bg-slate-900 p-1.5 rounded-xl border border-white/5 text-xs font-bold font-mono">
            <a href="?len=4" class="px-4 py-2 rounded-lg transition-all <?php echo $activeLength === 4 ? 'bg-rose-600 text-white' : 'text-slate-400 hover:text-white'; ?>">4 Digits (100x)</a>
            <a href="?len=6" class="px-4 py-2 rounded-lg transition-all <?php echo $activeLength === 6 ? 'bg-rose-600 text-white' : 'text-slate-400 hover:text-white'; ?>">6 Digits (500x)</a>
            <a href="?len=8" class="px-4 py-2 rounded-lg transition-all <?php echo $activeLength === 8 ? 'bg-rose-600 text-white' : 'text-slate-400 hover:text-white'; ?>">8 Digits (2500x)</a>
        </div>
    </div>

    <?php if(!empty($msg)): ?>
        <div class="p-4 bg-purple-600/20 border border-purple-500 rounded-xl text-xs font-mono font-bold"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- Pool Sheet Audit Matrix Cards -->
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-3">
        <div class="bg-slate-900 p-4 rounded-xl border border-white/5">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Audited Real Capital Pool</span>
            <p class="text-xl font-mono font-bold text-emerald-400 mt-1">₦ <?php echo number_format($audit['real_pool'], 2); ?></p>
        </div>
        <div class="bg-slate-900 p-4 rounded-xl border border-white/5">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Sandbox Simulated Pool</span>
            <p class="text-xl font-mono font-bold text-blue-400 mt-1">₦ <?php echo number_format($audit['demo_pool'], 2); ?></p>
        </div>
        <div class="bg-slate-900 p-4 rounded-xl border border-white/5">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Active Stream Entry Logs</span>
            <p class="text-xl font-mono font-bold text-white mt-1"><?php echo $audit['total_tickets']; ?> Lines</p>
        </div>
    </div>

    <!-- Settlement Strategy Form Options Controls Configuration -->
    <div class="bg-slate-900 p-6 rounded-2xl border border-white/10 space-y-4">
        <h3 class="text-xs font-bold uppercase text-slate-300 tracking-widest">Execute Draw Actions Matrix Matrix (Current Target Array Mode: <?php echo $activeLength; ?> Digits)</h3>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs text-slate-400 mb-2">Input Absolute Strategy Winning Character Sequence String (Exactly <?php echo $activeLength; ?> Characters required)</label>
                <input type="text" name="lucky_number" maxlength="<?php echo $activeLength; ?>" class="w-full bg-slate-950 border border-white/10 rounded-xl p-3 text-center tracking-widest font-mono text-2xl text-emerald-400 focus:outline-none focus:border-rose-500" placeholder="e.g. <?php echo str_repeat('7', $activeLength); ?>">
            </div>
            <button type="submit" name="action" value="settle" class="w-full bg-rose-600 hover:bg-rose-500 py-3.5 font-bold rounded-xl text-xs uppercase tracking-widest transition-all shadow-lg shadow-rose-600/10">
                Lock Matrix Logic Sequence & Distribute Ledger Earnings
            </button>
        </form>
    </div>
</body>
</html>