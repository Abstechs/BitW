-- BitW Evolution 2.0 Migration
-- Strategic Scarcity, Lotto-Sovereign, and Social Prediction Markets

-- 1. Scarcity Enhancements for Plans
ALTER TABLE plans ADD COLUMN IF NOT EXISTS purchase_limit INT DEFAULT 0; -- 0 means unlimited
ALTER TABLE plans ADD COLUMN IF NOT EXISTS cooldown_hours INT DEFAULT 0;

-- 2. Lotto-Sovereign System
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

-- 3. Social Prediction Market (P2P Betting)
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

-- 4. Unified Wallet Converter Logs
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
