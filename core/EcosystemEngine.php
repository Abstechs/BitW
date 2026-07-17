<?php
// core/EcosystemEngine.php

require_once __DIR__ . '/Settings.php';

class EcosystemEngine {
    /**
     * Re-calculates asset price using a Stochastic Mean-Reverting Model.
     * This makes the market "breathe" and prevents easy predictability.
     */
    public static function adjustPrice($pdo, $assetId, $tradeType, $amount) {
        // Fetch asset statistics
        $stmt = $pdo->prepare("SELECT * FROM trade_assets WHERE id = ?");
        $stmt->execute([$assetId]);
        $asset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$asset) return false;

        $currentPrice = floatval($asset['current_price']);
        $gravityPrice = floatval($asset['gravity_price'] ?? $asset['base_price']);
        $volatility = floatval($asset['volatility_constant'] ?? 0.02);
        $reversionSpeed = floatval($asset['mean_reversion_speed'] ?? 0.05);
        $liquidity = floatval($asset['liquidity_depth'] ?? 1000000);
        
        // 1. Mean Reversion Component (The "Gravity" pull)
        // dP_gravity = speed * (Gravity - Current)
        $gravityPull = $reversionSpeed * ($gravityPrice - $currentPrice);

        // 2. Momentum Component (Impact of the trade)
        // Impact is relative to liquidity depth
        $impactFactor = ($amount / $liquidity);
        $momentum = ($tradeType === 'buy') ? ($currentPrice * $impactFactor) : (-$currentPrice * $impactFactor);

        // 3. Stochastic Noise (The "Random Walk")
        // Using Box-Muller transform for Gaussian noise
        $u1 = mt_rand() / mt_getrandmax();
        $u2 = mt_rand() / mt_getrandmax();
        $z = sqrt(-2 * log($u1)) * cos(2 * pi() * $u2);
        
        $globalVolMult = floatval(Settings::get($pdo, 'global_volatility_multiplier', 1.0));
        $randomNoise = $currentPrice * $volatility * $globalVolMult * $z;

        // Final Price Calculation: Current + Gravity + Momentum + Noise
        $newPrice = $currentPrice + $gravityPull + $momentum + $randomNoise;

        // Hard baseline floor protection (20% of base_price)
        $floorLimit = floatval($asset['base_price']) * 0.20;
        if ($newPrice < $floorLimit) {
            $newPrice = $floorLimit;
        }

        // Log updated metrics
        $update = $pdo->prepare("UPDATE trade_assets SET current_price = ? WHERE id = ?");
        $update->execute([$newPrice, $assetId]);

        $log = $pdo->prepare("INSERT INTO asset_price_history (asset_id, price) VALUES (?, ?)");
        $log->execute([$assetId, $newPrice]);

        return $newPrice;
    }
}
