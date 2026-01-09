<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sushilagan"; // ฐานข้อมูลหลักที่ใช้ร่วมกันทั้งหมด

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>