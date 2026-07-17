-- BitW Evolution 1.0 Migration
-- Adds Dynamic Settings, P2P Ledger, and Premium System

-- 1. System Settings (The Nervous System)
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    setting_group VARCHAR(50) DEFAULT 'general',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Internal Ledger (Triple-Entry Accounting)
CREATE TABLE IF NOT EXISTS ledger (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(20,8) NOT NULL, -- High precision for crypto-like assets
    balance_after DECIMAL(20,8) NOT NULL,
    transaction_type VARCHAR(50) NOT NULL,
    reference_id VARCHAR(100),
    metadata JSON,
    checksum VARCHAR(64) NOT NULL, -- For integrity verification
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 3. Premium & Social Oracle
CREATE TABLE IF NOT EXISTS premium_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_name VARCHAR(50) NOT NULL,
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date TIMESTAMP NOT NULL,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS oracle_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    post_type ENUM('admin_blog', 'premium_insight') NOT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- 4. Trade Asset Enhancements (For Stochastic Math)
ALTER TABLE trade_assets ADD COLUMN IF NOT EXISTS volatility_constant DECIMAL(5,4) DEFAULT 0.0200;
ALTER TABLE trade_assets ADD COLUMN IF NOT EXISTS mean_reversion_speed DECIMAL(5,4) DEFAULT 0.0500;
ALTER TABLE trade_assets ADD COLUMN IF NOT EXISTS gravity_price DECIMAL(15,2);
ALTER TABLE trade_assets ADD COLUMN IF NOT EXISTS liquidity_depth DECIMAL(20,2) DEFAULT 1000000.00;
