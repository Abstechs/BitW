<?php
// core/EcosystemEngine.php

class EcosystemEngine {
    /**
     * Re-calculates asset price based on transaction momentum and organic drift.
     */
    public static function adjustPrice($pdo, $assetId, $tradeType, $amount) {
        // Fetch asset statistics
        $stmt = $pdo->prepare("SELECT * FROM trade_assets WHERE id = ?");
        $stmt->execute([$assetId]);
        $asset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$asset) return false;

        $currentPrice = floatval($asset['current_price']);
        $totalSupply = floatval($asset['total_supply']);
        
        // Impact factor: larger orders move the market more noticeably
        $impact = ($amount / $totalSupply) * 0.05; 
        
        // Micro-fluctuation variance (small noise to keep the graph dynamic)
        $noise = (rand(-100, 100) / 10000) * $currentPrice;

        if ($tradeType === 'buy') {
            $newPrice = $currentPrice * (1 + $impact) + $noise;
        } else { // sell
            $newPrice = $currentPrice * (1 - $impact) + $noise;
        }

        // Hard baseline floor protection: asset never drops below 20% of its initial value
        $floorLimit = floatval($asset['base_price']) * 0.20;
        if ($newPrice < $floorLimit) {
            $newPrice = $floorLimit;
        }

        // Log updated metrics into historical timeline matrices
        $update = $pdo->prepare("UPDATE trade_assets SET current_price = ? WHERE id = ?");
        $update->execute([$newPrice, $assetId]);

        $log = $pdo->prepare("INSERT INTO asset_price_history (asset_id, price) VALUES (?, ?)");
        $log->execute([$assetId, $newPrice]);

        return $newPrice;
    }
}