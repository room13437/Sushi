<?php
$servername = "localhost"; // ชื่อเซิร์ฟเวอร์ฐานข้อมูล (ส่วนใหญ่จะเป็น localhost)
$username = "root";       // ชื่อผู้ใช้ฐานข้อมูล
$password = "";           // รหัสผ่านฐานข้อมูล
$dbname = "queue_system"; // ชื่อฐานข้อมูลของคุณ (สมมติชื่อ queue_system)

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// หากเชื่อมต่อสำเร็จ ไม่จำเป็นต้องมี echo
?>