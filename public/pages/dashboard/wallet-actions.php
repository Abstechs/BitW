<section class="glass-card p-6">
    <div class="section-header">
        <div>
            <h2>Wallet actions</h2>
            <p class="text-sm text-slate-400 mt-1">Fund or withdraw from your wallet instantly.</p>
        </div>
        <span class="badge"><i class="bx bx-transfer"></i> Live</span>
    </div>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="dashboard_action" value="fund">
        <label class="block text-sm text-slate-300">Fund amount</label>
        <input class="form-field" type="number" step="0.01" name="amount" placeholder="Enter amount to fund">
        <button type="submit" class="action-button"><i class="bx bx-wallet"></i> Fund Wallet</button>
    </form>

    <div class="glass-card strong p-6 mt-6">
        <div class="section-header">
            <div>
                <h3 class="text-base font-semibold">Withdraw funds</h3>
                <p class="text-sm text-slate-400 mt-1">Minimum ₦<?= number_format($minWithdrawal, 2) ?></p>
            </div>
        </div>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="dashboard_action" value="withdraw">
            <label class="block text-sm text-slate-300">Withdraw amount</label>
            <input class="form-field" type="number" step="0.01" name="amount" placeholder="Enter amount to withdraw">
            <button type="submit" class="action-button"><i class="bx bx-up-arrow-circle"></i> Withdraw</button>
        </form>
    </div>
</section>
