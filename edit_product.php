<?php
// เริ่ม session
session_start();

// ตรวจสอบการ login (ใช้ session จาก formmenu)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login');
    exit;
}

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

$product = null;
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);

// ตรวจสอบว่า product_id ถูกต้องหรือไม่
if ($product_id <= 0) {
    header('Location: display_products');
    exit;
}

$target_dir = "uploads/";
$message = "";

// --- ส่วนที่ 1: การประมวลผลการบันทึกข้อมูลที่แก้ไข (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && $product_id > 0) {
    $product_name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = $conn->real_escape_string($_POST['price']);
    $old_image_path = $conn->real_escape_string($_POST['old_image_path']);
    $image_path_to_save = $old_image_path;

    $uploadOk = 1;

    // 1. จัดการการอัพโหลดไฟล์ใหม่ (ถ้ามีการเลือกไฟล์ใหม่)
    if (!empty($_FILES["product_image"]["name"])) {

        if (!empty($old_image_path) && file_exists($old_image_path)) {
            $final_target_file = $old_image_path;
            $imageFileType = strtolower(pathinfo($final_target_file, PATHINFO_EXTENSION));
        } else {
            $imageFileType = strtolower(pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION));
            $new_file_name = uniqid('product_', true) . "." . $imageFileType;
            $final_target_file = $target_dir . $new_file_name;
        }

        // ตรวจสอบขนาดไฟล์ (สูงสุด 5MB)
        if ($_FILES["product_image"]["size"] > 5000000) {
            $message .= "ขออภัย, ไฟล์ของคุณมีขนาดใหญ่เกินไป (สูงสุด 5MB).<br>";
            $uploadOk = 0;
        }

        // อนุญาตเฉพาะบางประเภทไฟล์
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            $message .= "ขออภัย, อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG & GIF เท่านั้น.<br>";
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $final_target_file)) {
                $image_path_to_save = $final_target_file;
            } else {
                $message .= "ขออภัย, มีข้อผิดพลาดในการอัพโหลดไฟล์ใหม่.<br>";
                $uploadOk = 0;
            }
        }
    }

    // 2. อัปเดตข้อมูลลงฐานข้อมูล
    if ($uploadOk == 1) {
        $sql = "UPDATE products SET name=?, description=?, price=?, image_path=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdsi", $product_name, $description, $price, $image_path_to_save, $product_id);

        if ($stmt->execute()) {
            $message = "<div style='background:#d4edda; color:#155724; padding:15px; border-radius:10px; margin:20px 0; border-left:4px solid #28a745;'><strong>✅ บันทึกการแก้ไขเรียบร้อยแล้ว!</strong></div>";
            // Reload to show updated data
            $_GET['id'] = $product_id; // Keep the ID
        } else {
            $message .= "Error: " . $stmt->error;
        }
        $stmt->close();
    }

}
// --- ส่วนที่ 2: ดึงข้อมูลเดิมมาแสดงในฟอร์ม (GET) ---
else if ($product_id > 0) {
    $sql = "SELECT id, name, description, price, image_path FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        $message = "ไม่พบสินค้าที่ต้องการแก้ไข";
    }
    $stmt->close();
} else {
    $message = "ระบุ ID สินค้าที่ถูกต้องเพื่อแก้ไข";
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✏️ แก้ไขเมนู - Delizio Shabu</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700&display=swap');

        :root {
            --primary-red: #d32f2f;
            --primary-orange: #ff6f00;
            --cream: #fff8e1;
            --dark-brown: #3e2723;
            --success-green: #4caf50;
            --danger-red: #f44336;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #fffbf0 0%, #ffe0b2 50%, #ffccbc 100%);
            background-attachment: fixed;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            position: relative;
        }

        /* Steam Animation */
        @keyframes steam {
            0% {
                transform: translateY(0) scale(1);
                opacity: 0;
            }

            50% {
                opacity: 0.3;
            }

            100% {
                transform: translateY(-150px) scale(1.8);
                opacity: 0;
            }
        }

        .steam-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .steam {
            position: absolute;
            bottom: -50px;
            width: 60px;
            height: 60px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.4) 0%, transparent 70%);
            border-radius: 50%;
            animation: steam 10s infinite ease-in-out;
        }

        .steam:nth-child(1) {
            left: 5%;
            animation-delay: 0s;
        }

        .steam:nth-child(2) {
            left: 25%;
            animation-delay: 2s;
        }

        .steam:nth-child(3) {
            left: 45%;
            animation-delay: 4s;
        }

        .steam:nth-child(4) {
            left: 65%;
            animation-delay: 1.5s;
        }

        .steam:nth-child(5) {
            left: 85%;
            animation-delay: 3.5s;
        }

        /* Login Container */
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 40px;
            background: white;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            border-radius: 20px;
            position: relative;
            z-index: 1;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            text-align: center;
            font-size: 4rem;
            margin-bottom: 10px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .login-container h2 {
            text-align: center;
            background: linear-gradient(90deg, var(--primary-red), var(--primary-orange));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 30px;
            font-size: 1.8rem;
            font-family: 'Prompt', sans-serif;
            font-weight: 700;
        }

        .login-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-brown);
            font-size: 1.05rem;
        }

        input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Sarabun', sans-serif;
            transition: all 0.3s;
        }

        input[type="password"]:focus {
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 3px rgba(255, 111, 0, 0.1);
            outline: none;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            margin-top: 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 700;
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, var(--success-green), #66bb6a);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        #login-error {
            color: var(--danger-red);
            text-align: center;
            margin-top: 15px;
            font-weight: 600;
            padding: 12px;
            background: #ffebee;
            border-radius: 8px;
            border-left: 4px solid var(--danger-red);
        }

        /* Product Edit Container */
        .container {
            max-width: 650px;
            width: 100%;
            background: white;
            padding: 40px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            border-radius: 20px;
            position: relative;
            z-index: 1;
            animation: slideUp 0.5s ease-out;
        }

        h1 {
            text-align: center;
            background: linear-gradient(90deg, var(--primary-red), var(--primary-orange));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 30px;
            font-size: 2rem;
            font-family: 'Prompt', sans-serif;
            font-weight: 700;
        }

        .logout-btn {
            width: 100%;
            padding: 12px;
            margin-bottom: 25px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 700;
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, var(--danger-red), #e53935);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
        }

        .logout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(244, 67, 54, 0.4);
        }

        label {
            display: block;
            margin-top: 20px;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-brown);
            font-size: 1.05rem;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Sarabun', sans-serif;
            transition: all 0.3s;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus {
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 3px rgba(255, 111, 0, 0.1);
            outline: none;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        input[type="file"] {
            padding: 12px;
            background: #f5f5f5;
            cursor: pointer;
        }

        input[type="file"]:hover {
            background: #eeeeee;
        }

        .current-image {
            display: block;
            max-width: 250px;
            height: auto;
            margin: 15px auto;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            border: 3px solid var(--cream);
        }

        input[type="submit"] {
            width: 100%;
            background: linear-gradient(135deg, var(--primary-orange), #ff9800);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            margin-top: 25px;
            font-size: 1.1rem;
            font-weight: 700;
            font-family: 'Prompt', sans-serif;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
        }

        input[type="submit"]:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 111, 0, 0.4);
        }

        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: var(--primary-red);
            text-decoration: none;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s;
        }

        .back-link:hover {
            color: var(--primary-orange);
            transform: translateX(-5px);
        }

        .error {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 12px;
            background: #ffebee;
            color: var(--danger-red);
            border-left: 4px solid var(--danger-red);
            font-weight: 600;
        }

        .no-image-text {
            text-align: center;
            color: #999;
            padding: 20px;
            font-style: italic;
        }

        /* Responsive */
        @media (max-width: 768px) {

            .container,
            .login-container {
                padding: 25px;
            }

            h1 {
                font-size: 1.6rem;
            }

            .current-image {
                max-width: 200px;
            }
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-10px);
            }

            75% {
                transform: translateX(10px);
            }
        }
    </style>
