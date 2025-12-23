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
-- Database: `queue_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounting`
--

CREATE TABLE `accounting` (
  `id` int(11) NOT NULL,
  `transaction_date` date NOT NULL,
  `transaction_type` enum('income','expense') NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `pickup_time` time DEFAULT NULL,
  `queue_date` date NOT NULL,
  `status` enum('Waiting','Called','Completed','Cancelled') DEFAULT 'Waiting',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_queue`
--

INSERT INTO `daily_queue` (`id`, `queue_number`, `customer_name`, `phone_number`, `details`, `pickup_time`, `queue_date`, `status`, `created_at`) VALUES
(97, 1, 'fefe', 'fefe', '', NULL, '2025-12-11', 'Waiting', '2025-12-11 09:08:13'),
(98, 2, 'fefe', 'fefef', '', NULL, '2025-12-11', 'Waiting', '2025-12-11 10:30:21'),
(181, 1, 'dwdw', '0610143416', '', NULL, '2025-12-12', 'Waiting', '2025-12-12 08:14:53'),
(182, 2, 'ดำดำ', '0611', '', NULL, '2025-12-12', 'Waiting', '2025-12-12 10:53:39'),
(185, 1, '1', '1', '', NULL, '2025-12-13', 'Waiting', '2025-12-13 07:42:26'),
(186, 2, '1', '1', '', NULL, '2025-12-13', 'Waiting', '2025-12-13 07:42:33'),
(187, 3, '1', '1', '', NULL, '2025-12-13', 'Waiting', '2025-12-13 07:42:37'),
(188, 4, '1', '1', '', NULL, '2025-12-13', 'Waiting', '2025-12-13 07:42:39'),
(189, 5, '1', '1', '', NULL, '2025-12-13', 'Waiting', '2025-12-13 07:42:40'),
(190, 6, '1', '1', '', NULL, '2025-12-13', 'Waiting', '2025-12-13 07:42:40'),
(191, 7, '1', '1', '', NULL, '2025-12-13', 'Waiting', '2025-12-13 07:42:40'),
(192, 8, '1', '1', '', NULL, '2025-12-13', 'Waiting', '2025-12-13 07:42:41'),
(193, 9, '1', '1', '', NULL, '2025-12-13', 'Waiting', '2025-12-13 07:42:41'),
(194, 10, '1', '1', '', NULL, '2025-12-13', 'Waiting', '2025-12-13 07:42:42'),
(195, 11, '1', '1', '', NULL, '2025-12-13', 'Waiting', '2025-12-13 07:42:43'),
(200, 1, '1231321', '00418048080', '', '00:00:00', '2025-12-15', 'Waiting', '2025-12-15 09:45:53'),
(201, 1, '30', '00', '', '00:00:00', '2025-12-16', 'Waiting', '2025-12-16 08:07:44'),
(202, 2, '30', '00', '', '00:00:00', '2025-12-16', 'Waiting', '2025-12-16 08:09:57'),
(203, 3, 'กไกไ', '5151', '', '00:00:00', '2025-12-16', 'Waiting', '2025-12-16 08:10:21'),
(204, 4, '5', '5', '', '00:00:00', '2025-12-16', 'Waiting', '2025-12-16 08:11:50'),
(205, 5, '5', '5', '', '00:00:00', '2025-12-16', 'Waiting', '2025-12-16 08:11:52'),
(206, 6, '841848', '818418', '', '00:00:00', '2025-12-16', 'Waiting', '2025-12-16 08:16:06'),
(207, 7, '1851818', '0610143416', '', '00:00:00', '2025-12-16', 'Waiting', '2025-12-16 08:16:34'),
(209, 1, '2', '2', '', '00:00:00', '2025-12-19', 'Called', '2025-12-19 06:42:57'),
(210, 2, '33', '33', '', '00:00:00', '2025-12-19', 'Waiting', '2025-12-19 07:22:48'),
(211, 1, 'โน๊ต', '0610143416', '', '00:00:00', '2025-12-20', 'Waiting', '2025-12-20 09:13:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounting`
--
ALTER TABLE `accounting`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`transaction_date`),
  ADD KEY `idx_type` (`transaction_type`);

--
-- Indexes for table `daily_queue`
--
ALTER TABLE `daily_queue`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `queue_date` (`queue_date`,`queue_number`),
  ADD KEY `queue_date_2` (`queue_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounting`
--
ALTER TABLE `accounting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `daily_queue`
--
ALTER TABLE `daily_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=212;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
