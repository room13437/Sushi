<?php
// Load environment variables
require_once __DIR__ . '/config.php';

// Get database configuration from environment variables
$host = env('DB_HOST', 'localhost');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
$db = env('DB_NAME', 'my_login_db');

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}
?>