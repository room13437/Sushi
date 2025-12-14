-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 14, 2025 at 10:26 AM
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
(2, '1', '$2y$10$szXuE5VdhpkLwr6M.RIczORB046RmCa/.y5R26Jln7.RLpr0eBqTK', 'สำรอง', '2025-12-14 03:27:47', '2025-12-14 08:29:17');

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
(1, 5, 'แลกเงิน: 20 บาท', -100, 'exchange', '2025-12-12 10:57:12'),
(2, 5, 'แลกเงิน: 40 บาท', -200, 'exchange', '2025-12-12 10:57:22'),
(3, 5, 'กาชา: ได้ 8 (สุทธิ 3)', 3, 'gacha', '2025-12-12 10:57:24'),
(4, 5, 'กาชา: ได้ 4 (สุทธิ -1)', -1, 'gacha', '2025-12-12 10:57:29'),
(5, 5, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-12 10:57:31'),
(6, 5, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-12 10:59:14'),
(7, 5, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-12 10:59:16'),
(8, 5, 'กาชา: ได้ 6 (สุทธิ 1)', 1, 'gacha', '2025-12-12 10:59:16'),
(9, 5, '1166', 500, 'code', '2025-12-12 10:59:35'),
(10, 5, 'แลกเงิน: 20 บาท', -100, 'exchange', '2025-12-12 11:00:06'),
(11, 5, 'แลกเงิน: 70 บาท', -300, 'exchange', '2025-12-12 11:00:17'),
(12, 5, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-13 03:23:06'),
(13, 5, 'แลกเงิน: 20 บาท', -100, 'exchange', '2025-12-13 03:23:07'),
(14, 5, 'แลกเงิน: 20 บาท', -100, 'exchange', '2025-12-13 03:23:09'),
(15, 5, 'แลกเงิน: 70 บาท', -300, 'exchange', '2025-12-13 03:23:27'),
(16, 5, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-13 03:23:34'),
(17, 5, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-13 03:23:35'),
(18, 5, 'กาชา: ได้ 6 (สุทธิ 1)', 1, 'gacha', '2025-12-13 04:51:26'),
(19, 5, 'กาชา: ได้ 7 (สุทธิ 2)', 2, 'gacha', '2025-12-13 04:51:27'),
(20, 5, 'กาชา: ได้ 8 (สุทธิ 3)', 3, 'gacha', '2025-12-13 04:51:27'),
(21, 5, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-13 04:51:28'),
(22, 5, 'กาชา: ได้ 8 (สุทธิ 3)', 3, 'gacha', '2025-12-13 04:51:28'),
(23, 5, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-13 04:51:28'),
(24, 5, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-13 04:51:28'),
(25, 5, 'กาชา: ได้ 5 (สุทธิ 0)', 0, 'gacha', '2025-12-13 04:51:33'),
(26, 5, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-13 04:51:34'),
(27, 5, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-13 04:51:34'),
(28, 5, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-13 04:51:34'),
(29, 5, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-13 04:51:35'),
(30, 5, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-13 04:51:35'),
(31, 5, 'กาชา: ได้ 6 (สุทธิ 1)', 1, 'gacha', '2025-12-13 04:51:35'),
(32, 5, 'กาชา: ได้ 4 (สุทธิ -1)', -1, 'gacha', '2025-12-13 04:51:36'),
(33, 5, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-13 04:51:36'),
(34, 5, 'กาชา: ได้ 7 (สุทธิ 2)', 2, 'gacha', '2025-12-13 04:51:36'),
(35, 5, 'กาชา: ได้ 4 (สุทธิ -1)', -1, 'gacha', '2025-12-13 04:51:45'),
(36, 5, 'กาชา: ได้ 4 (สุทธิ -1)', -1, 'gacha', '2025-12-13 06:09:09'),
(37, 5, 'แลกเงิน: 40 บาท', -200, 'exchange', '2025-12-13 06:09:20'),
(38, 5, 'แลกเงิน: 20 บาท', -100, 'exchange', '2025-12-13 06:09:21'),
(39, 5, 'TrueMoney: 46 บาท (เบอร์ 0610143416)', 46, 'truemoney', '2025-12-13 06:53:40'),
(40, 5, 'TrueMoney: 62 บาท (เบอร์ 0610143416)', 62, 'truemoney', '2025-12-13 06:54:46'),
(41, 5, 'กาชา: ได้ 4 (สุทธิ -1)', -1, 'gacha', '2025-12-13 06:57:25'),
(42, 5, 'แลกเงิน: 20 บาท', -100, 'exchange', '2025-12-13 07:46:22'),
(43, 5, 'แลกเงิน: 20 บาท', -100, 'exchange', '2025-12-13 07:46:23'),
(44, 5, 'กไกไ', 500, 'code', '2025-12-13 08:51:11'),
(45, 10, '123456', 9999, 'code', '2025-12-13 08:57:40'),
(46, 10, 'แลกซูชิ: 1 ชิ้น (#1)', -100, 'sushi', '2025-12-13 09:04:49'),
(47, 10, 'แลกซูชิ: 1 ชิ้น (#2)', -100, 'sushi', '2025-12-13 09:05:23'),
(48, 5, 'แลกซูชิ: 1 ชิ้น (#3)', -100, 'sushi', '2025-12-13 09:08:44'),
(49, 5, 'แลกซูชิ: 1 ชิ้น (#4)', -100, 'sushi', '2025-12-13 09:10:32'),
(50, 5, 'แลกซูชิ: 1 ชิ้น (#5)', -100, 'sushi', '2025-12-13 09:11:20'),
(51, 5, 'แลกซูชิ: 4 ชิ้น (#6)', -300, 'sushi', '2025-12-13 09:11:47'),
(52, 5, 'แลกซูชิ: 4 ชิ้น (#7)', -300, 'sushi', '2025-12-13 09:12:54'),
(53, 5, 'แลกซูชิ: 4 ชิ้น (#8)', -300, 'sushi', '2025-12-13 09:12:57'),
(54, 5, 'แลกซูชิ: 4 ชิ้น (#9)', -300, 'sushi', '2025-12-13 09:13:02'),
(55, 5, 'แลกซูชิ: 4 ชิ้น (#10)', -300, 'sushi', '2025-12-13 09:13:04'),
(56, 5, 'แลกซูชิ: 4 ชิ้น (#11)', -300, 'sushi', '2025-12-13 09:13:06'),
(57, 5, 'กาชา: ได้ 5 (สุทธิ 0)', 0, 'gacha', '2025-12-13 09:15:36'),
(58, 5, 'กาชา: ได้ 8 (สุทธิ 3)', 3, 'gacha', '2025-12-13 09:15:37'),
(59, 5, 'กาชา: ได้ 7 (สุทธิ 2)', 2, 'gacha', '2025-12-13 09:15:39'),
(60, 5, 'กาชา: ได้ 7 (สุทธิ 2)', 2, 'gacha', '2025-12-13 09:15:40'),
(61, 5, 'กาชา: ได้ 7 (สุทธิ 2)', 2, 'gacha', '2025-12-13 09:15:41'),
(62, 5, 'กาชา: ได้ 5 (สุทธิ 0)', 0, 'gacha', '2025-12-13 09:15:42'),
(63, 5, 'กาชา: ได้ 7 (สุทธิ 2)', 2, 'gacha', '2025-12-13 09:15:43'),
(64, 5, 'กาชา: ได้ 6 (สุทธิ 1)', 1, 'gacha', '2025-12-13 09:15:44'),
(65, 5, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-13 09:15:46'),
(66, 5, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-13 09:15:47'),
(67, 5, 'กาชา: ได้ 6 (สุทธิ 1)', 1, 'gacha', '2025-12-13 09:15:48'),
(68, 5, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-13 09:16:18'),
(69, 5, 'แลกซูชิ: 4 ชิ้น (#12)', -300, 'sushi', '2025-12-13 09:31:08'),
(70, 5, 'แลกซูชิ: 4 ชิ้น (#13)', -300, 'sushi', '2025-12-13 09:31:10'),
(71, 5, 'แลกซูชิ: 4 ชิ้น (#14)', -300, 'sushi', '2025-12-13 09:31:11'),
(72, 5, 'แลกซูชิ: 4 ชิ้น (#15)', -300, 'sushi', '2025-12-13 09:31:12'),
(73, 5, 'แลกซูชิ: 4 ชิ้น (#16)', -300, 'sushi', '2025-12-13 09:31:13'),
(74, 5, 'แลกซูชิ: 4 ชิ้น (#17)', -300, 'sushi', '2025-12-13 09:31:14'),
(75, 5, 'แลกซูชิ: 4 ชิ้น (#18)', -300, 'sushi', '2025-12-13 09:31:14'),
(76, 5, 'แลกซูชิ: 4 ชิ้น (#19)', -300, 'sushi', '2025-12-13 09:31:14'),
(77, 5, 'แลกซูชิ: 4 ชิ้น (#20)', -300, 'sushi', '2025-12-13 09:31:15'),
(78, 5, 'แลกซูชิ: 4 ชิ้น (#21)', -300, 'sushi', '2025-12-13 09:31:15'),
(79, 5, 'แลกซูชิ: 4 ชิ้น (#22)', -300, 'sushi', '2025-12-13 09:31:16'),
(80, 5, 'แลกซูชิ: 4 ชิ้น (#23)', -300, 'sushi', '2025-12-13 09:31:16'),
(81, 5, 'แลกซูชิ: 4 ชิ้น (#24)', -300, 'sushi', '2025-12-13 09:31:16'),
(82, 5, 'แลกซูชิ: 4 ชิ้น (#25)', -300, 'sushi', '2025-12-13 09:31:17'),
(83, 5, 'แลกซูชิ: 4 ชิ้น (#26)', -300, 'sushi', '2025-12-13 09:31:17'),
(84, 5, 'แลกซูชิ: 4 ชิ้น (#27)', -300, 'sushi', '2025-12-13 09:31:18'),
(85, 5, 'แลกซูชิ: 4 ชิ้น (#28)', -300, 'sushi', '2025-12-13 09:31:18'),
(86, 5, 'แลกซูชิ: 4 ชิ้น (#29)', -300, 'sushi', '2025-12-13 09:31:18'),
(87, 5, 'แลกซูชิ: 4 ชิ้น (#30)', -300, 'sushi', '2025-12-13 09:31:19'),
(88, 5, 'แลกซูชิ: 4 ชิ้น (#31)', -300, 'sushi', '2025-12-13 09:31:19'),
(89, 5, 'แลกซูชิ: 4 ชิ้น (#32)', -300, 'sushi', '2025-12-13 09:31:19'),
(90, 5, 'แลกซูชิ: 4 ชิ้น (#33)', -300, 'sushi', '2025-12-13 09:31:20'),
(91, 5, 'แลกซูชิ: 4 ชิ้น (#34)', -300, 'sushi', '2025-12-13 09:31:20'),
(92, 5, 'แลกซูชิ: 4 ชิ้น (#35)', -300, 'sushi', '2025-12-13 09:31:20'),
(93, 5, 'แลกซูชิ: 4 ชิ้น (#36)', -300, 'sushi', '2025-12-13 09:35:03'),
(94, 5, 'แลกซูชิ: 4 ชิ้น (#37)', -300, 'sushi', '2025-12-13 09:35:15'),
(95, 5, 'แลกซูชิ: 1 ชิ้น (#38)', -100, 'sushi', '2025-12-13 09:35:17'),
(96, 5, 'แลกซูชิ: 1 ชิ้น (#39)', -100, 'sushi', '2025-12-13 09:35:21'),
(97, 5, 'แลกซูชิ: 1 ชิ้น (#40)', -100, 'sushi', '2025-12-13 09:35:29'),
(98, 5, 'แลกซูชิ: 4 ชิ้น (#41)', -300, 'sushi', '2025-12-13 09:37:46'),
(99, 11, 'FREENEW', 250, 'code', '2025-12-14 06:58:42'),
(100, 11, 'แลกซูชิ: 1 ชิ้น (#42)', -100, 'sushi', '2025-12-14 06:59:16'),
(101, 11, 'แลกซูชิ: 1 ชิ้น (#43)', -100, 'sushi', '2025-12-14 06:59:40'),
(102, 11, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-14 08:25:50'),
(103, 11, 'กาชา: ได้ 4 (สุทธิ -1)', -1, 'gacha', '2025-12-14 08:25:53'),
(104, 11, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-14 08:25:55'),
(105, 11, 'กาชา: ได้ 7 (สุทธิ 2)', 2, 'gacha', '2025-12-14 08:25:57'),
(106, 11, 'กาชา: ได้ 8 (สุทธิ 3)', 3, 'gacha', '2025-12-14 08:25:59'),
(107, 11, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-14 08:26:02'),
(108, 11, 'กาชา: ไม่ได้ (สุทธิ -5)', -5, 'gacha', '2025-12-14 08:26:03');

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
(1, 10, '55', 100, 1, 'fulfilled', '2025-12-13 09:04:49', '2025-12-13 09:12:11'),
(2, 10, '55', 100, 1, 'fulfilled', '2025-12-13 09:05:23', '2025-12-13 09:12:11'),
(3, 5, '33', 100, 1, 'fulfilled', '2025-12-13 09:08:44', '2025-12-13 09:12:10'),
(4, 5, '33', 100, 1, 'fulfilled', '2025-12-13 09:10:32', '2025-12-13 09:12:10'),
(5, 5, '33', 100, 1, 'fulfilled', '2025-12-13 09:11:20', '2025-12-13 09:12:09'),
(6, 5, '33', 300, 4, 'fulfilled', '2025-12-13 09:11:47', '2025-12-13 09:12:07'),
(7, 5, '33', 300, 4, 'pending', '2025-12-13 09:12:54', NULL),
(8, 5, '33', 300, 4, 'pending', '2025-12-13 09:12:57', NULL),
(9, 5, '33', 300, 4, 'pending', '2025-12-13 09:13:02', NULL),
(10, 5, '33', 300, 4, 'pending', '2025-12-13 09:13:04', NULL),
(11, 5, '33', 300, 4, 'cancelled', '2025-12-13 09:13:06', NULL),
(12, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:08', NULL),
(13, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:10', NULL),
(14, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:11', NULL),
(15, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:12', NULL),
(16, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:13', NULL),
(17, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:14', NULL),
(18, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:14', NULL),
(19, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:14', NULL),
(20, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:15', NULL),
(21, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:15', NULL),
(22, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:16', NULL),
(23, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:16', NULL),
(24, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:16', NULL),
(25, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:17', NULL),
(26, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:17', NULL),
(27, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:18', NULL),
(28, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:18', NULL),
(29, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:18', NULL),
(30, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:19', NULL),
(31, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:19', NULL),
(32, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:19', NULL),
(33, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:20', NULL),
(34, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:20', NULL),
(35, 5, '33', 300, 4, 'pending', '2025-12-13 09:31:20', NULL),
(36, 5, '33', 300, 4, 'pending', '2025-12-13 09:35:03', NULL),
(37, 5, '33', 300, 4, 'pending', '2025-12-13 09:35:15', NULL),
(38, 5, '33', 100, 1, 'pending', '2025-12-13 09:35:17', NULL),
(39, 5, '33', 100, 1, 'pending', '2025-12-13 09:35:21', NULL),
(40, 5, '33', 100, 1, 'fulfilled', '2025-12-13 09:35:29', '2025-12-14 04:10:31'),
(41, 5, '33', 300, 4, 'cancelled', '2025-12-13 09:37:46', NULL),
(42, 11, '99', 100, 1, 'fulfilled', '2025-12-14 06:59:16', '2025-12-14 07:02:21'),
(43, 11, '99', 100, 1, 'fulfilled', '2025-12-14 06:59:40', '2025-12-14 07:01:59');

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
(1, '123456', '$2y$10$Ps5L0iWR.fwd7AWuK1Iaz.hifv722YI/i37mFc8CQ3ZgaSGvVcNuS', '2025-12-10 08:58:19', 11121, 0, 0),
(2, '1', '$2y$10$cpFhLHk1EiqeNfMAbm.vCOjGOLwRs0XqZBsXHvxTRjuqJYdTnzfYy', '2025-12-10 09:05:19', 50, 0, 0),
(3, '123', '$2y$10$UcCAZE8EihNhPhGgh8jiNOX.Dd/0B4fBgawvokCfR6Zd.CenNwil.', '2025-12-10 09:06:33', 5, 0, 0),
(4, '22', '$2y$10$XwCht4W1Ty2Iargw8poKweM8xBnmEyJLpVZNZXtABXu46Qtvwcmy2', '2025-12-10 09:08:43', 0, 0, 0),
(5, '33', '$2y$10$rWZvkkMjIZWigBihyVPhXeecqmdeMrAJ9QEcVNVcKgR/RimKnvJVa', '2025-12-10 09:09:10', 88437, 1, 1765449143),
(6, 'กก', '$2y$10$Z.DAXlp.Xwyv0jWK9zKYtu7IG2LNQ4echZ8DtQ7dDVZVLJzy//1dC', '2025-12-10 10:23:31', 0, 0, 0),
(7, 'Jame', '$2y$10$0T5DkMJ5xs1p9zIQ6Jfnbeu/7SAYhA7aWxU820jJaWcHuThutLQJG', '2025-12-11 04:30:54', 100000, 0, 1765427486),
(8, '66', '$2y$10$1cTHQH67vwwz9gTMGApXg./czseF4i1e6ZQo/6mbS66yoUOjFR1Ma', '2025-12-11 05:52:43', 1, 0, 1765432386),
(9, 'กไไ', '$2y$10$JQVvqc4t40mRoxj9n.jPU.JankkmYxzVD.wwh/SKFGCM6sxoCYgDi', '2025-12-12 10:39:18', 0, 0, 0),
(10, '55', '$2y$10$pi/LJdCLjtHB5UHJwzhy1OPMI6sA1UyE5EKG4y2E.Se9azb6qNDUy', '2025-12-13 08:57:16', 9799, 0, 0),
(11, '99', '$2y$10$Wpn0D9EO3NeTGOz4/qBYhOiR4OGsJ3GOZx28VbHlAAANKzCDYZA9.', '2025-12-13 10:11:58', 34, 0, 0);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `daily_queue`
--
ALTER TABLE `daily_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `redeem_codes`
--
ALTER TABLE `redeem_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `redemption_history`
--
ALTER TABLE `redemption_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `reward_claims`
--
ALTER TABLE `reward_claims`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `truemoney_vouchers`
--
ALTER TABLE `truemoney_vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
