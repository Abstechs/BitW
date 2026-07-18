<?php
// public/admin/blog-editor.php
require_once __DIR__ . '/includes/admin_init.php';
require_once __DIR__ . '/includes/admin_header.php';
?>

<!-- Toastify CSS CDN (Premium Modern UI Notifications) -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

<div class="space-y-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-white/5 pb-6">
        <div>
            <span class="badge">Oracle CMS</span>
            <h2 class="text-3xl font-black text-white mt-4 tracking-tight">Compose Ecosystem News</h2>
            <p class="text-slate-400 mt-2">Publish official ecosystem updates, rich media layouts, and direct market analysis insights.</p>
        </div>
        <button id="publish-btn" class="btn-primary shadow-lg shadow-blue-500/20 px-8" onclick="publishPost()">
            <i class="fas fa-paper-plane mr-2"></i> Publish to Oracle
        </button>
    </div>

    <div class="grid gap-8 lg:grid-cols-12">
        <div class="lg:col-span-8 space-y-6">
            <div class="admin-card space-y-4">
                <div class="space-y-2">
                    <label class="form-label font-bold text-slate-300">Post Title</label>
                    <input type="text" id="post-title" class="form-field text-xl font-bold w-full bg-slate-900 border border-white/10 p-3 rounded-xl text-white focus:outline-none focus:border-blue-500" placeholder="Enter a compelling title...">
                </div>

                <div class="space-y-2">
                    <label class="form-label font-bold text-slate-300">Content Body</label>
                    <!-- Core CKEditor Mounting Container Box Wrapper -->
                    <div class="ck-editor-wrapper">
                        <div id="editor"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-4 space-y-6">
            <div class="admin-card space-y-4">
                <h4 class="font-bold text-white">Publishing Protocol</h4>
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="form-label text-xs text-slate-400">Post Type</label>
                        <select id="post-type" class="form-field text-sm w-full bg-slate-900 border border-white/10 p-2.5 rounded-xl text-white focus:outline-none">
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

