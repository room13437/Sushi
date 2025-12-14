<?php
// ไฟล์ logout.php

// 1. ต้องเริ่ม Session ก่อน ถึงจะสามารถทำลาย Session ได้
session_start();

// 2. ทำลายตัวแปร Session ทั้งหมดในอาร์เรย์ $_SESSION
$_SESSION = array();

// 3. (ทางเลือก/แนะนำ) ทำลายคุกกี้ Session บนเครื่องผู้ใช้
// เพื่อให้แน่ใจว่าเบราว์เซอร์ล้างข้อมูล Session เก่าออกไป
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 4. ทำลาย Session บนเซิร์ฟเวอร์
session_destroy();

// 5. เปลี่ยนเส้นทางผู้ใช้กลับไปหน้า Login
// โปรดตรวจสอบว่าชื่อไฟล์ Login ของคุณถูกต้อง (ในที่นี้ใช้ formlogin.html)
header('Location: /');
exit;
?>