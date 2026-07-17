<?php
// public/help.php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/auth.php';

$pageTitle = 'Ecosystem Help Center';
require_once __DIR__ . '/pages/header.php';

$topic = $_GET['topic'] ?? 'general';

// In a real scenario, these would be fetched from the database 'system_settings' or a 'help_articles' table
$guides = [
    'general' => [
        'title' => 'Getting Started with BitW',
        'content' => 'Welcome to the BitW Sovereign Ecosystem. This platform allows you to participate in high-fidelity financial modules including Mining, Trading, and Social Predictions.'
    ],
    'lotto' => [
        'title' => 'Lotto-Sovereign Protocol',
        'content' => 'The Lotto-Sovereign engine uses a stochastic algorithm to identify the optimal lucky number. Users can predict a 6-digit sequence to win from the collective pool.'
    ],
    'predictions' => [
        'title' => 'Social Prediction Matrix',
        'content' => 'Verified users can launch their own prediction markets. The platform facilitates the pool and takes a small commission for providing the secure ledger infrastructure.'
    ]
];

$currentGuide = $guides[$topic] ?? $guides['general'];
?>

<div class="max-w-4xl mx-auto px-4 py-12 space-y-8">
    <div class="flex items-center gap-4 border-b border-white/5 pb-8">
        <a href="javascript:history.back()" class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center hover:bg-white/10 transition-all">
            <i class="bx bx-left-arrow-alt text-xl"></i>
        </a>
        <div>
            <p class="badge">Support & Documentation</p>
            <h1 class="text-3xl font-black text-white mt-1"><?= htmlspecialchars($currentGuide['title']) ?></h1>
        </div>
    </div>

    <div class="glass-card p-8 md:p-12 leading-relaxed text-slate-300 space-y-6">
        <?= nl2br(htmlspecialchars($currentGuide['content'])) ?>
        
        <div class="pt-8 border-t border-white/5">
            <h4 class="text-white font-bold mb-4">Related Topics</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php foreach ($guides as $key => $g): ?>
                    <?php if ($key !== $topic): ?>
                        <a href="help.php?topic=<?= $key ?>" class="p-4 rounded-2xl bg-white/5 border border-white/5 hover:bg-white/10 transition-all flex items-center justify-between">
                            <span class="text-sm font-medium"><?= $g['title'] ?></span>
                            <i class="bx bx-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/pages/footer.php'; ?>
