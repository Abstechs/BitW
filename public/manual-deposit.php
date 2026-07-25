<?php
// public/manual-deposit.php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/settings.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Global master kill-switch check from AppSettings
AppSettings::load();
if (!AppSettings::get('MANUAL_DEPOSIT_ENABLED')) {
    header('Location: deposit.php');
    exit;
}

// Pull all active structural methods from the DB
$methodStmt = $pdo->prepare("SELECT * FROM manual_methods WHERE status = 'active' ORDER BY id ASC");
$methodStmt->execute();
$methods = $methodStmt->fetchAll(PDO::FETCH_ASSOC);

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $method_id = intval($_POST['method_id'] ?? 0);
    $reference = 'MAN-' . strtoupper(bin2hex(random_bytes(6)));

    if ($amount <= 0) {
        $errors[] = 'Please enter a valid amount greater than zero.';
    }
    
    // Validate that the chosen account method exists and is active
    $checkMethod = $pdo->prepare("SELECT * FROM manual_methods WHERE id = ? AND status = 'active'");
    $checkMethod->execute([$method_id]);
    $selectedMethod = $checkMethod->fetch();
    
    if (!$selectedMethod) {
        $errors[] = 'Invalid or inactive payment method selected.';
    }

    if (!isset($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please upload a clear proof of transfer image.';
    } else {
        $file = $_FILES['proof'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($file['type'], $allowedTypes) || $file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Invalid file format or size limit exceeded (Max 5MB JPG/PNG).';
        }
    }

    if (empty($errors)) {
        $targetDir = __DIR__ . '/assets/deposit-proofs/';
        if (!file_exists($targetDir)) mkdir($targetDir, 0755, true);

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = $reference . '.' . $extension;
        
        if (move_uploaded_file($file['tmp_name'], $targetDir . $fileName)) {
            try {
                $description = "Manual Deposit via " . $selectedMethod['bank_name'] . " [Proof: " . $fileName . "]";
                
                $stmt = $pdo->prepare("
                    INSERT INTO transactions (user_id, method_id, type, amount, reference, status, description, created_at) 
                    VALUES (?, ?, 'deposit', ?, ?, 'pending', ?, NOW())
                ");
                $stmt->execute([$user_id, $method_id, $amount, $reference, $description]);
                $success = 'Deposit claim submitted successfully for administrative validation.';
            } catch (Exception $e) {
                $errors[] = 'Database handling error: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'Failed to safely store the uploaded file.';
        }
    }
}

$pageTitle = 'Manual Deposit - ' . (AppConfig::get('APP_ALIAS') ?: 'BitW');
require_once __DIR__ . '/pages/header.php';
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="badge">Funding Gateway</p>
            <h1 class="text-4xl font-semibold tracking-tight mt-4">Offline Wire Settlement</h1>
            <p class="mt-2 text-sm text-slate-400">Select an available account method, perform your transfer, and upload your verification receipt.</p>
        </div>
        <a href="deposit.php" class="action-button" style="max-width: 220px;"><i class="bx bx-arrow-back"></i> Back</a>
    </div>

    <?php if (count($methods) === 0): ?>
        <div class="glass-card p-8 text-center border border-yellow-500/20">
            <i class="bx bx-error text-4xl text-yellow-500 mb-2"></i>
            <p class="text-slate-300">No payment collection accounts are active at this moment. Please contact support.</p>
        </div>
    <?php else: ?>
        <div class="grid gap-6 md:grid-cols-3">
            <!-- Dynamic Method Form Data Displayer -->
            <div class="glass-card p-6 md:col-span-1" style="border-left: 4px solid #3b82f6;">
                <h2 class="text-lg font-bold text-white mb-4">Selected Account</h2>
                <div class="space-y-4" id="account-details-box">
                    <div>
                        <span class="text-xs text-slate-500 uppercase block">Bank Engine</span>
                        <strong id="display-bank" class="text-white font-medium text-base">-</strong>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 uppercase block">Account Target</span>
                        <strong id="display-number" class="text-white font-mono text-xl tracking-wide select-all">-</strong>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 uppercase block">Beneficiary Name</span>
                        <strong id="display-name" class="text-white font-medium text-base">-</strong>
                    </div>
                </div>
            </div>

            <!-- Main Input Payload Dispatcher Form -->
            <div class="glass-card p-6 md:col-span-2">
                <?php if ($success): ?>
                    <div class="p-4 mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 text-emerald-400 text-sm"><?= htmlspecialchars($success) ?></div>
                <?php else: ?>
                    <?php if (!empty($errors)) echo '<div class="p-4 mb-4 rounded-xl border border-rose-500/20 bg-rose-500/10 text-rose-400 text-sm">'.implode('<br>', $errors).'</div>'; ?>
                    
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <div>
                            <label class="block text-sm text-slate-300 mb-2">Choose Deposit Route</label>
                            <select name="method_id" id="method-selector" class="w-full bg-slate-900 border border-white/10 rounded-xl p-3 text-white focus:outline-none" onchange="switchMethodDetails()">
                                <?php foreach($methods as $m): ?>
                                    <option value="<?= $m['id'] ?>" data-bank="<?= htmlspecialchars($m['bank_name']) ?>" data-number="<?= htmlspecialchars($m['account_number']) ?>" data-name="<?= htmlspecialchars($m['account_name']) ?>">
                                        <?= htmlspecialchars($m['bank_name']) ?> (<?= htmlspecialchars($m['account_number']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm text-slate-300 mb-2">Transferred Amount (NGN)</label>
                            <input type="number" step="0.01" name="amount" class="w-full bg-slate-900 border border-white/10 rounded-xl p-3 text-white focus:outline-none" placeholder="0.00" required>
                        </div>

                        <div>
                            <label class="block text-sm text-slate-300 mb-2">Upload Transfer Snapshot Receipt</label>
                            <div class="relative border border-dashed border-white/10 bg-slate-900 rounded-xl p-6 text-center cursor-pointer">
                                <input type="file" name="proof" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="document.getElementById('file-name').innerText = this.files[0].name" required>
                                <i class="bx bx-cloud-upload text-3xl text-slate-500 mb-1 block"></i>
                                <span id="file-name" class="text-xs text-slate-400">Click to attach asset image (Max 5MB)</span>
                            </div>
                        </div>

                        <button type="submit" class="action-button w-full justify-center py-3 bg-blue-600 hover:bg-blue-500 transition-all border-none">Submit Settlement Receipt</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function switchMethodDetails() {
    const selector = document.getElementById('method-selector');
    if(!selector) return;
    const opt = selector.options[selector.selectedIndex];
    if(!opt) return;
    document.getElementById('display-bank').innerText = opt.getAttribute('data-bank');
    document.getElementById('display-number').innerText = opt.getAttribute('data-number');
    document.getElementById('display-name').innerText = opt.getAttribute('data-name');
}
document.addEventListener("DOMContentLoaded", switchMethodDetails);
</script>
<?php require_once __DIR__ . '/pages/footer.php'; ?>