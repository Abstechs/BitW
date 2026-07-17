<?php
// core/CronManager.php

require_once __DIR__ . '/LottoEngine.php';
require_once __DIR__ . '/Settings.php';

class CronManager {
    /**
     * This is triggered by the first user login/activity of the day.
     */
    public static function triggerDailyTasks($pdo) {
        $lastRun = Settings::get($pdo, 'last_cron_run', '1970-01-01');
        $today = date('Y-m-d');

        if ($lastRun === $today) return; // Already run today

        // 1. Process Lotto-Sovereign Draw
        self::processLottoDraw($pdo);

        // 2. Process Expired Mining Plans
        self::processExpiredPlans($pdo);

        // 3. Update Cron Last Run Date
        Settings::set($pdo, 'last_cron_run', $today, 'system', 'Last date the daily cron was triggered.');
    }

    private static function processLottoDraw($pdo) {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // Find open games from yesterday or earlier
        $stmt = $pdo->prepare("SELECT id FROM lotto_games WHERE status = 'open' AND draw_date <= ?");
        $stmt->execute([$yesterday]);
        $games = $stmt->fetchAll();

        foreach ($games as $g) {
            LottoEngine::runDraw($pdo, $g['id']);
        }
    }

    private static function processExpiredPlans($pdo) {
        $pdo->prepare("UPDATE user_mining SET status = 'completed' WHERE status = 'active' AND end_date < NOW()")
            ->execute();
    }
}
