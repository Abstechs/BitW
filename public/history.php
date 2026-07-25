<?php
// public/history.php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/services/LottoHistoryService.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);

// Initialize service container and capture mutations
$historyService = new LottoHistoryService($pdo, $userId);
$filterMode = $historyService->getFilterMode($_GET);
$ledgerEntries = $historyService->getLedgerEntries($filterMode);
$stats = $historyService->getAggregatedStats();

$pageTitle = 'Matrix Ledger Base';
require_once __DIR__ . '/pages/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-4 md:py-6 space-y-6 md:space-y-8">
    <!-- Breadcrumbs / Structural Identity Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-white/5 pb-4 md:pb-6">
        <div>
            <nav class="flex items-center gap-2 text-[10px] md:text-xs font-bold uppercase tracking-widest text-slate-500 mb-1.5 md:mb-2">
                <a href="lotto.php" class="hover:text-purple-400 transition-colors">Sovereign Terminal</a>
                <i class="bx bx-chevron-right text-sm"></i>
                <span class="text-slate-400">Ledger Audits</span>
            </nav>
            <h1 class="text-2xl md:text-3xl font-black text-white tracking-tight">Systemic Position Ledger</h1>
            <p class="text-xs md:text-sm text-slate-400 mt-1">Complete structural verification trail for current cryptographic allocations.</p>
        </div>
        
        <!-- Interactive Environment Filter Node -->
        <div class="flex w-full md:w-auto bg-slate-950 p-1 rounded-xl border border-white/5">
            <a href="history.php?mode=all" class="flex-1 md:flex-none text-center px-3 md:px-4 py-2 rounded-lg text-[9px] md:text-[10px] font-black uppercase tracking-widest transition-all <?php echo $filterMode === 'all' ? 'bg-purple-600 text-white shadow-md' : 'text-slate-500 hover:text-slate-300'; ?>">ALL ARRAYS</a>
            <a href="history.php?mode=real" class="flex-1 md:flex-none text-center px-3 md:px-4 py-2 rounded-lg text-[9px] md:text-[10px] font-black uppercase tracking-widest transition-all <?php echo $filterMode === 'real' ? 'bg-purple-600 text-white shadow-md' : 'text-slate-500 hover:text-slate-300'; ?>">LIVE ENTRIES</a>
            <a href="history.php?mode=demo" class="flex-1 md:flex-none text-center px-3 md:px-4 py-2 rounded-lg text-[9px] md:text-[10px] font-black uppercase tracking-widest transition-all <?php echo $filterMode === 'demo' ? 'bg-purple-600 text-white shadow-md' : 'text-slate-500 hover:text-slate-300'; ?>">SANDBOX DATA</a>
        </div>
    </div>

    <!-- Scaled Vector Metrics Dashboard -->
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="glass-card p-4 md:p-6 bg-gradient-to-br from-slate-900 to-slate-950 shadow-xl border-l-2 border-purple-500">
            <span class="text-[9px] md:text-[10px] uppercase text-slate-500 font-black tracking-wider">Total Positions Locked</span>
            <p class="text-2xl md:text-3xl font-mono font-black text-white mt-1"><?php echo number_format($stats['total_positions']); ?></p>
        </div>
        <div class="glass-card p-4 md:p-6 bg-gradient-to-br from-slate-900 to-slate-950 shadow-xl border-l-2 border-emerald-500">
            <span class="text-[9px] md:text-[10px] uppercase text-slate-500 font-black tracking-wider">Live System Liquidity Committed</span>
            <p class="text-2xl md:text-3xl font-mono font-black text-emerald-400 mt-1">₦<?php echo number_format($stats['total_real_staked'], 2); ?></p>
        </div>
        <div class="glass-card p-4 md:p-6 bg-gradient-to-br from-slate-900 to-slate-950 shadow-xl border-l-2 border-blue-500">
            <span class="text-[9px] md:text-[10px] uppercase text-slate-500 font-black tracking-wider">Simulated Sandbox Iterations</span>
            <p class="text-2xl md:text-3xl font-mono font-black text-blue-400 mt-1">₦<?php echo number_format($stats['total_demo_staked'], 2); ?></p>
        </div>
    </div>

    <!-- Responsive Table Block Container -->
    <div class="glass-card overflow-hidden shadow-2xl">
        <?php if (!empty($ledgerEntries)): ?>
            <!-- DESKTOP MODE: Standard Table Frame -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[10px] uppercase tracking-widest text-slate-400 bg-slate-950/70 border-b border-white/5">
                            <th class="px-6 py-4">Verification Frame Time</th>
                            <th class="px-6 py-4">Contract Domain</th>
                            <th class="px-6 py-4">Target Array Sequence</th>
                            <th class="px-6 py-4">Allocation Weight</th>
                            <th class="px-6 py-4">Settlement Horizon</th>
                            <th class="px-6 py-4 text-right">Status State</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($ledgerEntries as $row): ?>
                            <tr class="hover:bg-white/[0.01] transition-colors">
                                <td class="px-6 py-4 text-slate-400 text-xs font-mono">
                                    <?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded <?php echo $row['mode'] === 'real' ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'bg-blue-500/10 text-blue-400 border border-blue-500/20'; ?>">
                                        <?php echo htmlspecialchars($row['mode'] === 'real' ? 'Live Account' : 'Sandbox', ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-1 font-mono font-black text-base text-white tracking-widest">
                                        <?php 
                                        $digits = str_split($row['sequence']);
                                        foreach($digits as $digit): ?>
                                            <span class="inline-block bg-slate-950 border border-white/5 px-2 py-0.5 rounded text-sm"><?php echo htmlspecialchars($digit, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-200 font-mono text-sm font-semibold">
                                    ₦<?php echo number_format($row['amount'], 2); ?>
                                </td>
                                <td class="px-6 py-4 text-slate-400 text-xs font-mono">
                                    <?php echo date('Y-m-d', strtotime($row['draw_date'])); ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <?php 
                                        $status = strtolower($row['status'] ?? 'pending');
                                        if ($status === 'settled' || $status === 'win') {
                                            echo '<span class="text-[10px] uppercase font-black tracking-wider text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-3 py-1 rounded-md">Settled Match</span>';
                                        } elseif ($status === 'failed' || $status === 'loss') {
                                            echo '<span class="text-[10px] uppercase font-black tracking-wider text-slate-500 bg-slate-950 border border-white/5 px-3 py-1 rounded-md">Balanced</span>';
                                        } else {
                                            echo '<span class="text-[10px] uppercase font-black tracking-wider text-amber-400 bg-amber-500/10 border border-amber-500/20 px-3 py-1 rounded-md animate-pulse">Analyzing Nodes</span>';
                                        }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- MOBILE MODE: Card-shifting Layout -->
            <div class="block md:hidden divide-y divide-white/5">
                <?php foreach ($ledgerEntries as $row): ?>
                    <div class="p-4 space-y-3 bg-slate-900/40 hover:bg-slate-900/60 transition-colors">
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded <?php echo $row['mode'] === 'real' ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'bg-blue-500/10 text-blue-400 border border-blue-500/20'; ?>">
                                <?php echo htmlspecialchars($row['mode'] === 'real' ? 'Live Account' : 'Sandbox', ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <div>
                                <?php 
                                    $status = strtolower($row['status'] ?? 'pending');
                                    if ($status === 'settled' || $status === 'win') {
                                        echo '<span class="text-[9px] uppercase font-black tracking-wider text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-0.5 rounded">Settled Match</span>';
                                    } elseif ($status === 'failed' || $status === 'loss') {
                                        echo '<span class="text-[9px] uppercase font-black tracking-wider text-slate-500 bg-slate-950 border border-white/5 px-2.5 py-0.5 rounded">Balanced</span>';
                                    } else {
                                        echo '<span class="text-[9px] uppercase font-black tracking-wider text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2.5 py-0.5 rounded animate-pulse">Analyzing Nodes</span>';
                                    }
                                ?>
                            </div>
                        </div>

                        <div class="flex justify-between items-center">
                            <div class="flex gap-1 font-mono font-black text-white tracking-widest">
                                <?php 
                                $digits = str_split($row['sequence']);
                                foreach($digits as $digit): ?>
                                    <span class="inline-block bg-slate-950 border border-white/5 px-2 py-0.5 rounded text-xs"><?php echo htmlspecialchars($digit, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-right text-slate-200 font-mono text-sm font-black">
                                ₦<?php echo number_format($row['amount'], 2); ?>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-white/[0.03] text-[10px] font-mono text-slate-500">
                            <div>
                                <span class="block uppercase text-[8px] tracking-wider text-slate-600 font-bold">Logged At</span>
                                <span class="text-slate-400"><?php echo date('M d, H:i', strtotime($row['created_at'])); ?></span>
                            </div>
                            <div class="text-right">
                                <span class="block uppercase text-[8px] tracking-wider text-slate-600 font-bold">Settlement Horizon</span>
                                <span class="text-slate-400"><?php echo date('Y-m-d', strtotime($row['draw_date'])); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Shared Fallback Empty State Frame -->
            <div class="px-6 py-16 text-center text-sm text-slate-500 font-medium bg-slate-950/10">
                <i class="bx bx-file-blank text-3xl block text-slate-600 mb-2"></i>
                No position allocation records found for this matrix profile path.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/pages/footer.php'; ?>