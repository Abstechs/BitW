// public/assets/js/chart.js
class NativeTradeChart {
    constructor(canvasId, dataPoints) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.data = dataPoints; // Array of numeric price variations
    }

    render() {
        const ctx = this.ctx;
        const width = this.canvas.width;
        const height = this.canvas.height;
        ctx.clearRect(0, 0, width, height);

        if (this.data.length < 2) return;

        const max = Math.max(...this.data) * 1.02;
        const min = Math.min(...this.data) * 0.98;
        const range = max - min;

        // Draw structural grid paths
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.03)';
        ctx.lineWidth = 1;
        for (let i = 1; i < 4; i++) {
            let y = (height / 4) * i;
            ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(width, y); ctx.stroke();
        }

        // Map rendering coordinates
        const points = this.data.map((val, index) => {
            return {
                x: (width / (this.data.length - 1)) * index,
                y: height - ((val - min) / range) * height
            };
        });

        // Determine look and feel color scheme based on recent trajectory movement
        const color = this.data[this.data.length - 1] >= this.data[0] ? '#10b981' : '#f43f5e';

        // Render filled gradient field area underneath the path line
        let gradient = ctx.createLinearGradient(0, 0, 0, height);
        gradient.addColorStop(0, color + '22');
        gradient.addColorStop(1, 'transparent');
        ctx.fillStyle = gradient;
        ctx.beginPath();
        ctx.moveTo(0, height);
        points.forEach(p => ctx.lineTo(p.x, p.y));
        ctx.lineTo(width, height);
        ctx.fill();

        // Draw solid asset line trajectory wireframe
        ctx.strokeStyle = color;
        ctx.lineWidth = 2.5;
        ctx.lineJoin = 'round';
        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        points.forEach(p => ctx.lineTo(p.x, p.y));
        ctx.stroke();
    }
}