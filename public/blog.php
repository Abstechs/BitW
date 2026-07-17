<?php
// public/blog.php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/auth.php';

$pageTitle = 'Ecosystem News & Updates';
require_once __DIR__ . '/pages/header.php';

// Fetch latest blog posts
$stmt = $pdo->query("SELECT * FROM oracle_posts WHERE post_type = 'admin_blog' AND is_verified = 1 ORDER BY created_at DESC LIMIT 10");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-4xl mx-auto px-4 py-12 space-y-12">
    <!-- Header -->
    <div class="text-center space-y-4">
        <p class="badge">Sovereign Oracle</p>
        <h1 class="text-5xl font-black text-white tracking-tight">Ecosystem <span class="text-blue-500">Insights</span></h1>
        <p class="text-slate-400 max-w-lg mx-auto">Official updates, market analysis, and protocol announcements from the BitW Core Team.</p>
    </div>

    <div class="space-y-8">
        <?php if (empty($posts)): ?>
            <div class="glass-card p-12 text-center border-dashed border-2 border-white/5">
                <i class="bx bx-news text-5xl text-slate-700 mb-4"></i>
                <p class="text-slate-500 font-medium">The Oracle is currently silent. Check back soon for updates.</p>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <article class="glass-card overflow-hidden hover:border-blue-500/30 transition-all group">
                    <div class="p-8 space-y-4">
                        <div class="flex items-center gap-3 text-[10px] font-black uppercase tracking-widest text-blue-400">
                            <span>Admin Update</span>
                            <span class="w-1 h-1 bg-slate-700 rounded-full"></span>
                            <span class="text-slate-500"><?= date('M d, Y', strtotime($post['created_at'])) ?></span>
                        </div>
                        <h2 class="text-2xl font-bold text-white group-hover:text-blue-400 transition-colors"><?= htmlspecialchars($post['title']) ?></h2>
                        <div class="text-slate-400 leading-relaxed text-sm">
                            <?= nl2br(htmlspecialchars($post['content'])) ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/pages/footer.php'; ?>
