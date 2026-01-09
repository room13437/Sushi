<?php
require_once 'db_config.php';

echo "<h2>🔍 ตรวจสอบตาราง Products</h2>";

// ตรวจสอบว่าตาราง products มีอยู่หรือไม่
$check_table = $conn->query("SHOW TABLES LIKE 'products'");

if ($check_table->num_rows > 0) {
    echo "✅ ตาราง 'products' มีอยู่<br><br>";

    // นับจำนวนข้อมูล
    $count_result = $conn->query("SELECT COUNT(*) as total FROM products");
    $count = $count_result->fetch_assoc()['total'];

    echo "<strong>จำนวนสินค้าทั้งหมด:</strong> $count รายการ<br><br>";

    if ($count > 0) {
        // แสดงข้อมูล 5 รายการแรก
        echo "<h3>ตัวอย่างข้อมูล 5 รายการแรก:</h3>";
        $result = $conn->query("SELECT id, name, price, description FROM products LIMIT 5");

        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>ชื่อ</th><th>ราคา</th><th>คำอธิบาย</th></tr>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . number_format($row['price']) . " บาท</td>";
            echo "<td>" . htmlspecialchars(substr($row['description'], 0, 50)) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ <strong>ตาราง products ว่างเปล่า!</strong><br>";
        echo "<p style='color: red;'>ไม่มีข้อมูลสินค้าในฐานข้อมูล กรุณาเพิ่มสินค้าผ่านระบบ admin</p>";
    }

} else {
    echo "❌ <strong>ไม่พบตาราง 'products'!</strong><br>";
    echo "<p style='color: red;'>ต้องสร้างตาราง products ก่อน</p>";

    // แสดงตารางที่มีอยู่
    echo "<br><h3>ตารางที่มีในฐานข้อมูล:</h3>";
    $tables = $conn->query("SHOW TABLES");
    while ($table = $tables->fetch_array()) {
        echo "- " . $table[0] . "<br>";
    }
}

echo "<br><br>";
echo "<a href='index.php' style='padding: 10px 20px; background: #F97316; color: white; text-decoration: none; border-radius: 5px;'>กลับหน้าหลัก</a> ";
echo "<a href='formmenu' style='padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Admin Menu</a>";

$conn->close();
?>