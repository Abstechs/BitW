<?php
session_start();
// require_once "../core/session.php";
require_once "../core/config.php";
require_once "../core/auth.php";
require_once "../core/wallet.php";
require_once "../core/plans.php";
require_once "../core/mining.php";

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

//$user_id = Session::get('user_id');
$user_id = $_SESSION['user_id'] ?? null;
$user = getUser($user_id);
$wallet = getWallet($user_id);
$plans = getActivePlans();
$transactions = getTransactions($user_id);
$activeMinings = getActiveMinings($user_id);

$messages = [];
$minWithdrawal = AppConfig::get('MIN_WITHDRAWAL') ?: 1000;
$settings = include __DIR__ . '/../config/settings.php';

// Display payment success/error messages from query params
if (isset($_GET['success'])) {
    $messages[] = ['type' => 'success', 'text' => htmlspecialchars($_GET['success'])];
}
if (isset($_GET['error'])) {
    $messages[] = ['type' => 'error', 'text' => htmlspecialchars($_GET['error'])];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dashboard_action'])) {
    $action = $_POST['dashboard_action'];
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

    if ($amount <= 0) {
        $messages[] = ['type' => 'error', 'text' => 'Enter a valid amount to continue.'];
    } elseif ($action === 'withdraw') {
        if ($amount < $minWithdrawal) {
            $messages[] = ['type' => 'error', 'text' => 'Withdrawals must be at least ₦'.number_format($minWithdrawal, 2).'.'];
        } elseif (!debitWallet($user_id, $amount, 'withdrawal', 'Dashboard withdrawal')) {
            $messages[] = ['type' => 'error', 'text' => 'Insufficient balance for withdrawal.'];
        } else {
            $wallet = getWallet($user_id);
            $transactions = getTransactions($user_id);
            $messages[] = ['type' => 'success', 'text' => 'Withdrawal request completed successfully.'];
        }
    }
}

$pageTitle = 'Dashboard - ' . AppConfig::get('APP_ALIAS');
require_once "./pages/header.php";
?>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between mb-8">
        <div>
            <p class="badge">Welcome back</p>
            <h1 class="text-4xl font-semibold tracking-tight mt-4"><?= htmlspecialchars($user['username']) ?>’s dashboard</h1>
            <p class="mt-2 text-sm text-slate-400">Manage stones, wallet activity, orders, and transactions from one polished control center.</p>
        </div>
        <a href="logout.php" class="action-button" style="max-width: 220px;"><i class="bx bx-log-out-circle"></i> Logout</a>
    </div>

    <?php require_once "./pages/dashboard/messages.php"; ?>

    <div class="dashboard-grid">
        <main class="dashboard-main">
            <?php require_once "./pages/dashboard/wallet-summary.php"; ?>
            <?php require_once "./pages/dashboard/stones.php"; ?>
            <?php require_once "./pages/dashboard/orders.php"; ?>
        </main>

        <aside class="dashboard-sidebar">
            <?php require_once "./pages/dashboard/wallet-actions.php"; ?>
            <?php require_once "./pages/dashboard/transactions.php"; ?>
        </aside>
    </div>

<?php require_once "./pages/footer.php"; ?>