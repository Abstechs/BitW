/**
 * NetworkGuard.js
 * Monitors connectivity and provides visual feedback for weak networks.
 */
const NetworkGuard = {
    init() {
        window.addEventListener('online', () => this.notify(true));
        window.addEventListener('offline', () => this.notify(false));
        
        // Periodic latency check
        setInterval(() => this.checkLatency(), 10000);
    },

    notify(isOnline) {
        Toastify({
            text: isOnline ? "✅ Connection Restored. System Synchronized." : "🚨 Connection Lost. Transactional modules suspended.",
            duration: 5000,
            gravity: "top",
            position: "center",
            style: { background: isOnline ? "linear-gradient(to right, #059669, #10b981)" : "linear-gradient(to right, #dc2626, #f87171)" }
        }).showToast();
    },

    async checkLatency() {
        const start = Date.now();
        try {
            await fetch('/favicon.ico', { method: 'HEAD', cache: 'no-store' });
            const latency = Date.now() - start;
            
            if (latency > 1000) {
                Toastify({
                    text: "⚠️ Weak Network Detected. High-frequency trading may be delayed.",
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    style: { background: "linear-gradient(to right, #f59e0b, #d97706)" }
                }).showToast();
            }
        } catch (e) {
            // Handled by offline event
        }
    }
};

NetworkGuard.init();
