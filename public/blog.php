<?php
// public/blog.php
session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/auth.php';

$pageTitle = 'Ecosystem News & Updates';
require_once __DIR__ . '/pages/header.php';

// Fetch latest blog posts from oracle_posts matching your database mapping architecture
$stmt = $pdo->query("SELECT * FROM oracle_posts WHERE post_type = 'admin_blog' AND is_verified = 1 ORDER BY created_at DESC LIMIT 10");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-4xl mx-auto px-4 py-12 space-y-12">
    <!-- Header Grid Zone -->
    <div class="text-center space-y-4">
        <p class="badge">Sovereign Oracle</p>
        <h1 class="text-5xl font-black text-white tracking-tight">Ecosystem <span class="text-blue-500">Insights</span></h1>
        <p class="text-slate-400 max-w-lg mx-auto">Official updates, market analysis, and protocol announcements from the BitW Core Team.</p>
    </div>

    <!-- Feed Render Deck -->
    <div class="space-y-8">
        <?php if (empty($posts)): ?>
            <!-- Fallback Blank State View Container -->
            <div class="glass-card p-12 text-center border-dashed border-2 border-white/5">
                <i class="bx bx-news text-5xl text-slate-700 mb-4"></i>
                <p class="text-slate-500 font-medium">The Oracle is currently silent. Check back soon for updates.</p>
            </div>
        <?php else: ?>
            <!-- Active Publication Feed Cycle Loop -->
            <?php foreach ($posts as $post): ?>
                <article class="glass-card overflow-hidden hover:border-blue-500/30 transition-all group">
                    <div class="p-8 space-y-4">
                        <!-- Meta Tag Attributes Line -->
                        <div class="flex items-center gap-3 text-[10px] font-black uppercase tracking-widest text-blue-400">
                            <span>Admin Update</span>
                            <span class="w-1 h-1 bg-slate-700 rounded-full"></span>
                            <span class="text-slate-500"><?= date('M d, Y', strtotime($post['created_at'])) ?></span>
                        </div>
                        
                        <!-- Title -->
                        <h2 class="text-2xl font-bold text-white group-hover:text-blue-400 transition-colors">
                            <?= htmlspecialchars($post['title']) ?>
                        </h2>
                        
                        <!-- Core Body Content Context (Upgraded to map HTML structures natively) -->
                        <div class="oracle-rich-content text-slate-300 leading-relaxed text-sm">
                            <?= $post['content'] ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Custom Layout Style Injector to sanitize dynamic media dimension layouts safely -->
<style>
    .oracle-rich-content {
        font-family: 'Inter', sans-serif;
    }
    .oracle-rich-content p {
        margin-bottom: 1.25rem;
    }
    .oracle-rich-content p:last-child {
        margin-bottom: 0;
    }
    /* Constrain uploaded asset images to fit perfectly inside your dark glass template cards */
    .oracle-rich-content img {
        max-width: 100%;
        height: auto;
        border-radius: 12px;
        margin: 1.5rem auto;
        display: block;
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
    }
    .oracle-rich-content ul {
        list-style-type: disk;
        padding-left: 1.5rem;
        margin-bottom: 1.25rem;
    }
    .oracle-rich-content ol {
        list-style-type: decimal;
        padding-left: 1.5rem;
        margin-bottom: 1.25rem;
    }
    .oracle-rich-content blockquote {
        border-left: 4px solid #3b82f6;
        background-color: rgba(59, 130, 246, 0.03);
        padding: 1rem 1.25rem;
        border-radius: 0 12px 12px 0;
        margin: 1.5rem 0;
        font-style: italic;
        color: #94a3b8;
    }
</style>

<?php require_once __DIR__ . '/pages/footer.php'; ?>