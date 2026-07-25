<?php
// core/mining.php - Mining logic only (purchasePlan stays in plans.php)

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/wallet.php';
require_once __DIR__ . '/ranking.php';
require_once __DIR__ . '/notifications.php';

function claimMining($mining_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_mining WHERE id = ? AND status = 'active'");
        $stmt->execute([$mining_id]);
        $mining = $stmt->fetch();

        // Check for expiration
        if (isset($mining['end_date']) && strtotime($mining['end_date']) < time()) {
            $pdo->prepare("UPDATE user_mining SET status = 'completed' WHERE id = ?")->execute([$mining_id]);
            return ['success' => false, 'message' => 'This mining session has expired.'];
        }
        
        if (!$mining) {
            return ['success' => false, 'message' => 'Mining session not found'];
        }
        
        // Check if daily claim is allowed (simple 24h logic)
        $last_claim = strtotime($mining['last_claim'] ?? '0000-00-00');
        if (time() - $last_claim < 86400) {
            return ['success' => false, 'message' => 'You can only claim once per day'];
        }
        
        // Calculate earnings (basic)
        $daily_earnings = $mining['daily_earning'] ?? $mining['daily_earnings'] ?? 0;
        
        // Apply rank bonus
        $bonus = getRankBonus($mining['user_id']);
        $total = $daily_earnings * (1 + $bonus);
        
        // Credit wallet
        creditWallet($mining['user_id'], $total, "Mining Claim - Session #$mining_id");
        
        // Update last claim
        $stmt = $pdo->prepare("UPDATE user_mining SET last_claim = NOW(), total_earned = total_earned + ? WHERE id = ?");
        $stmt->execute([$total, $mining_id]);
        
        // Add notification
        addNotification($mining['user_id'], "You claimed ₦" . number_format($total, 2) . " from mining");
        
        return ['success' => true, 'amount' => $total];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Claim failed'];
    }
}

// Other mining helper functions can go here
function getActiveMinings($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT um.*, p.name as plan_name, p.description, p.background_story, p.read_more_link, p.image, p.daily_rate 
                          FROM user_mining um 
                          JOIN plans p ON um.plan_id = p.id 
                          WHERE um.user_id = ? AND um.status = 'active'");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}
?>