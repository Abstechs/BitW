<?php
require_once __DIR__ . '/includes/admin_init.php';

$search = trim($_GET['search'] ?? '');
$query = "SELECT t.*, u.username, u.email FROM transactions t LEFT JOIN users u ON u.id = t.user_id";
$params = [];
if ($search !== '') {
    $query .= " WHERE u.username LIKE ? OR u.email LIKE ? OR t.reference LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$query .= " ORDER BY t.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

require_once __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-top">
    <div>
        <span class="badge">Transactions</span>
        <h2 class="text-3xl font-bold mt-4">Review deposit and withdrawal activity</h2>
        <p class="text-slate-400 mt-2">Track payment status, gateway, and user history.</p>
    </div>
    <form method="GET" class="flex gap-3">
        <input name="search" class="form-field" placeholder="Search by user or reference" value="<?= htmlspecialchars($search) ?>">
        <button class="btn-secondary" type="submit">Search</button>
    </form>
</div>

<div class="admin-card">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Gateway</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $tx): ?>
                <tr>
                    <td><?= htmlspecialchars($tx['id']) ?></td>
                    <td><?= htmlspecialchars($tx['username'] ?? $tx['email'] ?? 'Unknown') ?></td>
                    <td><?= htmlspecialchars($tx['type']) ?></td>
                    <td>₦<?= number_format($tx['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($tx['gateway'] ?? 'Manual') ?></td>
                    <td><?= htmlspecialchars($tx['status']) ?></td>
                    <td><?= htmlspecialchars($tx['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php';
