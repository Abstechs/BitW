<?php
// public/admin/sovereign-settings.php
require_once __DIR__ . '/includes/admin_init.php';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Sovereign Control Center</h1>
        <button class="btn btn-primary" id="saveAllSettings">Apply Changes</button>
    </div>

    <div class="row">
        <!-- Market Math -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Market Math Constants</h6>
                </div>
                <div class="card-body">
                    <form>
                        <div class="mb-3">
                            <label class="form-label">Global Volatility Multiplier</label>
                            <input type="range" class="form-range" min="0.1" max="5.0" step="0.1" value="1.0">
                            <div class="d-flex justify-content-between small text-muted">
                                <span>Low (Stable)</span>
                                <span>High (Chaotic)</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mean Reversion Speed</label>
                            <input type="number" class="form-control" value="0.05" step="0.01">
                            <small class="text-muted">How fast prices pull back to the Gravity Point.</small>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Platform Toggles -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">System Toggles</h6>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" checked>
                        <label class="form-check-label">P2P Market Enabled</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" checked>
                        <label class="form-check-label">Lotto-Sovereign Engine Active</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox">
                        <label class="form-check-label">Maintenance Mode</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
