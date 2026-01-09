<?php
/**
 * Create Products Table Setup Script
 */

require_once 'db_config.php';

echo "<h2>📦 Setting up Products Table...</h2>";

$sql = "CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'products' created/exists successfully.<br>";
} else {
    echo "❌ Error creating table: " . $conn->error . "<br>";
}

// Create uploads directory if not exists
$target_dir = "uploads/products/";
if (!file_exists($target_dir)) {
    if (mkdir($target_dir, 0777, true)) {
        echo "✅ Directory 'uploads/products/' created.<br>";
    } else {
        echo "⚠️ Failed to create directory 'uploads/products/'. Check permissions.<br>";
    }
} else {
    echo "ℹ️ Directory 'uploads/products/' already exists.<br>";
}

echo "<br><h3 style='color: green;'>🎉 Setup Complete!</h3>";
echo "<p><a href='manage_products' style='padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Go to Product Management</a></p>";
echo "<p><a href='formmenu' style='padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px;'>Back to Admin Menu</a></p>";

$conn->close();
?>