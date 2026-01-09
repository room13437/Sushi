<?php
// แสดง error เพื่อ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตั้งค่า Timezone เป็นเวลาไทย
date_default_timezone_set('Asia/Bangkok');

require_once 'admin_auth.php';

// ตรวจสอบว่า login แล้วหรือยัง
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// ถ้ายังไม่ได้ login ให้ redirect ไป admin_login
if (!$isLoggedIn) {
    header('Location: admin_login');
    exit;
}

$success_message = '';
$error_message = '';

// ดึงข้อมูลปัจจุบัน
$open_time = '10:00';
$close_time = '20:00';
$manual_override = 'auto';

$result = $conn->query("SELECT setting_key, setting_value FROM store_settings WHERE setting_key IN ('store_open_time', 'store_close_time', 'manual_override')");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['setting_key'] == 'store_open_time') {
            $open_time = $row['setting_value'];
        } elseif ($row['setting_key'] == 'store_close_time') {
            $close_time = $row['setting_value'];
        } elseif ($row['setting_key'] == 'manual_override') {
            $manual_override = $row['setting_value'];
        }
    }
}

// คำนวณสถานะร้านปัจจุบัน
$current_time = date('H:i');
$is_open = false;

if ($manual_override == 'force_open') {
    $is_open = true;
    $status_text = 'เปิดทำการ (บังคับเปิด)';
    $status_color = 'green';
} elseif ($manual_override == 'force_closed') {
    $is_open = false;
    $status_text = 'ปิดทำการ (บังคับปิด)';
    $status_color = 'red';
} else {
    // Auto mode
    if ($current_time >= $open_time && $current_time < $close_time) {
        $is_open = true;
        $status_text = 'เปิดทำการ (อัตโนมัติ)';
        $status_color = 'green';
    } else {
        $is_open = false;
        $status_text = 'ปิดทำการ (อัตโนมัติ)';
        $status_color = 'red';
    }
}

// จัดการ POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_hours'])) {
        $new_open = $_POST['open_time'];
        $new_close = $_POST['close_time'];

        // Update เวลาเปิด-ปิด
        $stmt = $conn->prepare("INSERT INTO store_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");

        $key = 'store_open_time';
        $stmt->bind_param("sss", $key, $new_open, $new_open);
        $stmt->execute();

        $key = 'store_close_time';
        $stmt->bind_param("sss", $key, $new_close, $new_close);
        $stmt->execute();

        $stmt->close();

        $success_message = '✅ บันทึกเวลาเปิด-ปิดร้านเรียบร้อยแล้ว!';
        $open_time = $new_open;
        $close_time = $new_close;
    } elseif (isset($_POST['set_override'])) {
        $new_override = $_POST['override_mode'];

        $stmt = $conn->prepare("INSERT INTO store_settings (setting_key, setting_value) VALUES ('manual_override', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("ss", $new_override, $new_override);
        $stmt->execute();
        $stmt->close();

        $manual_override = $new_override;
        $success_message = '✅ อัปเดตสถานะร้านเรียบร้อยแล้ว!';
    }

    // Refresh หน้าเพื่อแสดงสถานะใหม่
    header("Location: manage_store?success=1");
    exit;
}

