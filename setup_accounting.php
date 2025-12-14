<?php
require_once 'db_config.php';

// สร้างตาราง accounting สำหรับบันทึกรายรับ-รายจ่าย
$sql = "CREATE TABLE IF NOT EXISTS accounting (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATE NOT NULL,
    transaction_type ENUM('income', 'expense') NOT NULL,
    category VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (transaction_date),
    INDEX idx_type (transaction_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "<h2 style='color:green;'>✅ ตาราง 'accounting' สร้างสำเร็จ หรือมีอยู่แล้ว!</h2>";
    echo "<p>กรุณาไปที่ <a href='manage_accounting'>ระบบจัดการบัญชี</a></p>";
} else {
    echo "<h2 style='color:red;'>❌ Error creating table: " . $conn->error . "</h2>";
}

$conn->close();
?>