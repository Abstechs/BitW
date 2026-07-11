<?php
// admin/settings.php
require_once __DIR__ . '/includes/admin_init.php';

AppSettings::load();

$errors = [];
$success = '';
$settingsFilePath = __DIR__ . '/../../config/settings.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. AppSettings Properties
    $paystackSecret = trim($_POST['paystack_secret'] ?? '');
    $paystackPublic = trim($_POST['paystack_public'] ?? '');
    $manualEnabled = isset($_POST['manual_enabled']) ? 1 : 0;
    $cryptoEnabled = isset($_POST['crypto_enabled']) ? 1 : 0;
    $defaultAccount = trim($_POST['default_account'] ?? '');
    $defaultBank = trim($_POST['default_bank'] ?? '');
    $defaultAccountName = trim($_POST['default_account_name'] ?? '');
    $defaultImage = trim($_POST['default_image'] ?? '/assets/images/default-plan.svg');
    $minWithdrawal = floatval($_POST['min_withdrawal'] ?? 0);

    // 2. AppConfig UI properties
    $appName = trim($_POST['app_name'] ?? 'BitWealthBuilder');
    $appAlias = trim($_POST['app_alias'] ?? 'BitW');
    
    // Validate core payout properties
    if (!$defaultAccount) {
        $errors[] = 'Default account number is required.';
    }
    if (!$defaultBank) {
        $errors[] = 'Default bank name is required.';
    }
    if (!$defaultAccountName) {
        $errors[] = 'Default account name is required.';
    }
    if ($minWithdrawal <= 0) {
        $errors[] = 'Minimum withdrawal amount must be greater than zero.';
    }

    if (empty($errors)) {
        // Persist AppSettings parameters into the database framework
        AppSettings::set('PAYSTACK_SECRET', $paystackSecret);
        AppSettings::set('PAYSTACK_PUBLIC', $paystackPublic);
        AppSettings::set('MANUAL_DEPOSIT_ENABLED', (bool)$manualEnabled);
        AppSettings::set('CRYPTO_DEPOSIT_ENABLED', (bool)$cryptoEnabled);
        AppSettings::set('PAYSTACK_DEFAULT_ACCOUNT', $defaultAccount);
        AppSettings::set('PAYSTACK_DEFAULT_BANK', $defaultBank);
        AppSettings::set('PAYSTACK_DEFAULT_ACCOUNT_NAME', $defaultAccountName);
        AppSettings::set('DEFAULT_PLAN_IMAGE', $defaultImage);
        AppSettings::set('MIN_WITHDRAWAL_AMOUNT', $minWithdrawal);

        // Compile dashboard interface layout structural changes
        $newDashboardConfig = [
            'BRAND' => trim($_POST['brand'] ?? $appAlias),
            'WELCOME_PREFIX' => trim($_POST['welcome_prefix'] ?? 'Welcome back'),
            'RANK_LABEL' => trim($_POST['rank_label'] ?? 'Current Rank'),
            'RANK_LEVEL_PREFIX' => trim($_POST['rank_level_prefix'] ?? 'Level'),
            'WALLET_BALANCE_LABEL' => trim($_POST['wallet_label'] ?? 'Wallet Balance'),
            'ACTIVE_MINING_LABEL' => trim($_POST['active_mining_label'] ?? 'Active Mining'),
            'REFERRAL_EARNINGS_LABEL' => trim($_POST['referral_label'] ?? 'Referral Earnings'),
            'ACTIVE_MINING_PLANS_LABEL' => trim($_POST['plans_label'] ?? 'Active Mining Plans'),
            'RECENT_ACTIVITY_LABEL' => trim($_POST['activity_label'] ?? 'Recent Activity'),
            'NO_ACTIVE_MINING_TEXT' => trim($_POST['no_active_mining'] ?? 'You have no active mining plans.'),
            'BROWSE_PLANS_TEXT' => trim($_POST['browse_plans_text'] ?? 'Browse Plans'),
            'NO_NOTIFICATIONS_TEXT' => trim($_POST['no_notifications'] ?? 'No notifications yet.'),
            'VIEW_REFERRALS_TEXT' => trim($_POST['view_referrals_text'] ?? 'View Referrals →'),
            'PLANS_RUNNING_TEXT' => trim($_POST['plans_running_text'] ?? 'Plans Running'),
            'DEPOSIT_TEXT' => trim($_POST['deposit_text'] ?? 'Deposit'),
            'WITHDRAW_TEXT' => trim($_POST['withdraw_text'] ?? 'Withdraw'),
            'LOGOUT_TEXT' => trim($_POST['logout_text'] ?? 'Logout'),
            'CLAIM_BUTTON_TEXT' => trim($_POST['claim_text'] ?? 'Claim Today'),
            'CLAIM_CONFIRM_MESSAGE' => trim($_POST['claim_confirm_message'] ?? 'Claim mining earnings for this session?'),
            'CLAIM_SUCCESS_MESSAGE' => trim($_POST['claim_success_message'] ?? 'Claim successful!'),
            'CLAIM_FAILED_MESSAGE' => trim($_POST['claim_failed_message'] ?? 'Claim failed'),
            'DAILY_EARNING_LABEL' => trim($_POST['daily_earning_label'] ?? 'Daily'),
            'CURRENCY_SYMBOL' => trim($_POST['currency_symbol'] ?? '₦'),
            'NAV_DASHBOARD' => trim($_POST['nav_dashboard'] ?? 'Dashboard'),
            'NAV_MINING' => trim($_POST['nav_mining'] ?? 'Mining'),
            'NAV_REFERRALS' => trim($_POST['nav_referrals'] ?? 'Referrals'),
            'NAV_TRANSACTIONS' => trim($_POST['nav_transactions'] ?? 'Transactions'),
            'NAV_WITHDRAW' => trim($_POST['nav_withdraw'] ?? 'Withdraw'),
            'NAV_SETTINGS' => trim($_POST['nav_settings'] ?? 'Settings'),
            'TITLE_PREFIX' => trim($_POST['title_prefix'] ?? 'Dashboard')
        ];

        // Safely rewrite configuration array straight back to config file
        if (file_exists($settingsFilePath)) {
            $currentFileSettings = include $settingsFilePath;
            if (!is_array($currentFileSettings)) { $currentFileSettings = []; }
            
            // Merge file parameters
            $updatedFileArray = array_merge($currentFileSettings, [
                'APP_NAME' => $appName,
                'APP_ALIAS' => $appAlias,
                'DASHBOARD' => $newDashboardConfig
            ]);

            $fileContent = "<?php\n// config/settings.php\nreturn " . var_export($updatedFileArray, true) . ";\n";
            file_put_contents($settingsFilePath, $fileContent);
        }

        $success = 'All configurations updated successfully.';
    }
}

