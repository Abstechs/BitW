<?php
require_once __DIR__ . '/includes/admin_init.php';

$search = trim($_GET['search'] ?? '');
$query = "SELECT u.*, w.balance FROM users u LEFT JOIN wallets w ON w.user_id = u.id";
$params = [];
if ($search !== '') {
    $query .= " WHERE u.username LIKE ? OR u.email LIKE ? OR u.phone LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"]; 
}
$query .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/includes/admin_header.php';
?>
<div class="admin-top">
    <div>
        <span class="badge">Users</span>
        <h2 class="text-3xl font-bold mt-4">Manage registered users</h2>
        <p class="text-slate-400 mt-2">Search, review wallet balances, and access user details.</p>
    </div>
    <form method="GET" class="flex gap-3">
        <input name="search" class="form-field" placeholder="Search by username, email or phone" value="<?= htmlspecialchars($search) ?>">
        <button class="btn-secondary" type="submit">Search</button>
    </form>
</div>

<div class="admin-card">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Wallet</th>
                <th>Role</th>
                <th>Joined</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['phone']) ?></td>
                    <td>₦<?= number_format($user['balance'] ?? 0, 2) ?></td>
                    <td><?= $user['is_admin'] ? 'Admin' : 'User' ?></td>
                    <td><?= htmlspecialchars($user['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php';
