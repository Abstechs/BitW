// public/assets/js/chart.js
class NativeTradeChart {
    constructor(canvasId, dataPoints) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) return;
        this.ctx = this.canvas.getContext('2d');
        this.data = dataPoints.map(Number);
    }

    render() {
        const ctx = this.ctx;
        if (!ctx) return;
        
        const width = this.canvas.width;
        const height = this.canvas.height;
        ctx.clearRect(0, 0, width, height);

        if (this.data.length < 2) return;

        const max = Math.max(...this.data) * 1.01;
        const min = Math.min(...this.data) * 0.99;
        const range = max - min === 0 ? 1 : max - min;

        // Draw structural ecosystem baseline mesh lines
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.03)';
        ctx.lineWidth = 1;
        for (let i = 1; i < 5; i++) {
            let y = (height / 5) * i;
            ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(width, y); ctx.stroke();
        }

        // Map positions onto Canvas space bounds
        const points = this.data.map((val, index) => {
            return {
                x: (width / (this.data.length - 1)) * index,
                y: height - ((val - min) / range) * height
            };
        });

        // Detect direction to determine trend colors (Green for upward trend, red for drops)
        const isUpwardTrend = this.data[this.data.length - 1] >= this.data[0];
        const primaryColor = isUpwardTrend ? '#10b981' : '#f43f5e';

        // Draw linear ambient alpha drop glow matching vector space pathing arrays
        let gradient = ctx.createLinearGradient(0, 0, 0, height);
        gradient.addColorStop(0, primaryColor + '25');
        gradient.addColorStop(1, 'transparent');
        ctx.fillStyle = gradient;
        
        ctx.beginPath();
        ctx.moveTo(0, height);
        points.forEach(p => ctx.lineTo(p.x, p.y));
        ctx.lineTo(width, height);
        ctx.fill();

        // Draw absolute structural coordinate path vector wire line
        ctx.strokeStyle = primaryColor;
        ctx.lineWidth = 3;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        
        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        points.forEach(p => ctx.lineTo(p.x, p.y));
        ctx.stroke();
    }
}