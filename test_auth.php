<?php
// ไฟล์ทดสอบว่า admin_auth.php ทำงานหรือไม่
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 ทดสอบระบบ Admin Authentication</h2>";

echo "<p>1. กำลังเชื่อมต่อ admin_auth.php...</p>";
try {
    require_once 'admin_auth.php';
    echo "<p style='color: green;'>✅ โหลด admin_auth.php สำเร็จ</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    die();
}

echo "<p>2. กำลังตรวจสอบ session...</p>";
if (isset($_SESSION)) {
    echo "<p style='color: green;'>✅ Session ทำงานปกติ</p>";
    echo "<pre>Session data: " . print_r($_SESSION, true) . "</pre>";
} else {
    echo "<p style='color: red;'>❌ Session ไม่ทำงาน</p>";
}

echo "<p>3. กำลังตรวจสอบการ login...</p>";
$isLoggedIn = requireAdminLogin();
if ($isLoggedIn) {
    echo "<p style='color: green;'>✅ Login แล้ว</p>";
} else {
    echo "<p style='color: blue;'>ℹ️ ยังไม่ได้ login</p>";
}

echo "<p>4. กำลังตรวจสอบ database...</p>";
if (isset($conn) && $conn) {
    echo "<p style='color: green;'>✅ เชื่อมต่อ database สำเร็จ</p>";

    // ตรวจสอบตาราง admin_users
    $checkTable = $conn->query("SHOW TABLES LIKE 'admin_users'");
    if ($checkTable && $checkTable->num_rows > 0) {
        echo "<p style='color: green;'>✅ ตาราง admin_users มีอยู่</p>";

        // นับจำนวน admin users
        $countResult = $conn->query("SELECT COUNT(*) as count FROM admin_users");
        if ($countResult) {
            $count = $countResult->fetch_assoc();
            echo "<p>จำนวน admin users: <strong>{$count['count']}</strong></p>";
        }
    } else {
        echo "<p style='color: red;'>❌ ไม่พบตาราง admin_users</p>";
        echo "<p>👉 กรุณารัน <a href='setup_admin.php'>setup_admin.php</a> เพื่อสร้างตาราง</p>";
    }
} else {
    echo "<p style='color: red;'>❌ ไม่สามารถเชื่อมต่อ database</p>";
}

echo "<hr>";
echo "<h3>✅ การทดสอบเสร็จสิ้น</h3>";
echo "<p><a href='formmenu'>ไปที่ formmenu</a></p>";
?>