<!-- Dependencies Engine Loads -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<script>
    let blogEditorInstance;

    // Custom File Pipeline Adapter to route clipboard & files safely to disk directories 
    class MyUploadAdapter {
        constructor(loader) {
            this.loader = loader;
        }

        upload() {
            return this.loader.file
                .then(file => new Promise((resolve, reject) => {
                    this._initRequest();
                    this._initListeners(resolve, reject, file);
                    this._sendRequest(file);
                }));
        }

        abort() {
            if (this.xhr) {
                this.xhr.abort();
            }
        }

        _initRequest() {
            const xhr = this.xhr = new XMLHttpRequest();
            xhr.open('POST', 'handlers/upload-media.php', true);
            xhr.responseType = 'json';
        }

        _initListeners(resolve, reject, file) {
            const xhr = this.xhr;
            const loader = this.loader;
            const genericErrorText = `Could not process file upload metadata stream: ${file.name}.`;

            xhr.addEventListener('error', () => reject(genericErrorText));
            xhr.addEventListener('abort', () => reject());
            xhr.addEventListener('load', () => {
                const response = xhr.response;
                if (!response || response.error) {
                    return reject(response && response.error ? response.error.message : genericErrorText);
                }
                resolve({ default: response.url });
            });

            if (xhr.upload) {
                xhr.upload.addEventListener('progress', evt => {
                    if (evt.lengthComputable) {
                        loader.uploadTotal = evt.total;
                        loader.uploaded = evt.loaded;
                    }
                });
            }
        }

        _sendRequest(file) {
            const data = new FormData();
            data.append('upload', file);
            this.xhr.send(data);
        }
    }

    // Plug core adapter definitions natively straight into CKEditor's initialization array
    function MyCustomUploadAdapterPlugin(editor) {
        editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
            return new MyUploadAdapter(loader);
        };
    }

    // Mount CKEditor with advanced clipboard filters active
    ClassicEditor
        .create(document.querySelector('#editor'), {
            placeholder: 'Draft updates, drop graphics, or write analysis metrics here...',
            toolbar: [ 'heading', '|', 'bold', 'italic', 'link', 'uploadImage', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo' ],
            extraPlugins: [MyCustomUploadAdapterPlugin]
        })
        .then(editor => {
            blogEditorInstance = editor;

            // Intercept direct external paste inputs to run them straight down our custom server upload path
            editor.editing.view.document.on('clipboardInput', (evt, data) => {
                const dataTransfer = data.dataTransfer;
                const htmlData = dataTransfer.getData('text/html');
                
                // If the incoming data fragment is a direct hotlinked image source string
                if (htmlData && htmlData.includes('<img')) {
                    // Let the FileRepository pipeline intercept and push it cleanly down the server disk route
                }
            });
        })
        .catch(error => {
            console.error('CKEditor component initialization error:', error);
            triggerToast('Failed to initialize advanced content workspace.', 'error');
        });

    function triggerToast(message, type = 'success') {
        const isSuccess = type === 'success';
        Toastify({
            text: message,
            duration: 4000,
            gravity: "top", 
            position: "right",
            stopOnFocus: true,
            style: {
                background: isSuccess ? "linear-gradient(135deg, #064e3b, #022c22)" : "linear-gradient(135deg, #4c0519, #881337)",
                color: isSuccess ? "#34d399" : "#f43f5e",
                border: isSuccess ? "1px solid rgba(52, 211, 153, 0.2)" : "1px solid rgba(244, 63, 94, 0.2)",
                borderRadius: "12px",
                fontFamily: "'Inter', sans-serif",
                fontSize: "14px",
                boxShadow: "0 10px 25px -5px rgba(0, 0, 0, 0.5)"
            }
        }).showToast();
    }

    function publishPost() {
        if (!blogEditorInstance) {
            triggerToast('The document editor canvas is still warming up.', 'error');
            return;
        }

        const titleInput = document.getElementById('post-title');
        const typeInput = document.getElementById('post-type');
        const verifiedInput = document.getElementById('is-verified');
        const publishBtn = document.getElementById('publish-btn');

        const title = titleInput.value.trim();
        const content = blogEditorInstance.getData().trim();
        const type = typeInput.value;
        const verified = verifiedInput.checked ? 1 : 0;

        if (!title || !content) {
            triggerToast('Validation Error: Form elements cannot contain empty strings.', 'error');
            return;
        }

        publishBtn.disabled = true;
        publishBtn.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Processing...`;

        fetch('handlers/process-blog.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                title: title,
                content: content,
                type: type,
                is_verified: verified
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                triggerToast(data.message, 'success');
                titleInput.value = "";
                blogEditorInstance.setData('');
            } else {
                triggerToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Request processing failure loop error:', error);
            triggerToast('An unhandled framework connectivity runtime fault occurred.', 'error');
        })
        .finally(() => {
            publishBtn.disabled = false;
            publishBtn.innerHTML = `<i class="fas fa-paper-plane mr-2"></i> Publish to Oracle`;
        });
    }
</script>

<style>
    /* Styling settings to marry CKEditor canvas seamlessly to your dark slate theme framework */
    .ck-editor-wrapper {
        --ck-color-base-border: rgba(255, 255, 255, 0.1);
        --ck-color-toolbar-background: #0f172a;
        --ck-color-editor-base-background: #020617;
        --ck-color-text: #f8fafc;
        --ck-color-input-background: #0f172a;
        --ck-color-button-default-hover-background: #1e293b;
        --ck-color-button-default-active-background: #2563eb;
    }
    
    .ck-editor__editable_inline {
        min-height: 400px !important;
        background-color: #020617 !important;
        color: #f8fafc !important;
        border-radius: 0 0 16px 16px !important;
        padding: 1.5rem !important;
    }

    .ck-toolbar {
        background-color: #0f172a !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        border-radius: 16px 16px 0 0 !important;
    }

    .ck.ck-reset_all, .ck.ck-reset_all * {
        color: #94a3b8 !important;
    }
    
    .ck.ck-editor__editable:not(.ck-editor__nested-editable).ck-focused {
        border-color: #3b82f6 !important;
        box-shadow: none !important;
    }
</style>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>