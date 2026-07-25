<?php
// src/LottoDrawEngineService.php

class LottoDrawEngineService {
    private PDO $pdo;
    private float $houseEdgeMultiplier;

    public function __construct(PDO $pdo, float $houseEdgeMultiplier = 0.80) {
        $this->pdo = $pdo;
        $this->houseEdgeMultiplier = $houseEdgeMultiplier;
    }
    /**
     * Main entry point to process daily draws across all configured digit lengths.
     */
    public function processDailyDraws(string $drawDate, array $digitLengths = [4, 6, 8], string $triggeredBy = 'system'): array {
        $results = [];
        foreach ($digitLengths as $length) {
            $results[$length] = $this->settleDrawForLength($drawDate, $length, $triggeredBy);
        }
        return $results;
    }

    /**
     * Executes draw evaluation and wallet settlements for a specific sequence length.
     */
    public function settleDrawForLength(string $drawDate, int $drawingLength, string $triggeredBy = 'admin'): array {
    try {
        $this->pdo->beginTransaction();

        // 1. Check if draw entry exists and lock the row
        $checkStmt = $this->pdo->prepare("
            SELECT id, lucky_number FROM lotto_draws 
            WHERE draw_date = :draw_date AND drawing_length = :length 
            FOR UPDATE
        ");
        $checkStmt->execute(['draw_date' => $drawDate, 'length' => $drawingLength]);
        $existingDraw = $checkStmt->fetch(PDO::FETCH_ASSOC);

        // If draw is already fully settled with a lucky number, abort
        if ($existingDraw && !empty($existingDraw['lucky_number'])) {
            $this->pdo->rollBack();
            return [
                'status'  => 'skipped', 
                'message' => "Draw for {$drawingLength}-digits on {$drawDate} is already settled."
            ];
        }

        // 2. Fetch pending allocations for this specific length
        $allocStmt = $this->pdo->prepare("
            SELECT id, user_id, sequence, amount, mode 
            FROM lotto_allocations 
            WHERE draw_date = :draw_date 
              AND CHAR_LENGTH(sequence) = :length 
              AND status = 'pending'
            FOR UPDATE
        ");
        $allocStmt->execute(['draw_date' => $drawDate, 'length' => $drawingLength]);
        $allocations = $allocStmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Determine Optimal Lucky Number
        $luckyNumber = $this->selectOptimalLuckyNumber($allocations, $drawingLength);

        // 4. Update existing draft record OR insert new draw entry
        if ($existingDraw) {
            $updateDraw = $this->pdo->prepare("
                UPDATE lotto_draws 
                SET lucky_number = :lucky_number, is_released = 1, updated_at = NOW() 
                WHERE id = :id
            ");
            $updateDraw->execute([
                'lucky_number' => $luckyNumber, 
                'id'           => $existingDraw['id']
            ]);
        } else {
            $insertDraw = $this->pdo->prepare("
                INSERT INTO lotto_draws (draw_date, drawing_length, lucky_number, is_released, created_at) 
                VALUES (:draw_date, :length, :lucky_number, 1, NOW())
            ");
            $insertDraw->execute([
                'draw_date'    => $drawDate,
                'length'       => $drawingLength,
                'lucky_number' => $luckyNumber
            ]);
        }

        // 5. Evaluate and Settle Positions
        $settledCount = 0;
        $totalPayout = 0.00;

        $updAlloc = $this->pdo->prepare("UPDATE lotto_allocations SET status = :status WHERE id = :id");
        $updWalletReal = $this->pdo->prepare("UPDATE wallets SET balance = balance + :payout WHERE user_id = :user_id");
        $updWalletDemo = $this->pdo->prepare("UPDATE wallets SET demo_balance = demo_balance + :payout WHERE user_id = :user_id");
        $logTx = $this->pdo->prepare("
            INSERT INTO transactions (user_id, amount, type, description, created_at) 
            VALUES (:user_id, :amount, 'deposit', :desc, NOW())
        ");

        foreach ($allocations as $alloc) {
            $matchCount = $this->calculateMatchScore($alloc['sequence'], $luckyNumber);
            $payout = $this->calculateTieredPayout((float)$alloc['amount'], $matchCount, $drawingLength);
            $status = ($payout > 0) ? 'won' : 'lost';

            // Update status
            $updAlloc->execute(['status' => $status, 'id' => $alloc['id']]);

            // Credit winnings
            if ($payout > 0) {
                if ($alloc['mode'] === 'real') {
                    $updWalletReal->execute(['payout' => $payout, 'user_id' => $alloc['user_id']]);
                    $logTx->execute([
                        'user_id' => $alloc['user_id'],
                        'amount'  => $payout,
                        'desc'    => "Lotto Win Payout ({$drawingLength}-Digit Match: {$matchCount})"
                    ]);
                } else {
                    $updWalletDemo->execute(['payout' => $payout, 'user_id' => $alloc['user_id']]);
                }
                $totalPayout += $payout;
            }
            $settledCount++;
        }

        $this->pdo->commit();

        return [
            'status'            => 'success',
            'drawing_length'    => $drawingLength,
            'lucky_number'      => $luckyNumber,
            'settled_positions' => $settledCount,
            'total_payout'      => $totalPayout,
            'triggered_by'      => $triggeredBy
        ];

    } catch (\Throwable $e) {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        error_log("Lotto Settlement Exception ({$drawingLength}-Digit): " . $e->getMessage());
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

    /**
     * Determines the winning sequence based on risk exposure and pool priority.
     */
    private function selectOptimalLuckyNumber(array $allocations, int $length): string {
        $realPoolStaked = 0.00;
        $realPredictions = [];
        $demoPredictions = [];

        foreach ($allocations as $alloc) {
            if ($alloc['mode'] === 'real') {
                $realPoolStaked += (float)$alloc['amount'];
                $realPredictions[] = $alloc['sequence'];
            } else {
                $demoPredictions[] = $alloc['sequence'];
            }
        }

        $maxAllowedPayout = $realPoolStaked * $this->houseEdgeMultiplier;

        // Priority 1: Pick a sequence from Demo predictions that does NOT collide with Real predictions
        $safeDemoCandidates = array_diff($demoPredictions, $realPredictions);
        if (!empty($safeDemoCandidates)) {
            return reset($safeDemoCandidates);
        }

        // Priority 2: Generate candidate sequences and pick the one with zero real liability
        for ($i = 0; $i < 100; $i++) {
            $candidate = str_pad((string)random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
            if (!in_array($candidate, $realPredictions, true)) {
                return $candidate; // Zero real payout sequence
            }
        }

        // Priority 3 (Fallback): Generate pseudo-random sequence
        return str_pad((string)random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Evaluates matching digits (right-aligned comparison).
     */
    public function calculateMatchScore(string $predicted, string $winning): int {
        $predLen = strlen($predicted);
        $winLen = strlen($winning);
        if ($predLen !== $winLen) {
            return 0;
        }

        $matches = 0;
        for ($i = $predLen - 1; $i >= 0; $i--) {
            if ($predicted[$i] === $winning[$i]) {
                $matches++;
            } else {
                break; // Sequential right-to-left match rule
            }
        }
        return $matches;
    }

    /**
     * Multiplier lookup based on digit tier and match count.
     */
    private function calculateTieredPayout(float $stake, int $matchCount, int $length): float {
        if ($matchCount < 2) {
            return 0.00;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT multiplier 
                FROM lotto_tier_settings 
                WHERE drawing_length = :length AND match_count = :match_count 
                LIMIT 1
            ");
            $stmt->execute([
                'length' => $length,
                'match_count' => $matchCount
            ]);
            
            $multiplier = $stmt->fetchColumn();
            return $multiplier !== false ? $stake * (float)$multiplier : 0.00;
        } catch (PDOException $e) {
            error_log("Failed to fetch tier multiplier: " . $e->getMessage());
            return 0.00;
        }
    }
}