if (isset($_GET['success'])) {
    $success_message = '✅ บันทึกข้อมูลเรียบร้อยแล้ว!';
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="icon/icons.png?v=4">
    <title>🕐 จัดการเวลาเปิด-ปิดร้าน | Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700&display=swap');

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(-45deg, #fffbf0, #ffe0b2, #ffccbc, #fffbf0);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
        }

        @keyframes gradientBG {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.8;
            }
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-6">
    <div class="glass-card rounded-3xl p-8 max-w-2xl w-full">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="text-6xl mb-4">🕐</div>
            <h1 class="text-3xl font-bold text-orange-600 font-display mb-2" style="font-family: 'Prompt', sans-serif;">
                จัดการเวลาเปิด-ปิดร้าน
            </h1>
            <p class="text-gray-600">Store Hours Management</p>
        </div>

        <!-- Success Message -->
        <?php if ($success_message): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-xl flex items-center gap-3">
                <i class="fas fa-check-circle text-2xl"></i>
                <span>
                    <?php echo $success_message; ?>
                </span>
            </div>
        <?php endif; ?>

        <!-- Current Status -->
        <div
            class="mb-8 p-6 rounded-2xl <?php echo $is_open ? 'bg-green-50 border-2 border-green-300' : 'bg-red-50 border-2 border-red-300'; ?>">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold mb-1" style="font-family: 'Prompt', sans-serif;">สถานะร้านปัจจุบัน</h3>
                    <p class="text-gray-600 text-sm">เวลาปัจจุบัน:
                        <?php echo date('H:i น.'); ?>
                    </p>
                </div>
                <div class="text-right">
                    <span class="status-badge inline-block px-6 py-3 rounded-full text-white font-bold text-xl"
                        style="background: <?php echo $is_open ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #ef4444, #dc2626)'; ?>">
                        <?php echo $is_open ? '🟢 เปิดทำการ' : '🔴 ปิดทำการ'; ?>
                    </span>
                    <p class="text-sm text-gray-600 mt-2">
                        <?php echo $status_text; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Manual Override Controls -->
        <div class="mb-8 p-6 bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl border-2 border-orange-200">
            <h3 class="text-xl font-bold mb-4 text-orange-800" style="font-family: 'Prompt', sans-serif;">
                <i class="fas fa-hand-pointer mr-2"></i>ควบคุมแบบแมนนวล
            </h3>
            <form method="POST" class="grid grid-cols-3 gap-3">
                <input type="hidden" name="set_override">

                <button type="submit" name="override_mode" value="auto"
                    class="p-4 rounded-xl font-bold transition-all <?php echo $manual_override == 'auto' ? 'bg-blue-500 text-white shadow-lg scale-105' : 'bg-white text-gray-700 hover:bg-blue-50'; ?>">
                    <i class="fas fa-sync-alt text-2xl mb-2"></i>
                    <div class="text-sm">อัตโนมัติ</div>
                </button>

                <button type="submit" name="override_mode" value="force_open"
                    class="p-4 rounded-xl font-bold transition-all <?php echo $manual_override == 'force_open' ? 'bg-green-500 text-white shadow-lg scale-105' : 'bg-white text-gray-700 hover:bg-green-50'; ?>">
                    <i class="fas fa-door-open text-2xl mb-2"></i>
                    <div class="text-sm">บังคับเปิด</div>
                </button>

                <button type="submit" name="override_mode" value="force_closed"
                    class="p-4 rounded-xl font-bold transition-all <?php echo $manual_override == 'force_closed' ? 'bg-red-500 text-white shadow-lg scale-105' : 'bg-white text-gray-700 hover:bg-red-50'; ?>">
                    <i class="fas fa-door-closed text-2xl mb-2"></i>
                    <div class="text-sm">บังคับปิด</div>
                </button>
            </form>
            <p class="text-sm text-gray-600 mt-4 text-center">
                <i class="fas fa-info-circle mr-1"></i>
                เลือก "อัตโนมัติ" เพื่อใช้เวลาที่ตั้งไว้ด้านล่าง
            </p>
        </div>

        <!-- Time Settings -->
        <div class="mb-8 p-6 bg-white rounded-2xl border-2 border-gray-200">
            <h3 class="text-xl font-bold mb-4 text-gray-800" style="font-family: 'Prompt', sans-serif;">
                <i class="fas fa-clock mr-2"></i>ตั้งเวลาเปิด-ปิดร้าน
            </h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="update_hours" value="1">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            <i class="fas fa-sun text-yellow-500 mr-1"></i>เวลาเปิดร้าน
                        </label>
                        <input type="time" name="open_time" value="<?php echo $open_time; ?>" required
                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 focus:border-orange-500 focus:ring-4 focus:ring-orange-200 outline-none text-lg font-bold">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            <i class="fas fa-moon text-blue-500 mr-1"></i>เวลาปิดร้าน
                        </label>
                        <input type="time" name="close_time" value="<?php echo $close_time; ?>" required
                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 focus:border-orange-500 focus:ring-4 focus:ring-orange-200 outline-none text-lg font-bold">
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white font-bold py-4 rounded-xl hover:from-orange-600 hover:to-orange-700 transition-all shadow-lg hover:shadow-xl">
                    <i class="fas fa-save mr-2"></i>บันทึกเวลาเปิด-ปิด
                </button>
            </form>
        </div>

        <!-- Footer Actions -->
        <div class="flex gap-4">
            <a href="formmenu"
                class="flex-1 bg-white border-2 border-gray-300 text-gray-700 font-bold py-3 rounded-xl hover:bg-gray-50 transition-all text-center">
                <i class="fas fa-arrow-left mr-2"></i>กลับเมนูหลัก
            </a>
            <a href="/"
                class="flex-1 bg-gradient-to-r from-gray-600 to-gray-700 text-white font-bold py-3 rounded-xl hover:from-gray-700 hover:to-gray-800 transition-all text-center">
                <i class="fas fa-home mr-2"></i>กลับหน้าแรก
            </a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="js/three_bg.js"></script>
</body>

</html>