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
            <input class="form-field" type="number" step="0.01" id="payamount" name="amount" placeholder="Enter amount to fund" required>
            <input type="hidden" id="email-address" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
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

<!-- Paystack Inline JS -->
<script src="https://js.paystack.co/v1/inline.js"></script>

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

// Handle Paystack payment callback
function handlePaymentCallback(response) {
    const postData = {
        ded_amount: document.getElementById("payamount").value,
        type: 'fund',
        ref: response.reference,
        mail: document.getElementById("email-address").value
    };

    // Show loading alert
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Payment Processing',
            text: 'Please wait, your request is processing...',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            didOpen: function() {
                Swal.showLoading();
            }
        });
    }

    // Send to verification endpoint
    fetch('../api/paystack-verify.php?reference=' + response.reference, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (typeof Swal !== 'undefined') {
            Swal.close();
        }

        if (data.status) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Success!',
                    text: 'Your wallet has been funded successfully. New balance: ₦' + (data.new_balance ? data.new_balance : 'updated'),
                    icon: 'success',
                    timer: 3000,
                    timerProgressBar: true
                }).then(() => {
                    window.location.reload();
                });
            } else {
                alert('Payment successful! Your wallet has been funded.');
                window.location.reload();
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error',
                    text: data.message || 'An error occurred. Please try again.',
                    icon: 'error'
                });
            } else {
                alert('Error: ' + (data.message || 'Payment verification failed'));
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Error',
                text: 'An error occurred while processing payment',
                icon: 'error'
            });
        } else {
            alert('An error occurred. Please try again.');
        }
    });
}

// Paystack form submission
document.getElementById('paystack-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const amount = document.getElementById('payamount').value;

    if (!amount || amount <= 0) {
        alert('Please enter a valid amount');
        return;
    }

    const button = this.querySelector('button');
    button.disabled = true;
    button.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Processing...';

    try {
        const response = await fetch('../api/paystack-initialize.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'amount=' + encodeURIComponent(amount)
        });

        const data = await response.json();

        if (data.status) {
            // Initialize Paystack
            const handler = PaystackPop.setup({
                key: '<?= htmlspecialchars($settings['PAYSTACK_PUBLIC'] ?? '') ?>',
                email: document.getElementById('email-address').value,
                amount: Math.round(amount * 100),
                ref: data.reference,
                onClose: function() {
                    button.disabled = false;
                    button.innerHTML = '<i class="bx bx-wallet"></i> Fund with Paystack';
                    alert('Window closed.');
                },
                onSuccess: function(transaction) {
                    handlePaymentCallback(transaction);
                }
            });
            handler.openIframe();
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
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const data = await response.json();

        if (data.status) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Success!',
                    text: data.message,
                    icon: 'success',
                    timer: 3000,
                    timerProgressBar: true
                });
            } else {
                alert(data.message);
            }
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
