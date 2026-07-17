<?php
// core/WalletConverter.php

require_once __DIR__ . '/Ledger.php';
require_once __DIR__ . '/Settings.php';

class WalletConverter {
    /**
     * Swap funds between different ecosystem wallets.
     */
    public static function convert($pdo, $userId, $fromWallet, $toWallet, $amount) {
        try {
            $pdo->beginTransaction();

            // 1. Validate wallets (mining, lotto, betting, main)
            $allowedWallets = ['main', 'mining', 'lotto', 'betting'];
            if (!in_array($fromWallet, $allowedWallets) || !in_array($toWallet, $allowedWallets)) {
                throw new Exception("Invalid wallet specified.");
            }

            // 2. Calculate Fee
            $feePercent = floatval(Settings::get($pdo, 'conversion_fee_percent', '1.0'));
            $fee = $amount * ($feePercent / 100);
            $netAmount = $amount - $fee;

            // 3. Debit Source Wallet
            $reference = "CONV-" . strtoupper(bin2hex(random_bytes(4)));
            $debit = Ledger::record($pdo, $userId, -$amount, "convert_out_$fromWallet", $reference, ['to' => $toWallet, 'fee' => $fee]);
            
            if (!$debit) throw new Exception("Insufficient funds in $fromWallet wallet.");

            // 4. Credit Destination Wallet
            $credit = Ledger::record($pdo, $userId, $netAmount, "convert_in_$toWallet", $reference, ['from' => $fromWallet]);
            
            if (!$credit) throw new Exception("Failed to credit $toWallet wallet.");

            // 5. Log conversion
            $stmt = $pdo->prepare("INSERT INTO wallet_conversions (user_id, from_wallet, to_wallet, amount, fee) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $fromWallet, $toWallet, $amount, $fee]);

            $pdo->commit();
            return ['success' => true, 'reference' => $reference, 'net_amount' => $netAmount];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
