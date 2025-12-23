-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 22, 2025 at 09:37 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `my_login_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `full_name`, `created_at`, `last_login`) VALUES
(1, 'admin', '$2y$10$uzZU6Q/3axQVVNPyrckYd.WebT7/8wA6u299oh8SjYyXWXwfwFfKi', 'ผู้ดูแลระบบ', '2025-12-13 10:43:45', '2025-12-14 03:25:44'),
(2, '1', '$2y$10$szXuE5VdhpkLwr6M.RIczORB046RmCa/.y5R26Jln7.RLpr0eBqTK', 'สำรอง', '2025-12-14 03:27:47', '2025-12-22 08:26:27');

-- --------------------------------------------------------

--
-- Table structure for table `code_redemptions`
--

CREATE TABLE `code_redemptions` (
  `id` int(11) NOT NULL,
  `code_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `redeemed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_queue`
--

CREATE TABLE `daily_queue` (
  `id` int(11) NOT NULL,
  `queue_number` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `queue_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daily_queue`
--

INSERT INTO `daily_queue` (`id`, `queue_number`, `customer_name`, `phone_number`, `details`, `queue_date`, `created_at`) VALUES
(1, 1, 'กไกไ', '0601616', '1561616', '2025-12-11', '2025-12-11 06:04:56'),
(2, 2, 'กไกไ', '06', '', '2025-12-11', '2025-12-11 06:29:55'),
(3, 3, 'กไกไ', '06', '', '2025-12-11', '2025-12-11 06:32:18'),
(4, 4, 'กไกไ', '06', 'กๆกๆไกไๆกไๆ', '2025-12-11', '2025-12-11 06:32:29'),
(5, 5, 'กไกไ', '06', 'กๆกๆไกไๆกไๆกไกไกไ', '2025-12-11', '2025-12-11 06:33:21'),
(6, 6, 'ำไๆดไำดไำดไำดำไ', 'ำไดไำดไำ', 'ไำดไำดไำ', '2025-12-11', '2025-12-11 06:33:36');

-- --------------------------------------------------------

--
-- Table structure for table `redeem_codes`
--

CREATE TABLE `redeem_codes` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 10,
  `max_uses` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `redeem_codes`
--

INSERT INTO `redeem_codes` (`id`, `code`, `points`, `max_uses`, `created_at`) VALUES
(19, '123', 10000, 1, '2025-12-20 06:51:58');

-- --------------------------------------------------------

--
-- Table structure for table `redemption_history`
--

CREATE TABLE `redemption_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code` varchar(100) NOT NULL,
  `points` int(11) NOT NULL,
  `type` varchar(20) DEFAULT 'code',
  `redeemed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `redemption_history`
--

INSERT INTO `redemption_history` (`id`, `user_id`, `code`, `points`, `type`, `redeemed_at`) VALUES
(119, 15, '123', 500, 'code', '2025-12-19 06:41:44'),
(120, 15, 'แลกซูชิ: 1 ชิ้น (#47)', -100, 'sushi', '2025-12-19 06:41:59'),
(121, 15, 'แลกซูชิ: 4 ชิ้น (#48)', -300, 'sushi', '2025-12-19 06:42:04'),
(122, 15, 'แลกซูชิ: 1 ชิ้น (#49)', -100, 'sushi', '2025-12-19 06:42:05'),
(123, 15, '123', 10000, 'code', '2025-12-19 09:46:05'),
(124, 15, '1234', 5000, 'code', '2025-12-19 09:46:12'),
(125, 15, 'แลกซูชิ: 4 ชิ้น (#50)', -300, 'sushi', '2025-12-19 09:46:17'),
(126, 15, 'แลกซูชิ: 4 ชิ้น (#51)', -300, 'sushi', '2025-12-19 09:46:19'),
(127, 15, 'แลกซูชิ: 4 ชิ้น (#52)', -300, 'sushi', '2025-12-19 09:46:22'),
(128, 15, 'แลกซูชิ: 4 ชิ้น (#53)', -300, 'sushi', '2025-12-19 09:46:23'),
(129, 15, 'แลกซูชิ: 4 ชิ้น (#54)', -300, 'sushi', '2025-12-19 09:46:23'),
(130, 15, 'แลกซูชิ: 4 ชิ้น (#55)', -300, 'sushi', '2025-12-19 09:46:24'),
(131, 15, 'แลกซูชิ: 4 ชิ้น (#56)', -300, 'sushi', '2025-12-19 09:46:24'),
(132, 15, 'แลกซูชิ: 4 ชิ้น (#57)', -300, 'sushi', '2025-12-19 09:46:24'),
(133, 15, 'แลกซูชิ: 4 ชิ้น (#58)', -300, 'sushi', '2025-12-19 09:46:25'),
(134, 15, 'แลกซูชิ: 4 ชิ้น (#59)', -300, 'sushi', '2025-12-19 09:46:25'),
(135, 15, 'แลกซูชิ: 4 ชิ้น (#60)', -300, 'sushi', '2025-12-19 09:46:25'),
(136, 15, 'แลกซูชิ: 4 ชิ้น (#61)', -300, 'sushi', '2025-12-19 09:46:26'),
(137, 15, 'แลกซูชิ: 4 ชิ้น (#62)', -300, 'sushi', '2025-12-19 09:46:26'),
(138, 15, 'แลกซูชิ: 4 ชิ้น (#63)', -300, 'sushi', '2025-12-19 09:46:27'),
(139, 15, 'แลกซูชิ: 4 ชิ้น (#64)', -300, 'sushi', '2025-12-19 09:46:27'),
(140, 15, 'แลกซูชิ: 4 ชิ้น (#65)', -300, 'sushi', '2025-12-19 09:46:27'),
(141, 15, 'แลกซูชิ: 4 ชิ้น (#66)', -300, 'sushi', '2025-12-19 09:46:28'),
(142, 15, 'แลกซูชิ: 4 ชิ้น (#67)', -300, 'sushi', '2025-12-19 09:46:28'),
(143, 15, 'แลกซูชิ: 4 ชิ้น (#68)', -300, 'sushi', '2025-12-19 09:46:28'),
(144, 15, 'แลกซูชิ: 4 ชิ้น (#69)', -300, 'sushi', '2025-12-19 09:46:29'),
(145, 15, 'แลกซูชิ: 4 ชิ้น (#70)', -300, 'sushi', '2025-12-19 09:46:29'),
(146, 15, 'แลกซูชิ: 4 ชิ้น (#71)', -300, 'sushi', '2025-12-19 09:46:29'),
(147, 15, 'แลกซูชิ: 4 ชิ้น (#72)', -300, 'sushi', '2025-12-19 09:46:30'),
(148, 15, 'แลกซูชิ: 4 ชิ้น (#73)', -300, 'sushi', '2025-12-19 09:46:30'),
(149, 15, 'แลกซูชิ: 4 ชิ้น (#74)', -300, 'sushi', '2025-12-19 09:46:31'),
(150, 15, 'แลกซูชิ: 4 ชิ้น (#75)', -300, 'sushi', '2025-12-19 09:46:31'),
(151, 15, 'แลกซูชิ: 4 ชิ้น (#76)', -300, 'sushi', '2025-12-19 09:46:31'),
(152, 15, 'แลกซูชิ: 4 ชิ้น (#77)', -300, 'sushi', '2025-12-19 09:46:32'),
(153, 15, 'แลกซูชิ: 4 ชิ้น (#78)', -300, 'sushi', '2025-12-19 09:46:32'),
(154, 15, 'แลกซูชิ: 4 ชิ้น (#79)', -300, 'sushi', '2025-12-19 09:46:33'),
(155, 15, 'แลกซูชิ: 4 ชิ้น (#80)', -300, 'sushi', '2025-12-19 09:46:33'),
(156, 15, 'แลกซูชิ: 4 ชิ้น (#81)', -300, 'sushi', '2025-12-19 09:46:33'),
(157, 15, 'แลกซูชิ: 4 ชิ้น (#82)', -300, 'sushi', '2025-12-19 09:46:34'),
(158, 15, 'แลกซูชิ: 4 ชิ้น (#83)', -300, 'sushi', '2025-12-19 09:46:34'),
(159, 15, 'แลกซูชิ: 4 ชิ้น (#84)', -300, 'sushi', '2025-12-19 09:46:34'),
(160, 15, 'แลกซูชิ: 4 ชิ้น (#85)', -300, 'sushi', '2025-12-19 09:46:35'),
(161, 15, 'แลกซูชิ: 4 ชิ้น (#86)', -300, 'sushi', '2025-12-19 09:48:10'),
(162, 15, 'แลกซูชิ: 4 ชิ้น (#87)', -300, 'sushi', '2025-12-19 09:48:12'),
(163, 15, 'แลกซูชิ: 4 ชิ้น (#88)', -350, 'sushi', '2025-12-20 03:34:02'),
(164, 15, 'แลกซูชิ: 4 ชิ้น (#89)', -350, 'sushi', '2025-12-20 03:34:05'),
(165, 15, 'แลกซูชิ: 5 ชิ้น (#90)', -450, 'sushi', '2025-12-20 09:38:38'),
(166, 15, 'แลกซูชิ: 5 ชิ้น (#91)', -450, 'sushi', '2025-12-20 09:38:47'),
(167, 15, 'แลกซูชิ: 5 ชิ้น (#92)', -450, 'sushi', '2025-12-20 09:38:53');

-- --------------------------------------------------------

--
-- Table structure for table `reward_claims`
--

CREATE TABLE `reward_claims` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `points_used` int(11) NOT NULL,
  `items_count` int(11) NOT NULL,
  `status` enum('pending','fulfilled','cancelled') DEFAULT 'pending',
  `claimed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fulfilled_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reward_claims`
--

INSERT INTO `reward_claims` (`id`, `user_id`, `username`, `points_used`, `items_count`, `status`, `claimed_at`, `fulfilled_at`) VALUES
(47, 15, '11', 100, 1, 'pending', '2025-12-19 06:41:59', NULL),
(48, 15, '11', 300, 4, 'pending', '2025-12-19 06:42:04', NULL),
(49, 15, '11', 100, 1, 'cancelled', '2025-12-19 06:42:05', NULL),
(50, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:17', NULL),
(51, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:19', NULL),
(52, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:22', NULL),
(53, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:23', NULL),
(54, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:23', NULL),
(55, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:24', NULL),
(56, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:24', NULL),
(57, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:24', NULL),
(58, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:25', NULL),
(59, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:25', NULL),
(60, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:25', NULL),
(61, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:26', NULL),
(62, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:26', NULL),
(63, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:27', NULL),
(64, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:27', NULL),
(65, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:27', NULL),
(66, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:28', NULL),
(67, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:28', NULL),
(68, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:28', NULL),
(69, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:29', NULL),
(70, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:29', NULL),
(71, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:29', NULL),
(72, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:30', NULL),
(73, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:30', NULL),
(74, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:31', NULL),
(75, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:31', NULL),
(76, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:31', NULL),
(77, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:32', NULL),
(78, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:32', NULL),
(79, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:33', NULL),
(80, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:33', NULL),
(81, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:33', NULL),
(82, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:34', NULL),
(83, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:34', NULL),
(84, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:34', NULL),
(85, 15, '11', 300, 4, 'pending', '2025-12-19 09:46:35', NULL),
(86, 15, '11', 300, 4, 'pending', '2025-12-19 09:48:10', NULL),
(87, 15, '11', 300, 4, 'pending', '2025-12-19 09:48:12', NULL),
(88, 15, '11', 350, 4, 'pending', '2025-12-20 03:34:02', NULL),
(89, 15, '11', 350, 4, 'fulfilled', '2025-12-20 03:34:05', '2025-12-20 09:35:58'),
(90, 15, '11', 450, 5, 'pending', '2025-12-20 09:38:38', NULL),
(91, 15, '11', 450, 5, 'pending', '2025-12-20 09:38:47', NULL),
(92, 15, '11', 450, 5, 'pending', '2025-12-20 09:38:53', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `store_locations`
--

CREATE TABLE `store_locations` (
  `id` int(11) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `address` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `store_locations`
--

INSERT INTO `store_locations` (`id`, `latitude`, `longitude`, `address`, `updated_at`) VALUES
(1, 13.74238656, 100.48016885, 'มารุซูชิ Paradise', '2025-12-20 03:49:08');

-- --------------------------------------------------------

--
-- Table structure for table `sushi_redemption_tiers`
--

CREATE TABLE `sushi_redemption_tiers` (
  `id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `pieces` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sushi_redemption_tiers`
--

INSERT INTO `sushi_redemption_tiers` (`id`, `points`, `pieces`, `created_at`) VALUES
(4, 100, 1, '2025-12-20 03:33:09'),
(5, 200, 2, '2025-12-20 03:33:15'),
(6, 350, 4, '2025-12-20 03:33:34'),
(7, 450, 5, '2025-12-20 09:37:17');

-- --------------------------------------------------------

--
-- Table structure for table `truemoney_vouchers`
--

CREATE TABLE `truemoney_vouchers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `voucher_code` varchar(16) NOT NULL,
  `phone_recipient` varchar(20) DEFAULT '0610143416',
  `amount_baht` decimal(10,2) NOT NULL,
  `points_added` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'success',
  `redeemed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `truemoney_vouchers`
--

INSERT INTO `truemoney_vouchers` (`id`, `user_id`, `voucher_code`, `phone_recipient`, `amount_baht`, `points_added`, `status`, `redeemed_at`) VALUES
(1, 5, '1bd4523a7808c8cc', '0610143416', 46.00, 46, 'success', '2025-12-13 06:53:40'),
(3, 5, 'c2e71b8b07925c0e', '0610143416', 62.00, 62, 'success', '2025-12-13 06:54:46');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `points` int(11) DEFAULT 0,
  `code_1234_redeemed` int(11) DEFAULT 0,
  `last_redeem_1234_time` bigint(20) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `created_at`, `points`, `code_1234_redeemed`, `last_redeem_1234_time`) VALUES
(15, '11', '$2y$10$6tqu81pvPluT90Iry9q9ouyiNHfLEvuV.rIsBQVEJOzuP5JLOh4Xi', '2025-12-19 05:45:56', 0, 0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `code_redemptions`
--
ALTER TABLE `code_redemptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_code` (`code_id`,`user_id`),
  ADD KEY `code_id` (`code_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `daily_queue`
--
ALTER TABLE `daily_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `queue_date` (`queue_date`);

--
-- Indexes for table `redeem_codes`
--
ALTER TABLE `redeem_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `redemption_history`
--
ALTER TABLE `redemption_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reward_claims`
--
ALTER TABLE `reward_claims`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `store_locations`
--
ALTER TABLE `store_locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sushi_redemption_tiers`
--
ALTER TABLE `sushi_redemption_tiers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `points` (`points`);

--
-- Indexes for table `truemoney_vouchers`
--
ALTER TABLE `truemoney_vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `voucher_code` (`voucher_code`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `code_redemptions`
--
ALTER TABLE `code_redemptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `daily_queue`
--
ALTER TABLE `daily_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `redeem_codes`
--
ALTER TABLE `redeem_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `redemption_history`
--
ALTER TABLE `redemption_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT for table `reward_claims`
--
ALTER TABLE `reward_claims`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `store_locations`
--
ALTER TABLE `store_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sushi_redemption_tiers`
--
ALTER TABLE `sushi_redemption_tiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `truemoney_vouchers`
--
ALTER TABLE `truemoney_vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
