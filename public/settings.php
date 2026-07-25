<?php
// public/settings.php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/wallet.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user = getUser($_SESSION['user_id']); 
$wallet = getWallet($user['id']);
$brand = AppConfig::get('APP_ALIAS') ?: 'BitW';

$success = "";
$error = "";

// Form Post Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $pdo;
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $phone = trim($_POST['phone'] ?? '');
        if (!empty($phone)) {
            $stmt = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
            $stmt->execute([$phone, $user['id']]);
            $success = "Profile metrics updated cleanly.";
            $user = getUser($user['id']); // Refresh data variables
        }
    } 
    
    else if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        
        if (password_verify($current, $user['password'])) {
            if (strlen($new) >= 6) {
                updateUserPassword($user['id'], $new);
                $success = "Password reset changed successfully.";
            } else {
                $error = "New password must contain at least 6 characters.";
            }
        } else {
            $error = "Current configuration password invalid.";
        }
    } 
    
    else if ($action === 'save_bank') {
        $bank_name = trim($_POST['bank_name'] ?? '');
        $bank_code = trim($_POST['bank_code'] ?? '');
        $account_number = trim($_POST['account_number'] ?? '');
        $account_name = trim($_POST['account_name'] ?? '');

        if (!empty($bank_name) && !empty($account_number) && !empty($account_name)) {
            $stmt = $pdo->prepare("UPDATE wallets SET bank_name = ?, bank_code = ?, account_number = ?, account_name = ? WHERE user_id = ?");
            $stmt->execute([$bank_name, $bank_code, $account_number, $account_name, $user['id']]);
            $success = "Payout banking vectors stored securely.";
            $wallet = getWallet($user['id']); // Refresh data variables
        } else {
            $error = "Please resolve the account name before processing changes.";
        }
    }
}

