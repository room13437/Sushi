<?php
// เริ่ม Session เพื่อใช้ Flash Message
session_start();

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

// โฟลเดอร์สำหรับเก็บรูปภาพที่อัพโหลด
$target_dir = "uploads/";

// ตรวจสอบว่าโฟลเดอร์มีอยู่หรือไม่ ถ้าไม่มีให้สร้าง
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$success = false;
$error_message = "";
$product_name = "";
$price = "";
$image_path = "";

// ตรวจสอบว่ามีการส่งฟอร์มมาหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. รับข้อมูลจากฟอร์ม
    $product_name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = $conn->real_escape_string($_POST['price']);

    // 2. จัดการการอัพโหลดไฟล์
    $target_file = $target_dir . basename($_FILES["product_image"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // สร้างชื่อไฟล์ใหม่ที่ไม่ซ้ำกันเพื่อป้องกันการเขียนทับ
    $new_file_name = uniqid('product_', true) . "." . $imageFileType;
    $final_target_file = $target_dir . $new_file_name;

    // ตรวจสอบขนาดไฟล์ (เช่น ไม่เกิน 5MB)
    if ($_FILES["product_image"]["size"] > 5000000) {
        $error_message .= "ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 5MB)<br>";
        $uploadOk = 0;
    }

    // อนุญาตเฉพาะบางประเภทไฟล์
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        $error_message .= "อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG & GIF เท่านั้น<br>";
        $uploadOk = 0;
    }

    // ตรวจสอบว่า $uploadOk เป็น 0 หรือไม่
    if ($uploadOk == 0) {
        $error_message .= "ไฟล์ไม่ได้ถูกอัพโหลด";
    } else {
        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $final_target_file)) {
            // 3. บันทึกข้อมูลลงฐานข้อมูล
            $image_path = $final_target_file;

            $sql = "INSERT INTO products (name, description, price, image_path) 
                    VALUES ('$product_name', '$description', '$price', '$image_path')";

            if ($conn->query($sql) === TRUE) {
                $success = true;

                // บันทึกข้อมูลลง Session สำหรับแสดงใน index.php
                $_SESSION['upload_status'] = 'success';
                $_SESSION['product_name'] = $product_name;
                $_SESSION['price'] = $price;
                $_SESSION['image_path'] = $image_path;
            } else {
                $error_message = "Database Error: " . $conn->error;
            }
        } else {
            $error_message = "มีข้อผิดพลาดในการอัพโหลดไฟล์";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $success ? '✅ สำเร็จ' : '❌ ผิดพลาด'; ?> - Delizio Shabu</title>
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

        /* Result Container */
        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1;
            text-align: center;
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

        .icon {
            font-size: 5rem;
            margin-bottom: 20px;
            animation: bounce 1s infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-15px);
            }
        }

        h2 {
            background: linear-gradient(90deg, var(--primary-red), var(--primary-orange));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
            font-family: 'Prompt', sans-serif;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .success-box {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            padding: 25px;
            border-radius: 15px;
            border-left: 5px solid var(--success-green);
            margin: 25px 0;
            text-align: left;
        }

        .success-box p {
            margin: 12px 0;
            font-size: 1.1rem;
            color: var(--dark-brown);
        }

        .success-box strong {
            color: var(--success-green);
            font-family: 'Prompt', sans-serif;
        }

        .error-box {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            padding: 25px;
            border-radius: 15px;
            border-left: 5px solid var(--danger-red);
            margin: 25px 0;
            text-align: left;
        }

        .error-box p {
            margin: 12px 0;
            font-size: 1.1rem;
            color: var(--dark-brown);
        }

        .product-image {
            max-width: 300px;
            width: 100%;
            height: auto;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            margin: 20px auto;
            display: block;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            flex: 1;
            min-width: 200px;
            padding: 14px 25px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.05rem;
            font-weight: 700;
            font-family: 'Prompt', sans-serif;
            text-decoration: none;
            color: white;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-orange), #ff9800);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--primary-red), #e53935);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-green), #66bb6a);
        }

        /* Responsive */
        @media (max-width: 600px) {
            .container {
                padding: 35px 25px;
            }

            h2 {
                font-size: 1.6rem;
            }

            .btn {
                min-width: 100%;
            }

            .icon {
                font-size: 4rem;
            }
        }

        /* Confetti animation for success */
        @keyframes confetti {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
            }

            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }

        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: var(--primary-orange);
            top: -10px;
            animation: confetti 3s linear;
            z-index: 999;
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

    <div class="container">
        <?php if ($success): ?>
            <div class="icon">🎉</div>
            <h2>บันทึกเมนูเรียบร้อยแล้ว!</h2>

            <div class="success-box">
                <p><strong>ชื่อเมนู:</strong> <?php echo htmlspecialchars($product_name); ?></p>
                <p><strong>ราคา:</strong> <?php echo htmlspecialchars($price); ?> บาท</p>
                <p><strong>ไฟล์รูปภาพ:</strong> <?php echo htmlspecialchars(basename($image_path)); ?></p>
            </div>

            <?php if (file_exists($image_path)): ?>
                <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($product_name); ?>"
                    class="product-image">
            <?php endif; ?>

            <div class="button-group">
                <a href="index" class="btn btn-primary">🏠 กลับหน้าหลัก</a>
                <a href="upload_form" class="btn btn-success">➕ เพิ่มเมนูอื่น</a>
                <a href="display_products" class="btn btn-secondary">📊 ดูรายการเมนู</a>
            </div>

            <script>
                // สร้าง confetti effect
                function createConfetti() {
                    for (let i = 0; i < 50; i++) {
                        setTimeout(() => {
                            const confetti = document.createElement('div');
                            confetti.className = 'confetti';
                            confetti.style.left = Math.random() * 100 + '%';
                            confetti.style.background = ['#d32f2f', '#ff6f00', '#4caf50', '#2196F3'][Math.floor(Math.random() * 4)];
                            confetti.style.animationDelay = Math.random() * 2 + 's';
                            document.body.appendChild(confetti);

                            setTimeout(() => confetti.remove(), 3000);
                        }, i * 50);
                    }
                }
                createConfetti();
            </script>

        <?php else: ?>
            <div class="icon">❌</div>
            <h2>เกิดข้อผิดพลาด!</h2>

            <div class="error-box">
                <p><?php echo $error_message; ?></p>
            </div>

            <div class="button-group">
                <a href="upload_form.php" class="btn btn-primary">🔙 กลับไปฟอร์ม</a>
                <a href="index.php" class="btn btn-secondary">🏠 กลับหน้าหลัก</a>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>