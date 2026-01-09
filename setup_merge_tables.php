<?php
require_once 'db_config.php';

// 1. Accounting Table
$sql = "CREATE TABLE IF NOT EXISTS `accounting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_date` date NOT NULL,
  `transaction_type` enum('income','expense') NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_date` (`transaction_date`),
  KEY `idx_type` (`transaction_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$conn->query($sql);
echo "Checked/Created 'accounting'<br>";

// 2. Promotions Table
$sql = "CREATE TABLE IF NOT EXISTS `promotions` (
  `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
$conn->query($sql);
echo "Checked/Created 'promotions'<br>";

// 3. Store Locations Table
$sql = "CREATE TABLE IF NOT EXISTS `store_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `address` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$conn->query($sql);
echo "Checked/Created 'store_locations'<br>";

// 4. Daily Queue Table (Re-create if exists with wrong schema, or alter)
// For safety, let's just create if not exists. If it exists from previous attempts, we assume it's correct or we alter it.
$sql = "CREATE TABLE IF NOT EXISTS `daily_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `queue_number` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `pickup_time` time DEFAULT NULL,
  `queue_date` date NOT NULL,
  `status` enum('Waiting','Called','Completed','Cancelled') DEFAULT 'Waiting',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `queue_date` (`queue_date`,`queue_number`),
  KEY `queue_date_2` (`queue_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
$conn->query($sql);
echo "Checked/Created 'daily_queue'<br>";

// 5. Bookings
$sql = "CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `booker_name` varchar(255) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `booking_number` varchar(50) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_number` (`booking_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
$conn->query($sql);
echo "Checked/Created 'bookings'<br>";

// 6. Migrate Store Settings (Key-Value)
// First, check if store_settings has 'setting_key' column
$res = $conn->query("SHOW COLUMNS FROM store_settings LIKE 'setting_key'");
if ($res->num_rows == 0) {
    // Old schema detected (open_time, close_time columns). Drop and Recreate.
    $conn->query("DROP TABLE IF EXISTS `store_settings`");

    $sql = "CREATE TABLE `store_settings` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `setting_key` varchar(50) NOT NULL,
      `setting_value` varchar(255) NOT NULL,
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $conn->query($sql);

    // Insert Defaults
    $conn->query("INSERT INTO store_settings (setting_key, setting_value) VALUES 
        ('store_open_time', '10:00'),
        ('store_close_time', '20:00'),
        ('store_status', 'OPEN')");

    echo "Migrated 'store_settings' to Key-Value pair.<br>";
} else {
    echo "'store_settings' already in Key-Value format.<br>";
}

echo "Database Merge Complete.";
?>