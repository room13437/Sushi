<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "products";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$target_dir = "uploads/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$success = false;
$error_message = "";
$title = "";
$image_path = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);

    $target_file = $target_dir . basename($_FILES["promo_image"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $new_file_name = uniqid('promo_', true) . "." . $imageFileType;
    $final_target_file = $target_dir . $new_file_name;

    if ($_FILES["promo_image"]["size"] > 5000000) {
        $error_message .= "ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 5MB)<br>";
        $uploadOk = 0;
    }

    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        $error_message .= "อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG & GIF เท่านั้น<br>";
        $uploadOk = 0;
    }

    if ($uploadOk == 0) {
        if (empty($error_message)) {
            $error_message .= "ไฟล์ไม่ได้ถูกอัพโหลด";
        }
    } else {
        if (move_uploaded_file($_FILES["promo_image"]["tmp_name"], $final_target_file)) {
            $image_path = $final_target_file;

            $sql = "INSERT INTO promotions (title, description, image_path) 
                    VALUES ('$title', '$description', '$image_path')";

            if ($conn->query($sql) === TRUE) {
                $success = true;
                $_SESSION['promo_status'] = 'success';
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

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #fffbf0 0%, #ffe0b2 50%, #ffccbc 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 600px;
            background: white;
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            text-align: center;
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
            font-family: 'Prompt', sans-serif;
            font-weight: 700;
            margin-bottom: 25px;
            font-size: 2rem;
        }

        .box {
            padding: 25px;
            border-radius: 15px;
            margin: 25px 0;
            text-align: left;
        }

        .success-box {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-left: 5px solid var(--success-green);
        }

        .error-box {
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            border-left: 5px solid var(--danger-red);
        }

        .btn {
            display: inline-block;
            min-width: 200px;
            padding: 14px 25px;
            border-radius: 12px;
            text-decoration: none;
            color: white;
            text-align: center;
            transition: all 0.3s;
            font-family: 'Prompt', sans-serif;
            font-weight: 700;
            margin: 5px;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
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

        .promo-img {
            max-width: 100%;
            border-radius: 10px;
            margin-top: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div class="container">
        <?php if ($success): ?>
            <div class="icon">🎉</div>
            <h2>บันทึกโปรโมชั่นสำเร็จ!</h2>
            <div class="box success-box">
                <p><strong>หัวข้อ:</strong> <?php echo htmlspecialchars($title); ?></p>
                <p><strong>รูปภาพ:</strong> <?php echo htmlspecialchars(basename($image_path)); ?></p>
            </div>
            <?php if (file_exists($image_path)): ?>
                <img src="<?php echo htmlspecialchars($image_path); ?>" class="promo-img">
            <?php endif; ?>
            <div style="margin-top: 30px;">
                <a href="manage_promotions" class="btn btn-secondary">📊 จัดการโปรโมชั่น</a>
                <a href="upload_promotion" class="btn btn-success">➕ เพิ่มอีก</a>
                <a href="index" class="btn btn-primary">🏠 หน้าหลัก</a>
            </div>
        <?php else: ?>
            <div class="icon">❌</div>
            <h2>เกิดข้อผิดพลาด!</h2>
            <div class="box error-box">
                <p><?php echo $error_message; ?></p>
            </div>
            <a href="upload_promotion" class="btn btn-primary">🔙 กลับไปแก้ไข</a>
        <?php endif; ?>
    </div>
</body>

</html>