</head>

<body>

    <div class="steam-bg">
        <div class="steam"></div>
        <div class="steam"></div>
        <div class="steam"></div>
        <div class="steam"></div>
        <div class="steam"></div>
    </div>

    <div class="container" id="product-edit-container">

        <a href="formmenu" class="logout-btn"
            style="display: inline-block; text-align: center; text-decoration: none;">← กลับเมนูหลัก</a>

        <h1>✏️ แก้ไขเมนู ID: <?php echo $product_id; ?></h1>

        <?php if ($message && !$product): ?>
            <div class="error"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($product): ?>
            <form action="edit_product" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">
                <input type="hidden" name="old_image_path" value="<?php echo htmlspecialchars($product['image_path']); ?>">

                <label for="name">🍜 ชื่อเมนู:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>

                <label for="description">📝 รายละเอียด:</label>
                <textarea id="description" name="description"
                    required><?php echo htmlspecialchars($product['description']); ?></textarea>

                <label for="price">💰 ราคา (บาท):</label>
                <input type="number" id="price" name="price" step="0.01"
                    value="<?php echo htmlspecialchars($product['price']); ?>" required>

                <label>📸 รูปภาพปัจจุบัน:</label>
                <?php if ($product['image_path'] && file_exists($product['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="Current Image"
                        class="current-image">
                <?php else: ?>
                    <p class="no-image-text">ไม่มีรูปภาพเดิม</p>
                <?php endif; ?>

                <label for="product_image">🖼️ อัพโหลดรูปภาพใหม่ (ถ้าต้องการเปลี่ยน):</label>
                <input type="file" name="product_image" id="product_image" accept="image/jpeg,image/png,image/gif">

                <input type="submit" value="✅ บันทึกการแก้ไข">
            </form>
        <?php endif; ?>

        <a href="display_products" class="back-link">← กลับไปรายการเมนู</a>
    </div>

    <script>
        // Using protect_admin.php for authentication
    </script>
</body>

</html>