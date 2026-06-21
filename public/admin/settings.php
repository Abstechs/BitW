<?php
require_once __DIR__ . '/includes/admin_init.php';

AppSettings::load();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paystackSecret = trim($_POST['paystack_secret'] ?? '');
    $paystackPublic = trim($_POST['paystack_public'] ?? '');
    $manualEnabled = isset($_POST['manual_enabled']) ? 1 : 0;
    $cryptoEnabled = isset($_POST['crypto_enabled']) ? 1 : 0;
    $defaultAccount = trim($_POST['default_account'] ?? '');
    $defaultBank = trim($_POST['default_bank'] ?? '');
    $defaultAccountName = trim($_POST['default_account_name'] ?? '');
    $defaultImage = trim($_POST['default_image'] ?? '/assets/images/default-plan.svg');

    if (!$defaultAccount) {
        $errors[] = 'Default account number is required.';
    }
    if (!$defaultBank) {
        $errors[] = 'Default bank is required.';
    }
    if (!$defaultAccountName) {
        $errors[] = 'Default account name is required.';
    }

    if (empty($errors)) {
        AppSettings::set('PAYSTACK_SECRET', $paystackSecret);
        AppSettings::set('PAYSTACK_PUBLIC', $paystackPublic);
        AppSettings::set('MANUAL_DEPOSIT_ENABLED', (bool)$manualEnabled);
        AppSettings::set('CRYPTO_DEPOSIT_ENABLED', (bool)$cryptoEnabled);
        AppSettings::set('PAYSTACK_DEFAULT_ACCOUNT', $defaultAccount);
        AppSettings::set('PAYSTACK_DEFAULT_BANK', $defaultBank);
        AppSettings::set('PAYSTACK_DEFAULT_ACCOUNT_NAME', $defaultAccountName);
        AppSettings::set('DEFAULT_PLAN_IMAGE', $defaultImage);
        $success = 'Settings updated successfully.';
    }
}

$settings = AppSettings::all();

require_once __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-top">
    <div>
        <span class="badge">Site Settings</span>
        <h2 class="text-3xl font-bold mt-4">Manage application and payment settings</h2>
        <p class="text-slate-400 mt-2">Update Paystack credentials, manual payment account details, and default plan images here.</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="admin-card mb-6">
        <p class="text-teal-200"><?= htmlspecialchars($success) ?></p>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="admin-card mb-6">
        <?php foreach ($errors as $error): ?>
            <p class="text-rose-300">• <?= htmlspecialchars($error) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="admin-card">
    <form method="POST">
        <div class="grid gap-5 md:grid-cols-2 mb-6">
            <div>
                <label class="form-label">Paystack Secret Key</label>
                <input name="paystack_secret" class="form-field" value="<?= htmlspecialchars($settings['PAYSTACK_SECRET'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Paystack Public Key</label>
                <input name="paystack_public" class="form-field" value="<?= htmlspecialchars($settings['PAYSTACK_PUBLIC'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Manual payment enabled</label>
                <input type="checkbox" name="manual_enabled" <?= $settings['MANUAL_DEPOSIT_ENABLED'] ? 'checked' : '' ?>>
            </div>
            <div>
                <label class="form-label">Crypto payment enabled</label>
                <input type="checkbox" name="crypto_enabled" <?= $settings['CRYPTO_DEPOSIT_ENABLED'] ? 'checked' : '' ?>>
            </div>
            <div>
                <label class="form-label">Default account number</label>
                <input name="default_account" class="form-field" value="<?= htmlspecialchars($settings['PAYSTACK_DEFAULT_ACCOUNT'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Default bank name</label>
                <input name="default_bank" class="form-field" value="<?= htmlspecialchars($settings['PAYSTACK_DEFAULT_BANK'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Default account name</label>
                <input name="default_account_name" class="form-field" value="<?= htmlspecialchars($settings['PAYSTACK_DEFAULT_ACCOUNT_NAME'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Default plan image URL</label>
                <input name="default_image" class="form-field" value="<?= htmlspecialchars($settings['DEFAULT_PLAN_IMAGE'] ?? '/assets/images/default-plan.svg') ?>">
            </div>
        </div>

        <button class="btn-primary" type="submit">Save Settings</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php';
