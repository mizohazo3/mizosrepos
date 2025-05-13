-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 20, 2025 at 08:34 AM
-- Server version: 8.0.40
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `timer_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `levels`
--

CREATE TABLE `levels` (
  `level` int UNSIGNED NOT NULL,
  `hours_required` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `rank_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reward_rate_per_hour` decimal(10,4) NOT NULL DEFAULT '0.0000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `levels`
--

INSERT INTO `levels` (`level`, `hours_required`, `rank_name`, `reward_rate_per_hour`) VALUES
(1, 0.0000, 'Novice', 0.1000),
(2, 5.0000, 'Novice', 0.1100),
(3, 10.0000, 'Novice', 0.1200),
(4, 15.0000, 'Novice', 0.1300),
(5, 20.0000, 'Novice', 0.1400),
(6, 26.0000, 'Novice', 0.1500),
(7, 32.0000, 'Novice', 0.1600),
(8, 38.0000, 'Novice', 0.1700),
(9, 44.0000, 'Novice', 0.1800),
(10, 50.0000, 'Novice', 0.1900),
(11, 60.0000, 'Apprentice', 0.2500),
(12, 70.0000, 'Apprentice', 0.2700),
(13, 80.0000, 'Apprentice', 0.2900),
(14, 90.0000, 'Apprentice', 0.3100),
(15, 100.0000, 'Apprentice', 0.3300),
(16, 120.0000, 'Apprentice', 0.3500),
(17, 140.0000, 'Apprentice', 0.3700),
(18, 160.0000, 'Apprentice', 0.3900),
(19, 180.0000, 'Apprentice', 0.4100),
(20, 200.0000, 'Apprentice', 0.4300),
(21, 220.0000, 'Intermediate', 0.5000),
(22, 240.0000, 'Intermediate', 0.5300),
(23, 260.0000, 'Intermediate', 0.5600),
(24, 280.0000, 'Intermediate', 0.5900),
(25, 300.0000, 'Intermediate', 0.6200),
(26, 330.0000, 'Intermediate', 0.6500),
(27, 360.0000, 'Intermediate', 0.6800),
(28, 390.0000, 'Intermediate', 0.7100),
(29, 420.0000, 'Intermediate', 0.7400),
(30, 450.0000, 'Intermediate', 0.7700),
(31, 480.0000, 'Advanced', 0.8500),
(32, 510.0000, 'Advanced', 0.8900),
(33, 540.0000, 'Advanced', 0.9300),
(34, 570.0000, 'Advanced', 0.9700),
(35, 600.0000, 'Advanced', 1.0100),
(36, 630.0000, 'Advanced', 1.0500),
(37, 660.0000, 'Advanced', 1.0900),
(38, 690.0000, 'Advanced', 1.1300),
(39, 720.0000, 'Advanced', 1.1700),
(40, 750.0000, 'Advanced', 1.2100),
(41, 780.0000, 'Specialist', 1.3000),
(42, 810.0000, 'Specialist', 1.3500),
(43, 840.0000, 'Specialist', 1.4000),
(44, 870.0000, 'Specialist', 1.4500),
(45, 900.0000, 'Specialist', 1.5000),
(46, 920.0000, 'Specialist', 1.5500),
(47, 940.0000, 'Specialist', 1.6000),
(48, 960.0000, 'Specialist', 1.6500),
(49, 980.0000, 'Specialist', 1.7000),
(50, 1000.0000, 'Specialist', 1.7500),
(51, 1030.0000, 'Expert', 1.8500),
(52, 1060.0000, 'Expert', 1.9100),
(53, 1090.0000, 'Expert', 1.9700),
(54, 1120.0000, 'Expert', 2.0300),
(55, 1150.0000, 'Expert', 2.0900),
(56, 1180.0000, 'Expert', 2.1500),
(57, 1210.0000, 'Expert', 2.2100),
(58, 1240.0000, 'Expert', 2.2700),
(59, 1270.0000, 'Expert', 2.3300),
(60, 1300.0000, 'Expert', 2.3900),
(61, 1330.0000, 'Elite', 2.5000),
(62, 1360.0000, 'Elite', 2.5800),
(63, 1390.0000, 'Elite', 2.6600),
(64, 1420.0000, 'Elite', 2.7400),
(65, 1450.0000, 'Elite', 2.8200),
(66, 1480.0000, 'Elite', 2.9000),
(67, 1510.0000, 'Elite', 2.9800),
(68, 1540.0000, 'Elite', 3.0600),
(69, 1570.0000, 'Elite', 3.1400),
(70, 1600.0000, 'Elite', 3.2200),
(71, 1630.0000, 'Master', 3.4000),
(72, 1660.0000, 'Master', 3.5000),
(73, 1690.0000, 'Master', 3.6000),
(74, 1720.0000, 'Master', 3.7000),
(75, 1750.0000, 'Master', 3.8000),
(76, 1780.0000, 'Master', 3.9000),
(77, 1810.0000, 'Master', 4.0000),
(78, 1840.0000, 'Master', 4.1000),
(79, 1870.0000, 'Master', 4.2000),
(80, 1900.0000, 'Master', 4.3000),
(81, 1930.0000, 'Grandmaster', 4.5000),
(82, 1960.0000, 'Grandmaster', 4.6500),
(83, 1990.0000, 'Grandmaster', 4.8000),
(84, 2020.0000, 'Grandmaster', 4.9500),
(85, 2050.0000, 'Grandmaster', 5.1000),
(86, 2080.0000, 'Grandmaster', 5.2500),
(87, 2110.0000, 'Grandmaster', 5.4000),
(88, 2140.0000, 'Grandmaster', 5.5500),
(89, 2170.0000, 'Grandmaster', 5.7000),
(90, 2200.0000, 'Grandmaster', 5.8500),
(91, 2240.0000, 'Legendary', 6.1000),
(92, 2280.0000, 'Legendary', 6.3000),
(93, 2320.0000, 'Legendary', 6.5000),
(94, 2360.0000, 'Legendary', 6.7000),
(95, 2400.0000, 'Legendary', 6.9000),
(96, 2440.0000, 'Legendary', 7.1000),
(97, 2480.0000, 'Legendary', 7.3000),
(98, 2520.0000, 'Legendary', 7.5000),
(99, 2560.0000, 'Legendary', 7.7000),
(100, 2600.0000, 'Ultimate', 8.0000);

-- --------------------------------------------------------

--
-- Table structure for table `levels_ranks`
--

CREATE TABLE `levels_ranks` (
  `level` int NOT NULL,
  `hours_required` decimal(10,4) NOT NULL,
  `rank_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hourly_rate` decimal(10,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `levels_ranks`
--

INSERT INTO `levels_ranks` (`level`, `hours_required`, `rank_name`, `hourly_rate`, `created_at`, `updated_at`) VALUES
(1, 0.0000, 'Novice', 0.1000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(2, 5.0000, 'Novice', 0.1100, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(3, 10.0000, 'Novice', 0.1200, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(4, 15.0000, 'Novice', 0.1300, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(5, 20.0000, 'Novice', 0.1400, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(6, 26.0000, 'Novice', 0.1500, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(7, 32.0000, 'Novice', 0.1600, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(8, 38.0000, 'Novice', 0.1700, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(9, 44.0000, 'Novice', 0.1800, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(10, 50.0000, 'Novice', 0.1900, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(11, 60.0000, 'Apprentice', 0.2500, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(12, 70.0000, 'Apprentice', 0.2700, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(13, 80.0000, 'Apprentice', 0.2900, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(14, 90.0000, 'Apprentice', 0.3100, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(15, 100.0000, 'Apprentice', 0.3300, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(16, 120.0000, 'Apprentice', 0.3500, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(17, 140.0000, 'Apprentice', 0.3700, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(18, 160.0000, 'Apprentice', 0.3900, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(19, 180.0000, 'Apprentice', 0.4100, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(20, 200.0000, 'Apprentice', 0.4300, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(21, 220.0000, 'Intermediate', 0.5000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(22, 240.0000, 'Intermediate', 0.5300, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(23, 260.0000, 'Intermediate', 0.5600, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(24, 280.0000, 'Intermediate', 0.5900, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(25, 300.0000, 'Intermediate', 0.6200, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(26, 330.0000, 'Intermediate', 0.6500, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(27, 360.0000, 'Intermediate', 0.6800, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(28, 390.0000, 'Intermediate', 0.7100, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(29, 420.0000, 'Intermediate', 0.7400, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(30, 450.0000, 'Intermediate', 0.7700, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(31, 480.0000, 'Advanced', 0.8500, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(32, 510.0000, 'Advanced', 0.8900, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(33, 540.0000, 'Advanced', 0.9300, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(34, 570.0000, 'Advanced', 0.9700, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(35, 600.0000, 'Advanced', 1.0100, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(36, 630.0000, 'Advanced', 1.0500, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(37, 660.0000, 'Advanced', 1.0900, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(38, 690.0000, 'Advanced', 1.1300, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(39, 720.0000, 'Advanced', 1.1700, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(40, 750.0000, 'Advanced', 1.2100, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(41, 780.0000, 'Specialist', 1.3000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(42, 810.0000, 'Specialist', 1.3500, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(43, 840.0000, 'Specialist', 1.4000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(44, 870.0000, 'Specialist', 1.4500, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(45, 900.0000, 'Specialist', 1.5000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(46, 920.0000, 'Specialist', 1.5500, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(47, 940.0000, 'Specialist', 1.6000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(48, 960.0000, 'Specialist', 1.6500, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(49, 980.0000, 'Specialist', 1.7000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(50, 1000.0000, 'Specialist', 1.7500, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(51, 1030.0000, 'Expert', 1.8500, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(52, 1060.0000, 'Expert', 1.9100, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(53, 1090.0000, 'Expert', 1.9700, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(54, 1120.0000, 'Expert', 2.0300, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(55, 1150.0000, 'Expert', 2.0900, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(56, 1180.0000, 'Expert', 2.1500, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(57, 1210.0000, 'Expert', 2.2100, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(58, 1240.0000, 'Expert', 2.2700, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(59, 1270.0000, 'Expert', 2.3300, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(60, 1300.0000, 'Expert', 2.3900, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(61, 1330.0000, 'Elite', 2.5000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(62, 1360.0000, 'Elite', 2.5800, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(63, 1390.0000, 'Elite', 2.6600, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(64, 1420.0000, 'Elite', 2.7400, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(65, 1450.0000, 'Elite', 2.8200, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(66, 1480.0000, 'Elite', 2.9000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(67, 1510.0000, 'Elite', 2.9800, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(68, 1540.0000, 'Elite', 3.0600, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(69, 1570.0000, 'Elite', 3.1400, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(70, 1600.0000, 'Elite', 3.2200, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(71, 1630.0000, 'Master', 3.4000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(72, 1660.0000, 'Master', 3.5000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(73, 1690.0000, 'Master', 3.6000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(74, 1720.0000, 'Master', 3.7000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(75, 1750.0000, 'Master', 3.8000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(76, 1780.0000, 'Master', 3.9000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(77, 1810.0000, 'Master', 4.0000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(78, 1840.0000, 'Master', 4.1000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(79, 1870.0000, 'Master', 4.2000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(80, 1900.0000, 'Master', 4.3000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(81, 1930.0000, 'Grandmaster', 4.5000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(82, 1960.0000, 'Grandmaster', 4.6500, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(83, 1990.0000, 'Grandmaster', 4.8000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(84, 2020.0000, 'Grandmaster', 4.9500, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(85, 2050.0000, 'Grandmaster', 5.1000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(86, 2080.0000, 'Grandmaster', 5.2500, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(87, 2110.0000, 'Grandmaster', 5.4000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(88, 2140.0000, 'Grandmaster', 5.5500, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(89, 2170.0000, 'Grandmaster', 5.7000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(90, 2200.0000, 'Grandmaster', 5.8500, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(91, 2240.0000, 'Legendary', 6.1000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(92, 2280.0000, 'Legendary', 6.3000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(93, 2320.0000, 'Legendary', 6.5000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(94, 2360.0000, 'Legendary', 6.7000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(95, 2400.0000, 'Legendary', 6.9000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(96, 2440.0000, 'Legendary', 7.1000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(97, 2480.0000, 'Legendary', 7.3000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(98, 2520.0000, 'Legendary', 7.5000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(99, 2560.0000, 'Legendary', 7.7000, '2025-04-19 11:09:33', '2025-04-19 11:09:33'),
(100, 2600.0000, 'Ultimate', 8.0000, '2025-04-19 11:09:33', '2025-04-19 11:09:33');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('difficulty_multiplier', '0.001');

-- --------------------------------------------------------

--
-- Table structure for table `timers`
--

CREATE TABLE `timers` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accumulated_seconds` bigint UNSIGNED DEFAULT '0',
  `start_time` datetime DEFAULT NULL,
  `is_running` tinyint(1) NOT NULL DEFAULT '0',
  `current_level` int UNSIGNED NOT NULL DEFAULT '1',
  `notified_level` int UNSIGNED NOT NULL DEFAULT '1',
  `earned_income` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `categories` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'Uncategorized',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_progress`
--

CREATE TABLE `user_progress` (
  `id` int UNSIGNED NOT NULL DEFAULT '1',
  `bank_balance` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `last_bank_update` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `user_progress`
--

INSERT INTO `user_progress` (`id`, `bank_balance`, `last_bank_update`) VALUES
(1, 0.3760, '2025-04-20 07:39:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `levels`
--
ALTER TABLE `levels`
  ADD PRIMARY KEY (`level`);

--
-- Indexes for table `levels_ranks`
--
ALTER TABLE `levels_ranks`
  ADD PRIMARY KEY (`level`);

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
  ADD KEY `current_level` (`current_level`),
  ADD KEY `is_running` (`is_running`),
  ADD KEY `categories` (`categories`);

--
-- Indexes for table `user_progress`
--
ALTER TABLE `user_progress`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `timers`
--
ALTER TABLE `timers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `timers`
--
ALTER TABLE `timers`
  ADD CONSTRAINT `timers_ibfk_1` FOREIGN KEY (`current_level`) REFERENCES `levels` (`level`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
