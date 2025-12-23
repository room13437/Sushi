<?php
session_start();
date_default_timezone_set('Asia/Bangkok');
include "db_config.php";

// Auto-create table with pickup_time
$table_sql = "CREATE TABLE IF NOT EXISTS daily_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_number INT NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(50),
    details TEXT,
    pickup_time TIME NULL,
    queue_date DATE NOT NULL,
    status ENUM('Waiting', 'Called', 'Completed', 'Cancelled') DEFAULT 'Waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (queue_date, queue_number),
    INDEX (queue_date)
)";

if (!$conn->query($table_sql)) {
    die("Error creating table: " . $conn->error);
}

// Add pickup_time column if not exists (for existing tables)
$conn->query("ALTER TABLE daily_queue ADD COLUMN pickup_time TIME NULL AFTER details");

// --- FETCH STORE HOURS FROM DATABASE ---
$store_open_time = '11:00';
$store_close_time = '22:00';

// Connect to products database to get store hours
$conn_products = new mysqli("localhost", "root", "", "products");
if (!$conn_products->connect_error) {
    $sql_hours = "SELECT setting_key, setting_value FROM store_settings WHERE setting_key IN ('store_open_time', 'store_close_time')";
    $result_hours = $conn_products->query($sql_hours);
    if ($result_hours) {
        while ($row = $result_hours->fetch_assoc()) {
            if ($row['setting_key'] === 'store_open_time') {
                $store_open_time = $row['setting_value'];
            } elseif ($row['setting_key'] === 'store_close_time') {
                $store_close_time = $row['setting_value'];
            }
        }
    }
    $conn_products->close();
}

// Parse hour values for validation
$open_hour = (int) substr($store_open_time, 0, 2);
$close_hour = (int) substr($store_close_time, 0, 2);

