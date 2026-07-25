<?php
// public/admin/deposits.php
// Admin manual deposit approvals

require_once __DIR__ . '/includes/admin_init.php';
require_once __DIR__ . '/../../core/wallet.php';

$pageTitle = 'Pending Deposits - Admin';
$currentPage = 'deposits';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $deposit_id = $_POST['deposit_id'] ?? null;
    
    if ($action && $deposit_id) {
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND type = 'deposit' AND status = 'pending'");
        $stmt->execute([$deposit_id]);
        $deposit = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($deposit) {
            if ($action === 'approve') {
                $pdo->beginTransaction();
                try {
                    // Update transaction status
                    $updateStmt = $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE id = ?");
                    $updateStmt->execute([$deposit_id]);

                    // Credit wallet
                    creditWallet($deposit['user_id'], $deposit['amount'], 'deposit', 'Admin approved: ' . $deposit['reference']);

                    $pdo->commit();
                    $_SESSION['admin_message'] = ['type' => 'success', 'text' => 'Deposit approved and wallet credited.'];
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Error: ' . $e->getMessage()];
                }
            } elseif ($action === 'reject') {
                $updateStmt = $pdo->prepare("UPDATE transactions SET status = 'rejected' WHERE id = ?");
                $updateStmt->execute([$deposit_id]);
                $_SESSION['admin_message'] = ['type' => 'success', 'text' => 'Deposit rejected.'];
            }
        }
    }

    header("Location: deposits.php");
    exit;
}

// Get pending deposits
$stmt = $pdo->prepare("
    SELECT t.*, u.username, u.email
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    WHERE t.type = 'deposit' AND t.status IN ('pending', 'rejected')
    ORDER BY t.created_at DESC
");
$stmt->execute();
$deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="/assets/css/bitw.css" rel="stylesheet">
    <style>
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(15, 23, 42, 0.5);
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .admin-table th {
            background: rgba(30, 41, 59, 0.8);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #e2e8f0;
            border-bottom: 1px solid rgba(71, 85, 105, 0.3);
        }

        .admin-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(71, 85, 105, 0.2);
            color: #cbd5e1;
        }

        .admin-table tr:hover {
            background: rgba(30, 41, 59, 0.3);
        }

        .badge-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-pending {
            background: rgba(249, 115, 22, 0.1);
            color: #fb923c;
        }

        .badge-completed {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .badge-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.5rem 0.75rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .btn-approve {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .btn-approve:hover {
            background: rgba(34, 197, 94, 0.3);
        }

        .btn-reject {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .btn-reject:hover {
            background: rgba(239, 68, 68, 0.3);
        }

        .proof-link {
            color: #60a5fa;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .proof-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
    <?php require_once './includes/admin_header.php'; ?>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">Manual Deposits</h1>
                <p class="text-slate-400">Review and approve/reject user deposit requests</p>
            </div>
            <a href="index.php" class="action-button">
                <i class="bx bx-arrow-back"></i> Back to Dashboard
            </a>
        </div>

        <?php if (isset($_SESSION['admin_message'])): ?>
            <div class="glass-card p-4 mb-6 border-l-4 <?= $_SESSION['admin_message']['type'] === 'success' ? 'border-green-500 bg-green-500/10' : 'border-red-500 bg-red-500/10' ?>">
                <p class="<?= $_SESSION['admin_message']['type'] === 'success' ? 'text-green-400' : 'text-red-400' ?>">
                    <?= htmlspecialchars($_SESSION['admin_message']['text']) ?>
                </p>
            </div>
            <?php unset($_SESSION['admin_message']); ?>
        <?php endif; ?>

        <div class="glass-card overflow-x-auto">
            <?php if (count($deposits) > 0): ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Amount (₦)</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th>Proof</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deposits as $deposit): ?>
                            <tr>
                                <td>
                                    <div>
                                        <div class="font-semibold"><?= htmlspecialchars($deposit['username']) ?></div>
                                        <div class="text-sm text-slate-400"><?= htmlspecialchars($deposit['email']) ?></div>
                                    </div>
                                </td>
                                <td class="font-semibold"><?= number_format($deposit['amount'], 2) ?></td>
                                <td class="font-mono text-sm"><?= htmlspecialchars($deposit['reference']) ?></td>
                                <td>
                                    <span class="badge-status badge-<?= $deposit['status'] ?>">
                                        <?= ucfirst($deposit['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    // Extract proof file from description
                                    preg_match('/\[Proof: (.+?)\]/', $deposit['description'], $matches);
                                    if ($matches && $matches[1]):
                                        $proof_file = $matches[1];
                                    ?>
                                        <a href="/assets/deposit-proofs/<?= htmlspecialchars($proof_file) ?>" target="_blank" class="proof-link">
                                            <i class="bx bx-file"></i> View
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td class="text-sm text-slate-400">
                                    <?= date('M d, Y H:i', strtotime($deposit['created_at'])) ?>
                                </td>
                                <td>
                                    <?php if ($deposit['status'] === 'pending'): ?>
                                        <form method="POST" class="action-buttons" style="display: inline-flex;">
                                            <input type="hidden" name="deposit_id" value="<?= $deposit['id'] ?>">
                                            <button type="submit" name="action" value="approve" class="action-btn btn-approve">
                                                <i class="bx bx-check"></i> Approve
                                            </button>
                                            <button type="submit" name="action" value="reject" class="action-btn btn-reject" onclick="return confirm('Reject this deposit?')">
                                                <i class="bx bx-x"></i> Reject
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-sm">No actions</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="p-8 text-center">
                    <i class="bx bx-inbox text-4xl text-slate-600 mb-4 block"></i>
                    <p class="text-slate-400">No pending deposits</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php require_once './includes/admin_footer.php'; ?>
</body>
</html>
