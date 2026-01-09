<?php
// manage_daily_reward.php - จัดการรางวัลเช็คอินรายวัน
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'admin_auth.php';
require_once 'db_config.php';

// Check Login
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
if (!$isLoggedIn) {
    header('Location: admin_login');
    exit;
}

$message = '';
$error = '';

// Get Selected Month (Default to current month)
$selected_month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');
if ($selected_month < 1 || $selected_month > 12)
    $selected_month = (int) date('m');

$th_months = [
    1 => 'มกราคม',
    2 => 'กุมภาพันธ์',
    3 => 'มีนาคม',
    4 => 'เมษายน',
    5 => 'พฤษภาคม',
    6 => 'มิถุนายน',
    7 => 'กรกฎาคม',
    8 => 'สิงหาคม',
    9 => 'กันยายน',
    10 => 'ตุลาคม',
    11 => 'พฤศจิกายน',
    12 => 'ธันวาคม'
];

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_daily_points'])) {
    $conn->begin_transaction();
    try {
        $edit_month = (int) $_POST['edit_month'];
        $days_in_edit_month = (int) date('t', mktime(0, 0, 0, $edit_month, 1, (int) date('Y')));

        for ($i = 1; $i <= $days_in_edit_month; $i++) {
            $val = (int) $_POST["daily_day_$i"];
            $key = "daily_reward_m{$edit_month}_d{$i}";
            $conn->query("INSERT INTO store_settings (setting_key, setting_value) VALUES ('$key', '$val') ON DUPLICATE KEY UPDATE setting_value = '$val'");
        }
        $conn->commit();
        $message = "บันทึกข้อมูลเดือน" . $th_months[$edit_month] . " ($days_in_edit_month วัน) เรียบร้อยแล้ว";
        $selected_month = $edit_month; // Stay on saved month
    } catch (Exception $e) {
        $conn->rollback();
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// Fetch Daily Points for Selected Month
$daily_rewards = [];
for ($i = 1; $i <= 31; $i++) {
    $key = "daily_reward_m{$selected_month}_d{$i}";
    $res = $conn->query("SELECT setting_value FROM store_settings WHERE setting_key = '$key'");

    // Default logic if not set: Matches previous 7-day pattern logic or 10pts base
    $default = 10;
    if ($i % 7 == 0)
        $default = 50;
    if ($i >= 30)
        $default = 100;

    $daily_rewards[$i] = ($res && $res->num_rows > 0) ? (int) $res->fetch_assoc()['setting_value'] : $default;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="icon/icons.png?v=4">
    <title>ตั้งค่าเช็คอินรายวัน | Admin Daily Reward</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700&display=swap');

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(-45deg, #fffbf0, #e3f2fd, #bbdefb, #fffbf0);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
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

        .glass-box {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        h2,
        h3 {
            font-family: 'Prompt', sans-serif;
        }
    </style>
</head>

<body>
    <div class="max-w-6xl mx-auto mt-10">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <a href="formmenu" class="bg-white/80 p-3 rounded-xl hover:bg-white transition-all shadow-sm"><i
                    class="fas fa-arrow-left text-gray-600"></i></a>
            <h2 class="text-3xl font-bold text-blue-700 mx-auto">📅 จัดการเช็คอินประจำเดือน</h2>
            <div class="w-10"></div>
        </div>

        <?php if ($message): ?>
            <div
                class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg shadow-sm animate-bounce">
                <i class="fas fa-check-circle mr-2"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="glass-box text-center">
            <div class="flex justify-center items-center gap-4 mb-6">
                <div class="text-6xl">🗓️</div>
                <div class="text-left">
                    <h3 class="text-2xl font-bold text-gray-800">กำหนดแต้มรายวัน</h3>
                    <p class="text-gray-500">เลือกเดือนที่ต้องการตั้งค่า</p>
                </div>
            </div>

            <!-- Month Selector -->
            <div class="flex flex-wrap justify-center gap-2 mb-8 p-4 bg-blue-50/50 rounded-2xl border border-blue-100">
                <?php foreach ($th_months as $m_num => $m_name): ?>
                    <a href="?month=<?php echo $m_num; ?>"
                        class="px-4 py-2 rounded-xl text-sm font-bold transition-all <?php echo $m_num == $selected_month ? 'bg-blue-600 text-white shadow-lg scale-105' : 'bg-white text-gray-500 hover:bg-blue-100'; ?>">
                        <?php echo $m_name; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php
            $days_in_selected_month = (int) date('t', mktime(0, 0, 0, $selected_month, 1, (int) date('Y')));
            ?>

            <div class="flex items-center gap-2 mb-6">
                <span
                    class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-blue-200 dark:text-blue-800">กำลังแก้ไข:
                    <?php echo $th_months[$selected_month]; ?> (มี <?php echo $days_in_selected_month; ?> วัน)</span>
            </div>

            <form method="POST" class="space-y-6">
                <input type="hidden" name="edit_month" value="<?php echo $selected_month; ?>">
                <div class="grid grid-cols-4 md:grid-cols-7 gap-3">
                    <?php for ($i = 1; $i <= $days_in_selected_month; $i++):
                        $is_special = ($i % 7 == 0) || $i == $days_in_selected_month;
                        ?>
                        <div
                            class="p-2 bg-blue-50/50 rounded-xl border border-blue-100 <?php echo $is_special ? 'border-yellow-300 bg-yellow-50 shadow-sm' : ''; ?>">
                            <label class="block text-[0.65rem] font-bold text-gray-500 mb-1 uppercase">Day
                                <?php echo $i; ?></label>
                            <input type="number" name="daily_day_<?php echo $i; ?>"
                                value="<?php echo $daily_rewards[$i]; ?>"
                                class="w-full p-1 text-center text-lg font-bold bg-white border border-transparent focus:border-blue-500 rounded-lg outline-none transition-all <?php echo $is_special ? 'text-yellow-600' : 'text-blue-600'; ?>"
                                required>
                        </div>
                    <?php endfor; ?>
                </div>

                <button type="submit" name="update_daily_points"
                    class="w-full max-w-md mx-auto py-4 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-2xl font-bold text-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all mt-8">
                    <i class="fas fa-save mr-2"></i> บันทึกข้อมูลประจำเดือน<?php echo $th_months[$selected_month]; ?>
                </button>
            </form>
        </div>
    </div>
</body>

</html>