-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 11, 2025 at 03:39 PM
-- Server version: 10.11.12-MariaDB
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mcgkxyz_timer_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `levels`
--

CREATE TABLE `levels` (
  `level` int(10) UNSIGNED NOT NULL,
  `hours_required` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `rank_name` varchar(50) NOT NULL,
  `reward_rate_per_hour` decimal(10,4) NOT NULL DEFAULT 0.0000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_items`
--

CREATE TABLE `marketplace_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `image_url` varchar(512) DEFAULT NULL,
  `stock` int(11) DEFAULT -1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `on_the_note`
--

CREATE TABLE `on_the_note` (
  `id` int(11) NOT NULL,
  `items_list` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`items_list`)),
  `total_amount` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `is_paid` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `on_the_note`
--
DELIMITER $$
CREATE TRIGGER `after_note_payment` AFTER UPDATE ON `on_the_note` FOR EACH ROW BEGIN
            IF NEW.is_paid = 1 AND OLD.is_paid = 0 THEN
                UPDATE user_progress 
                SET bank_balance = bank_balance - NEW.total_amount 
                WHERE id = 1;
            END IF;
        END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_logs`
--

CREATE TABLE `purchase_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `item_name_snapshot` varchar(100) NOT NULL,
  `price_paid` decimal(15,4) NOT NULL,
  `purchase_time` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timers`
--

CREATE TABLE `timers` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `accumulated_seconds` bigint(20) UNSIGNED DEFAULT 0,
  `start_time` datetime DEFAULT NULL,
  `is_running` tinyint(1) NOT NULL DEFAULT 0,
  `current_level` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `notified_level` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `categories` varchar(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timer_logs`
--

CREATE TABLE `timer_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `timer_id` int(10) UNSIGNED NOT NULL,
  `session_start_time` datetime NOT NULL,
  `session_end_time` datetime NOT NULL,
  `duration_seconds` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `earned_amount` decimal(15,6) NOT NULL DEFAULT 0.000000,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `transaction_type` enum('timer_earning','note_payment','refund') DEFAULT 'timer_earning',
  `transaction_details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_basket`
--

CREATE TABLE `user_basket` (
  `id` int(11) NOT NULL,
  `session_id` varchar(128) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `item_price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `added_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_progress`
--

CREATE TABLE `user_progress` (
  `id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `bank_balance` decimal(15,4) NOT NULL DEFAULT 0.0000
) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `levels`
--
ALTER TABLE `levels`
  ADD PRIMARY KEY (`level`);

--
-- Indexes for table `marketplace_items`
--
ALTER TABLE `marketplace_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `is_active` (`is_active`,`price`);

--
-- Indexes for table `on_the_note`
--
ALTER TABLE `on_the_note`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_note_status` (`is_paid`),
  ADD KEY `idx_note_created` (`created_at`),
  ADD KEY `idx_note_paid` (`paid_at`);

--
-- Indexes for table `purchase_logs`
--
ALTER TABLE `purchase_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `timers`
--
ALTER TABLE `timers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `is_running` (`is_running`),
  ADD KEY `fk_timers_level` (`current_level`);

--
-- Indexes for table `timer_logs`
--
ALTER TABLE `timer_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `timer_id` (`timer_id`);

--
-- Indexes for table `user_basket`
--
ALTER TABLE `user_basket`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `marketplace_items`
--
ALTER TABLE `marketplace_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `on_the_note`
--
ALTER TABLE `on_the_note`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_logs`
--
ALTER TABLE `purchase_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timers`
--
ALTER TABLE `timers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timer_logs`
--
ALTER TABLE `timer_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_basket`
--
ALTER TABLE `user_basket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `purchase_logs`
--
ALTER TABLE `purchase_logs`
  ADD CONSTRAINT `purchase_logs_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `marketplace_items` (`id`);

--
-- Constraints for table `timers`
--
ALTER TABLE `timers`
  ADD CONSTRAINT `fk_timers_level` FOREIGN KEY (`current_level`) REFERENCES `levels` (`level`) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Constraints for table `timer_logs`
--
ALTER TABLE `timer_logs`
  ADD CONSTRAINT `timer_logs_ibfk_1` FOREIGN KEY (`timer_id`) REFERENCES `timers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
