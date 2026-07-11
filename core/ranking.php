<?php
// core/ranking.php
require_once __DIR__ . '/database.php';

function getRankBonus($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT rank_level FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $rank = $stmt->fetchColumn();
    
    $bonuses = [0 => 0, 1 => 0.05, 2 => 0.10, 3 => 0.15]; // 0%, 5%, 10%, 15%
    return $bonuses[$rank] ?? 0;
}

function updateUserRank($user_id) {
    global $pdo;
    // Simple rank logic based on referrals or total earned (expand later)
    $stmt = $pdo->prepare("UPDATE users SET rank_level = LEAST(rank_level + 1, 3) WHERE id = ?");
    $stmt->execute([$user_id]);
}
?>