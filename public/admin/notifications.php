<?php
require_once __DIR__ . '/includes/admin_init.php';

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_type = $_POST['target_type'] ?? 'broadcast';
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($title) || empty($message)) {
        $error = "Please provide both a title and message content.";
    } else {
        try {
            if ($target_type === 'broadcast') {
                // Global broadcast maps user_id strictly as NULL value
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, created_at) VALUES (NULL, ?, ?, NOW())");
                $stmt->execute([$title, $message]);
                $success = "Global broadcast dispatched successfully!";
            } else {
                // Direct User routing execution
                $user_id = intval($_POST['user_id'] ?? 0);
                if ($user_id <= 0) {
                    $error = "Please select a valid user target destination.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$user_id, $title, $message]);
                    $success = "Direct notification delivered successfully.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database dispatch crash: " . $e->getMessage();
        }
    }
}

// Gather platform users for target allocation dropdown selector
$allUsers = $pdo->query("SELECT id, username, email FROM users WHERE is_admin = 0 ORDER BY username ASC")->fetchAll();

require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-top">
    <div>
        <span class="badge">Notification Center</span>
        <h2 class="text-3xl font-bold mt-4">Broadcast & In-App Bulletins</h2>
        <p class="text-slate-400 mt-2">Publish announcements to all ecosystem accounts or drop targeted memos to personal dashboards.</p>
    </div>
</div>

<div class="max-w-2xl">
    <?php if ($success): ?>
        <div class="p-4 mb-4 text-sm text-green-400 bg-green-950/40 border border-green-800 rounded-xl"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="p-4 mb-4 text-sm text-red-400 bg-red-950/40 border border-red-800 rounded-xl"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="admin-card space-y-4">
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Target Scope Group</label>
            <select name="target_type" id="target_type" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-white focus:outline-none" onchange="toggleUserSelector(this.value)">
                <option value="broadcast">Broadcast to All Users</option>
                <option value="individual">Send to Individual User</option>
            </select>
        </div>

        <div id="user_select_wrapper" class="hidden">
            <label class="block text-sm font-medium text-slate-300 mb-2">Select Recipient User Account</label>
            <select name="user_id" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-white focus:outline-none">
                <option value="">-- Choose User --</option>
                <?php foreach ($allUsers as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars($u['email']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Notification Title</label>
            <input type="text" name="title" placeholder="e.g., Scheduled Core Maintenance" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-white focus:outline-none" required>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-300 mb-2">Message Body Content</label>
            <textarea name="message" rows="5" placeholder="Write the full broadcast message content details here..." class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 text-white focus:outline-none" required></textarea>
        </div>

        <button type="submit" class="btn-secondary w-full bg-yellow-500 hover:bg-yellow-600 text-black font-semibold py-3 rounded-xl transition">
            <i class="fas fa-paper-plane mr-2"></i> Dispatch Notification Bulletin
        </button>
    </form>
</div>

<script>
function toggleUserSelector(val) {
    const el = document.getElementById('user_select_wrapper');
    if(val === 'individual') {
        el.classList.remove('hidden');
    } else {
        el.classList.add('hidden');
    }
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>