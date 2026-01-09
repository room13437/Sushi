<?php
require_once 'db_config.php';

echo "<h2>🔧 ตั้งค่าระบบซูชิละกัน (Shared Database)</h2>";

// 1. สร้างตาราง products
echo "<p>1. กำลังสร้างตาราง products...</p>";
$createProductsSQL = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($createProductsSQL)) {
    echo "<p style='color: green;'>✅ สร้างตาราง products สำเร็จ!</p>";
} else {
    echo "<p style='color: red;'>❌ Error (Products): " . $conn->error . "</p>";
}

// 2. สร้างตาราง admin_users
echo "<p>2. กำลังสร้างตาราง admin_users...</p>";
$createAdminSQL = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($createAdminSQL)) {
    echo "<p style='color: green;'>✅ สร้างตาราง admin_users สำเร็จ!</p>";
} else {
    echo "<p style='color: red;'>❌ Error (Admin Users): " . $conn->error . "</p>";
}

// 3. สร้าง admin default ถ้ายังไม่มี
$checkAdmin = $conn->query("SELECT COUNT(*) as count FROM admin_users");
if ($checkAdmin) {
    $row = $checkAdmin->fetch_assoc();
    if ($row['count'] == 0) {
        echo "<p>3. กำลังสร้างผู้ดูแลระบบเริ่มต้น...</p>";
        $user = '2544';
        $pass = '2545';
        $name = 'ผู้ดูแลระบบ';
        $stmt = $conn->prepare("INSERT INTO admin_users (username, password, full_name) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $user, $pass, $name);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ สร้างผู้ดูแลระบบสำเร็จ! (User: 2544 / Pass: 2545)</p>";
        }
        $stmt->close();
    } else {
        echo "<p style='color: blue;'>ℹ️ มีผู้ดูแลระบบในระบบแล้ว</p>";
    }
}

echo "<hr>";
echo "<h3>🚀 ตั้งค่าเสร็จสิ้น!</h3>";
echo "<p><a href='index.php'>ไปที่หน้าแรก</a> | <a href='upload_form.php'>เข้าสู่ระบบเพื่ออัพโหลด</a></p>";
?>