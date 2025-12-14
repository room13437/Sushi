<?php
require_once 'protect_admin.php'; // MySQL Authentication

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "products";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$promotion = null;
$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);
$target_dir = "uploads/";
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && $id > 0) {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $old_image_path = $conn->real_escape_string($_POST['old_image_path']);
    $image_path_to_save = $old_image_path;

    $uploadOk = 1;

    if (!empty($_FILES["promo_image"]["name"])) {
        if (!empty($old_image_path) && file_exists($old_image_path)) {
            $final_target_file = $old_image_path;
            $imageFileType = strtolower(pathinfo($final_target_file, PATHINFO_EXTENSION));
        } else {
            $imageFileType = strtolower(pathinfo($_FILES["promo_image"]["name"], PATHINFO_EXTENSION));
            $new_file_name = uniqid('promo_', true) . "." . $imageFileType;
            $final_target_file = $target_dir . $new_file_name;
        }

        if ($_FILES["promo_image"]["size"] > 5000000) {
            $message .= "ไฟล์ใหญ่เกินไป<br>";
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["promo_image"]["tmp_name"], $final_target_file)) {
                $image_path_to_save = $final_target_file;
            } else {
                $message .= "อัพโหลดไม่สำเร็จ<br>";
                $uploadOk = 0;
            }
        }
    }

    if ($uploadOk == 1) {
        $sql = "UPDATE promotions SET title=?, description=?, image_path=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $title, $description, $image_path_to_save, $id);

        if ($stmt->execute()) {
            header("Location: manage_promotions");
            exit;
        } else {
            $message .= "Error: " . $stmt->error;
        }
        $stmt->close();
    }

} else if ($id > 0) {
    $sql = "SELECT * FROM promotions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $promotion = $result->fetch_assoc();
    } else {
        $message = "ไม่พบโปรโมชั่น";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✏️ แก้ไขโปรโมชั่น - Delizio Sushi</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700&display=swap"
        rel="stylesheet">
    <style>
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
            max-width: 600px;
            width: 100%;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1;
        }

        h1 {
            text-align: center;
            background: linear-gradient(90deg, var(--primary-red), var(--primary-orange));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-family: 'Prompt';
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: 600;
            color: var(--dark-brown);
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
        }

        .current-image {
            max-width: 200px;
            margin: 10px 0;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            margin-top: 20px;
            cursor: pointer;
            font-weight: bold;
            font-family: 'Prompt';
            color: white;
            font-size: 1.1rem;
        }

        .btn-save {
            background: linear-gradient(135deg, var(--primary-orange), #ff9800);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary-red);
            text-decoration: none;
            font-weight: bold;
        }

        /* Steam Animation */
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

        /* Login */
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 40px;
            background: white;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            border-radius: 20px;
            position: absolute;
            max-width: 450px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 999;
            text-align: center;
        }

        .login-btn {
            background: var(--success-green);
        }

        #login-error {
            color: red;
            display: none;
            margin-top: 10px;
        }
    </style>
</head>


<body>

    <div class="steam-bg">
        <div class="steam"></div>
        <div class="steam"></div>
        <div class="steam"></div>
    </div>

    <div class="container" id="edit-content">
        <h1>✏️ แก้ไขโปรโมชั่น</h1>

        <?php if ($message): ?>
            <p style="color:red; text-align:center;"><?php echo $message; ?></p>
        <?php endif; ?>

        <?php if ($promotion): ?>
            <form action="edit_promotion" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($promotion['id']); ?>">
                <input type="hidden" name="old_image_path"
                    value="<?php echo htmlspecialchars($promotion['image_path']); ?>">

                <label>📌 หัวข้อโปรโมชั่น</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($promotion['title']); ?>" required>

                <label>📝 รายละเอียด</label>
                <textarea name="description" rows="5"><?php echo htmlspecialchars($promotion['description']); ?></textarea>

                <label>🖼️ รูปภาพปัจจุบัน</label>
                <?php if ($promotion['image_path']): ?>
                    <img src="<?php echo htmlspecialchars($promotion['image_path']); ?>" class="current-image">
                <?php else: ?>
                    <p>ไม่มีรูปภาพ</p>
                <?php endif; ?>

                <label>📤 เปลี่ยนรูปภาพ (ถ้ามี)</label>
                <input type="file" name="promo_image" accept="image/*">

                <button type="submit" class="btn btn-save">บันทึกการแก้ไข</button>
            </form>
        <?php else: ?>
            <p style="text-align:center;">ไม่พบข้อมูลโปรโมชั่น</p>
        <?php endif; ?>

        <a href="manage_promotions" class="back-link">← กลับไปหน้าจัดการ</a>
    </div>

    </script>
</body>

</html>