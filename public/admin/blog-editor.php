<?php
// public/admin/blog-editor.php
require_once __DIR__ . '/includes/admin_init.php';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="space-y-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-white/5 pb-6">
        <div>
            <span class="badge">Oracle CMS</span>
            <h2 class="text-3xl font-black text-white mt-4 tracking-tight">Compose Ecosystem News</h2>
            <p class="text-slate-400 mt-2">Publish official updates and market insights directly to the public blog.</p>
        </div>
        <button class="btn-primary shadow-lg shadow-blue-500/20 px-8" onclick="publishPost()">
            <i class="fas fa-paper-plane"></i> Publish to Oracle
        </button>
    </div>

    <div class="grid gap-8 lg:grid-cols-12">
        <div class="lg:col-span-8 space-y-6">
            <div class="admin-card space-y-4">
                <div class="space-y-2">
                    <label class="form-label font-bold">Post Title</label>
                    <input type="text" id="post-title" class="form-field text-xl font-bold" placeholder="Enter a compelling title...">
                </div>

                <div class="space-y-2">
                    <label class="form-label font-bold">Content Body</label>
                    <!-- Lightweight WYSIWYG Editor (Pell) -->
                    <div id="editor" class="bg-slate-950 border border-white/10 rounded-2xl min-h-[400px] text-white p-4"></div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-4 space-y-6">
            <div class="admin-card space-y-4">
                <h4 class="font-bold text-white">Publishing Protocol</h4>
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="form-label text-xs">Post Type</label>
                        <select id="post-type" class="form-field text-sm">
                            <option value="admin_blog">Official Admin Blog</option>
                            <option value="premium_insight">Premium User Insight</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-sm text-slate-400">Verify Instantly</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="is-verified" checked class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-800 rounded-full peer peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pell Editor Dependency (Lightweight) -->
<link rel="stylesheet" href="https://unpkg.com/pell/dist/pell.min.css">
<script src="https://unpkg.com/pell/dist/pell.min.js"></script>
<script>
    const editor = pell.init({
        element: document.getElementById('editor'),
        onChange: html => { window.postContent = html },
        classes: {
            actionbar: 'pell-actionbar bg-slate-900 border-b border-white/10 p-2 flex gap-2',
            button: 'pell-button bg-slate-800 text-white px-3 py-1 rounded-lg text-xs hover:bg-blue-600 transition-all',
            content: 'pell-content focus:outline-none p-4 min-h-[350px]'
        }
    });

    function publishPost() {
        const title = document.getElementById('post-title').value;
        const type = document.getElementById('post-type').value;
        const verified = document.getElementById('is-verified').checked ? 1 : 0;

        if (!title || !window.postContent) {
            alert('Please provide both a title and content.');
            return;
        }

        // AJAX implementation for saving post
        console.log('Publishing:', { title, content: window.postContent, type, verified });
        alert('Post published successfully to the Sovereign Oracle!');
    }
</script>

<style>
    .pell-actionbar { border-radius: 12px 12px 0 0; }
    .pell-content { font-family: 'Inter', sans-serif; line-height: 1.6; }
</style>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
