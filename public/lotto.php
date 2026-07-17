<?php
// public/lotto.php
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/ui.php';
require_once __DIR__ . '/pages/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <!-- Main Game Area -->
        <div class="col-lg-8">
            <div class="glass-card p-4 mb-4 neon-glow-purple">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="neon-text-gold mb-0">Lotto-Sovereign</h2>
                    <span class="badge bg-danger">Next Draw in <span id="countdown">23:59:59</span></span>
                </div>
                
                <p class="text-muted">Predict the 6-digit lucky number and win from the pool!</p>
                
                <form id="lottoBetForm" class="mt-4">
                    <div class="mb-4">
                        <label class="form-label">Your Predicted Number</label>
                        <input type="text" class="form-control lotto-number-input" maxlength="6" placeholder="000000" required>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Bet Amount</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-white">₦</span>
                                <input type="number" class="form-control bg-dark border-secondary text-white" placeholder="Min 100" min="100" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Potential Win</label>
                            <div class="form-control bg-dark border-secondary text-success">₦ <span id="potentialWin">0.00</span></div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="bitw-btn-primary py-3 fw-bold">PLACE BET NOW</button>
                        <button type="button" class="btn btn-outline-info">TRY DEMO MODE</button>
                    </div>
                </form>
            </div>
            
            <!-- History -->
            <div class="glass-card p-4">
                <h4 class="mb-4">Your Recent Bets</h4>
                <div class="table-responsive">
                    <table class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Number</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="lottoHistory">
                            <!-- Dynamically loaded -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Sidebar / Stats -->
        <div class="col-lg-4">
            <div class="glass-card p-4 mb-4 neon-glow-blue">
                <h4 class="neon-text-gold mb-3">Today's Pool</h4>
                <h1 class="display-4 fw-bold">₦ 1,245,000</h1>
                <hr class="border-secondary">
                <div class="d-flex justify-content-between text-muted">
                    <span>Total Participants</span>
                    <span>1,420</span>
                </div>
            </div>
            
            <div class="glass-card p-4">
                <h5 class="mb-3">How it Works</h5>
                <ul class="small text-muted ps-3">
                    <li class="mb-2">Pick a unique 6-digit number.</li>
                    <li class="mb-2">The system calculates the lucky number daily.</li>
                    <li class="mb-2">If no one picks the exact number, the closest low-liability number is chosen.</li>
                    <li class="mb-2">Demo mode wins do not pay real money but show you the engine's power.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/pages/footer.php'; ?>
