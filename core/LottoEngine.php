<?php
// core/LottoEngine.php

require_once __DIR__ . '/Ledger.php';
require_once __DIR__ . '/Settings.php';

class LottoEngine {
    /**
     * Run the daily draw logic.
     * High-level logic to pick the "missing" or "lowest-risk" number.
     */
    public static function runDraw($pdo, $gameId) {
        $stmt = $pdo->prepare("SELECT * FROM lotto_games WHERE id = ? AND status = 'open'");
        $stmt->execute([$gameId]);
        $game = $stmt->fetch();

        if (!$game) return false;

        // 1. Get all bets for this game
        $stmt = $pdo->prepare("SELECT predicted_number, SUM(amount) as total_bet, COUNT(*) as bet_count, is_demo 
                               FROM lotto_bets WHERE game_id = ? GROUP BY predicted_number, is_demo");
        $stmt->execute([$gameId]);
        $bets = $stmt->fetchAll();

        // 2. Algorithm to find the "Lucky Number"
        // Priority 1: A number that NO ONE picked (Missing Number)
        // Priority 2: The number with the absolute lowest payout risk for the platform
        
        $luckyNumber = self::calculateOptimalLuckyNumber($bets);

        // 3. Close game and set lucky number
        $pdo->prepare("UPDATE lotto_games SET lucky_number = ?, status = 'closed' WHERE id = ?")
            ->execute([$luckyNumber, $gameId]);

        // 4. Process winners
        self::settleBets($pdo, $gameId, $luckyNumber);

        return $luckyNumber;
    }

    private static function calculateOptimalLuckyNumber($bets) {
        // Generate a random 6-digit number as a baseline
        $candidate = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Logic to find a missing number or the one with the lowest "Real Money" liability
        $pickedNumbers = array_column($bets, 'predicted_number');
        
        if (!in_array($candidate, $pickedNumbers)) {
            return $candidate; // Missing number found!
        }

        // If all common numbers are picked, find the one with the lowest total_bet where is_demo = 0
        usort($bets, function($a, $b) {
            if ($a['is_demo'] != $b['is_demo']) return $a['is_demo'] - $b['is_demo'];
            return $a['total_bet'] - $b['total_bet'];
        });

        return $bets[0]['predicted_number'];
    }

    private static function settleBets($pdo, $gameId, $luckyNumber) {
        // Mark losers
        $pdo->prepare("UPDATE lotto_bets SET status = 'lost' WHERE game_id = ? AND predicted_number != ?")
            ->execute([$gameId, $luckyNumber]);

        // Mark winners
        $stmt = $pdo->prepare("SELECT * FROM lotto_bets WHERE game_id = ? AND predicted_number = ?");
        $stmt->execute([$gameId, $luckyNumber]);
        $winners = $stmt->fetchAll();

        foreach ($winners as $w) {
            $pdo->prepare("UPDATE lotto_bets SET status = 'won' WHERE id = ?")->execute([$w['id']]);
            
            // Only pay out real money if it wasn't a demo bet
            if ($w['is_demo'] == 0) {
                Ledger::record($pdo, $w['user_id'], $w['potential_win'], 'lotto_win', "GAME-$gameId");
            }
        }
    }
}
