/**
 * SovereignPulse.js
 * A high-performance, zero-dependency real-time charting engine for BitW.
 */
class SovereignPulse {
    constructor(canvasId, basePrice) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.points = [];
        this.currentPrice = basePrice;
        this.maxPoints = 100;
        this.isRunning = false;
        
        // Configuration
        this.colors = {
            up: '#10b981',
            down: '#f43f5e',
            line: '#3b82f6',
            grid: 'rgba(255, 255, 255, 0.05)'
        };
    }

    start() {
        this.isRunning = true;
        this.animate();
    }

    updatePrice(newPrice) {
        this.points.push(newPrice);
        if (this.points.length > this.maxPoints) {
            this.points.shift();
        }
        this.currentPrice = newPrice;
    }

    animate() {
        if (!this.isRunning) return;
        this.render();
        requestAnimationFrame(() => this.animate());
    }

    render() {
        const { ctx, canvas, points } = this;
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        if (points.length < 2) return;

        const max = Math.max(...points) * 1.001;
        const min = Math.min(...points) * 0.999;
        const range = max - min;
        const stepX = canvas.width / (this.maxPoints - 1);

        // Draw Grid
        ctx.strokeStyle = this.colors.grid;
        ctx.lineWidth = 1;
        for (let i = 0; i < 5; i++) {
            const y = (canvas.height / 4) * i;
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(canvas.width, y);
            ctx.stroke();
        }

        // Draw Line
        ctx.beginPath();
        ctx.strokeStyle = this.colors.line;
        ctx.lineWidth = 3;
        ctx.lineJoin = 'round';

        points.forEach((p, i) => {
            const x = i * stepX;
            const y = canvas.height - ((p - min) / range) * canvas.height;
            if (i === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
        });
        ctx.stroke();

        // Draw Gradient Fill
        const grad = ctx.createLinearGradient(0, 0, 0, canvas.height);
        grad.addColorStop(0, 'rgba(59, 130, 246, 0.2)');
        grad.addColorStop(1, 'rgba(59, 130, 246, 0)');
        
        ctx.lineTo((points.length - 1) * stepX, canvas.height);
        ctx.lineTo(0, canvas.height);
        ctx.fillStyle = grad;
        ctx.fill();
        
        // Draw Current Price Dot
        const lastX = (points.length - 1) * stepX;
        const lastY = canvas.height - ((this.currentPrice - min) / range) * canvas.height;
        
        ctx.beginPath();
        ctx.arc(lastX, lastY, 6, 0, Math.PI * 2);
        ctx.fillStyle = this.colors.line;
        ctx.fill();
        ctx.strokeStyle = '#fff';
        ctx.lineWidth = 2;
        ctx.stroke();
    }
}
