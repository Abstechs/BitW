<?php
// core/services/LottoResultService.php

class LottoResultService {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Gets the confirmed winning configuration vector specifically for Yesterday's draw.
     */
    public function getYesterdayResult(): ?array {
        try {
            // Target the draw path explicitly linked to the calendar day prior to today
            $yesterdayDate = date('Y-m-d', strtotime('yesterday'));
            
            $query = "SELECT winning_sequence, draw_date, total_prize_pool, total_winners 
                      FROM lotto_draws 
                      WHERE draw_date = :yesterday 
                      LIMIT 1";
                      
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(['yesterday' => $yesterdayDate]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Failed to extract yesterday's draw results matrix: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieves previous historical winning matrices for comprehensive data verification.
     */
    public function getHistoricalResults(int $limit = 15): array {
        try {
            $query = "SELECT winning_sequence, draw_date, total_prize_pool, total_winners 
                      FROM lotto_draws 
                      WHERE draw_date < :today
                      ORDER BY draw_date DESC 
                      LIMIT :limit";
                      
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':today', date('Y-m-d'), PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Failed to extract historical matrix arrays: " . $e->getMessage());
            return [];
        }
    }
}