/**
 * SovereignTimer.js
 * High-precision live countdown timers for BitW modules.
 */
class SovereignTimer {
    constructor(elementId, endTime, callback = null) {
        this.element = document.getElementById(elementId);
        this.endTime = new Date(endTime).getTime();
        this.callback = callback;
        this.interval = null;
    }

    start() {
        this.update();
        this.interval = setInterval(() => this.update(), 1000);
    }

    update() {
        const now = new Date().getTime();
        const distance = this.endTime - now;

        if (distance < 0) {
            clearInterval(this.interval);
            if (this.element) this.element.innerHTML = "EXPIRED";
            if (this.callback) this.callback();
            return;
        }

        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        const timeString = 
            String(hours).padStart(2, '0') + ":" + 
            String(minutes).padStart(2, '0') + ":" + 
            String(seconds).padStart(2, '0');

        if (this.element) {
            this.element.innerHTML = timeString;
        }
    }
}
