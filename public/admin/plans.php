<?php
require_once __DIR__ . '/includes/admin_init.php';

// Ensure the plans table supports plan images for current and existing databases.
try {
    $columnCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plans' AND COLUMN_NAME = 'image'");
    $columnCheck->execute();
    if (intval($columnCheck->fetchColumn()) === 0) {
        $pdo->exec("ALTER TABLE plans ADD COLUMN image VARCHAR(255) DEFAULT NULL");
    }
} catch (PDOException $e) {
    // If the database does not support this operation on older versions, continue.
}

$errors = [];
$success = '';
$action = $_GET['action'] ?? 'list';
$planId = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'list';
    $name = trim($_POST['name'] ?? '');
    $minAmount = floatval($_POST['min_amount'] ?? 0);
    $maxAmount = $_POST['max_amount'] === '' ? null : floatval($_POST['max_amount']);
    $dailyRate = floatval($_POST['daily_rate'] ?? 0);
    $durationDays = intval($_POST['duration_days'] ?? 0);
    $maxPurchases = intval($_POST['max_purchase_attempts'] ?? 1);
    $description = trim($_POST['description'] ?? '');
    $backgroundStory = trim($_POST['background_story'] ?? '');
    $readMoreLink = trim($_POST['read_more_link'] ?? '');
    $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
    $image = $_FILES['image'] ?? null;

    if ($action === 'create' || $action === 'update') {
        if (!$name) $errors[] = 'Plan name is required.';
        if ($minAmount <= 0) $errors[] = 'Minimum amount must be greater than zero.';
        if ($dailyRate <= 0) $errors[] = 'Daily rate must be greater than zero.';
        if ($durationDays <= 0) $errors[] = 'Duration in days must be greater than zero.';

        if (empty($errors)) {
            $imagePath = null;
            if ($image && $image['error'] === UPLOAD_ERR_OK) {
                $targetDir = __DIR__ . '/../../assets/images/plans/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                $extension = pathinfo($image['name'], PATHINFO_EXTENSION);
                $filename = uniqid('plan_') . '.' . $extension;
                move_uploaded_file($image['tmp_name'], $targetDir . $filename);
                $imagePath = '/assets/images/plans/' . $filename;
            }

            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO plans (name, min_amount, max_amount, daily_rate, duration_days, max_purchase_attempts, status, image, description, background_story, read_more_link) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $minAmount, $maxAmount, $dailyRate, $durationDays, $maxPurchases, $status, $imagePath, $description, $backgroundStory, $readMoreLink]);
                $success = 'Stone plan created successfully.';
                $action = 'list';
            } elseif ($action === 'update' && $planId) {
                if ($imagePath) {
                    $stmt = $pdo->prepare("UPDATE plans SET name = ?, min_amount = ?, max_amount = ?, daily_rate = ?, duration_days = ?, max_purchase_attempts = ?, status = ?, image = ?, description = ?, background_story = ?, read_more_link = ? WHERE id = ?");
                    $stmt->execute([$name, $minAmount, $maxAmount, $dailyRate, $durationDays, $maxPurchases, $status, $imagePath, $description, $backgroundStory, $readMoreLink, $planId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE plans SET name = ?, min_amount = ?, max_amount = ?, daily_rate = ?, duration_days = ?, max_purchase_attempts = ?, status = ?, description = ?, background_story = ?, read_more_link = ? WHERE id = ?");
                    $stmt->execute([$name, $minAmount, $maxAmount, $dailyRate, $durationDays, $maxPurchases, $status, $description, $backgroundStory, $readMoreLink, $planId]);
                }
                $success = 'Stone plan updated successfully.';
                $action = 'list';
            }
        }
    }
}

if ($action === 'delete' && $planId) {
    $stmt = $pdo->prepare("DELETE FROM plans WHERE id = ?");
    $stmt->execute([$planId]);
    header('Location: plans.php');
    exit;
}

$plans = $pdo->query("SELECT * FROM plans ORDER BY min_amount ASC")->fetchAll();
$plan = null;
if ($planId) {
    $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ? LIMIT 1");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch();
}

