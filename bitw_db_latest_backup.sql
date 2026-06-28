-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 28, 2026 at 01:23 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bitw_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 2, 'Withdrawal requested', 'Your withdrawal is under review.', 0, '2026-06-27 15:45:15'),
(2, 2, 'Stone saved to wishlist', 'The stone is ready for later purchase.', 0, '2026-06-28 11:49:21'),
(3, 2, 'Withdrawal requested', 'Your withdrawal is under review.', 0, '2026-06-28 12:16:16'),
(4, 2, 'Stone purchased successfully', 'Your Astral Shard mining plan is now active.', 0, '2026-06-28 13:16:40'),
(5, 2, 'You claimed ₦21.00 from mining', '', 0, '2026-06-28 13:18:42'),
(6, 2, 'Withdrawal requested', 'Your withdrawal is under review.', 0, '2026-06-28 13:19:41');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `item_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `type` enum('purchase','fund','withdrawal','wishlist') DEFAULT 'purchase',
  `status` enum('pending','completed','failed') DEFAULT 'completed',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `item_id`, `amount`, `type`, `status`, `description`, `created_at`) VALUES
(1, 2, 2, 1000.00, 'purchase', 'completed', 'Stone purchase: Astral Shard', '2026-06-28 13:16:40');

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `min_amount` decimal(15,2) NOT NULL,
  `max_amount` decimal(15,2) DEFAULT NULL,
  `daily_rate` decimal(5,2) NOT NULL,
  `duration_days` int NOT NULL,
  `max_purchase_attempts` int DEFAULT '1',
  `status` enum('active','inactive') DEFAULT 'active',
  `image` varchar(255) DEFAULT NULL,
  `description` text,
  `background_story` text,
  `read_more_link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `plans`
--

INSERT INTO `plans` (`id`, `name`, `min_amount`, `max_amount`, `daily_rate`, `duration_days`, `max_purchase_attempts`, `status`, `image`, `description`, `background_story`, `read_more_link`) VALUES
(1, 'Obsidian Stone', 4700.00, 9999.99, 1.50, 30, 3, 'active', '/assets/images/plans/plan_6a3ff1ec5a0f6.png', 'A stable starter stone with reliable daily yield and a low-entry cost.', 'Obsidian is a naturally occurring, silica-rich volcanic glass formed when extrusive lava cools rapidly with minimal crystal growth. Classified as an igneous rock, it is highly durable, brittle, and famous for its razor-sharp conchoidal fractures, which have historically made it a prime material for ancient tools and modern surgical scalpels.\r\nObsidian Stone was forged from volcanic basalt and engineered for calm, consistent compounding.', 'https://en.wikipedia.org/wiki/Obsidian'),
(2, 'Astral Shard', 1000.00, 4999.00, 2.00, 45, 2, 'active', NULL, 'A mid-tier mining crystal known for its precise yield and long-horizon performance.', 'Astral Shard carries a luminous core believed to sync with nightly mining cycles.', 'https://example.com/astral-shard'),
(3, 'Titan Ember', 5000.00, NULL, 2.50, 60, 1, 'active', NULL, 'A premium stone for ambitious miners seeking high yield and prestige.', 'Titan Ember is a rare relic stone, prized for its heat signature and high-output mining profile.', 'https://example.com/titan-ember');

-- --------------------------------------------------------

--
-- Table structure for table `ranks`
--

