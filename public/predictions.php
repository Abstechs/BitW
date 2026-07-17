<?php
// public/predictions.php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/ui.php';
require_once __DIR__ . '/pages/header.php';
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="neon-text-gold">Social Prediction Markets</h1>
        <button class="bitw-btn-primary" data-bs-toggle="modal" data-bs-target="#createMarketModal">+ Create Market</button>
    </div>

    <div class="row g-4" id="predictionGrid">
        <!-- Sample Market Card -->
        <div class="col-md-6">
            <div class="glass-card p-4 neon-glow-blue h-100">
                <div class="d-flex justify-content-between mb-2">
                    <span class="badge bg-primary">Sports</span>
                    <span class="text-muted small">Ends in 4h 20m</span>
                </div>
                <h3>Arsenal will win against Chelsea tomorrow</h3>
                <p class="text-muted small">Created by @PremiumUser123 (Verified)</p>
                
                <div class="mt-4 mb-4">
                    <div class="progress bg-dark mb-2" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width: 65%"></div>
                        <div class="progress-bar bg-danger" style="width: 35%"></div>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-success">Agree: ₦ 450,000 (65%)</span>
                        <span class="text-danger">Disagree: ₦ 240,000 (35%)</span>
                    </div>
                </div>
                
                <div class="row g-2">
                    <div class="col-6">
                        <button class="btn btn-outline-success w-100 py-2">AGREE</button>
                    </div>
                    <div class="col-6">
                        <button class="btn btn-outline-danger w-100 py-2">DISAGREE</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- More cards will be loaded here -->
    </div>
</div>

<!-- Create Market Modal -->
<div class="modal fade" id="createMarketModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card p-4">
            <h3 class="neon-text-gold mb-4">Launch New Market</h3>
            <form id="createMarketForm">
                <div class="mb-3">
                    <label class="form-label">Market Title</label>
                    <input type="text" class="form-control bg-dark border-secondary text-white" placeholder="e.g. Bitcoin will hit $100k by Sunday" required>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label">Option A</label>
                        <input type="text" class="form-control bg-dark border-secondary text-white" value="Agree">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Option B</label>
                        <input type="text" class="form-control bg-dark border-secondary text-white" value="Disagree">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Commission Fee</label>
                    <select class="form-select bg-dark border-secondary text-white">
                        <option value="5">5% (Standard)</option>
                        <option value="10">10% (High Reward)</option>
                    </select>
                    <small class="text-muted">The platform takes this commission from the total pool.</small>
                </div>
                <button type="submit" class="bitw-btn-primary w-100">LAUNCH MARKET</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/pages/footer.php'; ?>
