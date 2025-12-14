-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 11, 2025 at 11:25 AM
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
(1, '123456', '$2y$10$Ps5L0iWR.fwd7AWuK1Iaz.hifv722YI/i37mFc8CQ3ZgaSGvVcNuS', '2025-12-10 08:58:19', 0, 0, 0),
(2, '1', '$2y$10$cpFhLHk1EiqeNfMAbm.vCOjGOLwRs0XqZBsXHvxTRjuqJYdTnzfYy', '2025-12-10 09:05:19', 0, 0, 0),
(3, '123', '$2y$10$UcCAZE8EihNhPhGgh8jiNOX.Dd/0B4fBgawvokCfR6Zd.CenNwil.', '2025-12-10 09:06:33', 5, 0, 0),
(4, '22', '$2y$10$XwCht4W1Ty2Iargw8poKweM8xBnmEyJLpVZNZXtABXu46Qtvwcmy2', '2025-12-10 09:08:43', 0, 0, 0),
(5, '33', '$2y$10$9ZUlcGW5vz2YWHVNX5f3y.bp/DwbH3a06JTkjYE.WqA74I6NHCTNG', '2025-12-10 09:09:10', 100, 1, 1765441138),
(6, 'กก', '$2y$10$Z.DAXlp.Xwyv0jWK9zKYtu7IG2LNQ4echZ8DtQ7dDVZVLJzy//1dC', '2025-12-10 10:23:31', 0, 0, 0),
(7, 'Jame', '$2y$10$0T5DkMJ5xs1p9zIQ6Jfnbeu/7SAYhA7aWxU820jJaWcHuThutLQJG', '2025-12-11 04:30:54', 2, 0, 1765427486),
(8, '66', '$2y$10$1cTHQH67vwwz9gTMGApXg./czseF4i1e6ZQo/6mbS66yoUOjFR1Ma', '2025-12-11 05:52:43', 1, 0, 1765432386);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `daily_queue`
--
ALTER TABLE `daily_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `queue_date` (`queue_date`);

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
-- AUTO_INCREMENT for table `daily_queue`
--
ALTER TABLE `daily_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
