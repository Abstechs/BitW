<?php
// core/seeders/SystemSeeder.php

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../Settings.php';

function seedSystem($pdo) {
    $settings = [
        // Market Math Constants
        ['market_drift_enabled', '1', 'market', 'Toggle global market movement'],
        ['global_volatility_multiplier', '1.0', 'market', 'Scales the volatility of all assets'],
        ['p2p_market_fee_percent', '0.5', 'market', 'Fee for internal P2P trades'],
        
        // Platform Controls
        ['platform_name', 'BitW Sovereign', 'general', 'The public name of the platform'],
        ['maintenance_mode', '0', 'general', 'Toggle platform-wide maintenance'],
        ['p2p_transfers_enabled', '1', 'features', 'Allow users to send funds to each other'],
        ['premium_system_enabled', '1', 'features', 'Enable the subscription and social oracle system'],
        
        // Financial Thresholds
        ['min_withdrawal_amount', '1000', 'finance', 'Minimum amount a user can withdraw'],
        ['max_daily_withdrawal', '500000', 'finance', 'Maximum daily withdrawal limit per user'],
        ['referral_bonus_percent', '10', 'finance', 'Percentage bonus for direct referrals'],
    ];

    foreach ($settings as $s) {
        Settings::set($pdo, $s[0], $s[1], $s[2], $s[3]);
    }

    echo "Sovereign system settings seeded successfully.\n";
}

// If run directly
if (php_sapi_name() === 'cli') {
    // Assuming $pdo is available in database.php or create a local one
    $config = require __DIR__ . '/../../config/database.php';
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        seedSystem($pdo);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
