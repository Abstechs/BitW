<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/wallet.php';

/**
 * Get single plan
 */
function getPlan($id)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT *
        FROM plans
        WHERE id = ?
    ");

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
        SELECT *
        FROM plans
        WHERE status = 'active'
        ORDER BY price ASC
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

    // Unlimited purchase
    if ((int)$plan['purchase_limit'] === 0) {
        return true;
    }

    return $plan['total_purchased'] < $plan['purchase_limit'];
}

/**
 * Increment purchase count
 */
function incrementPlanPurchase($plan_id)
{
    global $pdo;

    $stmt = $pdo->prepare("
        UPDATE plans
        SET total_purchased = total_purchased + 1
        WHERE id = ?
    ");

    return $stmt->execute([$plan_id]);
}

/**
 * Purchase and activate a plan
 */
function purchasePlan($user_id, $plan_id)
{
    global $pdo;

    $plan = getPlan($plan_id);

    if (!$plan) {
        return [
            'status' => false,
            'message' => 'Plan not found'
        ];
    }

    if (!canPurchasePlan($plan_id)) {
        return [
            'status' => false,
            'message' => 'Plan limit reached'
        ];
    }

    $wallet = getWallet($user_id);

    if (!$wallet || $wallet['balance'] < $plan['price']) {
        return [
            'status' => false,
            'message' => 'Insufficient wallet balance'
        ];
    }

    $dailyReward = (
        $plan['price'] * $plan['yield_rate']
    ) / 100;

    $pdo->beginTransaction();

    try {

        debitWallet(
            $user_id,
            $plan['price'],
            'plan_purchase',
            'Purchased ' . $plan['name']
        );

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
            $plan['price'],
            $dailyReward,
            $plan['duration_days']
        ]);

        incrementPlanPurchase($plan_id);

        $pdo->commit();

        return [
            'status' => true,
            'message' => 'Plan activated successfully'
        ];

    } catch (Exception $e) {

        $pdo->rollBack();

        return [
            'status' => false,
            'message' => 'Activation failed'
        ];
    }
}