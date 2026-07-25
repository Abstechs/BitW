<?php
// public/trading.php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Fetch all active trade stones/assets
$stmt = $pdo->query("SELECT * FROM trade_assets ORDER BY id ASC");
$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Select default asset (first one found)
$selectedAsset = $assets[0] ?? null;
$historyData = [];

if ($selectedAsset) {
    $histStmt = $pdo->prepare("SELECT price FROM asset_price_history WHERE asset_id = ? ORDER BY recorded_at DESC LIMIT 15");
    $histStmt->execute([$selectedAsset['id']]);
    $historyData = array_reverse($histStmt->fetchAll(PDO::FETCH_COLUMN));
    
    // Fallback if data points are sparse
    if (count($historyData) < 2) {
        $historyData = [$selectedAsset['base_price'], $selectedAsset['current_price']];
    }
}

$pageTitle = 'Resource Exchange Matrix';
require_once __DIR__ . '/pages/header.php';
?>

<!-- Global Dependencies Setup -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <!-- Header Context with Navigation Action Trigger -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-white/5 pb-4">
        <div>
            <p class="badge">Ecosystem Market</p>
            <h1 class="text-3xl font-bold text-white mt-2">Interplanetary Resource Exchange</h1>
            <p class="text-sm text-slate-400">Trade ecosystem stones with native automated volatility and calculate compounding yield structures.</p>
        </div>
        <div>
            <a href="dashboard.php" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 text-slate-300 border border-white/10 text-sm font-semibold hover:bg-slate-700 transition-all shadow-md">
                <i class="bx bx-left-arrow-alt text-lg"></i> Return to Dashboard
            </a>
        </div>
    </div>

    <?php if (!$selectedAsset): ?>
        <div class="glass-card p-8 text-center border border-yellow-500/10">
            <p class="text-slate-400">No trading assets initialized yet. Admin must seed resources first.</p>
        </div>
    <?php else: ?>
        <div class="grid gap-6 lg:grid-cols-3">
            
            <!-- Left Grid: Chart & Marketplace -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Custom Canvas Chart Card -->
                <div class="glass-card p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-white" id="asset-title"><?= htmlspecialchars($selectedAsset['name']) ?></h2>
                            <p class="text-xs font-mono text-slate-400" id="asset-ticker"><?= $selectedAsset['ticker'] ?> / USDT</p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-mono font-bold text-emerald-400" id="live-price">$<?= number_format($selectedAsset['current_price'], 4) ?></span>
                        </div>
                    </div>

                    <!-- Dependency-Free Graphic Field -->
                    <div class="relative bg-slate-950/60 rounded-xl p-2 border border-white/5">
                        <canvas id="nativeVectorChart" width="700" height="300" class="w-full h-[300px]"></canvas>
                    </div>
                </div>

                <!-- Live Buying/Selling Processing Block -->
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="glass-card p-6 border-l-4 border-emerald-500 flex flex-col justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-emerald-400 uppercase tracking-wider mb-3">Acquire Resource</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-xs text-slate-400">Allocation Amount</label>
                                    <input type="number" id="buy-amount" placeholder="0.00" class="w-full bg-slate-950 border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-emerald-500">
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button onclick="processTrade('buy', event)" class="action-button w-full justify-center bg-emerald-600 border-none hover:bg-emerald-500 text-sm py-3 font-semibold transition-all rounded-xl text-white">Execute Buy Pipeline</button>
                        </div>
                    </div>

                    <div class="glass-card p-6 border-l-4 border-rose-500 flex flex-col justify-between relative" id="sell-container">
                        <div>
                            <h3 class="text-sm font-bold text-rose-400 uppercase tracking-wider mb-3">Liquidate Asset</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-xs text-slate-400">Resource Balance Units</label>
                                    <input type="number" id="sell-amount" placeholder="0.00" class="w-full bg-slate-950 border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-rose-500">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Premium Liquidate Overlay Confirmation Panel -->
                        <div id="sell-confirm-panel" class="hidden absolute inset-0 bg-slate-950/95 backdrop-blur-sm rounded-xl p-4 flex flex-col justify-center items-center text-center space-y-3 z-10 animate-fade-in">
                            <i class="bx bx-info-circle text-amber-400 text-3xl"></i>
                            <p class="text-xs text-slate-300 px-4 leading-relaxed">Selling back to the Platform applies an immediate <span class="text-amber-400 font-bold">3% liquidity spread discount</span> below spot value.</p>
                            <div class="flex gap-2 w-full px-4">
                                <button onclick="cancelSellConfirmation()" class="w-1/2 py-2 bg-slate-800 text-slate-400 rounded-lg text-xs hover:bg-slate-700 transition-all">Cancel</button>
                                <button onclick="commitLiquidateExecution(event)" class="w-1/2 py-2 bg-rose-600 text-white rounded-lg text-xs font-semibold hover:bg-rose-500 transition-all shadow-lg">Confirm Sell</button>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button onclick="processTrade('sell', event)" class="action-button w-full justify-center bg-rose-600 border-none hover:bg-rose-500 text-sm py-3 font-semibold transition-all rounded-xl text-white">Execute Liquidate Pipeline</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Grid: Live Yield Simulator -->
            <div class="space-y-6">
                <div class="glass-card p-6 space-y-4 bg-gradient-to-b from-slate-900 via-slate-900 to-blue-950/20">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <i class="bx bx-calculator text-blue-400"></i> Staking Yield Estimator
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Calculate projected compound growth metrics based on resource staking lock intervals.</p>
                    
                    <div class="space-y-4 pt-2">
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-slate-400">Principal Deposit Value</span>
                                <span class="text-white font-bold font-mono" id="principal-val">$1,000</span>
                            </div>
                            <input type="range" id="slider-principal" min="100" max="50000" step="100" value="1000" class="w-full accent-blue-500" oninput="updateYieldCalculation()">
                        </div>

                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-slate-400">Staking Term Duration</span>
                                <span class="text-white font-bold font-mono" id="duration-val">12 Months</span>
                            </div>
                            <input type="range" id="slider-duration" min="1" max="36" step="1" value="12" class="w-full accent-blue-500" oninput="updateYieldCalculation()">
                        </div>

                        <div>
                            <label class="block text-xs text-slate-400 mb-1">Estimated Base APY Multiplier</label>
                            <select id="calc-apy" class="w-full bg-slate-950 border border-white/10 rounded-xl p-3 text-white focus:outline-none" onchange="updateYieldCalculation()">
                                <option value="0.12">Alpha Tier (12% Base APY)</option>
                                <option value="0.24">Nebula Tier (24% Mid APY)</option>
                                <option value="0.45">Quantum Tier (45% Max Return High-Scarcity)</option>
                            </select>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-950/80 border border-white/5 space-y-2 mt-4">
                            <div class="flex justify-between items-center text-xs text-slate-400">
                                <span>Total Harvested Returns</span>
                                <span class="text-white font-mono font-bold text-base text-blue-400" id="result-total">$0.00</span>
                            </div>
                            <div class="flex justify-between items-center text-xs text-slate-400 border-t border-white/5 pt-2">
                                <span>Net Yield Profit Margin</span>
                                <span class="text-emerald-400 font-mono font-semibold text-sm" id="result-profit">+$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Asset Selection List -->
                <div class="glass-card p-4 space-y-2">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 px-2 mb-2">Available Resources</h4>
                    <?php foreach ($assets as $a): ?>
                        <div class="p-3 rounded-xl border border-white/5 bg-slate-950/30 hover:bg-slate-950/70 transition-all cursor-pointer flex justify-between items-center">
                            <div>
                                <p class="text-sm font-bold text-white"><?= htmlspecialchars($a['name']) ?></p>
                                <span class="text-[10px] bg-blue-500/10 text-blue-400 px-1.5 py-0.5 rounded font-mono"><?= $a['ticker'] ?></span>
                            </div>
                            <span class="font-mono text-sm text-slate-300 font-semibold">$<?= number_format($a['current_price'], 4) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

