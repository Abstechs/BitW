<?php
// core/PredictionMarket.php

require_once __DIR__ . '/Ledger.php';
require_once __DIR__ . '/Settings.php';

class PredictionMarket {
    /**
     * Settle a prediction market and distribute the pool.
     */
    public static function settle($pdo, $marketId, $winnerOption) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT * FROM prediction_markets WHERE id = ? AND status = 'pending_result' FOR UPDATE");
            $stmt->execute([$marketId]);
            $market = $stmt->fetch();

            if (!$market) throw new Exception("Market not found or not ready for settlement.");

            // 1. Calculate pools
            $stmt = $pdo->prepare("SELECT selected_option, SUM(amount) as total FROM prediction_bets WHERE market_id = ? GROUP BY selected_option");
            $stmt->execute([$marketId]);
            $pools = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $winningPool = $pools[$winnerOption] ?? 0;
            $totalPool = $market['total_pool'];
            
            if ($winningPool <= 0) {
                // No winners, platform takes the pool or refund? Let's assume refund or platform takes it.
                $pdo->prepare("UPDATE prediction_markets SET status = 'cancelled' WHERE id = ?")->execute([$marketId]);
                $pdo->commit();
                return true;
            }

            // 2. Calculate Platform Commission
            $commission = $totalPool * ($market['commission_percent'] / 100);
            $distributablePool = $totalPool - $commission;

            // 3. Distribute to winners
            $stmt = $pdo->prepare("SELECT * FROM prediction_bets WHERE market_id = ? AND selected_option = ?");
            $stmt->execute([$marketId, $winnerOption]);
            $winners = $stmt->fetchAll();

            foreach ($winners as $w) {
                // Share = (Your Bet / Total Winning Pool) * Distributable Pool
                $share = ($w['amount'] / $winningPool) * $distributablePool;
                
                Ledger::record($pdo, $w['user_id'], $share, 'prediction_win', "MARKET-$marketId", ['bet_id' => $w['id']]);
                $pdo->prepare("UPDATE prediction_bets SET status = 'won' WHERE id = ?")->execute([$w['id']]);
            }

            // 4. Mark losers
            $pdo->prepare("UPDATE prediction_bets SET status = 'lost' WHERE market_id = ? AND selected_option != ?")
                ->execute([$marketId, $winnerOption]);

            $pdo->prepare("UPDATE prediction_markets SET status = 'settled', winner_option = ? WHERE id = ?")
                ->execute([$winnerOption, $marketId]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }
}
