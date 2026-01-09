<?php
require_once 'db_config.php';

// SQL to create table
$sql = "CREATE TABLE IF NOT EXISTS `store_settings` (
  `id` int(11) NOT NULL PRIMARY KEY,
  `open_time` time NOT NULL DEFAULT '00:00:00',
  `close_time` time NOT NULL DEFAULT '00:00:00',
  `manual_override` tinyint(1) NOT NULL DEFAULT 0,
  `is_open_now` tinyint(1) NOT NULL DEFAULT 1,
  `closed_days` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Table store_settings created successfully or already exists.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// SQL to insert default data
$sql_insert = "INSERT INTO `store_settings` (`id`, `open_time`, `close_time`, `manual_override`, `is_open_now`, `closed_days`) 
VALUES (1, '09:00:00', '22:00:00', 0, 1, '') ON DUPLICATE KEY UPDATE id=id;";

if ($conn->query($sql_insert) === TRUE) {
    echo "Default data inserted successfully.<br>";
} else {
    echo "Error inserting data: " . $conn->error . "<br>";
}

$conn->close();
?>