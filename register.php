<?php
session_start();
include "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($username) || empty($password)) {
        $_SESSION['register_error'] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        header('Location: formregister');
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['register_error'] = 'รหัสผ่านไม่ตรงกัน กรุณาลองใหม่อีกครั้ง';
        header('Location: formregister');
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['register_error'] = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
        header('Location: formregister');
        exit;
    }

    // ตรวจสอบ username ซ้ำ
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['register_error'] = 'ชื่อผู้ใช้นี้มีคนใช้แล้ว กรุณาเลือกชื่อใหม่';
        $check->close();
        header('Location: formregister');
        exit;
    }
    $check->close();

    // เข้ารหัสรหัสผ่าน
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // บันทึกข้อมูล
    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $hash);

    if ($stmt->execute()) {
        $_SESSION['register_success'] = 'สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ';
        $stmt->close();
        $conn->close();
        header('Location: formlogin');
        exit;
    } else {
        $_SESSION['register_error'] = 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง';
        $stmt->close();
        header('Location: formregister');
        exit;
    }
}

$conn->close();
?>