// Fetch general banks list from Paystack API to build dropdown selector
$settings = include __DIR__ . '/../config/settings.php';
$paystack_secret = $settings['PAYSTACK_SECRET'] ?? '';
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.paystack.co/bank?currency=NGN",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["authorization: Bearer " . $paystack_secret],
]);
$bankResponse = json_decode(curl_exec($curl), true);
curl_close($curl);
$banks = $bankResponse['data'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - <?= htmlspecialchars($brand) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>.glass { background: rgba(255,255,255,0.05); backdrop-filter: blur(12px); }</style>
</head>
<body class="bg-gray-950 text-white min-h-screen flex flex-col">

    <!-- DESKTOP SIDEBAR -->
    <div id="sidebar" class="w-72 bg-black h-screen fixed top-0 left-0 p-6 z-50 md:block hidden">
        <div class="flex items-center gap-3 mb-10"><h1 class="text-3xl font-bold text-yellow-400"><?= htmlspecialchars($brand) ?></h1></div>
        <nav class="space-y-2">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-400 hover:bg-gray-800 rounded-xl hover:text-white"><i class="fas fa-home"></i> Dashboard</a>
            <a href="settings.php" class="flex items-center gap-3 px-4 py-3 bg-gray-800 rounded-xl text-white"><i class="fas fa-cog"></i> Settings Configuration</a>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-8 md:ml-72">
        <h1 class="text-3xl font-bold mb-6">Profile Settings Center</h1>

        <?php if ($success): ?><div class="p-4 mb-6 bg-green-900/40 text-green-400 border border-green-800 rounded-2xl text-sm"><?= $success ?></div><?php endif; ?>
        <?php if ($error): ?><div class="p-4 mb-6 bg-red-900/40 text-red-400 border border-red-800 rounded-2xl text-sm"><?= $error ?></div><?php endif; ?>

        <div class="grid gap-8 grid-cols-1 lg:grid-cols-2">
            <!-- PROFILE AND PASSWORD METRICS -->
            <div class="space-y-6">
                <!-- Profile Block -->
                <form method="POST" class="glass border border-gray-800 rounded-3xl p-6 space-y-4">
                    <input type="hidden" name="action" value="update_profile">
                    <h2 class="text-xl font-semibold text-yellow-400 mb-2"><i class="fas fa-user-circle mr-2"></i>General Details</h2>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Email (Immutable Target)</label>
                        <input type="text" value="<?= htmlspecialchars($user['email']) ?>" class="w-full bg-gray-900 border border-gray-800 p-3 rounded-xl opacity-60 cursor-not-allowed text-sm" disabled>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Mobile Contact Phone Number</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" class="w-full bg-gray-900 border border-gray-800 p-3 rounded-xl text-sm focus:outline-none focus:border-yellow-500">
                    </div>
                    <button type="submit" class="px-5 py-2.5 bg-gray-800 hover:bg-gray-700 text-sm font-semibold rounded-xl">Save Info Changes</button>
                </form>

                <!-- Password Block -->
                <form method="POST" class="glass border border-gray-800 rounded-3xl p-6 space-y-4">
                    <input type="hidden" name="action" value="change_password">
                    <h2 class="text-xl font-semibold text-red-400 mb-2"><i class="fas fa-shield-alt mr-2"></i>Security Shield</h2>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Current Active Password</label>
                        <input type="password" name="current_password" class="w-full bg-gray-900 border border-gray-800 p-3 rounded-xl text-sm focus:outline-none focus:border-red-500" required>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">New Selection Password</label>
                        <input type="password" name="new_password" class="w-full bg-gray-900 border border-gray-800 p-3 rounded-xl text-sm focus:outline-none focus:border-red-500" required>
                    </div>
                    <button type="submit" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-sm font-semibold rounded-xl">Override System Password</button>
                </form>
            </div>

            <!-- DYNAMIC PAYSTACK BANK ACCOUNT LINKER -->
            <form method="POST" id="bankForm" class="glass border border-gray-800 rounded-3xl p-6 space-y-4 h-fit">
                <input type="hidden" name="action" value="save_bank">
                <input type="hidden" name="bank_name" id="hidden_bank_name" value="<?= htmlspecialchars($wallet['bank_name'] ?? '') ?>">
                <h2 class="text-xl font-semibold text-green-400 mb-2"><i class="fas fa-university mr-2"></i>Settlement Account Setup</h2>
                
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Select Bank Corporation</label>
                    <select name="bank_code" id="bank_code" class="w-full bg-gray-900 border border-gray-800 p-3 rounded-xl text-sm focus:outline-none focus:border-green-500" onchange="resetValidation()">
                        <option value="">-- Select Bank Target --</option>
                        <?php foreach($banks as $b): ?>
                            <option value="<?= $b['code'] ?>" <?= ($wallet['bank_code'] ?? '') === $b['code'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-400 mb-1">NUBAN Account Number (10 Digits)</label>
                    <input type="text" name="account_number" id="account_number" maxlength="10" value="<?= htmlspecialchars($wallet['account_number'] ?? '') ?>" class="w-full bg-gray-900 border border-gray-800 p-3 rounded-xl text-sm focus:outline-none focus:border-green-500" oninput="resetValidation()">
                </div>

                <div class="pt-2">
                    <button type="button" onclick="verifyBankAccount()" id="verifyBtn" class="w-full py-3 bg-cyan-600 hover:bg-cyan-700 text-sm font-semibold rounded-xl transition">
                        <i class="fas fa-sync-alt mr-2" id="spinner"></i> Verify Account Name
                    </button>
                </div>

                <div id="resolutionWrapper" class="bg-black/40 border border-gray-800 p-4 rounded-xl <?= empty($wallet['account_name']) ? 'hidden' : '' ?>">
                    <label class="block text-[10px] tracking-wider uppercase text-gray-500 mb-0.5">Verified Legal Recipient Name</label>
                    <input type="text" name="account_name" id="account_name" value="<?= htmlspecialchars($wallet['account_name'] ?? '') ?>" class="bg-transparent text-lg font-bold text-green-400 focus:outline-none w-full" readonly>
                </div>

                <button type="submit" id="saveBankBtn" class="w-full py-3 bg-green-600 hover:bg-green-700 text-sm font-semibold rounded-xl transition <?= empty($wallet['account_name']) ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= empty($wallet['account_name']) ? 'disabled' : '' ?>>
                    Save Bank Metrics to Profile
                </button>
            </form>
        </div>
    </main>

    <script>
        function resetValidation() {
            document.getElementById('resolutionWrapper').classList.add('hidden');
            document.getElementById('account_name').value = '';
            const btn = document.getElementById('saveBankBtn');
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            btn.disabled = true;
        }

        function verifyBankAccount() {
            const num = document.getElementById('account_number').value;
            const selectEl = document.getElementById('bank_code');
            const code = selectEl.value;
            const bankName = selectEl.options[selectEl.selectedIndex].text;
            
            if(num.length !== 10 || !code) {
                alert('Provide a valid bank selection and complete 10-digit NUBAN number entries.');
                return;
            }

            document.getElementById('spinner').classList.add('fa-spin');
            
            fetch('api/verify_bank.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ account_number: num, bank_code: code })
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('spinner').classList.remove('fa-spin');
                if(data.success) {
                    document.getElementById('hidden_bank_name').value = bankName;
                    document.getElementById('account_name').value = data.account_name;
                    document.getElementById('resolutionWrapper').classList.remove('hidden');
                    
                    const btn = document.getElementById('saveBankBtn');
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    btn.disabled = false;
                } else {
                    alert(data.message);
                }
            })
            .catch(() => {
                document.getElementById('spinner').classList.remove('fa-spin');
                alert('Fatal interface exception trying to communicate with routing gateway.');
            });
        }
    </script>
</body>
</html>