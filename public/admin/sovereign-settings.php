<?php
// public/admin/sovereign-settings.php
require_once __DIR__ . '/includes/admin_init.php';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="space-y-8">
    <!-- Admin Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-white/5 pb-6">
        <div>
            <span class="badge">Sovereign Engine</span>
            <h2 class="text-3xl font-black text-white mt-4 tracking-tight">Ecosystem Configuration</h2>
            <p class="text-slate-400 mt-2">Adjust the high-level mathematical constants and system-wide protocol toggles.</p>
        </div>
        <button class="btn-primary shadow-lg shadow-blue-500/20 px-8">
            <i class="fas fa-save"></i> Commit Global Changes
        </button>
    </div>

    <div class="grid gap-8 lg:grid-cols-2">
        <!-- Market Dynamics -->
        <div class="admin-card border-l-4 border-blue-500 space-y-6">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center">
                    <i class="fas fa-chart-line text-blue-500"></i>
                </div>
                <h3 class="text-xl font-bold text-white">Market Math Protocol</h3>
            </div>
            
            <div class="space-y-6">
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <label class="form-label mb-0 font-bold">Global Volatility Multiplier</label>
                        <span class="text-xs font-mono text-blue-400 bg-blue-500/10 px-2 py-1 rounded">1.2x</span>
                    </div>
                    <input type="range" class="w-full accent-blue-500 bg-slate-800 rounded-lg h-2" min="0.1" max="5.0" step="0.1" value="1.2">
                    <p class="text-[10px] text-slate-500 italic">Adjusts the Gaussian noise amplitude in the stochastic engine.</p>
                </div>

                <div class="space-y-2">
                    <label class="form-label font-bold">Mean Reversion Gravity (κ)</label>
                    <input type="number" class="form-field focus:ring-2 focus:ring-blue-500/20" value="0.05" step="0.01">
                    <p class="text-[10px] text-slate-500">The strength at which prices are pulled back to the central gravity point.</p>
                </div>

                <div class="space-y-2">
                    <label class="form-label font-bold">Liquidity Depth Constant</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">₦</span>
                        <input type="text" class="form-field pl-8" value="1,000,000">
                    </div>
                </div>
            </div>
        </div>

        <!-- Protocol Toggles -->
        <div class="admin-card border-l-4 border-purple-500 space-y-6">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center">
                    <i class="fas fa-shield-alt text-purple-500"></i>
                </div>
                <h3 class="text-xl font-bold text-white">System Guard Toggles</h3>
            </div>

            <div class="divide-y divide-white/5">
                <div class="py-4 flex items-center justify-between">
                    <div>
                        <p class="font-bold text-white">Lotto-Sovereign Draw</p>
                        <p class="text-xs text-slate-500">Allow users to trigger the daily draw sequence.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" checked class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                    </label>
                </div>

                <div class="py-4 flex items-center justify-between">
                    <div>
                        <p class="font-bold text-white">Social Betting Verification</p>
                        <p class="text-xs text-slate-500">Require manual admin approval for all new markets.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                    </label>
                </div>

                <div class="py-4 flex items-center justify-between">
                    <div>
                        <p class="font-bold text-white">Global Maintenance Lock</p>
                        <p class="text-xs text-slate-500">Instantly suspend all transactional modules.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"></div>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
