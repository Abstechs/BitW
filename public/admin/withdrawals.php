<?php
// admin/withdrawals.php
require_once __DIR__ . '/includes/admin_init.php';

$success = '';
$error = '';

// Handle Action Processing Requests (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_action'])) {
    global $pdo;
    $ticket_id = intval($_POST['ticket_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($ticket_id > 0 && in_array($action, ['completed', 'rejected'])) {
        try {
            $pdo->beginTransaction();

            // Select target pending row and lock it for update safety
            $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ? FOR UPDATE");
            $stmt->execute([$ticket_id]);
            $ticket = $stmt->fetch();

            if ($ticket && $ticket['status'] === 'pending') {
                // Update transaction state parameters
                $upd = $pdo->prepare("UPDATE withdrawals SET status = ? WHERE id = ?");
                $upd->execute([$action, $ticket_id]);

                // If rejected, refund the locked amount seamlessly back into user wallet parameters
                if ($action === 'rejected') {
                    $refund = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
                    $refund->execute([$ticket['amount'], $ticket['user_id']]);
                }

                $pdo->commit();
                $success = "Withdrawal ticket #" . $ticket_id . " marked as " . strtoupper($action) . " successfully.";
            } else {
                $pdo->rollBack();
                $error = "Action blocked: Target ticket already processed or invalid.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "System processing failure: " . $e->getMessage();
        }
    }
}

// Fetch all filed records joined with custom user metrics, totals of active orders, and referral profiles
$query = "
    SELECT 
        w.*, 
        u.username, 
        u.email,
        COALESCE((SELECT COUNT(*) FROM referrals r WHERE r.referrer_id = w.user_id), 0) as total_referrals,
        COALESCE((SELECT SUM(o.amount) FROM orders o WHERE o.user_id = w.user_id AND o.status = 'completed'), 0) as total_invested
    FROM withdrawals w 
    JOIN users u ON w.user_id = u.id 
    ORDER BY w.created_at DESC
";
$tickets = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Initialize dynamic counters for filter buttons
$counts = ['all' => 0, 'pending' => 0, 'completed' => 0, 'rejected' => 0];
foreach ($tickets as $t) {
    $counts['all']++;
    if (isset($counts[$t['status']])) {
        $counts[$t['status']]++;
    }
}

// Index all withdrawal history grouped by user to embed tracking inside the sub-layout grids
$historyStmt = $pdo->query("SELECT * FROM withdrawals ORDER BY created_at DESC");
$allWithdrawals = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
$userHistoryCache = [];
foreach ($allWithdrawals as $hist) {
    $userHistoryCache[$hist['user_id']][] = $hist;
}

require_once __DIR__ . '/includes/admin_header.php';
?>

<!-- Toastify CSS embedded directly to guarantee availability -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

<style>
    /* Toastify Custom Styling Overrides */
    .toastify-custom-success {
        background: linear-gradient(135deg, #059669, #10b981) !important;
        border-radius: 1rem !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3) !important;
    }
    .toastify-custom-error {
        background: linear-gradient(135deg, #dc2626, #f87171) !important;
        border-radius: 1rem !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3) !important;
    }
    .modal-glass { background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(12px); }
    
    /* Interactive Filter Bar Styling */
    .filter-tab {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: #94a3b8;
        border-radius: 0.75rem;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .filter-tab:hover { color: #fff; background: rgba(255, 255, 255, 0.05); }
    .filter-tab.active { color: #fff; background: rgba(255, 255, 255, 0.1); box-shadow: inset 0 1px 0 0 rgba(255,255,255,0.1); }
</style>

<!-- ==================== CUSTOM MODAL SYSTEM ==================== -->
<div id="adminConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm hidden opacity-0 transition-all duration-300">
    <div class="modal-glass border border-gray-700 max-w-sm w-full p-6 rounded-3xl shadow-2xl transform scale-95 transition-all duration-300">
        <div class="flex items-center gap-3 text-amber-400 mb-4">
            <i class="fas fa-exclamation-circle text-2xl"></i>
            <h3 class="text-xl font-bold text-white">Confirm Action</h3>
        </div>
        <p id="adminConfirmMessage" class="text-gray-300 text-sm leading-relaxed mb-6"></p>
        <div class="flex items-center justify-end gap-3">
            <button id="adminCancelBtn" class="btn-secondary py-2 px-4 text-sm" style="border-radius:12px;">
                Cancel
            </button>
            <button id="adminProceedBtn" class="btn-primary py-2 px-4 text-sm" style="border-radius:12px; background: linear-gradient(135deg,#60a5fa,#7c3aed);">
                Confirm
            </button>
        </div>
    </div>
</div>
<!-- ============================================================= -->

<div class="admin-top">
    <div>
        <span class="badge">Cashout Tickets</span>
        <h2 class="text-3xl font-bold mt-4">Manage System Payout Requests</h2>
        <p class="text-slate-400 mt-2">Audit profile metrics, active holdings, payment destinations, and complete payout timelines per user.</p>
    </div>
</div>

<!-- QUICK FILTERS ACTION BAR -->
<div class="flex flex-wrap items-center gap-2 mb-4 bg-white/[0.02] border border-white/5 p-1.5 rounded-2xl max-w-max">
    <button class="filter-tab active" onclick="filterTickets('all', this)">
        All <span class="bg-white/10 px-2 py-0.5 text-xs rounded-md text-slate-300"><?= $counts['all'] ?></span>
    </button>
    <button class="filter-tab" onclick="filterTickets('pending', this)">
        Pending <span class="bg-amber-500/20 px-2 py-0.5 text-xs rounded-md text-amber-400"><?= $counts['pending'] ?></span>
    </button>
    <button class="filter-tab" onclick="filterTickets('completed', this)">
        Completed <span class="bg-emerald-500/20 px-2 py-0.5 text-xs rounded-md text-emerald-400"><?= $counts['completed'] ?></span>
    </button>
    <button class="filter-tab" onclick="filterTickets('rejected', this)">
        Rejected <span class="bg-rose-500/20 px-2 py-0.5 text-xs rounded-md text-rose-400"><?= $counts['rejected'] ?></span>
    </button>
</div>

<div class="admin-card">
    <div class="overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th>User Profile</th>
                    <th>Account Health</th>
                    <th>Payout Amount</th>
                    <th>Payment Destination (Details)</th>
                    <th>Control Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                    <tr class="ticket-row-wrapper" data-status="all">
                        <td colspan="5" style="text-align: center;" class="text-slate-500 py-8">No withdrawal requests found in system queue.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tickets as $t): ?>
                        <!-- Main Transaction Record Row container utilizing custom data-status indicators -->
                        <tr class="border-b border-white/5 ticket-row-wrapper" data-status="<?= htmlspecialchars($t['status']) ?>" id="main-row-<?= $t['id'] ?>">
                            <td>
                                <span class="font-bold text-white"><?= htmlspecialchars($t['username']) ?></span><br>
                                <span class="text-xs text-slate-500"><?= htmlspecialchars($t['email']) ?></span><br>
                                <span class="text-[10px] text-slate-400 block mt-1"><i class="far fa-clock mr-1"></i><?= $t['created_at'] ?></span>
                            </td>
                            <td>
                                <div class="text-xs space-y-1 text-slate-300">
                                    <div>Invested: <span class="text-emerald-400 font-semibold">₦<?= number_format($t['total_invested'], 2) ?></span></div>
                                    <div>Referrals: <span class="text-indigo-400 font-semibold"><?= intval($t['total_referrals']) ?> users</span></div>
                                </div>
                            </td>
                            <td>
                                <strong class="text-white text-base">₦<?= number_format($t['amount'], 2) ?></strong>
                            </td>
                            <td>
                                <?php 
                                    $displayDetails = !empty($t['payout_details']) ? $t['payout_details'] : $t['method']; 
                                ?>
                                <div class="max-w-xs text-xs font-mono text-slate-200 bg-white/5 border border-white/10 px-3 py-1.5 rounded-xl block break-words" title="<?= htmlspecialchars($displayDetails) ?>">
                                    <?= htmlspecialchars($displayDetails) ?>
                                </div>
                                <?php if (!empty($t['payout_details'])): ?>
                                    <span class="block text-[10px] text-slate-500 mt-1 pl-1"><i class="fas fa-wallet mr-1"></i><?= htmlspecialchars($t['method']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <div class="flex items-center gap-2">
                                        <?php if ($t['status'] === 'pending'): ?>
                                            <form id="form-<?= $t['id'] ?>" method="POST" style="display:none;">
                                                <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                                                <input type="hidden" name="process_action" value="1">
                                                <input type="hidden" id="action-input-<?= $t['id'] ?>" name="action" value="">
                                            </form>

                                            <button type="button" class="btn-primary py-1.5 px-3 text-xs" style="border-radius:12px; font-weight:600; background: linear-gradient(135deg,#10b981,#059669);" onclick="triggerVerification(<?= $t['id'] ?>, 'completed', 'Approve distribution layout settlement for this cashout request?')">
                                                Approve
                                            </button>
                                            <button type="button" class="btn-secondary py-1.5 px-3 text-xs" style="border-radius:12px; font-weight:600; border-color: rgba(244,63,94,0.3); color: #fda4af;" onclick="triggerVerification(<?= $t['id'] ?>, 'rejected', 'Reject request and bounce funds back to user wallet balance?')">
                                                Reject
                                            </button>
                                        <?php else: ?>
                                            <span class="badge" style="background: <?= $t['status'] === 'completed' ? 'rgba(16,185,129,0.15)' : 'rgba(244,63,94,0.15)' ?>; color: <?= $t['status'] === 'completed' ? '#34d399' : '#fb7185' ?>;">
                                                <?= htmlspecialchars(ucfirst($t['status'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Toggle History Button -->
                                    <button type="button" class="text-[11px] text-blue-400 hover:underline mt-1 flex items-center gap-1" onclick="toggleUserHistory(<?= $t['id'] ?>)">
                                        <i class="fas fa-history"></i> History Logs
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Sub-Row containing Personal Cashout History Log Timelines -->
                        <tr id="history-row-<?= $t['id'] ?>" class="hidden bg-black/20 ticket-history-row" data-parent-status="<?= htmlspecialchars($t['status']) ?>">
                            <td colspan="5" class="p-4">
                                <div class="border border-white/5 rounded-2xl p-4 bg-white/[0.01]">
                                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-2">
                                        <i class="fas fa-folder-open text-blue-400"></i> Withdrawal History Timeline (<?= htmlspecialchars($t['username']) ?>)
                                    </h4>
                                    
                                    <?php if (empty($userHistoryCache[$t['user_id']])): ?>
                                        <p class="text-xs text-slate-500 italic">No historical cashout records found for this profile.</p>
                                    <?php else: ?>
                                        <div class="grid gap-2 max-h-[160px] overflow-y-auto pr-1 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                                            <?php foreach ($userHistoryCache[$t['user_id']] as $h): ?>
                                                <div class="border border-white/10 rounded-xl p-2.5 bg-white/[0.02] flex items-center justify-between gap-3 text-xs">
                                                    <div>
                                                        <div class="font-bold text-white">₦<?= number_format($h['amount'], 2) ?></div>
                                                        <div class="text-[10px] text-slate-500 mt-0.5"><?= $h['created_at'] ?></div>
                                                    </div>
                                                    <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold tracking-tight <?= $h['status'] === 'completed' ? 'bg-emerald-500/10 text-emerald-400' : ($h['status'] === 'rejected' ? 'bg-rose-500/10 text-rose-400' : 'bg-slate-500/10 text-slate-400') ?>">
                                                        <?= htmlspecialchars($h['status']) ?>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <!-- Dynamic empty placeholder state if a filter returns nothing -->
                    <tr id="no-filter-results" class="hidden">
                        <td colspan="5" style="text-align: center;" class="text-slate-500 py-8">No withdrawal requests found matching this status filter.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Toastify JavaScript Library dependency definition -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<script>
    // Pure instant Client-side Filter Implementation
    function filterTickets(status, element) {
        // Toggle active design styles across tabs
        document.querySelectorAll('.filter-tab').forEach(btn => btn.classList.remove('active'));
        element.classList.add('active');

        let visibleCount = 0;
        const mainRows = document.querySelectorAll('.ticket-row-wrapper');
        const historyRows = document.querySelectorAll('.ticket-history-row');

        mainRows.forEach(row => {
            const rowStatus = row.getAttribute('data-status');
            
            // Handle standard fallback mapping rules
            if (status === 'all' || rowStatus === status) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
                
                // If main row is hidden, force-collapse its history details row as well
                const targetId = row.id.replace('main-row-', '');
                const assocHistory = document.getElementById('history-row-' + targetId);
                if (assocHistory) assocHistory.classList.add('hidden');
            }
        });

        // Toggle dynamic fallback information visibility metrics
        const placeholderRow = document.getElementById('no-filter-results');
        if (placeholderRow) {
            placeholderRow.style.display = (visibleCount === 0) ? '' : 'none';
        }
    }

    // Toggle historical sub-grid wrapper display safely
    function toggleUserHistory(ticketId) {
        const row = document.getElementById('history-row-' + ticketId);
        if (row) {
            row.classList.toggle('hidden');
        }
    }

    function showToastNotification(text, type = 'success') {
        let className = "toastify-custom-success";
        if (type === 'error') className = "toastify-custom-error";

        Toastify({
            text: text,
            duration: 3500,
            close: true,
            gravity: "top", 
            position: "right",
            className: className,
            stopOnFocus: true
        }).showToast();
    }

    // Trigger Toast notifications on PHP script return execution
    <?php if ($success): ?>
        showToastNotification(<?= json_encode($success) ?>, 'success');
    <?php endif; ?>
    <?php if ($error): ?>
        showToastNotification(<?= json_encode($error) ?>, 'error');
    <?php endif; ?>

    // Elegant Dialog Confirmation System Mechanics
    let activeTicketId = null;
    let activeAction = null;

    const confirmModal = document.getElementById('adminConfirmModal');
    const confirmMessage = document.getElementById('adminConfirmMessage');
    const proceedBtn = document.getElementById('adminProceedBtn');
    const cancelBtn = document.getElementById('adminCancelBtn');

    function triggerVerification(ticketId, action, textPrompt) {
        activeTicketId = ticketId;
        activeAction = action;
        confirmMessage.textContent = textPrompt;

        confirmModal.classList.remove('hidden');
        setTimeout(() => {
            confirmModal.classList.remove('opacity-0');
            confirmModal.querySelector('.modal-glass').classList.remove('scale-95');
        }, 10);
    }

    function hideVerificationModal() {
        confirmModal.classList.add('opacity-0');
        confirmModal.querySelector('.modal-glass').classList.add('scale-95');
        setTimeout(() => {
            confirmModal.classList.add('hidden');
            activeTicketId = null;
            activeAction = null;
        }, 300);
    }

    cancelBtn.addEventListener('click', hideVerificationModal);

    proceedBtn.addEventListener('click', () => {
        if(activeTicketId && activeAction) {
            document.getElementById('action-input-' + activeTicketId).value = activeAction;
            document.getElementById('form-' + activeTicketId).submit();
        }
    });
</script>

<?php 
require_once __DIR__ . '/includes/admin_footer.php'; 
?>