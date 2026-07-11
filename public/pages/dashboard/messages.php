<?php if (!empty($messages)): ?>
    <div class="space-y-3 mb-6">
        <?php foreach ($messages as $msg): ?>
            <div class="alert <?= $msg['type'] === 'success' ? 'alert-success' : 'alert-error' ?>">
                <?= htmlspecialchars($msg['text']) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
