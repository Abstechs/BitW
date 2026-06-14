<?php

require_once __DIR__ . "/database.php";
require_once __DIR__ . "/helpers.php";

/**
 * Get user wallet
 */
function getWallet($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM wallets WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$wallet) {
        // Create wallet if not exists
        $stmt = $pdo->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0.00)");
        $stmt->execute([$user_id]);
        return getWallet($user_id);
    }
    return $wallet;
}

/**
 * Credit wallet
 */
function creditWallet($user_id, $amount, $type = 'credit', $description = '') {
    global $pdo;
    $wallet = getWallet($user_id);

    $newBalance = $wallet['balance'] + $amount;

    $stmt = $pdo->prepare("UPDATE wallets SET balance = ? WHERE user_id = ?");
    $stmt->execute([$newBalance, $user_id]);

    // Log transaction
    logTransaction($user_id, 'deposit', $amount, $description);

    return $newBalance;
}

/**
 * Debit wallet
 */
function debitWallet($user_id, $amount, $type = 'debit', $description = '') {
    global $pdo;
    $wallet = getWallet($user_id);

    if ($wallet['balance'] < $amount) {
        return false;
    }

    $newBalance = $wallet['balance'] - $amount;

    $stmt = $pdo->prepare("UPDATE wallets SET balance = ? WHERE user_id = ?");
    $stmt->execute([$newBalance, $user_id]);

    logTransaction($user_id, 'withdrawal', $amount, $description);

    return $newBalance;
}

/**
 * Log transaction
 */
function logTransaction($user_id, $type, $amount, $description) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $type, $amount, $description]);
}

/**
 * Get transactions
 */
function getTransactions($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