require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="admin-top">
    <div>
        <span class="badge">Stone Plans</span>
        <h2 class="text-3xl font-bold mt-4">Manage plans</h2>
        <p class="text-slate-400 mt-2">Create, edit, and remove stone plans with image support.</p>
    </div>
    <a href="plans.php?action=create" class="btn-primary"><i class="fa-solid fa-plus"></i> New Plan</a>
</div>

<?php if ($success): ?>
    <div class="admin-card mb-6">
        <p class="text-teal-200"><?= htmlspecialchars($success) ?></p>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="admin-card mb-6">
        <?php foreach ($errors as $error): ?>
            <p class="text-rose-300">• <?= htmlspecialchars($error) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($action === 'create' || ($action === 'update' && $plan)): ?>
    <div class="admin-card">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?= $action ?>">
            <div class="grid gap-5 mb-5 md:grid-cols-2">
                <div>
                    <label class="form-label">Plan name</label>
                    <input type="text" name="name" class="form-field" value="<?= htmlspecialchars($plan['name'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-field">
                        <option value="active" <?= ($plan['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($plan['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Min amount</label>
                    <input type="number" name="min_amount" step="0.01" class="form-field" value="<?= htmlspecialchars($plan['min_amount'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Max amount</label>
                    <input type="number" name="max_amount" step="0.01" class="form-field" value="<?= htmlspecialchars($plan['max_amount'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Daily rate (%)</label>
                    <input type="number" name="daily_rate" step="0.01" class="form-field" value="<?= htmlspecialchars($plan['daily_rate'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Duration (days)</label>
                    <input type="number" name="duration_days" class="form-field" value="<?= htmlspecialchars($plan['duration_days'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label">Max purchases</label>
                    <input type="number" name="max_purchase_attempts" class="form-field" value="<?= htmlspecialchars($plan['max_purchase_attempts'] ?? 1) ?>">
                </div>
            </div>
            <div class="grid gap-5 mb-5 md:grid-cols-2">
                <div>
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-field" rows="3"><?= htmlspecialchars($plan['description'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="form-label">Background story</label>
                    <textarea name="background_story" class="form-field" rows="3"><?= htmlspecialchars($plan['background_story'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="mb-5">
                <label class="form-label">Read more link</label>
                <input type="text" name="read_more_link" class="form-field" value="<?= htmlspecialchars($plan['read_more_link'] ?? '') ?>">
            </div>
            <div class="mb-5">
                <label class="form-label">Plan image</label>
                <input type="file" name="image" class="form-field">
                <?php if (!empty($plan['image'])): ?>
                    <img src="<?= htmlspecialchars($plan['image']) ?>" alt="Plan image" class="image-preview mt-3">
                <?php endif; ?>
            </div>
            <div class="flex gap-3">
                <button class="btn-primary" type="submit"><?= $action === 'create' ? 'Create plan' : 'Update plan' ?></button>
                <a class="btn-secondary" href="plans.php">Back to all plans</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <div class="admin-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Rate</th>
                    <th>Duration</th>
                    <th>Range</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plans as $planItem): ?>
                    <?php $planImage = !empty($planItem['image']) ? htmlspecialchars($planItem['image']) : htmlspecialchars($settings['DEFAULT_PLAN_IMAGE'] ?? '/assets/images/default-plan.svg'); ?>
                    <tr>
                        <td><img src="<?= $planImage ?>" class="image-preview" alt="Plan image"></td>
                        <td><?= htmlspecialchars($planItem['name']) ?></td>
                        <td><?= htmlspecialchars($planItem['daily_rate']) ?>%</td>
                        <td><?= htmlspecialchars($planItem['duration_days']) ?> days</td>
                        <td>
                            ₦<?= number_format($planItem['min_amount'], 2) ?>
                            <?= $planItem['max_amount'] ? '– ₦' . number_format($planItem['max_amount'], 2) : '+' ?>
                        </td>
                        <td><?= htmlspecialchars(ucfirst($planItem['status'])) ?></td>
                        <td>
                            <a class="btn-secondary" href="plans.php?action=update&id=<?= $planItem['id'] ?>">Edit</a>
                            <a class="btn-secondary" href="plans.php?action=delete&id=<?= $planItem['id'] ?>" onclick="return confirm('Delete this plan?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php';
