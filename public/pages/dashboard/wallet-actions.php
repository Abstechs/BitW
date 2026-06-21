<section class="glass-card p-6">
    <div class="section-header">
        <div>
            <h2>Wallet actions</h2>
            <p class="text-sm text-slate-400 mt-1">Fund or withdraw from your wallet.</p>
        </div>
        <span class="badge"><i class="bx bx-transfer"></i> Live</span>
    </div>

    <!-- Tabs for payment methods -->
    <div class="flex gap-2 mb-6 border-b border-slate-700">
        <button class="payment-tab active" data-tab="paystack">
            <i class="bx bx-credit-card"></i> Paystack
        </button>
        <button class="payment-tab" data-tab="manual">
            <i class="bx bx-upload"></i> Manual Deposit
        </button>
    </div>

    <!-- Paystack Tab -->
    <div id="paystack-tab" class="payment-content">
        <form id="paystack-form" class="space-y-4">
            <label class="block text-sm text-slate-300">Fund amount (₦)</label>
            <input class="form-field" type="number" step="0.01" id="paystack_amount" name="amount" placeholder="Enter amount to fund" required>
            <button type="submit" class="action-button w-full">
                <i class="bx bx-wallet"></i> Fund with Paystack
            </button>
        </form>
    </div>

    <!-- Manual Deposit Tab -->
    <div id="manual-tab" class="payment-content hidden">
        <form id="manual-form" enctype="multipart/form-data" class="space-y-4">
            <label class="block text-sm text-slate-300">Deposit amount (₦)</label>
            <input class="form-field" type="number" step="0.01" id="manual_amount" name="amount" placeholder="Enter amount" required>

            <label class="block text-sm text-slate-300 mt-4">Upload payment proof</label>
            <input class="form-field" type="file" id="manual_proof" name="proof" accept="image/*,.pdf" required>
            <p class="text-xs text-slate-400 mt-1">Supported: JPG, PNG, GIF, PDF (Max 5MB)</p>

            <button type="submit" class="action-button w-full">
                <i class="bx bx-upload"></i> Submit for Approval
            </button>
        </form>
    </div>

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
            <button type="submit" class="action-button w-full"><i class="bx bx-up-arrow-circle"></i> Withdraw</button>
        </form>
    </div>
</section>

<style>
.payment-tab {
    padding: 0.5rem 1rem;
    border: none;
    background: none;
    color: #94a3b8;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    transition: all 0.3s ease;
}

.payment-tab:hover {
    color: #cbd5e1;
}

.payment-tab.active {
    color: #60a5fa;
    border-bottom-color: #60a5fa;
}

.payment-content {
    transition: opacity 0.3s ease;
}

.payment-content.hidden {
    display: none;
}
</style>

<script>
// Tab switching
document.querySelectorAll('.payment-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const tabName = this.dataset.tab;

        // Update active tab
        document.querySelectorAll('.payment-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');

        // Update content visibility
        document.querySelectorAll('.payment-content').forEach(content => content.classList.add('hidden'));
        document.getElementById(tabName + '-tab').classList.remove('hidden');
    });
});

// Paystack form submission
document.getElementById('paystack-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const amount = document.getElementById('paystack_amount').value;
    const button = this.querySelector('button');
    button.disabled = true;
    button.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Processing...';

    try {
        const response = await fetch('../api/paystack-initialize.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'amount=' + encodeURIComponent(amount)
        });

        const data = await response.json();

        if (data.status) {
            window.location.href = data.authorization_url;
        } else {
            alert('Error: ' + (data.message || 'Failed to initialize payment'));
            button.disabled = false;
            button.innerHTML = '<i class="bx bx-wallet"></i> Fund with Paystack';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while processing payment');
        button.disabled = false;
        button.innerHTML = '<i class="bx bx-wallet"></i> Fund with Paystack';
    }
});

// Manual deposit form submission
document.getElementById('manual-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const button = this.querySelector('button');
    button.disabled = true;
    button.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Uploading...';

    try {
        const response = await fetch('../api/manual-deposit.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.status) {
            alert(data.message);
            this.reset();
            button.disabled = false;
            button.innerHTML = '<i class="bx bx-upload"></i> Submit for Approval';
        } else {
            alert('Error: ' + (data.message || 'Failed to submit deposit'));
            button.disabled = false;
            button.innerHTML = '<i class="bx bx-upload"></i> Submit for Approval';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while submitting the deposit');
        button.disabled = false;
        button.innerHTML = '<i class="bx bx-upload"></i> Submit for Approval';
    }
});
</script>
