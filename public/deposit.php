<?php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Fetch settings array safely to pass the public key to frontend
$settings = include __DIR__ . '/../config/settings.php';
$paystack_public = $settings['PAYSTACK_PUBLIC'] ?? '';

$pageTitle = 'Deposit - ' . (AppConfig::get('APP_ALIAS') ?: 'BitW');
require_once __DIR__ . '/pages/header.php';
?>
<!-- Include Paystack Modern Inline V2 Script Hook -->
<script src="https://js.paystack.co/v2/inline.js"></script>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="badge">Funding</p>
            <h1 class="text-4xl font-semibold tracking-tight mt-4">Top up your wallet securely</h1>
            <p class="mt-2 text-sm text-slate-400">Use a trusted gateway or manual transfer to bring funds into your wallet.</p>
        </div>
        <a href="dashboard.php" class="action-button" style="max-width: 220px;"><i class="bx bx-arrow-back"></i> Back to Dashboard</a>
    </div>

    <section class="glass-card p-6">
        <div class="section-header">
            <div>
                <h2>Deposit center</h2>
                <p class="text-sm text-slate-400 mt-1">Choose the funding route you prefer.</p>
            </div>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <!-- Paystack Gateway Card -->
            <div class="rounded-2xl border border-white/10 p-4 flex flex-col justify-between">
                <div>
                    <div class="font-semibold">Paystack</div>
                    <div class="text-sm text-slate-400 mt-2">Card payments with live confirmation.</div>
                    
                    <div class="mt-4">
                        <label for="paystack-amount" class="block text-xs text-slate-400 mb-1">Amount (NGN)</label>
                        <input type="number" id="paystack-amount" class="w-full bg-slate-900 border border-white/10 rounded-lg p-2 text-white placeholder-slate-600 focus:outline-none focus:border-white/30" placeholder="Enter amount" min="100" value="100">
                    </div>
                </div>
                <button id="paystack-btn" class="action-button mt-4 w-full justify-center flex items-center gap-2">
                    <span id="btn-text">Continue with Paystack</span>
                </button>
            </div>

            <!-- Manual Deposit Card -->
            <div class="rounded-2xl border border-white/10 p-4 flex flex-col justify-between">
                <div>
                    <div class="font-semibold">Manual deposit</div>
                    <div class="text-sm text-slate-400 mt-2">Upload a transfer proof in the admin panel.</div>
                </div>
                <a href="manual-deposit.php" class="action-button mt-4 justify-center">Use manual deposit</a>
            </div>
        </div>
    </section>
</div>

<!-- Vanilla Toast Notification Container -->
<div id="toast-box" class="fixed bottom-5 right-5 z-50 transform translate-y-20 opacity-0 transition-all duration-300 pointer-events-none bg-slate-900 border text-sm text-white px-4 py-3 rounded-xl shadow-xl flex items-center gap-2"></div>

<script>
function showToast(message, type = 'info') {
    const toast = document.getElementById('toast-box');
    toast.innerText = message;
    toast.className = `fixed bottom-5 right-5 z-50 transform translate-y-0 opacity-100 transition-all duration-300 bg-slate-900 border px-4 py-3 rounded-xl shadow-xl flex items-center gap-2 ${
        type === 'error' ? 'border-red-500 text-red-400' : 'border-green-500 text-green-400'
    }`;
    setTimeout(() => {
        toast.className = "fixed bottom-5 right-5 z-50 transform translate-y-20 opacity-0 transition-all duration-300 pointer-events-none";
    }, 4000);
}

document.getElementById('paystack-btn').addEventListener('click', function(e) {
    e.preventDefault();
    
    // Safety verification check for the V2 library footprint
    if (typeof PaystackPop === 'undefined') {
        showToast('Payment gateway engine is loading. Please wait a moment...', 'error');
        return;
    }
    
    const amountInput = document.getElementById('paystack-amount');
    const amount = amountInput.value.trim();
    const btn = this;
    const btnText = document.getElementById('btn-text');
    
    if (!amount || amount <= 0) {
        showToast('Please enter a valid amount.', 'error');
        return;
    }
    
    // UI Visual Loading Feedbacks
    btn.disabled = true;
    btnText.innerHTML = `<svg class="animate-spin h-4 w-4 text-white inline mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Initializing checkout...`;
    showToast('Contacting secure gateway...', 'info');

    const formData = new URLSearchParams();
    formData.append('amount', amount);
    
    fetch('../api/paystack-initialize.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status && data.access_code) {
            showToast('Opening payment window...', 'success');

            // Initialize the correct Paystack V2 Popup engine footprint instance
            const popup = new PaystackPop();
            
            // Resume transaction strictly off the backend access token metadata state
            popup.resumeTransaction(data.access_code, {
                onSuccess: function(response) {
                    showToast('Payment successful! Synchronizing wallet...', 'success');
                    window.location.href = "dashboard.php?payment=success&ref=" + response.reference;
                },
                onCancel: function() {
                    showToast('Payment window closed.', 'error');
                    btn.disabled = false;
                    btnText.innerText = 'Continue with Paystack';
                },
                onError: function(error) {
                    showToast(error.message || 'Gateway initialization failure.', 'error');
                    btn.disabled = false;
                    btnText.innerText = 'Continue with Paystack';
                }
            });
        } else {
            throw new Error(data.message || 'Initialization failed.');
        }
    })
    .catch(error => {
        console.error(error);
        showToast(error.message || 'An unexpected error occurred.', 'error');
        btn.disabled = false;
        btnText.innerText = 'Continue with Paystack';
    });
});
</script>

<?php require_once __DIR__ . '/pages/footer.php'; ?>