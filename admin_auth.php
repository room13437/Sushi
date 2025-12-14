<?php
// เริ่ม session ถ้ายังไม่ได้เริ่ม
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// สร้างตาราง admin_users ถ้ายังไม่มี
$createTableSQL = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$conn->query($createTableSQL);

// ตรวจสอบว่ามี admin user แล้วหรือยัง
$checkAdmin = $conn->query("SELECT COUNT(*) as count FROM admin_users");
$result = $checkAdmin->fetch_assoc();

// ถ้ายังไม่มี ให้สร้าง admin user เริ่มต้น
if ($result['count'] == 0) {
    $defaultUsername = 'admin';
    $defaultPassword = password_hash('AdminN_N', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO admin_users (username, password, full_name) VALUES (?, ?, ?)");
    $fullName = 'ผู้ดูแลระบบ';
    $stmt->bind_param("sss", $defaultUsername, $defaultPassword, $fullName);
    $stmt->execute();
    $stmt->close();
}

// ฟังก์ชันตรวจสอบการ login
function checkAdminLogin($username, $password, $conn)
{
    $stmt = $conn->prepare("SELECT id, password, full_name FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // อัปเดต last_login
            $updateStmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $user['id']);
            $updateStmt->execute();
            $updateStmt->close();

            // เก็บข้อมูลใน session
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_name'] = $user['full_name'];
            $_SESSION['admin_login_time'] = time();

            $stmt->close();
            return true;
        }
    }
    $stmt->close();
    return false;
}

// จัดการ login request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (checkAdminLogin($username, $password, $conn)) {
        // Redirect to formmenu after successful login
        header('Location: formmenu');
        exit;
    } else {
        $loginError = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง";
    }
}

// จัดการ logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_login');
    exit;
}

// ตรวจสอบ session timeout (15 นาที)
$sessionTimeout = 15 * 60; // 15 minutes
if (isset($_SESSION['admin_logged_in']) && isset($_SESSION['admin_login_time'])) {
    if (time() - $_SESSION['admin_login_time'] > $sessionTimeout) {
        session_destroy();
        header('Location: admin_login');
        exit;
    }
    // Update login time
    $_SESSION['admin_login_time'] = time();
}

// ฟังก์ชันตรวจสอบว่า login แล้วหรือยัง
function requireAdminLogin()
{
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    return true;
}
?>