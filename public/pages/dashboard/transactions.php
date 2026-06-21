<section class="glass-card p-6">
    <div class="section-header">
        <div>
            <h2>Transaction history</h2>
            <p class="text-sm text-slate-400 mt-1">Latest wallet updates, mining claims, and referrals.</p>
        </div>
        <span class="badge"><i class="bx bx-history"></i> Recent</span>
    </div>

    <div class="dashboard-table-wrapper overflow-x-auto">
        <table class="dashboard-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Event</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="3" class="text-slate-400">No transactions recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach (array_slice($transactions, 0, 6) as $txn): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('M j, Y', strtotime($txn['created_at']))) ?></td>
                            <td><strong><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $txn['type']))) ?></strong> — <?= htmlspecialchars($txn['description'] ?: 'Wallet update') ?></td>
                            <td>₦<?= number_format($txn['amount'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
