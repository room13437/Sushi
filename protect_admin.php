<?php
/**
 * Admin Session Protection Helper
 * 
 * ไฟล์นี้ใช้สำหรับป้องกันหน้าที่ต้องการสิทธิ์ admin โดยใช้ admin_auth.php
 * เพิ่มที่ต้นไฟล์ PHP ที่ต้องการป้องกัน
 * 
 * ตัวอย่างการใช้งาน:
 * <?php
 * require_once 'protect_admin.php';
 * ?>
 */

require_once 'admin_auth.php';

// ตรวจสอบว่า login แล้วหรือยัง
if (!requireAdminLogin()) {
    // ถ้ายังไม่ login ให้ redirect ไปหน้า admin_login
    header('Location: admin_login');
    exit;
}

// ถ้า login แล้วจะทำงานต่อไปได้ตามปกติ
?>