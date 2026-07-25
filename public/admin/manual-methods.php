<?php
// public/admin/manual-methods.php
session_start();
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/auth.php';

// Strict Admin Gate Interception
if (!isLoggedIn() || ($_SESSION['is_admin'] ?? 0) != 1) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$statusType = 'success';

// Handle Channel Injection Action Pipeline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_inject'])) {
    $bankName = trim($_POST['bank_name'] ?? '');
    $accNumber = trim($_POST['account_number'] ?? '');
    $accName = trim($_POST['account_name'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if (empty($bankName) || empty($accNumber) || empty($accName)) {
        $message = "All funding dimension parameters are required.";
        $statusType = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO manual_methods (bank_name, account_number, account_name, status, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$bankName, $accNumber, $accName, $status]);
            $message = "Payment channel injected into database successfully.";
        } catch (Exception $e) {
            $message = "Ledger Injection Failure: " . $e->getMessage();
            $statusType = 'error';
        }
    }
}

// Fetch all registered manual channels
$methods = $pdo->query("SELECT * FROM manual_methods ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Manual Gateway Configuration';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <style>
        body { background: #0b0f19; color: #f8fafc; }
        .glass-card { background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.08); }
    </style>
</head>
<body class="min-h-screen p-4 md:p-8">

    <div class="max-w-6xl mx-auto space-y-6">
        <!-- Header Nav Block -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-white/5 pb-5">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-blue-400 bg-blue-500/10 px-2.5 py-1 rounded-md">System Core</span>
                <h1 class="text-2xl font-black text-white mt-2">Manual Funding Methods Manager</h1>
            </div>
            <a href="index.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 border border-white/10 text-sm font-semibold text-slate-300 hover:bg-slate-800 transition-all">
                <i class="bx bx-left-arrow-alt text-lg"></i> Admin Panel
            </a>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <!-- Injection Form Blueprint Panel -->
            <div class="glass-card rounded-2xl p-6 h-fit">
                <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wider border-b border-white/5 pb-3 mb-4">Create Deposit Method</h3>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Bank Institution Name</label>
                        <input type="text" name="bank_name" placeholder="e.g. Kuda Bank" class="w-full bg-slate-950 border border-white/10 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-blue-500 transition-all" required>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Account Number String</label>
                        <input type="text" name="account_number" placeholder="e.g. 3003728830" class="w-full bg-slate-950 border border-white/10 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-blue-500 transition-all" required>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Account Designation Name</label>
                        <input type="text" name="account_name" placeholder="e.g. Abstech Integrated Services" class="w-full bg-slate-950 border border-white/10 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-blue-500 transition-all" required>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Initial Visibility Status</label>
                        <select name="status" class="w-full bg-slate-950 border border-white/10 rounded-xl p-3 text-sm text-white focus:outline-none focus:border-blue-500 transition-all">
                            <option value="active">Active (Visible Globally)</option>
                            <option value="inactive">Disabled (Hidden Context)</option>
                        </select>
                    </div>
                    <button type="submit" name="action_inject" class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm transition-all shadow-lg shadow-blue-600/10">Inject Channel Method</button>
                </form>
            </div>

            <!-- Active Channels Display Output -->
            <div class="md:col-span-2 glass-card rounded-2xl p-6">
                <h3 class="text-sm font-bold text-slate-300 uppercase tracking-wider border-b border-white/5 pb-3 mb-4">Live System Gateways</h3>
                
                <?php if (empty($methods)): ?>
                    <p class="text-sm text-slate-400 py-6 text-center">No active billing methods found in database cache layers.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($methods as $m): ?>
                            <div class="p-4 rounded-xl border border-white/5 bg-slate-950/40 flex justify-between items-center gap-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-bold text-white"><?= htmlspecialchars($m['bank_name']) ?></h4>
                                        <span class="text-[10px] uppercase font-mono px-2 py-0.5 rounded <?= $m['status'] === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' ?>">
                                            <?= $m['status'] ?>
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-300 font-mono"><?= htmlspecialchars($m['account_number']) ?></p>
                                    <p class="text-[11px] text-slate-400"><?= htmlspecialchars($m['account_name']) ?></p>
                                </div>
                                <i class="bx bx-wallet text-2xl text-slate-600"></i>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($message)): ?>
    <script>
        Toastify({
            text: "<?= $message ?>",
            duration: 4000,
            gravity: "top",
            position: "right",
            style: { background: "<?= $statusType === 'success' ? 'linear-gradient(to right, #10b981, #059669)' : 'linear-gradient(to right, #ef4444, #dc2626)' ?>" }
        }).showToast();
    </script>
    <?php endif; ?>
</body>
</html>