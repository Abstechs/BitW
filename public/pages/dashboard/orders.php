<section class="glass-card p-6">
    <div class="section-header">
        <div>
            <h2>Orders & activity</h2>
            <p class="text-sm text-slate-400 mt-1">Track plan purchases, funding, and withdrawals in one place.</p>
        </div>
        <a class="dashboard-link" href="#transactions"><i class="bx bx-list-check"></i> View history</a>
    </div>

    <div class="dashboard-table-wrapper overflow-x-auto">
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="4" class="text-slate-400">No recent orders or wallet activity yet.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($transactions as $txn): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($txn['description'] ?: ucfirst($txn['type'])) ?></strong></td>
                        <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $txn['type']))) ?></td>
                        <td>₦<?= number_format($txn['amount'], 2) ?></td>
                        <td><span class="status-pill <?= $txn['status'] === 'completed' ? 'status-success' : 'status-muted' ?>"><?= htmlspecialchars(ucfirst($txn['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
