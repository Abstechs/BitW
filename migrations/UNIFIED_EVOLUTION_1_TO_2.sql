-- BitW Unified Evolution Migration (1.0 to 2.0)
-- Run this script to update your database to the latest Sovereign Ecosystem version.

-- 1. System Settings & Configuration
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    setting_group VARCHAR(50) DEFAULT 'general',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Sovereign Ledger (Triple-Entry)
CREATE TABLE IF NOT EXISTS ledger (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(20,8) NOT NULL,
    balance_after DECIMAL(20,8) NOT NULL,
    transaction_type VARCHAR(50) NOT NULL,
    reference_id VARCHAR(100),
    metadata JSON,
    checksum VARCHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 3. Lotto-Sovereign System
CREATE TABLE IF NOT EXISTS lotto_games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    draw_date DATE NOT NULL,
    lucky_number VARCHAR(10) DEFAULT NULL,
    status ENUM('open', 'calculating', 'closed') DEFAULT 'open',
    total_pool DECIMAL(15,2) DEFAULT 0.00,
    is_demo TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS lotto_bets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    predicted_number VARCHAR(10) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    potential_win DECIMAL(15,2) NOT NULL,
    is_demo TINYINT(1) DEFAULT 0,
    status ENUM('pending', 'won', 'lost') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (game_id) REFERENCES lotto_games(id)
);

-- 4. Social Prediction Market
CREATE TABLE IF NOT EXISTS prediction_markets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    creator_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    option_a VARCHAR(50) DEFAULT 'Agree',
    option_b VARCHAR(50) DEFAULT 'Disagree',
    total_pool DECIMAL(15,2) DEFAULT 0.00,
    commission_percent DECIMAL(5,2) DEFAULT 5.00,
    status ENUM('open', 'pending_result', 'settled', 'cancelled') DEFAULT 'open',
    winner_option ENUM('a', 'b') DEFAULT NULL,
    end_time TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS prediction_bets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    market_id INT NOT NULL,
    selected_option ENUM('a', 'b') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'won', 'lost', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (market_id) REFERENCES prediction_markets(id)
);

-- 5. Premium Subscriptions & Social Oracle
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

-- 6. Wallet Conversions & Scarcity
CREATE TABLE IF NOT EXISTS wallet_conversions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    from_wallet VARCHAR(50) NOT NULL,
    to_wallet VARCHAR(50) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    fee DECIMAL(15,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 7. Schema Enhancements
ALTER TABLE plans ADD COLUMN IF NOT EXISTS purchase_limit INT DEFAULT 0;
ALTER TABLE plans ADD COLUMN IF NOT EXISTS cooldown_hours INT DEFAULT 0;
ALTER TABLE trade_assets ADD COLUMN IF NOT EXISTS volatility_constant DECIMAL(5,4) DEFAULT 0.0200;
ALTER TABLE trade_assets ADD COLUMN IF NOT EXISTS mean_reversion_speed DECIMAL(5,4) DEFAULT 0.0500;
ALTER TABLE trade_assets ADD COLUMN IF NOT EXISTS gravity_price DECIMAL(15,2);
ALTER TABLE trade_assets ADD COLUMN IF NOT EXISTS liquidity_depth DECIMAL(20,2) DEFAULT 1000000.00;