// Fetch states for view distribution
$settings = AppSettings::all();
$appName = AppConfig::get('APP_NAME') ?: 'BitWealthBuilder';
$appAlias = AppConfig::get('APP_ALIAS') ?: 'BitW';
$dashboardConfig = AppConfig::get('DASHBOARD') ?: [];

require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-top">
    <div>
        <span class="badge">Master Settings</span>
        <h2 class="text-3xl font-bold mt-4">Application Parameters Control</h2>
        <p class="text-slate-400 mt-2">Dynamically control active infrastructure keys, default payouts, and dashboard contextual client layout labels from one place.</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="admin-card mb-6" style="border-left: 4px solid #10b981;">
        <p class="text-emerald-400 font-medium"><?= htmlspecialchars($success) ?></p>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="admin-card mb-6" style="border-left: 4px solid #f43f5e;">
        <?php foreach ($errors as $error): ?>
            <p class="text-rose-400 font-medium">• <?= htmlspecialchars($error) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="POST" class="space-y-8">
    <!-- SECTION 1: SYSTEM INFRASTRUCTURE & PAYMENTS -->
    <div class="admin-card">
        <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i class="fas fa-server text-blue-400"></i> Application & Gateway configurations
        </h3>
        
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label class="form-label">Application Core Name</label>
                <input name="app_name" class="form-field" value="<?= htmlspecialchars($appName) ?>">
            </div>
            <div>
                <label class="form-label">Application Menu Short Alias</label>
                <input name="app_alias" class="form-field" value="<?= htmlspecialchars($appAlias) ?>">
            </div>
            <div>
                <label class="form-label">Paystack Secret Key</label>
                <input type="password" name="paystack_secret" class="form-field" value="<?= htmlspecialchars($settings['PAYSTACK_SECRET'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Paystack Public Key</label>
                <input name="paystack_public" class="form-field" value="<?= htmlspecialchars($settings['PAYSTACK_PUBLIC'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Default Account Number</label>
                <input name="default_account" class="form-field" value="<?= htmlspecialchars($settings['PAYSTACK_DEFAULT_ACCOUNT'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Default Bank Name</label>
                <input name="default_bank" class="form-field" value="<?= htmlspecialchars($settings['PAYSTACK_DEFAULT_BANK'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Default Payout Account Name</label>
                <input name="default_account_name" class="form-field" value="<?= htmlspecialchars($settings['PAYSTACK_DEFAULT_ACCOUNT_NAME'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Default Plan Fallback Image URL</label>
                <input name="default_image" class="form-field" value="<?= htmlspecialchars($settings['DEFAULT_PLAN_IMAGE'] ?? '/assets/images/default-plan.svg') ?>">
            </div>
            <div>
                <label class="form-label text-yellow-400">Minimum Global Withdrawal Threshold (₦)</label>
                <input type="number" step="0.01" name="min_withdrawal" class="form-field border-yellow-500/30 focus:border-yellow-500" value="<?= htmlspecialchars($settings['MIN_WITHDRAWAL_AMOUNT'] ?? '2000') ?>">
            </div>
            <div class="flex items-center gap-8 pt-4">
                <label class="flex items-center gap-2 text-sm text-slate-300 select-none cursor-pointer">
                    <input type="checkbox" name="manual_enabled" <?= (!isset($settings['MANUAL_DEPOSIT_ENABLED']) || $settings['MANUAL_DEPOSIT_ENABLED']) ? 'checked' : '' ?> class="rounded bg-black border-gray-700 text-blue-500 focus:ring-0"> 
                    Enable Manual Processing
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-300 select-none cursor-pointer">
                    <input type="checkbox" name="crypto_enabled" <?= (!empty($settings['CRYPTO_DEPOSIT_ENABLED'])) ? 'checked' : '' ?> class="rounded bg-black border-gray-700 text-blue-500 focus:ring-0"> 
                    Enable Crypto Gateways
                </label>
            </div>
        </div>
    </div>

    <!-- SECTION 2: CLIENT USER DASHBOARD TEXT & TRANSLATIONS -->
    <div class="admin-card">
        <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i class="fas fa-sliders-h text-purple-400"></i> Dashboard Client Display Language Strings
        </h3>
        
        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="form-label">Logo Brand Label</label>
                <input name="brand" class="form-field" value="<?= htmlspecialchars($dashboardConfig['BRAND'] ?? $appAlias) ?>">
            </div>
            <div>
                <label class="form-label">Welcome Prefix Text</label>
                <input name="welcome_prefix" class="form-field" value="<?= htmlspecialchars($dashboardConfig['WELCOME_PREFIX'] ?? 'Welcome back') ?>">
            </div>
            <div>
                <label class="form-label">Title Tab Window Prefix</label>
                <input name="title_prefix" class="form-field" value="<?= htmlspecialchars($dashboardConfig['TITLE_PREFIX'] ?? 'Dashboard') ?>">
            </div>
            <div>
                <label class="form-label">Rank Section Header</label>
                <input name="rank_label" class="form-field" value="<?= htmlspecialchars($dashboardConfig['RANK_LABEL'] ?? 'Current Rank') ?>">
            </div>
            <div>
                <label class="form-label">Fallback Rank Level Prefix</label>
                <input name="rank_level_prefix" class="form-field" value="<?= htmlspecialchars($dashboardConfig['RANK_LEVEL_PREFIX'] ?? 'Level') ?>">
            </div>
            <div>
                <label class="form-label">Wallet Balance Label</label>
                <input name="wallet_label" class="form-field" value="<?= htmlspecialchars($dashboardConfig['WALLET_BALANCE_LABEL'] ?? 'Wallet Balance') ?>">
            </div>
            <div>
                <label class="form-label">Active Counter Label</label>
                <input name="active_mining_label" class="form-field" value="<?= htmlspecialchars($dashboardConfig['ACTIVE_MINING_LABEL'] ?? 'Active Mining') ?>">
            </div>
            <div>
                <label class="form-label">Referrals Earning Header</label>
                <input name="referral_label" class="form-field" value="<?= htmlspecialchars($dashboardConfig['REFERRAL_EARNINGS_LABEL'] ?? 'Referral Earnings') ?>">
            </div>
            <div>
                <label class="form-label">Mining Layout Panel Label</label>
                <input name="plans_label" class="form-field" value="<?= htmlspecialchars($dashboardConfig['ACTIVE_MINING_PLANS_LABEL'] ?? 'Active Mining Plans') ?>">
            </div>
            <div>
                <label class="form-label">Recent Activity Title</label>
                <input name="activity_label" class="form-field" value="<?= htmlspecialchars($dashboardConfig['RECENT_ACTIVITY_LABEL'] ?? 'Recent Activity') ?>">
            </div>
            <div>
                <label class="form-label">Plans Track Suffix Label</label>
                <input name="plans_running_text" class="form-field" value="<?= htmlspecialchars($dashboardConfig['PLANS_RUNNING_TEXT'] ?? 'Plans Running') ?>">
            </div>
            <div>
                <label class="form-label">Daily Yield Track Text</label>
                <input name="daily_earning_label" class="form-field" value="<?= htmlspecialchars($dashboardConfig['DAILY_EARNING_LABEL'] ?? 'Daily') ?>">
            </div>
            <div>
                <label class="form-label">System Currency String Symbol</label>
                <input name="currency_symbol" class="form-field" value="<?= htmlspecialchars($dashboardConfig['CURRENCY_SYMBOL'] ?? '₦') ?>">
            </div>
            <div>
                <label class="form-label">Browse Content Action Link</label>
                <input name="browse_plans_text" class="form-field" value="<?= htmlspecialchars($dashboardConfig['BROWSE_PLANS_TEXT'] ?? 'Browse Plans') ?>">
            </div>
            <div>
                <label class="form-label">Referrals Link Anchor Suffix</label>
                <input name="view_referrals_text" class="form-field" value="<?= htmlspecialchars($dashboardConfig['VIEW_REFERRALS_TEXT'] ?? 'View Referrals →') ?>">
            </div>
            <div>
                <label class="form-label">Deposit Action Button Text</label>
                <input name="deposit_text" class="form-field" value="<?= htmlspecialchars($dashboardConfig['DEPOSIT_TEXT'] ?? 'Deposit') ?>">
            </div>
            <div>
                <label class="form-label">Withdraw Action Button Text</label>
                <input name="withdraw_text" class="form-field" value="<?= htmlspecialchars($dashboardConfig['WITHDRAW_TEXT'] ?? 'Withdraw') ?>">
            </div>
            <div>
                <label class="form-label">Logout Trigger Link Text</label>
                <input name="logout_text" class="form-field" value="<?= htmlspecialchars($dashboardConfig['LOGOUT_TEXT'] ?? 'Logout') ?>">
            </div>
            <div>
                <label class="form-label text-emerald-400">Mining Claim Earnings Action Text</label>
                <input name="claim_text" class="form-field border-emerald-500/20 focus:border-emerald-500" value="<?= htmlspecialchars($dashboardConfig['CLAIM_BUTTON_TEXT'] ?? 'Claim Today') ?>">
            </div>
            <div>
                <label class="form-label">Claim Success Notice String</label>
                <input name="claim_success_message" class="form-field" value="<?= htmlspecialchars($dashboardConfig['CLAIM_SUCCESS_MESSAGE'] ?? 'Claim successful!') ?>">
            </div>
            <div>
                <label class="form-label">Claim Failure Notice String</label>
                <input name="claim_failed_message" class="form-field" value="<?= htmlspecialchars($dashboardConfig['CLAIM_FAILED_MESSAGE'] ?? 'Claim failed') ?>">
            </div>
        </div>

        <!-- Navigation Links Configuration Fields -->
        <h4 class="text-sm font-bold text-slate-400 uppercase tracking-wider mt-8 mb-4 border-b border-white/5 pb-2">Client Navigation Links Layout Texts</h4>
        <div class="grid gap-5 md:grid-cols-3 lg:grid-cols-6">
            <div>
                <label class="form-label text-xs">Nav: Dashboard</label>
                <input name="nav_dashboard" class="form-field text-xs p-2" value="<?= htmlspecialchars($dashboardConfig['NAV_DASHBOARD'] ?? 'Dashboard') ?>">
            </div>
            <div>
                <label class="form-label text-xs">Nav: Mining</label>
                <input name="nav_mining" class="form-field text-xs p-2" value="<?= htmlspecialchars($dashboardConfig['NAV_MINING'] ?? 'Mining') ?>">
            </div>
            <div>
                <label class="form-label text-xs">Nav: Referrals</label>
                <input name="nav_referrals" class="form-field text-xs p-2" value="<?= htmlspecialchars($dashboardConfig['NAV_REFERRALS'] ?? 'Referrals') ?>">
            </div>
            <div>
                <label class="form-label text-xs">Nav: Ledger History</label>
                <input name="nav_transactions" class="form-field text-xs p-2" value="<?= htmlspecialchars($dashboardConfig['NAV_TRANSACTIONS'] ?? 'Transactions') ?>">
            </div>
            <div>
                <label class="form-label text-xs">Nav: Cashouts</label>
                <input name="nav_withdraw" class="form-field text-xs p-2" value="<?= htmlspecialchars($dashboardConfig['NAV_WITHDRAW'] ?? 'Withdraw') ?>">
            </div>
            <div>
                <label class="form-label text-xs">Nav: Personal Settings</label>
                <input name="nav_settings" class="form-field text-xs p-2" value="<?= htmlspecialchars($dashboardConfig['NAV_SETTINGS'] ?? 'Settings') ?>">
            </div>
        </div>

        <!-- Alerts Configuration Fields -->
        <h4 class="text-sm font-bold text-slate-400 uppercase tracking-wider mt-8 mb-4 border-b border-white/5 pb-2">Empty State Messages & Alerts</h4>
        <div class="space-y-4">
            <div>
                <label class="form-label">No Active Holding Notification Warning Text</label>
                <input name="no_active_mining" class="form-field" value="<?= htmlspecialchars($dashboardConfig['NO_ACTIVE_MINING_TEXT'] ?? 'You have no active mining plans.') ?>">
            </div>
            <div>
                <label class="form-label">No Notifications Queue Fallback Text</label>
                <input name="no_notifications" class="form-field" value="<?= htmlspecialchars($dashboardConfig['NO_NOTIFICATIONS_TEXT'] ?? 'No notifications yet.') ?>">
            </div>
            <div>
                <label class="form-label text-yellow-400">Interactive Modal Yield Claim Dialog Warning Text Prompt</label>
                <textarea name="claim_confirm_message" class="form-field h-20 border-yellow-500/20 focus:border-yellow-500" style="font-family: inherit; resize: vertical;"><?= htmlspecialchars($dashboardConfig['CLAIM_CONFIRM_MESSAGE'] ?? 'Claim mining earnings for this session?') ?></textarea>
            </div>
        </div>
    </div>

    <!-- MAIN CONTROL DISPATCH ACTION BAR -->
    <div class="flex items-center justify-end gap-4 bg-slate-900 border border-white/5 p-4 rounded-3xl">
        <p class="text-xs text-slate-500 italic hidden md:block">Saving values will overwrite parameters instantly across active user sessions.</p>
        <button type="submit" class="btn-primary" style="margin: 0; min-width: 200px; background: linear-gradient(135deg, #3b82f6, #7c3aed);">
            Save All Configurations
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>