CREATE TABLE `ranks` (
  `level` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `min_referrals` int DEFAULT '0',
  `bonus_rate` decimal(5,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ranks`
--

INSERT INTO `ranks` (`level`, `name`, `min_referrals`, `bonus_rate`) VALUES
(1, 'Novice', 0, 0.00),
(2, 'Builder', 5, 0.50),
(3, 'Mythic', 20, 1.00);

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `id` int NOT NULL,
  `referrer_id` int NOT NULL,
  `referred_id` int NOT NULL,
  `bonus_amount` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `type` enum('deposit','withdrawal','mining_claim','referral_bonus','investment') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text,
  `reference` varchar(100) DEFAULT NULL,
  `gateway` varchar(50) DEFAULT NULL,
  `status` enum('pending','completed','failed','rejected') DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `type`, `amount`, `description`, `reference`, `gateway`, `status`, `created_at`) VALUES
(1, 2, 'withdrawal', 0.00, 'Withdrawal request', NULL, NULL, 'completed', '2026-06-27 15:45:14'),
(2, 2, 'deposit', 100.00, 'Paystack deposit - Internal Ref: ref_2_28062026112121', 'bitw_1782642081_2_2ae86c', NULL, 'pending', '2026-06-28 10:21:21'),
(3, 2, 'deposit', 100.00, 'Paystack deposit - Internal Ref: ref_2_28062026115339', 'bitw_1782644019_2_ecc863', NULL, 'pending', '2026-06-28 10:53:39'),
(4, 2, 'deposit', 1000.00, 'Paystack deposit - Internal Ref: ref_2_28062026115618', 'bitw_1782644178_2_7a540f', NULL, 'pending', '2026-06-28 10:56:18'),
(5, 2, 'deposit', 1000.00, 'Paystack deposit - Internal Ref: ref_2_28062026115901', 'bitw_1782644341_2_967c33', NULL, 'pending', '2026-06-28 10:59:01'),
(6, 2, 'deposit', 1000.00, 'Paystack deposit - Internal Ref: ref_2_28062026121341', 'bitw_1782645221_2_d8d323', NULL, 'pending', '2026-06-28 11:13:41'),
(7, 2, 'deposit', 1000.00, 'Paystack deposit - Internal Ref: ref_2_28062026121516', 'bitw_1782645316_2_06e9af', NULL, 'pending', '2026-06-28 11:15:16'),
(8, 2, 'deposit', 1000.00, 'Paystack deposit - Internal Ref: ref_2_28062026121625', 'bitw_1782645385_2_b14eac', NULL, 'pending', '2026-06-28 11:16:25'),
(9, 2, 'deposit', 100.00, 'Paystack deposit - Internal Ref: ref_2_28062026121937', 'bitw_1782645577_2_b6e924', NULL, 'pending', '2026-06-28 11:19:37'),
(10, 2, 'deposit', 1000.00, 'Paystack deposit - Internal Ref: ref_2_28062026123136', 'bitw_1782646296_2_614d47', NULL, 'pending', '2026-06-28 11:31:36'),
(11, 2, 'deposit', 100.00, 'Paystack deposit - Internal Ref: ref_2_28062026125020', 'bitw_1782647420_2_6850ad', NULL, 'pending', '2026-06-28 11:50:20'),
(12, 2, 'withdrawal', 10.00, 'Withdrawal request', NULL, NULL, 'completed', '2026-06-28 12:16:16'),
(13, 2, 'deposit', 100.00, 'Paystack deposit - Internal Ref: ref_2_28062026012408', 'bitw_1782649448_2_a748c5', NULL, 'completed', '2026-06-28 12:24:08'),
(14, 2, 'deposit', 100.00, 'Paystack deposit - Internal Ref: ref_2_28062026021307', 'bitw_1782652387_2_46f0b8', NULL, 'completed', '2026-06-28 13:13:07'),
(15, 2, 'deposit', 10000.00, 'Paystack deposit - Internal Ref: ref_2_28062026021610', 'bitw_1782652570_2_acc2ce', NULL, 'completed', '2026-06-28 13:16:10'),
(16, 2, 'withdrawal', 1000.00, 'Plan purchase', NULL, NULL, 'completed', '2026-06-28 13:16:40'),
(17, 2, 'deposit', 21.00, '', NULL, NULL, 'completed', '2026-06-28 13:18:41'),
(18, 2, 'withdrawal', 10.00, 'Withdrawal request', NULL, NULL, 'completed', '2026-06-28 13:19:39');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `pin` varchar(10) DEFAULT NULL,
  `q1` varchar(255) DEFAULT NULL,
  `a1` varchar(255) DEFAULT NULL,
  `q2` varchar(255) DEFAULT NULL,
  `a2` varchar(255) DEFAULT NULL,
  `q3` varchar(255) DEFAULT NULL,
  `a3` varchar(255) DEFAULT NULL,
  `referral_code` varchar(20) DEFAULT NULL,
  `referred_by` int DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT '0',
  `rank_level` int DEFAULT '1',
  `total_referrals` int DEFAULT '0',
  `referral_earnings` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `phone`, `pin`, `q1`, `a1`, `q2`, `a2`, `q3`, `a3`, `referral_code`, `referred_by`, `is_admin`, `rank_level`, `total_referrals`, `referral_earnings`, `created_at`) VALUES
(1, 'admin', 'admin@bitw.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ADMIN123', NULL, 1, 1, 0, 0.00, '2026-06-27 15:21:42'),
(2, 'Abstech', 'abdulfataisodiqtoyin@gmail.com', '$2y$10$WVOkmTNiHzS.nhbtgD/eeOdY0BN46i2UWvX6ADqjeAOI3ucmtHeAO', '08069764769', '1234', 's1?', 's1 ans', 's2?', 's2 ans', 's3?', 's3 ans', 'BITW9673', NULL, 0, 1, 0, 0.00, '2026-06-27 15:27:40');

-- --------------------------------------------------------

--
-- Table structure for table `user_mining`
--

CREATE TABLE `user_mining` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `plan_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `start_date` date DEFAULT (curdate()),
  `end_date` date DEFAULT NULL,
  `daily_earnings` decimal(15,2) DEFAULT '0.00',
  `total_earned` decimal(15,2) DEFAULT '0.00',
  `status` enum('active','completed','claimed') DEFAULT 'active',
  `last_claim` datetime DEFAULT NULL,
  `duration_days` int DEFAULT '30'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_mining`
--

INSERT INTO `user_mining` (`id`, `user_id`, `plan_id`, `amount`, `start_date`, `end_date`, `daily_earnings`, `total_earned`, `status`, `last_claim`, `duration_days`) VALUES
(1, 2, 2, 1000.00, '2026-06-28', NULL, 20.00, 21.00, 'active', '2026-06-28 14:18:42', 45);

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

CREATE TABLE `wallets` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `balance` decimal(15,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wallets`
--

INSERT INTO `wallets` (`id`, `user_id`, `balance`) VALUES
(1, 1, 10000.00),
(2, 2, 9311.00);

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `plan_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wishlists`
--

INSERT INTO `wishlists` (`id`, `user_id`, `plan_id`, `created_at`) VALUES
(1, 2, 2, '2026-06-28 11:49:20');

-- --------------------------------------------------------

--
-- Table structure for table `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `method` varchar(50) DEFAULT 'wallet',
  `status` enum('pending','completed','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `withdrawals`
--

INSERT INTO `withdrawals` (`id`, `user_id`, `amount`, `method`, `status`, `created_at`) VALUES
(1, 2, 0.00, 'wallet', 'pending', '2026-06-27 15:45:14'),
(2, 2, 10.00, 'wallet', 'pending', '2026-06-28 12:16:16'),
(3, 2, 10.00, 'wallet', 'pending', '2026-06-28 13:19:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ranks`
--
ALTER TABLE `ranks`
  ADD PRIMARY KEY (`level`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `referrer_id` (`referrer_id`),
  ADD KEY `referred_id` (`referred_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_mining`
--
ALTER TABLE `user_mining`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_mining`
--
ALTER TABLE `user_mining`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `wallets`
--
ALTER TABLE `wallets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `referrals_ibfk_2` FOREIGN KEY (`referred_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_mining`
--
ALTER TABLE `user_mining`
  ADD CONSTRAINT `user_mining_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_mining_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallets`
--
ALTER TABLE `wallets`
  ADD CONSTRAINT `wallets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlists_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD CONSTRAINT `withdrawals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
