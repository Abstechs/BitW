<?php

require_once __DIR__ . "/database.php";
require_once __DIR__ . "/wallet.php";
require_once __DIR__ . "/plans.php";

if (!function_exists('creditWallet')) {
    function creditWallet($user_id, $amount, $type, $description)
    {
        return false;
    }
}

/**
 * Check if user can claim
 */
function checkClaimEligibility($mine)
{
    $today = date("Y-m-d");

    if ($mine['last_claim_date'] === $today) {
        return false;
    }

    return true;
}

/**
 * Claim Mining Reward
 */
function claimMiningReward($mine_id, $user_id)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT *
        FROM user_mining
        WHERE id = ?
        AND user_id = ?
        AND status = 'active'
    ");

    $stmt->execute([
        $mine_id,
        $user_id
    ]);

    $mine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mine) {
        return [
            'status' => false,
            'message' => 'Mining record not found'
        ];
    }

    if (!checkClaimEligibility($mine)) {
        return [
            'status' => false,
            'message' => 'Already claimed today'
        ];
    }

    $plan = getPlan($mine['plan_id']);

    if (!$plan) {
        return [
            'status' => false,
            'message' => 'Plan not found'
        ];
    }

    $reward = (
        $plan['price'] *
        $plan['yield_rate']
    ) / 100;

    creditWallet(
        $user_id,
        $reward,
        "mining",
        "Mining reward"
    );

    $newDay = $mine['current_day'] + 1;

    $stmt = $pdo->prepare("
        UPDATE user_mining
        SET
            current_day = ?,
            last_claim_date = ?,
            total_earned = total_earned + ?
        WHERE id = ?
    ");

    $stmt->execute([
        $newDay,
        date("Y-m-d"),
        $reward,
        $mine_id
    ]);

    return [
        'status' => true,
        'message' => 'Reward claimed',
        'amount' => $reward
    ];
}