<script src="assets/js/chart.js"></script>
<script>
let lastTriggeredButton = null;

document.addEventListener("DOMContentLoaded", function() {
    const chartDataPoints = <?= json_encode($historyData) ?>;
    const chart = new NativeTradeChart('nativeVectorChart', chartDataPoints);
    chart.render();
    
    updateYieldCalculation();
});

function updateYieldCalculation() {
    const P = parseFloat(document.getElementById('slider-principal').value);
    const t = parseFloat(document.getElementById('slider-duration').value) / 12; 
    const r = parseFloat(document.getElementById('calc-apy').value);
    
    document.getElementById('principal-val').innerText = '$' + P.toLocaleString();
    document.getElementById('duration-val').innerText = document.getElementById('slider-duration').value + ' Months';
    
    const A = P * Math.pow((1 + (r / 12)), (12 * t));
    const profit = A - P;
    
    document.getElementById('result-total').innerText = '$' + A.toFixed(2);
    document.getElementById('result-profit').innerText = '+$' + profit.toFixed(2);
}

function processTrade(tradeType, event) {
    const inputField = tradeType === 'buy' ? 'buy-amount' : 'sell-amount';
    const amountVal = parseFloat(document.getElementById(inputField).value);

    if (!amountVal || amountVal <= 0) {
        Toastify({
            text: "🚨 Please specify a valid quantity of resource units to trade.",
            duration: 3500,
            gravity: "top",
            position: "right",
            style: { background: "linear-gradient(to right, #f43f5e, #e11d48)" }
        }).showToast();
        return;
    }

    lastTriggeredButton = event.target;

    // Trigger confirmation overlay UI instead of native confirm()
    if (tradeType === 'sell') {
        document.getElementById('sell-confirm-panel').classList.remove('hidden');
        return;
    }

    // Direct fire for purchase processing
    transmitTradePayload(tradeType, amountVal, lastTriggeredButton);
}

