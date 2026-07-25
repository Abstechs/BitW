-- BitW Full Schema for New Installation
-- Run this completely for fresh setup

CREATE DATABASE IF NOT EXISTS bitw_db;
USE bitw_db;

-- Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    referral_code VARCHAR(20) UNIQUE,
    referred_by INT DEFAULT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    rank_level INT DEFAULT 1,
    total_referrals INT DEFAULT 0,
    referral_earnings DECIMAL(15,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Wallets
CREATE TABLE IF NOT EXISTS wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    balance DECIMAL(15,2) DEFAULT 0.00,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Plans
CREATE TABLE IF NOT EXISTS plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    min_amount DECIMAL(15,2) NOT NULL,
    max_amount DECIMAL(15,2),
    daily_rate DECIMAL(5,2) NOT NULL,
    duration_days INT NOT NULL,
    max_purchase_attempts INT DEFAULT 1,
    status ENUM('active', 'inactive') DEFAULT 'active',
    image VARCHAR(255) DEFAULT NULL,
    description TEXT,
    background_story TEXT,
    read_more_link VARCHAR(255) DEFAULT NULL
);

-- User Mining / Investments
CREATE TABLE IF NOT EXISTS user_mining (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    daily_earnings DECIMAL(15,2) DEFAULT 0.00,
    total_earned DECIMAL(15,2) DEFAULT 0.00,
    status ENUM('active', 'completed', 'claimed') DEFAULT 'active',
    last_claim DATETIME DEFAULT NULL,
    duration_days INT DEFAULT 30,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (plan_id) REFERENCES plans(id)
);

-- Transactions
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('deposit', 'withdrawal', 'mining_claim', 'referral_bonus', 'investment') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    description TEXT,
    reference VARCHAR(100),
    gateway VARCHAR(50),
    status ENUM('pending', 'completed', 'failed', 'rejected') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Referrals
CREATE TABLE IF NOT EXISTS referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL,
    referred_id INT NOT NULL,
    bonus_amount DECIMAL(15,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(id),
    FOREIGN KEY (referred_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS wishlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    type ENUM('purchase', 'fund', 'withdrawal', 'wishlist') DEFAULT 'purchase',
    status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    method VARCHAR(50) DEFAULT 'wallet',
    status ENUM('pending', 'completed', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications (in-app)
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Ranks (optional config table)
CREATE TABLE IF NOT EXISTS ranks (
    level INT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    min_referrals INT DEFAULT 0,
    bonus_rate DECIMAL(5,2) DEFAULT 0.00
);

-- Sample Data
INSERT IGNORE INTO plans (name, min_amount, max_amount, daily_rate, duration_days, max_purchase_attempts, description, background_story, read_more_link) VALUES
('Obsidian Stone', 100.00, 999.00, 1.5, 30, 3, 'A stable starter stone with reliable daily yield and a low-entry cost.', 'Obsidian Stone was forged from volcanic basalt and engineered for calm, consistent compounding.', 'https://example.com/obsidian-stone'),
('Astral Shard', 1000.00, 4999.00, 2.0, 45, 2, 'A mid-tier mining crystal known for its precise yield and long-horizon performance.', 'Astral Shard carries a luminous core believed to sync with nightly mining cycles.', 'https://example.com/astral-shard'),
('Titan Ember', 5000.00, NULL, 2.5, 60, 1, 'A premium stone for ambitious miners seeking high yield and prestige.', 'Titan Ember is a rare relic stone, prized for its heat signature and high-output mining profile.', 'https://example.com/titan-ember');

INSERT IGNORE INTO ranks (level, name, min_referrals, bonus_rate) VALUES
(1, 'Novice', 0, 0),
(2, 'Builder', 5, 0.5),
(3, 'Mythic', 20, 1.0);

INSERT IGNORE INTO users (username, email, password, referral_code, is_admin) VALUES
('admin', 'admin@bitw.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN123', 1); -- password: password

-- Add wallet for admin
INSERT IGNORE INTO wallets (user_id, balance) SELECT id, 10000.00 FROM users WHERE username = 'admin';