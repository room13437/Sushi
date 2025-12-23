<?php
// Start session and handle authentication
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'admin_auth.php';

// Check if logged in
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if (!$isLoggedIn) {
    header('Location: admin_login');
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "products";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure store_status exists (auto-create if not)
$conn->query("INSERT INTO store_settings (setting_key, setting_value) VALUES ('store_status', 'OPEN') ON DUPLICATE KEY UPDATE setting_key=setting_key");

// Handle manual status toggle
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $new_status = $_POST['new_status'] ?? 'OPEN';

    $stmt = $conn->prepare("UPDATE store_settings SET setting_value = ? WHERE setting_key = 'store_status'");
    $stmt->bind_param("s", $new_status);

    if ($stmt->execute()) {
        $status_text = $new_status === 'OPEN' ? 'เปิดร้าน' : 'ปิดร้าน';
        $_SESSION['success_message'] = "อัพเดทสถานะเป็น '{$status_text}' สำเร็จ!";
        header('Location: manage_hours');
        exit;
    }
    $stmt->close();
}

// Handle hours update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_hours'])) {
    $open_time = $_POST['open_time'] ?? '';
    $close_time = $_POST['close_time'] ?? '';

    if (!empty($open_time) && !empty($close_time)) {
        // Update opening time
        $stmt1 = $conn->prepare("UPDATE store_settings SET setting_value = ? WHERE setting_key = 'store_open_time'");
        $stmt1->bind_param("s", $open_time);

        // Update closing time
        $stmt2 = $conn->prepare("UPDATE store_settings SET setting_value = ? WHERE setting_key = 'store_close_time'");
        $stmt2->bind_param("s", $close_time);

        if ($stmt1->execute() && $stmt2->execute()) {
            $_SESSION['success_message'] = 'อัพเดทเวลาเปิด-ปิดร้านสำเร็จ!';
            header('Location: manage_hours');
            exit;
        } else {
            $error_message = 'เกิดข้อผิดพลาดในการอัพเดท';
        }

        $stmt1->close();
        $stmt2->close();
    } else {
        $error_message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    }
}

// Get current store hours and status
$open_time = '11:00';
$close_time = '22:00';
$store_status = 'OPEN';

$result = $conn->query("SELECT setting_key, setting_value FROM store_settings WHERE setting_key IN ('store_open_time', 'store_close_time', 'store_status')");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['setting_key'] === 'store_open_time') {
            $open_time = $row['setting_value'];
        } elseif ($row['setting_key'] === 'store_close_time') {
            $close_time = $row['setting_value'];
        } elseif ($row['setting_key'] === 'store_status') {
            $store_status = $row['setting_value'];
        }
    }
}

