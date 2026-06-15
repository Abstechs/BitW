<?php
// core/plans.php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/wallet.php';

/**
 * Get single plan
 */
function getPlan($id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
    $stmt->execute([$id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get all active plans
 */
function getActivePlans()
{
    global $pdo;

    $stmt = $pdo->query("
        SELECT * FROM plans 
        WHERE status = 'active' 
        ORDER BY min_amount ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Check if plan can still be purchased
 */
function canPurchasePlan($plan_id)
{
    $plan = getPlan($plan_id);

    if (!$plan) {
        return false;
    }

    return true;
}

/**
 * Purchase and activate a plan
 */
function purchasePlan($user_id, $plan_id, $amount)
{
    global $pdo;

    $plan = getPlan($plan_id);

    if (!$plan) {
        return [
            'status' => false,
            'message' => 'Plan not found'
        ];
    }

    // Validate amount is within plan range
    if ($amount < $plan['min_amount']) {
        return [
            'status' => false,
            'message' => 'Amount is below the minimum for this plan'
        ];
    }

    if ($plan['max_amount'] !== null && $amount > $plan['max_amount']) {
        return [
            'status' => false,
            'message' => 'Amount exceeds the maximum for this plan'
        ];
    }

    $wallet = getWallet($user_id);

    if (!$wallet || $wallet['balance'] < $amount) {
        return [
            'status' => false,
            'message' => 'Insufficient wallet balance'
        ];
    }

    $dailyReward = ($amount * $plan['daily_rate']) / 100;

    $pdo->beginTransaction();

    try {
        debitWallet($user_id, $amount);

        $stmt = $pdo->prepare("
            INSERT INTO user_mining (
                user_id,
                plan_id,
                purchase_amount,
                daily_reward,
                duration_days
            )
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $user_id,
            $plan_id,
            $amount,
            $dailyReward,
            $plan['duration_days']
        ]);

        $pdo->commit();

        return [
            'status' => true,
            'message' => 'Plan activated successfully'
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        return [
            'status' => false,
            'message' => 'Activation failed: ' . $e->getMessage()
        ];
    }
}