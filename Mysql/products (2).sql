-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 22, 2025 at 09:38 AM
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
-- Database: `products`
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
(1, 'admin', '$2y$10$YzJkNjE4ZTNhYjQ4Y2Y5Y.nB5tFVZqGKqVqGKqVqGKqVqGKqVqGK', 'ผู้ดูแลระบบ', '2025-12-13 10:54:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) UNSIGNED NOT NULL,
  `booker_name` varchar(255) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `booking_number` varchar(50) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_queue`
--

INSERT INTO `daily_queue` (`id`, `queue_number`, `customer_name`, `phone_number`, `details`, `queue_date`, `created_at`) VALUES
(1, 1, 'dwdw', 'dwdw', 'dwdw', '2025-12-11', '2025-12-11 06:17:16'),
(2, 2, 'dwdw', 'dwdw', 'dwdw', '2025-12-11', '2025-12-11 06:21:20'),
(3, 3, 'กำำกำ', '0610143416', '', '2025-12-11', '2025-12-11 06:21:36'),
(4, 4, 'กำำกำ', '0610143416', '', '2025-12-11', '2025-12-11 06:22:30'),
(5, 5, 'กำำกำ', '0610143416', '', '2025-12-11', '2025-12-11 06:22:35');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image_path`, `created_at`) VALUES
(51, 'เนื้อ 1', '', 100.00, 'uploads/product_693d177f6913b2.03169199.jpg', '2025-12-13 07:36:31'),
(52, 'เนื้อ 2', '', 100.00, 'uploads/product_693d178dea0032.38544572.jpg', '2025-12-13 07:36:45'),
(54, 'เนื้อ 4', '', 100.00, 'uploads/product_693d17a4236a54.47802916.jpg', '2025-12-13 07:37:08'),
(55, 'เนื้อ 5', 'กไกไกไ', 100.00, 'uploads/product_693d17add22285.45984373.jpg', '2025-12-13 07:37:17'),
(56, 'ผัก 1', '', 50.00, 'uploads/product_693d17cfa0a071.81198208.jpg', '2025-12-13 07:37:51'),
(57, 'ผัก 2', 'ดำดำ', 50.00, 'uploads/product_693d17d8ad0f18.26534023.jpg', '2025-12-13 07:38:00'),
(58, 'ผัก 3', '', 50.00, 'uploads/product_693d17e08719a2.38103518.jpg', '2025-12-13 07:38:08'),
(59, 'ผัก 4', '5ชิ้น 1', 50.00, 'uploads/product_693d17ea085151.37559113.jpg', '2025-12-13 07:38:18');

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` int(6) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 13.81496599, 100.32093564, 'ร้าน SushiLagan', '2025-12-22 08:03:20');

-- --------------------------------------------------------

--
-- Table structure for table `store_settings`
--

CREATE TABLE `store_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_settings`
--

INSERT INTO `store_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'store_open_time', '10:00', '2025-12-19 10:34:25'),
(2, 'store_close_time', '20:00', '2025-12-19 10:35:09'),
(3, 'store_status', 'OPEN', '2025-12-19 10:34:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_number` (`booking_number`);

--
-- Indexes for table `daily_queue`
--
ALTER TABLE `daily_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `queue_date` (`queue_date`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `store_locations`
--
ALTER TABLE `store_locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `store_settings`
--
ALTER TABLE `store_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_queue`
--
ALTER TABLE `daily_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `store_locations`
--
ALTER TABLE `store_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `store_settings`
--
ALTER TABLE `store_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