// Check for success message from session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="icon/icons.png?v=4">
    <title>🕐 จัดการเวลาเปิด-ปิดร้าน | Store Hours Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family:Prompt:wght@400;600;700&display=swap');

        :root {
            --primary-orange: #ff6f00;
            --primary-red: #d32f2f;
            --dark-brown: #3e2723;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --shadow-soft: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(-45deg, #fffbf0, #ffe0b2, #ffccbc, #fffbf0);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .container-box {
            background: white;
            backdrop-filter: blur(15px);
            padding: 50px 40px;
            width: 100%;
            max-width: 650px;
            border-radius: 25px;
            box-shadow: var(--shadow-soft);
            animation: fadeInUp 0.8s ease-out forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-icon {
            font-size: 4rem;
            margin-bottom: 15px;
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

        h2 {
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, #FF6F00, #d32f2f);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2rem;
            margin: 0 0 10px 0;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            font-family: 'Prompt', sans-serif;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .time-input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 1.2rem;
            font-family: 'Sarabun', sans-serif;
            transition: all 0.3s;
            box-sizing: border-box;
            text-align: center;
            fond-weight: 600;
        }

        .time-input:focus {
            outline: none;
            border-color: #FF6F00;
            box-shadow: 0 0 0 3px rgba(255, 111, 0, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #FF6F00, #d32f2f);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            font-family: 'Prompt', sans-serif;
            transition: all 0.3s;
            box-shadow: 0 6px 20px rgba(255, 111, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #d32f2f, #FF6F00);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 111, 0, 0.4);
        }

        .btn-back {
            width: 100%;
            padding: 14px;
            background: white;
            color: #666;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Prompt', sans-serif;
            transition: all 0.3s;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-back:hover {
            background: #f5f5f5;
            border-color: #FF6F00;
            color: #FF6F00;
        }

        .success-message {
            background: linear-gradient(135deg, #4CAF50, #8BC34A);
            color: white;
            padding: 16px 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            animation: slideDown 0.4s ease;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .error-message {
            background: linear-gradient(135deg, #f44336, #e91e63);
            color: white;
            padding: 16px 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            animation: slideDown 0.4s ease;
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .current-hours {
            background: linear-gradient(135deg, #FFF3E0, #FFE0B2);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            border: 2px solid #FFB74D;
        }

        .current-hours-title {
            font-family: 'Prompt', sans-serif;
            font-weight: 600;
            color: #E65100;
            margin-bottom: 10px;
            font-size: 1rem;
        }

        .current-hours-value {
            font-family: 'Prompt', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: #FF6F00;
        }
    </style>
</head>

<body>
    <div class="container-box">
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 35px;">
            <div class="header-icon">🕐</div>
            <h2>จัดการเวลาเปิด-ปิดร้าน</h2>
            <p style="color: #666; font-size: 0.95rem; margin-top: 5px;">Store Hours Management</p>
        </div>

        <!-- Success Message -->
        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle" style="font-size: 1.5rem;"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle" style="font-size: 1.5rem;"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Current Hours Display -->
        <div class="current-hours">
            <div class="current-hours-title">⏰ เวลาเปิด-ปิดร้านปัจจุบัน</div>
            <div class="current-hours-value">
                <?php echo htmlspecialchars($open_time); ?> - <?php echo htmlspecialchars($close_time); ?> น.
            </div>
        </div>

        <!-- Store Status Toggle -->
        <div
            style="background: linear-gradient(135deg, #E3F2FD, #BBDEFB); padding: 30px; border-radius: 20px; margin-bottom: 30px; border: 3px solid #2196F3;">
            <div style="text-align: center; margin-bottom: 20px;">
                <div
                    style="font-family: 'Prompt', sans-serif; font-weight: 700; font-size: 1.3rem; color: #1565C0; margin-bottom: 15px;">
                    🏪 สถานะร้านปัจจุบัน
                </div>
                <div style="font-size: 3rem; font-weight: 900; margin: 15px 0;">
                    <?php if ($store_status === 'OPEN'): ?>
                        <span style="color: #4CAF50;">🟢 ร้านเปิดอยู่</span>
                    <?php else: ?>
                        <span style="color: #f44336;">🔴 ร้านปิดอยู่</span>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST">
                <?php if ($store_status === 'OPEN'): ?>
                    <input type="hidden" name="new_status" value="CLOSED">
                    <button type="submit" name="toggle_status"
                        style="width: 100%; padding: 20px; background: linear-gradient(135deg, #f44336, #e91e63); color: white; border: none; border-radius: 15px; font-size: 1.3rem; font-weight: 700; cursor: pointer; font-family: 'Prompt', sans-serif; transition: all 0.3s; box-shadow: 0 8px 25px rgba(244, 67, 54, 0.4);">
                        🚫 ปิดร้านทันที
                    </button>
                <?php else: ?>
                    <input type="hidden" name="new_status" value="OPEN">
                    <button type="submit" name="toggle_status"
                        style="width: 100%; padding: 20px; background: linear-gradient(135deg, #4CAF50, #8BC34A); color: white; border: none; border-radius: 15px; font-size: 1.3rem; font-weight: 700; cursor: pointer; font-family: 'Prompt', sans-serif; transition: all 0.3s; box-shadow: 0 8px 25px rgba(76, 175, 80, 0.4);">
                        ✅ เปิดร้านทันที
                    </button>
                <?php endif; ?>
            </form>

            <div
                style="margin-top: 15px; padding: 15px; background: rgba(255,255,255,0.8); border-radius: 12px; font-size: 0.95rem; color: #616161; text-align: center; font-weight: 600;">
                ℹ️ สถานะนี้จะแสดงบนหน้าเว็บทันที (ไม่ขึ้นกับเวลาเปิด-ปิด)
            </div>
        </div>

        <hr style="border: none; border-top: 3px dashed #e0e0e0; margin: 30px 0;">

        <!-- Hours Edit Form -->
        <div style="margin-top: 30px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h3 style="font-family: 'Prompt', sans-serif; font-size: 1.3rem; color: #FF6F00; font-weight: 700;">⚙️
                    ตั้งค่าเวลาเปิด-ปิด</h3>
                <p style="font-size: 0.9rem; color: #999;">เวลาที่ตั้งไว้จะใช้สำหรับการแสดงผลเมื่อร้านเปิด</p>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-clock" style="color: #4CAF50;"></i> เวลาเปิดร้าน (Opening Time)
                    </label>
                    <input type="time" name="open_time" class="time-input"
                        value="<?php echo htmlspecialchars($open_time); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-clock" style="color: #f44336;"></i> เวลาปิดร้าน (Closing Time)
                    </label>
                    <input type="time" name="close_time" class="time-input"
                        value="<?php echo htmlspecialchars($close_time); ?>" required>
                </div>

                <button type="submit" name="update_hours" class="btn-submit">
                    <i class="fas fa-save"></i>
                    <span>บันทึกเวลา</span>
                </button>
            </form>
        </div>

        <!-- Back Button -->
        <a href="formmenu" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            <span>กลับไปเมนูผู้ดูแล</span>
        </a>
    </div>

    <script>
        // Auto-hide success message after 3 seconds
        setTimeout(() => {
            const successMsg = document.querySelector('.success-message');
            if (successMsg) {
                successMsg.style.opacity = '0';
                successMsg.style.transition = 'opacity 0.5s ease';
                setTimeout(() => successMsg.remove(), 500);
            }
        }, 3000);
    </script>
</body>

</html>