<?php
// core/PaymentAdapter.php

require_once __DIR__ . '/Settings.php';

class PaymentAdapter {
    /**
     * Factory method to get the active gateway.
     * This allows the platform to switch between Paystack, BitW-Native, etc.
     */
    public static function getGateway($pdo) {
        $activeGateway = Settings::get($pdo, 'active_payment_gateway', 'paystack');
        
        switch ($activeGateway) {
            case 'paystack':
                return new PaystackGateway($pdo);
            case 'bitw_native':
                return new BitWNativeGateway($pdo);
            default:
                throw new Exception("Unknown gateway: $activeGateway");
        }
    }
}

interface SovereignGateway {
    public function initialize($amount, $email, $metadata);
    public function verify($reference);
}

class PaystackGateway implements SovereignGateway {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    public function initialize($amount, $email, $metadata) {
        // Logic moved from paystack-initialize.php
        // Returns the authorization URL
    }

    public function verify($reference) {
        // Logic moved from paystack-verify.php
        // Returns success/failure and amount
    }
}

class BitWNativeGateway implements SovereignGateway {
    // Placeholder for the future "BitW Sovereign Gateway"
    public function initialize($amount, $email, $metadata) { return "bitw://pay/" . bin2hex(random_bytes(8)); }
    public function verify($reference) { return ['status' => 'success', 'amount' => 0]; }
}
