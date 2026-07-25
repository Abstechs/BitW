<?php
// core/Ledger.php

class Ledger {
    /**
     * Record a transaction in the sovereign ledger with checksum integrity.
     */
    public static function record($pdo, $userId, $amount, $type, $referenceId = null, $metadata = []) {
        try {
            $pdo->beginTransaction();

            // 1. Get current balance from wallet
            $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $currentBalance = $stmt->fetchColumn();

            if ($currentBalance === false) {
                // Auto-create wallet if missing
                $pdo->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0)")->execute([$userId]);
                $currentBalance = 0;
            }

            $newBalance = $currentBalance + $amount;

            // 2. Update Wallet
            $update = $pdo->prepare("UPDATE wallets SET balance = ? WHERE user_id = ?");
            $update->execute([$newBalance, $userId]);

            // 3. Generate Checksum for integrity
            // Hash: userId + amount + newBalance + type + referenceId + secret_salt
            $salt = "BITW_SOVEREIGN_SALT_2026"; 
            $checksumData = $userId . $amount . $newBalance . $type . $referenceId . $salt;
            $checksum = hash('sha256', $checksumData);

            // 4. Insert into Ledger
            $insert = $pdo->prepare("INSERT INTO ledger (user_id, amount, balance_after, transaction_type, reference_id, metadata, checksum) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([
                $userId, 
                $amount, 
                $newBalance, 
                $type, 
                $referenceId, 
                json_encode($metadata), 
                $checksum
            ]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Ledger Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Internal P2P Transfer between two users.
     */
    public static function transfer($pdo, $fromUserId, $toUserId, $amount, $fee = 0) {
        $reference = "P2P-" . strtoupper(bin2hex(random_bytes(4)));
        
        // Debit sender
        $debit = self::record($pdo, $fromUserId, -($amount + $fee), 'p2p_transfer_out', $reference, ['to' => $toUserId, 'fee' => $fee]);
        if (!$debit) return false;

        // Credit receiver
        $credit = self::record($pdo, $toUserId, $amount, 'p2p_transfer_in', $reference, ['from' => $fromUserId]);
        if (!$credit) return false;

        return $reference;
    }
}
