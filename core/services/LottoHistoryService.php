<?php
// src/LottoHistoryService.php

class LottoHistoryService {
    private $pdo;
    private $userId;

    public function __construct(PDO $pdo, int $userId) {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }

    /**
     * Sanitizes and parses the filtering criteria string argument.
     */
    public function getFilterMode(array $getRequest): string {
        return isset($getRequest['mode']) && in_array($getRequest['mode'], ['real', 'demo'], true) 
            ? $getRequest['mode'] 
            : 'all';
    }

    /**
     * Retrieves the entire filtered history log for the current authenticated user context,
     * joining with lotto_draws to include winning numbers and release statuses.
     */
        public function getLedgerEntries(string $filterMode = 'all'): array {
        try {
            // 1. Normalize the filter mode input
            $modeFilter = strtolower(trim($filterMode));

            $query = "SELECT 
                        a.sequence, 
                        a.amount, 
                        a.mode, 
                        a.status, 
                        a.draw_date, 
                        a.created_at,
                        d.lucky_number,
                        d.is_released
                    FROM lotto_allocations a
                    LEFT JOIN lotto_draws d 
                        ON a.draw_date = d.draw_date 
                        AND CHAR_LENGTH(a.sequence) = d.drawing_length
                    WHERE a.user_id = :user_id";
            
            $params = ['user_id' => $this->userId];
            
            // 2. Apply dynamic mode filtering
            if ($modeFilter === 'real') {
                $query .= " AND LOWER(a.mode) = 'real'";
            } elseif ($modeFilter === 'demo' || $modeFilter === 'sandbox') {
                // Accommodates either 'demo' or 'sandbox' saved in the DB
                $query .= " AND LOWER(a.mode) IN ('demo', 'sandbox')";
            }
            
            $query .= " ORDER BY a.created_at DESC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("History Matrix Ledger Query Exception Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculates data metric aggregations for rendering layout metrics cards.
     */
    public function getAggregatedStats(): array {
        $fallback = ['total_real_staked' => 0.00, 'total_demo_staked' => 0.00, 'total_positions' => 0];
        try {
            $statsQuery = "SELECT 
                SUM(CASE WHEN mode = 'real' THEN amount ELSE 0 END) as total_real_staked,
                SUM(CASE WHEN mode = 'demo' THEN amount ELSE 0 END) as total_demo_staked,
                COUNT(id) as total_positions
                FROM lotto_allocations WHERE user_id = :user_id";
                
            $statsStmt = $this->pdo->prepare($statsQuery);
            $statsStmt->execute(['user_id' => $this->userId]);
            $res = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            return $res ? [
                'total_real_staked' => (float)($res['total_real_staked'] ?? 0.00),
                'total_demo_staked' => (float)($res['total_demo_staked'] ?? 0.00),
                'total_positions'   => (int)($res['total_positions'] ?? 0)
            ] : $fallback;
        } catch (PDOException $e) {
            error_log("History Metric Stats Calculation Exception Error: " . $e->getMessage());
            return $fallback;
        }
    }
}