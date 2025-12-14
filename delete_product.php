<?php
// กำหนดค่าการเชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "products";

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_to_delete = $_GET['id'];

    // 1. ดึง path รูปภาพเพื่อลบไฟล์จริงออกจากเซิร์ฟเวอร์
    $stmt_select = $conn->prepare("SELECT image_path FROM products WHERE id = ?");
    $stmt_select->bind_param("i", $id_to_delete);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();

    if ($result_select->num_rows > 0) {
        $row = $result_select->fetch_assoc();
        $image_path = $row['image_path'];

        // 2. ลบไฟล์รูปภาพจริง (ถ้ามี)
        if (file_exists($image_path)) {
            unlink($image_path);
        }

        // 3. ลบข้อมูลออกจากฐานข้อมูล
        $stmt_delete = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt_delete->bind_param("i", $id_to_delete);

        if ($stmt_delete->execute()) {
            // ใช้ JavaScript redirect กลับไปหน้าแสดงสินค้า
            echo "<script>alert('ลบสินค้า ID: $id_to_delete เรียบร้อยแล้ว'); window.location.href='display_products';</script>";
        } else {
            echo "Error deleting record: " . $conn->error;
        }
        $stmt_delete->close();
    } else {
        echo "<script>alert('ไม่พบสินค้า ID: $id_to_delete'); window.location.href='display_products.php';</script>";
    }
    $stmt_select->close();
} else {
    echo "<script>alert('ระบุ ID ที่ถูกต้องเพื่อลบสินค้า'); window.location.href='display_products.php';</script>";
}

$conn->close();
?>