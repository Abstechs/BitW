<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/notifications.php';

function createOrder($user_id, $item_id, $amount, $type = 'purchase', $status = 'completed', $description = '') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, item_id, amount, type, status, description, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    return $stmt->execute([$user_id, $item_id, $amount, $type, $status, $description]);
}

function getOrders($user_id, $limit = 20) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    
    // Explicitly bind parameters with their correct data types
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function addToWishlist($user_id, $plan_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM wishlists WHERE user_id = ? AND plan_id = ? LIMIT 1");
    $stmt->execute([$user_id, $plan_id]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Stone already in wishlist'];
    }

    $insert = $pdo->prepare("INSERT INTO wishlists (user_id, plan_id, created_at) VALUES (?, ?, NOW())");
    $insert->execute([$user_id, $plan_id]);
    addNotification($user_id, 'Stone saved to wishlist', 'The stone is ready for later purchase.');
    return ['success' => true, 'message' => 'Stone added to wishlist'];
}

function removeFromWishlist($user_id, $plan_id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM wishlists WHERE user_id = ? AND plan_id = ?");
    $stmt->execute([$user_id, $plan_id]);
    addNotification($user_id, 'Stone removed from wishlist', 'The stone has been removed.');
    return ['success' => true, 'message' => 'Stone removed from wishlist'];
}

function getWishlist($user_id) {
    global $pdo;
    // Explicitly renaming columns to prevent collisions
    $stmt = $pdo->prepare("SELECT w.id AS wishlist_row_id, w.plan_id, p.id AS plan_id, p.name, p.image, p.daily_rate, p.duration_days, p.min_amount, p.max_amount 
                          FROM wishlists w 
                          JOIN plans p ON p.id = w.plan_id 
                          WHERE w.user_id = ? 
                          ORDER BY w.created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getWithdrawalRequests($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function requestWithdrawal($user_id, $amount, $method = 'wallet') {
    global $pdo;
    $wallet = getWallet($user_id);

    if (!$wallet || $wallet['balance'] < $amount) {
        return ['success' => false, 'message' => 'Insufficient balance'];
    }

    $pdo->beginTransaction();
    try {
        debitWallet($user_id, $amount, 'withdrawal', 'Withdrawal request');
        $stmt = $pdo->prepare("INSERT INTO withdrawals (user_id, amount, method, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
        $stmt->execute([$user_id, $amount, $method]);
        $pdo->commit();
        addNotification($user_id, 'Withdrawal requested', 'Your withdrawal is under review.');
        return ['success' => true, 'message' => 'Withdrawal requested successfully'];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Withdrawal request failed'];
    }
}

function getReferralSummary($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_referrals, COALESCE(SUM(bonus_amount), 0) as total_bonus FROM referrals WHERE referrer_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function recordReferralSignup($referrerId, $newUserId) {
    global $pdo;
    $bonus = 100.00;
    $stmt = $pdo->prepare("INSERT INTO referrals (referrer_id, referred_id, bonus_amount, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$referrerId, $newUserId, $bonus]);
    creditWallet($referrerId, $bonus, 'referral_bonus', 'Referral bonus');
    $pdo->prepare("UPDATE users SET total_referrals = total_referrals + 1, referral_earnings = referral_earnings + ? WHERE id = ?")->execute([$bonus, $referrerId]);
    addNotification($referrerId, 'Referral bonus earned', 'A new user joined through your link.');
}