function cancelSellConfirmation() {
    document.getElementById('sell-confirm-panel').className += ' hidden';
}

function commitLiquidateExecution(event) {
    document.getElementById('sell-confirm-panel').className += ' hidden';
    const amountVal = parseFloat(document.getElementById('sell-amount').value);
    transmitTradePayload('sell', amountVal, lastTriggeredButton);
}

function transmitTradePayload(tradeType, amountVal, actionBtn) {
    const assetId = <?= isset($selectedAsset['id']) ? intval($selectedAsset['id']) : 0 ?>; 
    const inputField = tradeType === 'buy' ? 'buy-amount' : 'sell-amount';

    if (actionBtn) {
        actionBtn.disabled = true;
        actionBtn.innerText = "Transmitting to Ledger...";
    }

    fetch('api/trade-process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            asset_id: assetId,
            type: tradeType,
            amount: amountVal
        })
    })
    .then(response => response.json())
    .then(data => {
        if (actionBtn) {
            actionBtn.disabled = false;
            actionBtn.innerText = tradeType === 'buy' ? 'Execute Buy Pipeline' : 'Execute Liquidate Pipeline';
        }

        if (data.success) {
            Toastify({
                text: `✅ Success: ${data.log}`,
                duration: 4000,
                gravity: "top",
                position: "right",
                style: { background: "linear-gradient(to right, #10b981, #059669)" }
            }).showToast();

            if(document.getElementById('live-price')) {
                document.getElementById('live-price').innerText = '$' + data.new_price;
            }
            document.getElementById(inputField).value = '';
            
            setTimeout(() => {
                window.location.reload();
            }, 1200);
        } else {
            Toastify({
                text: `❌ Transaction Halt: ${data.message}`,
                duration: 5000,
                gravity: "top",
                position: "right",
                style: { background: "linear-gradient(to right, #ef4444, #dc2626)" }
            }).showToast();
        }
    })
    .catch(err => {
        if (actionBtn) {
            actionBtn.disabled = false;
            actionBtn.innerText = tradeType === 'buy' ? 'Execute Buy Pipeline' : 'Execute Liquidate Pipeline';
        }
        console.error("Transmission breakdown:", err);
        Toastify({
            text: "⚠️ System pipeline connection timeout.",
            duration: 4000,
            gravity: "top",
            position: "right",
            style: { background: "linear-gradient(to right, #7c3aed, #6d28d9)" }
        }).showToast();
    });
}
</script>

<?php require_once __DIR__ . '/pages/footer.php'; ?>