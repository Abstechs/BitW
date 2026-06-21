<section class="glass-card p-6">
    <div class="section-header">
        <div>
            <h2>Stone plans</h2>
            <p class="text-sm text-slate-400 mt-1">Browse current investment stones and your active mining sessions.</p>
        </div>
        <a class="dashboard-link" href="#stones"><i class="bx bx-diamond"></i> Explore stones</a>
    </div>

    <div class="plan-grid">
        <?php foreach ($plans as $p): ?>
            <div class="glass-card p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold"><?= htmlspecialchars($p['name']) ?></h3>
                        <p class="text-sm text-slate-400"><?= $p['daily_rate'] ?>% daily • <?= $p['duration_days'] ?> days</p>
                    </div>
                    <div class="icon-box"><i class="bx bx-flame"></i></div>
                </div>
                <div class="text-sm text-slate-400 mb-3">Range</div>
                <div class="text-xl font-semibold">
                    ₦<?= number_format($p['min_amount']) ?>
                    <?php if ($p['max_amount']): ?>
                        - ₦<?= number_format($p['max_amount']) ?>
                    <?php else: ?>
                        +
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($activeMinings)): ?>
        <div class="glass-card p-5 mt-6">
            <div class="section-header">
                <div>
                    <h2>Your stones</h2>
                    <p class="text-sm text-slate-400 mt-1">Active stone investments and progress.</p>
                </div>
                <span class="badge"><i class="bx bx-time-five"></i> Active</span>
            </div>
            <div class="space-y-4">
                <?php foreach ($activeMinings as $mining): ?>
                    <div class="glass-card p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-slate-400"><?= htmlspecialchars($mining['plan_name'] ?? 'Stone') ?></p>
                                <p class="font-semibold">₦<?= number_format($mining['amount'], 2) ?> invested</p>
                                <p class="text-sm text-slate-400 mt-1">Daily earnings: ₦<?= number_format($mining['daily_earnings'], 2) ?></p>
                            </div>
                            <span class="status-pill <?= $mining['status'] === 'active' ? 'status-success' : 'status-muted' ?>"><?= ucfirst($mining['status']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
