<?php
require_once 'db_config.php';

echo "<h2>📦 Updating Database Schema...</h2>";

// Add stock_quantity column if it doesn't exist
$sql = "SHOW COLUMNS FROM products LIKE 'stock_quantity'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $alterSql = "ALTER TABLE products ADD COLUMN stock_quantity INT(11) DEFAULT 0 AFTER price";
    if ($conn->query($alterSql) === TRUE) {
        echo "✅ Added 'stock_quantity' column to 'products' table.<br>";
    } else {
        echo "❌ Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ 'stock_quantity' column already exists.<br>";
}

echo "<br><h3>🎉 Database Update Complete!</h3>";
echo "<a href='manage_products'>Go to Manage Products</a>";

$conn->close();
?>