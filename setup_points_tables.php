<?php
require_once 'db_config.php';

// Create Customers Table
$sql_customers = "CREATE TABLE IF NOT EXISTS customers (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    points INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_customers) === TRUE) {
    echo "Table 'customers' created successfully.<br>";
} else {
    echo "Error creating table 'customers': " . $conn->error . "<br>";
}

// Create Point Codes Table
$sql_codes = "CREATE TABLE IF NOT EXISTS point_codes (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    points_value INT(11) NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    used_by INT(11) DEFAULT NULL,
    used_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_codes) === TRUE) {
    echo "Table 'point_codes' created successfully.<br>";
} else {
    echo "Error creating table 'point_codes': " . $conn->error . "<br>";
}

// Create Redemption History Table
$sql_history = "CREATE TABLE IF NOT EXISTS redemption_history (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    code VARCHAR(100) NOT NULL,
    points INT(11) NOT NULL,
    type VARCHAR(20) DEFAULT 'code',
    redeemed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_history) === TRUE)
    echo "Table 'redemption_history' created successfully.<br>";
else
    echo "Error creating table 'redemption_history': " . $conn->error . "<br>";

// Create Reward Claims Table (Sushi Redemption)
$sql_claims = "CREATE TABLE IF NOT EXISTS reward_claims (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    username VARCHAR(100) NOT NULL,
    points_used INT(11) NOT NULL,
    items_count INT(11) NOT NULL,
    status ENUM('pending', 'fulfilled', 'cancelled') DEFAULT 'pending',
    claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fulfilled_at TIMESTAMP NULL,
    KEY user_id (user_id),
    KEY status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_claims) === TRUE)
    echo "Table 'reward_claims' created successfully.<br>";
else
    echo "Error creating table 'reward_claims': " . $conn->error . "<br>";

// Create Sushi Redemption Tiers Table
$sql_tiers = "CREATE TABLE IF NOT EXISTS sushi_redemption_tiers (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    points INT(11) NOT NULL UNIQUE,
    pieces INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_tiers) === TRUE) {
    echo "Table 'sushi_redemption_tiers' created successfully.<br>";
    // Insert Default Tiers if empty
    $conn->query("INSERT IGNORE INTO sushi_redemption_tiers (points, pieces) VALUES (50, 5), (100, 12), (200, 25), (500, 60)");
} else {
    echo "Error creating table 'sushi_redemption_tiers': " . $conn->error . "<br>";
}

// Create Code Redemptions Tracking Table (For Multi-use codes per user)
$sql_code_redemptions = "CREATE TABLE IF NOT EXISTS code_redemptions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    code_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    redeemed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY code_user (code_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_code_redemptions) === TRUE)
    echo "Table 'code_redemptions' created successfully.<br>";
else
    echo "Error creating table 'code_redemptions': " . $conn->error . "<br>";

// Update point_codes table to support max_uses if not exists
$conn->query("ALTER TABLE point_codes ADD COLUMN max_uses INT(11) DEFAULT 1 AFTER points_value");

// Update point_codes table to support type (manual/random)
$conn->query("ALTER TABLE point_codes ADD COLUMN type ENUM('random', 'manual') DEFAULT 'random' AFTER code");
// Migration: Assume codes that are NOT 8 chars were manual
$conn->query("UPDATE point_codes SET type='manual' WHERE LENGTH(code) != 8 OR max_uses > 1");

echo "Database setup completed.";
?>