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

// Fetch Admin configurations dynamically (fallback to 2000 default if setting is not loaded)
$adminSettings = function_exists('getAdminSettings') ? getAdminSettings() : [];
$minWithdrawal = floatval($adminSettings['MIN_WITHDRAWAL_AMOUNT'] ?? 2000);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw_request'])) {
    $amount = (float) ($_POST['amount'] ?? 0);
    
    // 1. Core Restriction Check: Validate if bank profile data metrics have been filled
    if (empty($wallet['account_number']) || empty($wallet['bank_name']) || empty($wallet['account_name'])) {
        $messages[] = [
            'type' => 'error', 
            'text' => 'Withdrawal blocked. Please navigate to Settings to save your bank details before filing a payout.'
        ];
    }
    // 2. Core Restriction Check: Enforce minimum admin payout setting amount
    else if ($amount < $minWithdrawal) {
        $messages[] = [
            'type' => 'error', 
            'text' => 'Withdrawal blocked. The minimum single withdrawal amount allowed is ₦' . number_format($minWithdrawal, 2)
        ];
    }
    // 3. Balance Validation
    else if ($amount > ($wallet['balance'] ?? 0)) {
        $messages[] = [
            'type' => 'error', 
            'text' => 'Insufficient funds available. Your current balance is ₦' . number_format($wallet['balance'], 2)
        ];
    } 
    else {
        // Keep the main category query string uniform
        $methodString = "Bank Transfer";
        
        // Create an un-alterable tracking log string snapshot detailing target settlement parameters
        $snapshotLog = "Bank: " . $wallet['bank_name'] . " | Account No: " . $wallet['account_number'] . " | Account Name: " . $wallet['account_name'];
        
        // Process withdrawal metrics passing the snapshot payload tracking argument
        $result = requestWithdrawal($user_id, $amount, $methodString, $snapshotLog);
        $messages[] = ['type' => $result['success'] ? 'success' : 'error', 'text' => $result['message']];
        
        // Refresh local cache viewport instance metrics
        $wallet = getWallet($user_id);
    }
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
        <!-- WITHDRAWAL REQUEST INTAKE BLOCK -->
        <section class="glass-card p-6">
            <div class="section-header">
                <div>
                    <h2>Withdrawal request</h2>
                    <p class="text-sm text-slate-400 mt-1">Create a request from your available wallet balance.</p>
                </div>
            </div>
            
            <form method="POST" class="form-row">
                <input type="hidden" name="withdraw_request" value="1">
                
                <div class="mb-2">
                    <label class="block text-xs text-slate-400 mb-1">Enter Payout Amount (₦)</label>
                    <input type="number" step="0.01" name="amount" class="form-field" placeholder="0.00" required>
                </div>
                
                <!-- Display target settlement verification directly inside layout context -->
                <div class="bg-white/5 border border-white/10 rounded-xl p-3 text-xs text-slate-300 space-y-1">
                    <span class="text-slate-500 block uppercase font-bold tracking-wider text-[10px]">Settlement Account Target</span>
                    <?php if(!empty($wallet['account_number'])): ?>
                        <div class="font-semibold text-white"><i class="bx bx-git-commit mr-1 text-green-400"></i><?= htmlspecialchars($wallet['bank_name']) ?> — <?= htmlspecialchars($wallet['account_number']) ?></div>
                        <div class="text-[11px] text-slate-400"><?= htmlspecialchars($wallet['account_name']) ?></div>
                    <?php else: ?>
                        <div class="text-red-400 font-semibold"><i class="bx bx-error-alt mr-1"></i> No bank details linked yet. Please visit your profile settings.</div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="action-button mt-2 w-full">Submit withdrawal</button>
            </form>
            
            <div class="mt-4 flex justify-between text-xs text-slate-400">
                <div>Available balance: <span class="text-green-400 font-semibold">₦<?= number_format($wallet['balance'] ?? 0, 2) ?></span></div>
                <div>Min Single Payout Limit: <span class="text-slate-200">₦<?= number_format($minWithdrawal, 2) ?></span></div>
            </div>
        </section>

        <!-- SECURE HISTORY TRACKING RECORD LOGS -->
        <section class="glass-card p-6">
            <div class="section-header">
                <div>
                    <h2>Withdrawal history</h2>
                    <p class="text-sm text-slate-400 mt-1">Track pending and completed cashouts.</p>
                </div>
            </div>
            <div class="space-y-3 max-h-[400px] overflow-y-auto pr-1">
                <?php if (empty($withdrawals)): ?>
                    <p class="text-sm text-slate-400">No withdrawals requested yet.</p>
                <?php else: ?>
                    <?php foreach ($withdrawals as $item): ?>
                        <div class="rounded-2xl border border-white/10 p-3 flex items-center justify-between gap-4 bg-white/[0.02]">
                            <div>
                                <div class="font-semibold text-white">₦<?= number_format($item['amount'], 2) ?></div>
                                <!-- Gracefully handle layout mapping for older records without detail values -->
                                <div class="text-xs text-slate-400 mt-0.5 max-w-[240px] break-words">
                                    <?= htmlspecialchars(!empty($item['payout_details']) ? $item['payout_details'] : $item['method']) ?>
                                </div>
                                <div class="text-[10px] text-slate-500 mt-1"><i class="bx bx-time-five"></i> <?= $item['created_at'] ?></div>
                            </div>
                            <span class="status-pill <?= $item['status'] === 'completed' ? 'status-success' : ($item['status'] === 'rejected' ? 'status-danger' : 'status-muted') ?>">
                                <?= htmlspecialchars(ucfirst($item['status'])) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
<?php require_once __DIR__ . '/pages/footer.php'; ?>