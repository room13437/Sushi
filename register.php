<?php
// ไฟล์ register.php (หรือชื่อไฟล์ที่คุณใช้)
include "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // ตรวจสอบ username ซ้ำ
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // หากชื่อผู้ใช้ซ้ำ
        echo "ชื่อผู้ใช้นี้มีอยู่แล้ว!";
        exit;
    }

    // เข้ารหัสรหัสผ่าน (ใช้ bcrypt เป็นมาตรฐานที่ดี)
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $hash);

    if ($stmt->execute()) {
        // *** ส่วนที่แก้ไข: การเปลี่ยนเส้นทางอัตโนมัติ ***

        // 1. กำหนด Header Location ไปยังหน้า formlogin.html (สมมติว่าเป็นไฟล์ HTML)
        // ถ้า formlogin เป็นไฟล์ PHP ให้ใช้ header('Location: formlogin.php');
        header('Location: formlogin'); 
        
        // 2. หยุดการทำงานของสคริปต์ทันที
        exit; 

    } else {
        echo "เกิดข้อผิดพลาด: " . $stmt->error;
    }
    
    $stmt->close();
}

$conn->close();

?>