// Handle booking
$ticket = null;
$error = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['customer_name']);
    $phone = trim($_POST['phone_number']);
    $details = trim($_POST['details']);
    $pickup_time = isset($_POST['pickup_time']) ? trim($_POST['pickup_time']) : null;
    $today = date('Y-m-d');

    if ($name && $phone) {
        // Validate pickup time based on store hours from database
        if ($pickup_time) {
            $hour = (int) substr($pickup_time, 0, 2);
            if ($hour < $open_hour || $hour >= $close_hour) {
                $error = "กรุณาเลือกเวลารับของระหว่าง {$store_open_time} - {$store_close_time} น.";
            }
        }

        if (!$error) {
            $stmt_max = $conn->prepare("SELECT MAX(queue_number) as max_q FROM daily_queue WHERE queue_date = ?");
            $stmt_max->bind_param("s", $today);
            $stmt_max->execute();
            $result = $stmt_max->get_result();
            $row = $result->fetch_assoc();
            $next_queue = ($row['max_q'] ?? 0) + 1;

            $stmt_insert = $conn->prepare("INSERT INTO daily_queue (queue_number, customer_name, phone_number, details, pickup_time, queue_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("isssss", $next_queue, $name, $phone, $details, $pickup_time, $today);

            if ($stmt_insert->execute()) {
                $ticket = [
                    'number' => $next_queue,
                    'name' => $name,
                    'date' => $today,
                    'time' => date('H:i'),
                    'pickup_time' => $pickup_time
                ];
            } else {
                $error = "บันทึกข้อมูลไม่สำเร็จ: " . $conn->error;
            }
        }
    } else {
        $error = "กรุณากรอกชื่อและเบอร์โทรศัพท์";
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="icon/icons.png?v=4">
    <title>🎫 จองคิวออนไลน์ | ซูชิละกัน</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #FFF9F0 0%, #FFEDD5 50%, #FED7AA 100%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Floating Sushi Decorations */
        .floating-sushi {
            position: absolute;
            font-size: 4rem;
            opacity: 0.15;
            animation: float 6s ease-in-out infinite;
            pointer-events: none;
        }

        .floating-sushi:nth-child(1) {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-sushi:nth-child(2) {
            top: 20%;
            right: 15%;
            animation-delay: 1s;
            font-size: 3rem;
        }

        .floating-sushi:nth-child(3) {
            bottom: 15%;
            left: 15%;
            animation-delay: 2s;
            font-size: 3.5rem;
        }

        .floating-sushi:nth-child(4) {
            bottom: 25%;
            right: 10%;
            animation-delay: 3s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(5deg);
            }
        }

        .container {
            width: 100%;
            max-width: 550px;
            position: relative;
            z-index: 10;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(249, 115, 22, 0.2);
            border-radius: 30px;
            padding: 50px 40px;
            box-shadow: 0 20px 60px rgba(249, 115, 22, 0.15);
            text-align: center;
            animation: slideUp 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.9);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .page-title {
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, #F97316, #C2410C);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .subtitle {
            color: #9A3412;
            font-size: 1.1rem;
            margin-bottom: 35px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 700;
            font-family: 'Prompt', sans-serif;
            color: #EA580C;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        input,
        textarea {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #FED7AA;
            border-radius: 16px;
            font-family: 'Sarabun', sans-serif;
            font-size: 1.05rem;
            background: #FFFBF7;
            transition: all 0.3s;
        }

        input:focus,
        textarea:focus {
            border-color: #F97316;
            outline: none;
            box-shadow: 0 0 0 5px rgba(249, 115, 22, 0.1);
            background: white;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #F97316, #EA580C);
            color: white;
            border: none;
            padding: 18px 40px;
            width: 100%;
            border-radius: 50px;
            font-size: 1.3rem;
            font-weight: 700;
            font-family: 'Prompt', sans-serif;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(249, 115, 22, 0.4);
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #EA580C, #C2410C);
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(234, 88, 12, 0.5);
        }

        .btn-submit:active {
            transform: translateY(-1px);
        }

        /* Ticket Styles */
        .ticket-container {
            animation: ticketPop 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        @keyframes ticketPop {
            0% {
                opacity: 0;
                transform: scale(0.5) rotate(-5deg);
            }

            100% {
                opacity: 1;
                transform: scale(1) rotate(0);
            }
        }

        .success-icon {
            font-size: 5rem;
            animation: bounce 1s ease infinite;
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

        .ticket-box {
            background: linear-gradient(135deg, #FFF9F0, #FFEDD5);
            border: 3px dashed #F97316;
            padding: 40px;
            border-radius: 25px;
            margin: 30px 0;
            position: relative;
            overflow: hidden;
        }

        .ticket-box::before {
            content: '🍣';
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 3rem;
            opacity: 0.1;
        }

        .ticket-label {
            font-size: 1rem;
            color: #9A3412;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .ticket-number {
            font-size: 6rem;
            font-weight: 900;
            background: linear-gradient(135deg, #F97316, #C2410C);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
            font-family: 'Prompt', sans-serif;
            margin: 15px 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .ticket-name {
            font-weight: 700;
            font-size: 1.4rem;
            color: #7C2D12;
            margin: 15px 0;
        }

        .ticket-datetime {
            font-size: 1rem;
            color: #9A3412;
            opacity: 0.8;
        }

        .btn-back {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #9A3412;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 1.05rem;
        }

        .btn-back:hover {
            color: #F97316;
            transform: translateX(-5px);
        }

        .error-box {
            background: #FFE4E1;
            border-left: 4px solid #DC2626;
            color: #7F1D1D;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .note-box {
            background: #FEF3C7;
            border-left: 4px solid #F59E0B;
            color: #92400E;
            padding: 15px 20px;
            border-radius: 12px;
            margin-top: 25px;
            font-weight: 600;
            text-align: left;
        }
    </style>
</head>

<body>
    <!-- Floating Decorations -->
    <div class="floating-sushi">🍣</div>
    <div class="floating-sushi">🍱</div>
    <div class="floating-sushi">🍤</div>
    <div class="floating-sushi">🥢</div>

    <div class="container">
        <div class="glass-card">
            <?php if ($ticket): ?>
                <!-- Success Ticket -->
                <div class="ticket-container">
                    <div class="success-icon">🎉</div>
                    <h2 class="page-title" style="margin: 20px 0;">จองคิวสำเร็จ!</h2>

                    <div class="ticket-box">
                        <div class="ticket-label">หมายเลขคิวของคุณ</div>
                        <div class="ticket-number">
                            <?php echo str_pad($ticket['number'], 3, '0', STR_PAD_LEFT); ?>
                        </div>
                        <div class="ticket-name">
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars($ticket['name']); ?>
                        </div>
                        <div class="ticket-datetime">
                            <i class="far fa-calendar-alt"></i>
                            <?php echo date('d/m/Y', strtotime($ticket['date'])); ?>
                            <i class="far fa-clock"></i>
                            <?php echo $ticket['time']; ?> น.
                        </div>
                    </div>

                    <?php if (!empty($ticket['pickup_time'])): ?>
                        <div
                            style="margin-top: 20px; padding: 20px; background: rgba(249, 115, 22, 0.15); border: 2px solid #F97316; border-radius: 16px; font-size: 1.2rem; color: #C2410C; font-weight: 700; text-align: center;">
                            <i class="fas fa-clock" style="font-size: 1.5rem; margin-right: 10px;"></i>
                            <div style="margin-top: 10px;">เวลารับของ</div>
                            <div style="font-size: 2rem; color: #EA580C; margin-top: 5px;">
                                <?php echo date('H:i', strtotime($ticket['pickup_time'])); ?> น.
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="note-box">
                        <i class="fas fa-info-circle"></i>
                        กรุณาแคปหน้าจอเพื่อเป็นหลักฐาน และติดต่อเคาน์เตอร์เมื่อถึงร้าน
                    </div>

                    <a href="queue_reservation" class="btn-submit"
                        style="text-decoration:none; display:block; margin-top:25px;">
                        <i class="fas fa-plus-circle"></i> จองคิวใหม่
                    </a>
                    <a href="index" class="btn-back">
                        <i class="fas fa-arrow-left"></i> กลับหน้าหลัก
                    </a>
                </div>

                <!-- Prevent refresh resubmission - redirect to index on refresh -->
                <script>
                    // Replace the current history state to prevent form resubmission
                    if (window.history.replaceState) {
                        window.history.replaceState(null, null, window.location.href);
                    }

                    // Detect if user tries to refresh and redirect to index
                    window.addEventListener('beforeunload', function (e) {
                        // Redirect to index to prevent resubmission
                        window.location.href = 'index.php';
                    });
                </script>

            <?php else: ?>
                <!-- Booking Form -->
                <h1 class="page-title">
                    <i class="fas fa-ticket-alt"></i>
                    <span>จองคิวออนไลน์</span>
                </h1>
                <p class="subtitle">รับบัตรคิวสะดวกรวดเร็ว ไม่ต้องรอนาน 🚀</p>

                <?php if ($error): ?>
                    <div class="error-box">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-user"></i>
                            ชื่อคุณลูกค้า
                        </label>
                        <input type="text" name="customer_name" required placeholder="ระบุชื่อของคุณ" autofocus>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-phone"></i>
                            เบอร์โทรศัพท์
                        </label>
                        <input type="tel" name="phone_number" required placeholder="0xx-xxx-xxxx" pattern="[0-9\-]+">
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-clock"></i>
                            เวลารับของ (เลือกได้)
                        </label>
                        <select name="pickup_time"
                            style="width: 100%; padding: 16px 20px; border: 2px solid #FED7AA; border-radius: 16px; font-family: 'Sarabun', sans-serif; font-size: 1.05rem; background: #FFFBF7; cursor: pointer; transition: all 0.3s;">
                            <option value="">ไม่ระบุเวลา (รับได้ทันที)</option>
                            <?php
                            // Generate time options based on store hours from database
                            $start_hour = $open_hour;
                            $end_hour = $close_hour;

                            for ($hour = $start_hour; $hour < $end_hour; $hour++) {
                                foreach ([0, 30] as $minute) {
                                    // Skip the last 30-minute slot if it's at closing time
                                    if ($hour == $end_hour - 1 && $minute == 30 && $hour >= 21)
                                        continue;
                                    $time = sprintf("%02d:%02d", $hour, $minute);
                                    echo "<option value='$time'>$time น.</option>";
                                }
                            }
                            ?>
                        </select>
                        <small style="display: block; margin-top: 8px; color: #9A3412; font-size: 0.9rem;">
                            <i class="fas fa-info-circle"></i> เวลาทำการ: <?php echo $store_open_time; ?> -
                            <?php echo $store_close_time; ?> น.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-users"></i>
                            รายละเอียด
                        </label>
                        <textarea name="details" rows="3" placeholder="รายละเอียดเพิ่มเติม"></textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-check-circle"></i> ยืนยันการจองคิว
                    </button>
                </form>

                <a href="index" class="btn-back">
                    <i class="fas fa-arrow-left"></i> กลับหน้าหลัก
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>