<section class="glass-card p-6">
    <div class="section-header">
        <div>
            <h2>Wallet summary</h2>
            <p class="text-sm text-slate-400 mt-1">Quick access to your current balance and wallet health.</p>
        </div>
        <span class="badge"><i class="bx bx-wallet"></i> <?= htmlspecialchars(AppConfig::get('APP_ALIAS')) ?></span>
    </div>

    <div class="mini-card-grid">
        <div class="glass-card p-5">
            <div class="flex items-start gap-4">
                <div class="icon-box"><i class="bx bx-piggy-bank bx-lg"></i></div>
                <div>
                    <p class="text-sm text-slate-400">Wallet balance</p>
                    <p class="text-3xl font-semibold">₦<?= number_format($wallet['balance'] ?? 0, 2) ?></p>
                </div>
            </div>
        </div>
        <div class="glass-card p-5">
            <div class="flex items-start gap-4">
                <div class="icon-box"><i class="bx bx-trending-up bx-lg"></i></div>
                <div>
                    <p class="text-sm text-slate-400">Active stones</p>
                    <p class="text-3xl font-semibold"><?= count($activeMinings) ?></p>
                </div>
            </div>
        </div>
    </div>
</section>
