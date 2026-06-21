-- Migration: Add columns for payment and purchase management

-- Add max_purchase_attempts to plans table
ALTER TABLE plans ADD COLUMN max_purchase_attempts INT DEFAULT 1 AFTER duration_days;

-- Add status column to user_mining if not exists
ALTER TABLE user_mining ADD COLUMN status ENUM('active', 'completed', 'claimed') DEFAULT 'active';

-- Add duration_days to user_mining if not exists  
ALTER TABLE user_mining ADD COLUMN duration_days INT DEFAULT 30;

-- Add start_date and status to user_mining if they don't exist
ALTER TABLE user_mining MODIFY start_date DATE DEFAULT CURDATE();

-- Ensure transactions table has proper status enum
ALTER TABLE transactions MODIFY status ENUM('pending', 'completed', 'failed', 'rejected') DEFAULT 'completed';
