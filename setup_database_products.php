<?php
/**
 * Setup script for Products Table in consolidated database
 */
require_once 'db_config.php';

echo "<h2>📦 Consolidating Database: Products Table</h2>";

// 1. Create Table
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
    echo "✅ Table 'products' is ready.<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// 2. Add Seed Data if empty
$check = $conn->query("SELECT id FROM products LIMIT 1");
if ($check->num_rows == 0) {
    echo "🌱 Adding sample products...<br>";
    $seed = [
        ['Salmon Nigiri', 120.00, 'Fresh salmon on vinegared rice', 'icon/icons.png'],
        ['Tuna Sashimi', 250.00, 'Premium sliced bluefin tuna', 'icon/icons.png'],
        ['California Roll', 180.00, 'Crab stick, avocado, and cucumber', 'icon/icons.png']
    ];

    $stmt = $conn->prepare("INSERT INTO products (name, price, description, image_path) VALUES (?, ?, ?, ?)");
    foreach ($seed as $item) {
        $stmt->bind_param("sdss", $item[0], $item[1], $item[2], $item[3]);
        $stmt->execute();
    }
    echo "✅ Sample products added.<br>";
}

// 3. Ensure uploads folder exists
if (!file_exists('uploads/products')) {
    mkdir('uploads/products', 0777, true);
    echo "✅ Directory 'uploads/products' created.<br>";
}

echo "<br><h3 style='color: green;'>🎉 Integration Complete!</h3>";
echo "<p><a href='index.php#menu' style='padding: 10px 20px; background: #F97316; color: white; text-decoration: none; border-radius: 5px;'>View Index</a> | ";
echo "<a href='manage_products' style='padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>Manage Products</a></p>";

$conn->close();
?>