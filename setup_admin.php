<?php
// สร้างตารางและ admin user อัตโนมัติ
// เปิดไฟล์นี้ใน browser: http://localhost/setup_admin.php

require_once 'db.php';

echo "<h2>🔧 ติดตั้งระบบ Admin Authentication</h2>";

// 1. สร้างตาราง admin_users
echo "<p>1. กำลังสร้างตาราง admin_users...</p>";
$createTableSQL = "CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL,
    INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($createTableSQL)) {
    echo "<p style='color: green;'>✅ สร้างตารางสำเร็จ!</p>";
} else {
    echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
}

// 2. ตรวจสอบว่ามี admin user แล้วหรือยัง
echo "<p>2. กำลังตรวจสอบ admin user...</p>";
$checkAdmin = $conn->query("SELECT COUNT(*) as count FROM admin_users");

if ($checkAdmin) {
    $result = $checkAdmin->fetch_assoc();

    if ($result['count'] == 0) {
        // 3. สร้าง admin user
        echo "<p>3. กำลังสร้าง admin user...</p>";
        $defaultUsername = 'admin';
        $defaultPassword = password_hash('AdminN_N', PASSWORD_DEFAULT);
        $fullName = 'ผู้ดูแลระบบ';

        $stmt = $conn->prepare("INSERT INTO admin_users (username, password, full_name) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $defaultUsername, $defaultPassword, $fullName);

        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ สร้าง admin user สำเร็จ!</p>";
            echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
            echo "<h3>📋 ข้อมูล Login:</h3>";
            echo "<p><strong>Username:</strong> admin</p>";
            echo "<p><strong>Password:</strong> AdminN_N</p>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>❌ Error: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        echo "<p style='color: blue;'>ℹ️ มี admin user อยู่แล้ว (จำนวน: {$result['count']} user)</p>";

        // แสดงรายการ admin users
        $users = $conn->query("SELECT id, username, full_name, created_at, last_login FROM admin_users");
        if ($users && $users->num_rows > 0) {
            echo "<h3>👥 รายการ Admin Users:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
            echo "<tr style='background: #f5f5f5;'><th>ID</th><th>Username</th><th>Full Name</th><th>Created</th><th>Last Login</th></tr>";
            while ($user = $users->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td><strong>" . htmlspecialchars($user['username']) . "</strong></td>";
                echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
                echo "<td>" . $user['created_at'] . "</td>";
                echo "<td>" . ($user['last_login'] ?? '-') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
}

echo "<hr>";
echo "<h3>✅ เสร็จสิ้นการติดตั้ง!</h3>";
echo "<p><a href='formmenu' style='padding: 10px 20px; background: #FF6F00; color: white; text-decoration: none; border-radius: 5px;'>ไปที่ Admin Panel</a></p>";
echo "<p style='color: #666; font-size: 0.9em;'>⚠️ หลังจากติดตั้งเสร็จแล้ว ควรลบไฟล์ setup_admin.php ออกเพื่อความปลอดภัย</p>";

$conn->close();
?>