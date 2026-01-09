<?php
session_start();
require_once 'db_config.php';

// Check Login
if (!isset($_SESSION['customer_id'])) {
    header("Location: login");
    exit;
}

$user_identifier = $_SESSION['customer_id'];
$username = htmlspecialchars($_SESSION['customer_name']);
$table_name = "customers";
$message = "";
$gacha_cost = 5; // Cost per Gacha Spin

// -------------------------------------------------------------------------
// 0. Daily Login Logic
// -------------------------------------------------------------------------
// Fetch Daily Rewards Settings for CURRENT MONTH (Day 1-31)
$current_month = (int)date('m');
$daily_login_rewards = [];
for ($i = 1; $i <= 31; $i++) {
    $search_key = "daily_reward_m{$current_month}_d{$i}";
    $res = $conn->query("SELECT setting_value FROM store_settings WHERE setting_key = '$search_key'");
    
    // Default: Day 7,14,21,28 = 50pts, Month End (30,31) = 100pts, Others = 10pts
    $default = 10;
    if ($i % 7 == 0) $default = 50;
    if ($i >= 30) $default = 100;
    
    $daily_login_rewards[$i] = ($res && $res->num_rows > 0) ? (int) $res->fetch_assoc()['setting_value'] : $default;
}

// Monthly Calendar Logic
$today_day = (int)date('d'); // 1-31
$current_month = date('m');
$current_year = date('Y');
$days_in_month = date('t');

// Check claimed days in current month
$claimed_days = [];
$hist_res = $conn->query("SELECT DAY(redeemed_at) as d FROM redemption_history WHERE user_id = $user_identifier AND MONTH(redeemed_at) = $current_month AND YEAR(redeemed_at) = $current_year AND type = 'daily'");
if ($hist_res) {
    while($row = $hist_res->fetch_assoc()) {
        $claimed_days[] = (int)$row['d'];
    }
}

$can_claim_today = !in_array($today_day, $claimed_days);
$today_reward_points = $daily_login_rewards[$today_day];

$stmt_me = $conn->prepare("SELECT points, last_daily_login, login_streak FROM customers WHERE id = ?");
$stmt_me->bind_param("i", $user_identifier);
$stmt_me->execute();
$me_data = $stmt_me->get_result()->fetch_assoc();
$user_points_current = $me_data['points'];
$last_login_date = $me_data['last_daily_login'];
$streak = $me_data['login_streak'];

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$can_claim = ($last_login_date !== $today);

// Reset streak logic if missed a day (Not today and not yesterday)
if ($last_login_date !== $today && $last_login_date !== $yesterday) {
    $streak = 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['claim_daily_login'])) {
    // Verify not claimed today
    if ($can_claim_today) {
        $award_day = (int)date('d');
        $points_to_give = $daily_login_rewards[$award_day];

        $conn->begin_transaction();
        try {
            // Update User Points and Streak
            $new_streak = ($last_login_date === $yesterday) ? $streak + 1 : 1;
            
            $stmt_update = $conn->prepare("UPDATE customers SET points = points + ?, last_daily_login = CURDATE(), login_streak = ? WHERE id = ?");
            $stmt_update->bind_param("iii", $points_to_give, $new_streak, $user_identifier);
            $stmt_update->execute();
            
            // Log History
            $desc = "Daily Login (Day $award_day)";
            $stmt_hist = $conn->prepare("INSERT INTO redemption_history (user_id, code, points, type) VALUES (?, ?, ?, 'daily')");
            $stmt_hist->bind_param("isi", $user_identifier, $desc, $points_to_give);
            $stmt_hist->execute();
            
            $conn->commit();
            $message = "<div class='alert alert-success'>🎉 เช็คอินวันที่ $award_day สำเร็จ! รับ $points_to_give แต้ม</div>";
            
            // Update local vars for immediate display
            $user_points_current += $points_to_give;
            $current_points_for_display = number_format($user_points_current);
            $can_claim_today = false;
            $claimed_days[] = $award_day; // Update visual grid immediately
            $streak = $new_streak; // Update visual streak immediately
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='alert alert-danger'>เกิดข้อผิดพลาด: " . $e->getMessage() . "</div>";
        }
    }
}


// -------------------------------------------------------------------------
// 1. Process: Change Password
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "<div class='alert alert-error'>❌ กรุณากรอกข้อมูลให้ครบถ้วน</div>";
    } elseif ($new_password !== $confirm_password) {
        $message = "<div class='alert alert-error'>❌ รหัสผ่านใหม่ไม่ตรงกัน</div>";
    } elseif (strlen($new_password) < 6) {
        $message = "<div class='alert alert-error'>❌ รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร</div>";
    } else {
        $stmt = $conn->prepare("SELECT password FROM customers WHERE id = ?");
        $stmt->bind_param("i", $user_identifier);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            if (password_verify($current_password, $row['password'])) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE customers SET password = ? WHERE id = ?");
                $update->bind_param("si", $new_hash, $user_identifier);
                if ($update->execute()) {
                    $message = "<div class='alert alert-success'>✅ เปลี่ยนรหัสผ่านสำเร็จ!</div>";
                } else {
                    $message = "<div class='alert alert-error'>❌ เกิดข้อผิดพลาด</div>";
                }
            } else {
                $message = "<div class='alert alert-error'>❌ รหัสผ่านปัจจุบันไม่ถูกต้อง</div>";
            }
        }
    }
}

// -------------------------------------------------------------------------
// 2. Process: Edit Profile
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_profile'])) {
    $new_name = trim($_POST['new_username']); // Using as Full Name
    if (empty($new_name)) {
        $message = "<div class='alert alert-error'>❌ กรุณากรอกชื่อ</div>";
    } else {
        $update = $conn->prepare("UPDATE customers SET full_name = ? WHERE id = ?");
        $update->bind_param("si", $new_name, $user_identifier);
        if ($update->execute()) {
            $_SESSION['customer_name'] = $new_name;
            $username = htmlspecialchars($new_name);
            $message = "<div class='alert alert-success'>✅ แก้ไขข้อมูลสำเร็จ!</div>";
        } else {
            $message = "<div class='alert alert-error'>❌ เกิดข้อผิดพลาด</div>";
        }
    }
}

// -------------------------------------------------------------------------
// 3. Fetch Current Points (Updated)
// -------------------------------------------------------------------------
// Logic moved to section 0 for efficiency
$current_points_for_display = number_format($user_points_current);

// -------------------------------------------------------------------------
// 4. Process: Redeem Code
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['redeem_code'])) {
    $input_code = strtoupper(trim($_POST['redeem_code']));

    // Check Code exists
    $stmt = $conn->prepare("SELECT id, points_value, max_uses, 
        (SELECT COUNT(*) FROM code_redemptions WHERE code_id = point_codes.id) as current_uses 
        FROM point_codes WHERE code = ?");
    $stmt->bind_param("s", $input_code);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $code_data = $res->fetch_assoc();
        $code_id = $code_data['id'];
        $points_val = $code_data['points_value'];
        $max_uses = $code_data['max_uses'];
        $current_uses = $code_data['current_uses'];

        if ($current_uses >= $max_uses) {
            $message = "<div class='alert alert-error'>❌ โค้ดนี้ถูกใช้งานครบจำนวนแล้ว</div>";
        } else {
            // Check if user used it
            $check_user = $conn->prepare("SELECT id FROM code_redemptions WHERE code_id = ? AND user_id = ?");
            $check_user->bind_param("ii", $code_id, $user_identifier);
            $check_user->execute();
            if ($check_user->get_result()->num_rows > 0) {
                $message = "<div class='alert alert-error'>❌ คุณใช้โค้ดนี้ไปแล้ว</div>";
            } else {
                // REDEEM
                $conn->begin_transaction();
                try {
                    $conn->query("UPDATE customers SET points = points + $points_val WHERE id = $user_identifier");
                    $conn->query("INSERT INTO code_redemptions (code_id, user_id) VALUES ($code_id, $user_identifier)");
                    $stmt_hist = $conn->prepare("INSERT INTO redemption_history (user_id, code, points, type) VALUES (?, ?, ?, 'code')");
                    $stmt_hist->bind_param("isi", $user_identifier, $input_code, $points_val);
                    $stmt_hist->execute();

                    $conn->commit();
                    $user_points_current += $points_val;
                    $current_points_for_display = number_format($user_points_current);
                    $message = "<div class='alert alert-success'>✅ ได้รับ $points_val แต้มเรียบร้อย!</div>";
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = "<div class='alert alert-error'>❌ เกิดข้อผิดพลาดในระบบ</div>";
                }
            }
        }
    } else {
        $message = "<div class='alert alert-error'>❌ ไม่พบรหัสโค้ดนี้</div>";
    }
}

// -------------------------------------------------------------------------
// 5. Process: Gacha Spin
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['gacha_spin'])) {
    if ($user_points_current < $gacha_cost) {
        $message = "<div class='alert alert-error'>💸 แต้มไม่พอ! ต้องใช้ $gacha_cost แต้ม</div>";
    } else {
        $roll = rand(1, 100);
        $points_won = 0;
        // 60% Chance to win 4-8 points
        if ($roll <= 60) {
            $points_won = rand(4, 8);
        }
        $net_change = $points_won - $gacha_cost;

        $conn->query("UPDATE customers SET points = points + ($net_change) WHERE id = $user_identifier");

        $desc = "Gacha: " . ($points_won > 0 ? "Won $points_won" : "Lost") . " (Net: $net_change)";
        $stmt_hist = $conn->prepare("INSERT INTO redemption_history (user_id, code, points, type) VALUES (?, ?, ?, 'gacha')");
        $stmt_hist->bind_param("isi", $user_identifier, $desc, $net_change);
        $stmt_hist->execute();

        $user_points_current += $net_change;
        $current_points_for_display = number_format($user_points_current);

        if ($points_won > 0) {
            $message = "<div class='alert alert-success'>🎰 ยินดีด้วย! คุณได้ $points_won แต้ม</div>";
        } else {
            $message = "<div class='alert alert-warning'>🎰 เสียใจด้วย ครั้งนี้ไม่ได้แต้ม</div>";
        }
    }
}

// -------------------------------------------------------------------------
// 6. Discount & Sushi Data
// -------------------------------------------------------------------------
$discount_percent = isset($_SESSION['discount_percent']) ? $_SESSION['discount_percent'] : 0;
$discount_code_used = isset($_SESSION['discount_code']) ? $_SESSION['discount_code'] : '';

// 6.1 Handle Discount Code Input
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply_discount'])) {
    $code_input = strtoupper(trim($_POST['discount_code']));
    $stmt = $conn->prepare("SELECT * FROM discount_codes WHERE code = ? AND is_active = 1");
    if ($stmt) {
        $stmt->bind_param("s", $code_input);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $d = $res->fetch_assoc();
            
            // Check usage limit
            if ($d['max_uses'] > 0 && $d['used_count'] >= $d['max_uses']) {
                $message = "<div class='alert alert-error'>❌ โค้ดส่วนลดนี้ถูกใช้งานครบจำนวนแล้ว</div>";
            } else {
                $_SESSION['discount_percent'] = $d['percent'];
                $_SESSION['discount_code'] = $d['code'];
                $message = "<div class='alert alert-success'>✅ ใช้โค้ดส่วนลด <b>{$d['code']}</b> ลด {$d['percent']}% เรียบร้อย!</div>";
                // Refresh variables
                $discount_percent = $d['percent'];
                $discount_code_used = $d['code'];
            }
        } else {
            $message = "<div class='alert alert-error'>❌ ไม่พบโค้ดส่วนลดหรือโค้ดหมดอายุ</div>";
        }
    } else {
        $message = "<div class='alert alert-error'>❌ ระบบขัดข้อง: ไม่สามารถตรวจสอบโค้ดได้</div>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_discount'])) {
    unset($_SESSION['discount_percent']);
    unset($_SESSION['discount_code']);
    $discount_percent = 0;
    $discount_code_used = '';
    $message = "<div class='alert alert-warning'>ยกเลิกส่วนลดแล้ว</div>";
}

// -------------------------------------------------------------------------
// 7. Process: Redeem Sushi
// -------------------------------------------------------------------------
$sushi_tiers = [];
$res_tiers = $conn->query("SELECT points, pieces FROM sushi_redemption_tiers ORDER BY points ASC");
while ($row = $res_tiers->fetch_assoc())
    $sushi_tiers[$row['points']] = $row['pieces'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['redeem_sushi'])) {
    $original_pts = (int) $_POST['redeem_sushi'];

    // Calculate Actual Cost
    $actual_cost = $original_pts;
    if ($discount_percent > 0) {
        $discount_val = floor(($original_pts * $discount_percent) / 100);
        $actual_cost = $original_pts - $discount_val;
    }

    if (!isset($sushi_tiers[$original_pts])) {
        $message = "<div class='alert alert-error'>❌ ข้อมูลไม่ถูกต้อง</div>";
    } elseif ($user_points_current < $actual_cost) {
        $message = "<div class='alert alert-error'>💸 แต้มไม่พอ</div>";
    } else {
        $pieces = $sushi_tiers[$original_pts];

        $conn->begin_transaction();
        try {
            // Re-check discount limit if applicable
            if ($discount_percent > 0 && !empty($discount_code_used)) {
                $stmt_check = $conn->prepare("SELECT max_uses, used_count FROM discount_codes WHERE code = ? AND is_active = 1 FOR UPDATE");
                $stmt_check->bind_param("s", $discount_code_used);
                $stmt_check->execute();
                $check_data = $stmt_check->get_result()->fetch_assoc();
                
                if ($check_data && $check_data['max_uses'] > 0 && $check_data['used_count'] >= $check_data['max_uses']) {
                    throw new Exception("DISCOUNT_LIMIT_REACHED");
                }
            }

            $conn->query("UPDATE customers SET points = points - $actual_cost WHERE id = $user_identifier");

            $stmt_claim = $conn->prepare("INSERT INTO reward_claims (user_id, username, points_used, items_count) VALUES (?, ?, ?, ?)");
            $stmt_claim->bind_param("isii", $user_identifier, $username, $actual_cost, $pieces);
            $stmt_claim->execute();
            $claim_id = $stmt_claim->insert_id;

            // Increment Discount Code usage if used
            if ($discount_percent > 0 && !empty($discount_code_used)) {
                $stmt_upd = $conn->prepare("UPDATE discount_codes SET used_count = used_count + 1 WHERE code = ?");
                $stmt_upd->bind_param("s", $discount_code_used);
                $stmt_upd->execute();

                // Auto-delete if reached max uses
                $stmt_del = $conn->prepare("DELETE FROM discount_codes WHERE code = ? AND max_uses > 0 AND used_count >= max_uses");
                $stmt_del->bind_param("s", $discount_code_used);
                $stmt_del->execute();
            }

            $desc = "Redeem Sushi: $pieces pcs ($original_pts P)";
            if ($discount_percent > 0) {
                $desc .= " [Code: $discount_code_used -{$discount_percent}%]";
            }
            $stmt_hist = $conn->prepare("INSERT INTO redemption_history (user_id, code, points, type) VALUES (?, ?, ?, 'sushi')");
            $neg_cost = -1 * $actual_cost;
            $stmt_hist->bind_param("isi", $user_identifier, $desc, $neg_cost);
            $stmt_hist->execute();

            $conn->commit();
            $user_points_current -= $actual_cost;
            $current_points_for_display = number_format($user_points_current);
            $message = "<div class='alert alert-success'>🍣 แลกสำเร็จ! ($pieces ชิ้น ใช้ $actual_cost แต้ม) รหัส #$claim_id</div>";

            // Optional: Clear discount after use if desired. User didn't specify. I'll keep it for now as "session discount".
        } catch (Exception $e) {
            $conn->rollback();
            if ($e->getMessage() === "DISCOUNT_LIMIT_REACHED") {
                $message = "<div class='alert alert-error'>❌ เสียใจด้วย! โค้ดส่วนลดนี้ถูกใช้งานครบจำนวนแล้วในขณะที่คุณกำลังทำรายการ</div>";
                unset($_SESSION['discount_percent']);
                unset($_SESSION['discount_code']);
            } else {
                $message = "<div class='alert alert-error'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</div>";
            }
        }
    }
}

// Fetch Data for View
$my_claims = [];
$res_c = $conn->query("SELECT * FROM reward_claims WHERE user_id = $user_identifier ORDER BY claimed_at DESC LIMIT 10");
while ($row = $res_c->fetch_assoc())
    $my_claims[] = $row;

$history = [];
$res_h = $conn->query("SELECT * FROM redemption_history WHERE user_id = $user_identifier ORDER BY redeemed_at DESC LIMIT 20");
while ($row = $res_h->fetch_assoc())
    $history[] = $row;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎁 ศูนย์รวม Point | ซูชิละกัน</title>
    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 'orange': { 50: '#FFF8F0', 100: '#FFEDD5', 200: '#FED7AA', 500: '#F97316', 600: '#EA580C', 700: '#C2410C' } },
                    fontFamily: { 'display': ['Prompt', 'sans-serif'], 'body': ['Sarabun', 'sans-serif'] },
                    animation: { 'bounce-slow': 'bounce 3s infinite', 'pulse-slow': 'pulse 3s infinite' }
                }
            }
        }
    </script>
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: black;
            /* Dark BG for Three.js */
            color: #333;
            overflow-x: hidden;
        }

        /* Glassmorphism */
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        /* Gradient Text */
        .text-gradient {
            background: linear-gradient(135deg, #FF6F00, #d32f2f);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Alerts */
        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            padding: 1rem;
            border-radius: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #6EE7B7;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            padding: 1rem;
            border-radius: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #FCA5A5;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-warning {
            background: #FEF3C7;
            color: #92400E;
            padding: 1rem;
            border-radius: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #FDE68A;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 111, 0, 0.5);
            border-radius: 4px;
        }

        /* Loader */
        #loader-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #d32f2f, #FF6F00);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.5s ease-out;
        }

        .loader {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-top: 5px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Hide loader */
        .loaded #loader-wrapper {
            opacity: 0;
            pointer-events: none;
        }
    </style>

    <!-- Three.js Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="js/three_bg.js"></script>
</head>

<body class="p-4 md:p-8 min-h-screen relative">

    <!-- Loader -->
    <div id="loader-wrapper">
        <div class="loader"></div>
    </div>

    <div class="max-w-5xl mx-auto relative z-10">

        <!-- Header -->
        <div
            class="glass-card rounded-3xl p-6 md:p-8 mb-8 flex flex-col md:flex-row justify-between items-center gap-6 animate-fade-in-down">
            <div class="flex items-center gap-6">
                <div
                    class="w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center text-5xl shadow-inner animate-bounce-slow">
                    🍣</div>
                <div class="text-center md:text-left">
                    <h1 class="text-3xl font-display font-extrabold text-gradient mb-1">ศูนย์รวม Point</h1>
                    <p class="text-gray-500 font-medium">สวัสดีคุณ <span
                            class="font-bold text-orange-600 text-lg"><?php echo $username; ?></span> 👋</p>
                </div>
            </div>
            <div class="flex gap-3 w-full md:w-auto">
                <a href="/"
                    class="flex-1 md:flex-none px-6 py-3 rounded-xl bg-orange-50 text-orange-600 font-bold hover:bg-orange-100 transition-all text-center shadow-sm hover:shadow-md border border-orange-100">
                    <i class="fas fa-home mr-2"></i> หน้าหลัก
                </a>
                <a href="logout_customer"
                    class="flex-1 md:flex-none px-6 py-3 rounded-xl bg-red-50 text-red-500 font-bold hover:bg-red-100 transition-all text-center shadow-sm hover:shadow-md border border-red-100">
                    <i class="fas fa-sign-out-alt mr-2"></i> ออก
                </a>
            </div>
        </div>

        <?php if ($message)
            echo "<div class='mb-6 animate-pulse'>$message</div>"; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Left: Points & Menu -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Points Card -->
                <div
                    class="glass-card rounded-3xl p-8 text-center border-t-4 border-orange-500 relative overflow-hidden group">
                    <div
                        class="absolute top-0 right-0 p-4 opacity-10 text-9xl transform rotate-12 group-hover:rotate-45 transition-transform duration-700">
                        💎</div>
                    <p class="text-gray-500 font-bold mb-2 uppercase tracking-wide text-sm">คะแนนสะสมของคุณ</p>
                    <div
                        class="text-6xl font-extrabold text-gradient mb-2 drop-shadow-sm scale-100 group-hover:scale-110 transition-transform duration-300">
                        <?php echo $current_points_for_display; ?>
                    </div>
                    <p class="text-orange-400 text-sm font-bold">POINTS</p>
                </div>

                <!-- Menu Buttons (Vertical Grid on Desktop) -->
                <div class="glass-card rounded-3xl p-4 grid grid-cols-2 gap-3">
                    <button onclick="toggleSection('dailyLoginCard')" class="col-span-2 p-3 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-bold shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 hover:-translate-y-1 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-calendar-check"></i> เช็คอินรายวัน
                        <?php if($can_claim): ?><span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full animate-pulse">!</span><?php endif; ?>
                    </button>
                    <button onclick="toggleSection('redeemCodeCard')" class="col-span-2 md:col-span-1 p-3 rounded-xl bg-gradient-to-r from-orange-500 to-orange-600 text-white font-bold shadow-lg shadow-orange-500/30 hover:shadow-orange-500/50 hover:-translate-y-1 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-gift"></i> แลกโค้ด
                    </button>
                    <button onclick="toggleSection('gachaCard')" class="col-span-1 p-3 rounded-xl bg-gradient-to-r from-pink-500 to-pink-600 text-white font-bold shadow-lg shadow-pink-500/30 hover:shadow-pink-500/50 hover:-translate-y-1 transition-all flex flex-col items-center gap-1">
                        <i class="fas fa-dice text-xl"></i> สุ่มกาชา
                    </button>
                    <button onclick="toggleSection('historyCard')" class="col-span-1 p-3 rounded-xl bg-gradient-to-r from-purple-500 to-purple-600 text-white font-bold shadow-lg shadow-purple-500/30 hover:shadow-purple-500/50 hover:-translate-y-1 transition-all flex flex-col items-center gap-1">
                        <i class="fas fa-history text-xl"></i> ประวัติ
                    </button>
                    <button onclick="toggleSection('editProfileCard')" class="col-span-1 p-2 rounded-xl bg-teal-50 text-teal-600 font-bold hover:bg-teal-100 transition-all text-xs border border-teal-100">
                        <i class="fas fa-user-edit mb-1 block text-lg"></i> แก้ไขข้อมูล
                    </button>
                    <button onclick="toggleSection('passwordCard')" class="col-span-1 p-2 rounded-xl bg-blue-50 text-blue-600 font-bold hover:bg-blue-100 transition-all text-xs border border-blue-100">
                        <i class="fas fa-key mb-1 block text-lg"></i> เปลี่ยนรหัส
                    </button>
                </div>
            </div>

            <!-- Right: Dynamic Content Area -->
            <div class="lg:col-span-2">


                <!-- 0. Daily Login UI (Redesigned) -->
                <div id="dailyLoginCard" class="hidden glass-card rounded-3xl p-6 md:p-8 h-full relative overflow-hidden bg-gradient-to-b from-white to-orange-50/20">
                    <!-- Decorative BG Elements -->
                    <div class="absolute -top-10 -right-10 w-64 h-64 bg-orange-400/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute bottom-0 left-0 w-full h-32 bg-gradient-to-t from-white via-white/80 to-transparent z-10 pointer-events-none"></div>

                    <!-- Header -->
                    <div class="text-center relative z-20 mb-6">
                        <div class="inline-flex items-center justify-center p-3 bg-orange-100 rounded-full mb-3 shadow-inner">
                            <span class="text-3xl">🗓️</span>
                        </div>
                        <h3 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-orange-600 to-red-600 mb-1 drop-shadow-sm">
                            เช็คอินรายวัน
                        </h3>
                        <p class="text-gray-500 text-sm font-medium">เก็บสะสมแต้มฟรีทุกวัน ยิ่งเช็คอินยิ่งคุ้ม!</p>
                    </div>

                    <!-- Streak Badge -->
                    <div class="flex justify-center mb-8 relative z-20">
                        <div class="bg-white border border-orange-100 shadow-xl shadow-orange-100/50 rounded-2xl px-6 py-3 flex items-center gap-4 transform hover:scale-105 transition-transform duration-300">
                            <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-2xl animate-pulse border border-red-100">
                                🔥
                            </div>
                            <div class="text-left">
                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Streak ต่อเนื่อง</p>
                                <div class="flex items-baseline gap-1">
                                    <p class="text-3xl font-black text-gray-800 leading-none"><?php echo number_format($streak); ?></p>
                                    <span class="text-xs font-bold text-gray-400">วัน</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Calendar Grid -->
                    <div class="relative z-20 mb-8 max-h-[400px] overflow-y-auto pr-2 customize-scrollbar pb-24"> <!-- Added pb-24 for button space -->
                        <div class="grid grid-cols-5 sm:grid-cols-6 md:grid-cols-7 gap-2 sm:gap-3 p-1">
                            <?php 
                            for ($day = 1; $day <= $days_in_month; $day++): 
                                $is_today = ($day == $today_day);
                                $is_claimed = in_array($day, $claimed_days);
                                $is_future = ($day > $today_day);
                                $is_missed = ($day < $today_day) && !$is_claimed;
                                
                                $pts_val = $daily_login_rewards[$day];
                                $is_big_reward = ($pts_val >= 50); // Big reward threshold
                            ?>
                                <!-- Day Cell -->
                                <div class="group relative aspect-square flex flex-col items-center justify-center rounded-2xl border-2 transition-all duration-300
                                    <?php 
                                    if ($is_today) {
                                        if (!$is_claimed) echo 'bg-gradient-to-br from-orange-500 to-red-500 border-transparent shadow-lg shadow-orange-500/40 scale-105 z-10 ring-4 ring-orange-50';
                                        else echo 'bg-green-50 border-green-200 grayscale-0 ring-2 ring-green-100';
                                    } elseif ($is_claimed) {
                                        echo 'bg-green-50/50 border-green-100 opacity-60 hover:opacity-100';
                                    } elseif ($is_missed) {
                                        echo 'bg-gray-50 border-gray-100 opacity-40 grayscale';
                                    } else { // Future
                                        echo $is_big_reward ? 'bg-yellow-50 border-yellow-200' : 'bg-white border-gray-100 hover:border-orange-200 hover:shadow-md';
                                    }
                                    ?>">
                                    
                                    <!-- Day Number -->
                                    <span class="absolute top-1 left-2 text-[10px] font-bold <?php echo ($is_today && !$is_claimed) ? 'text-orange-100' : 'text-gray-400'; ?>">
                                        <?php echo $day; ?>
                                    </span>

                                    <!-- Content -->
                                    <div class="flex items-center justify-center mt-2">
                                        <?php if ($is_claimed): ?>
                                            <i class="fas fa-check-circle text-2xl text-green-500"></i>
                                        <?php elseif ($is_missed): ?>
                                            <i class="fas fa-times text-xl text-gray-300"></i>
                                        <?php elseif ($is_today && !$is_claimed): ?>
                                            <span class="text-3xl animate-bounce drop-shadow-md">🎁</span>
                                        <?php elseif ($is_big_reward): ?>
                                            <span class="text-2xl drop-shadow-sm filter contrast-125">👑</span>
                                        <?php else: ?>
                                            <div class="flex flex-col items-center">
                                                <span class="text-xs font-bold text-gray-300 group-hover:text-orange-400 transition-colors">+<?php echo $pts_val; ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Action Button (Fixed Bottom) -->
                    <div class="absolute bottom-0 left-0 w-full p-6 bg-white/90 backdrop-blur-md border-t border-gray-100 z-30">
                        <?php if ($can_claim_today): ?>
                            <form method="POST">
                                <button type="submit" name="claim_daily_login" class="w-full relative py-4 bg-gradient-to-r from-orange-500 to-red-600 text-white rounded-2xl font-bold font-display shadow-lg shadow-orange-500/30 hover:shadow-orange-500/50 hover:scale-[1.02] active:scale-95 transition-all overflow-hidden group">
                                    <span class="relative z-10 flex items-center justify-center gap-2 text-lg">
                                        <i class="fas fa-gift animate-pulse"></i> 
                                        กดรับ <?php echo $today_reward_points; ?> แต้ม
                                    </span>
                                    <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="w-full py-4 bg-gray-100 text-gray-400 rounded-2xl font-bold border-2 border-dashed border-gray-200 flex items-center justify-center gap-2 cursor-not-allowed">
                                <i class="fas fa-check-circle text-green-500"></i> รับเรียบร้อยแล้ว
                            </div>
                            <p class="text-center text-[10px] text-gray-400 mt-2 font-medium">พบกันใหม่พรุ่งนี้นะครับ!</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 1. Redeem Code -->
                <div id="redeemCodeCard" class="hidden glass-card rounded-3xl p-8 h-full flex flex-col justify-center relative overflow-hidden">
                    <div class="absolute -right-10 -bottom-10 text-9xl opacity-5 rotate-12">🎁</div>
                    <h3 class="text-2xl font-bold text-orange-800 mb-6 flex items-center gap-3"><span class="bg-orange-100 p-2 rounded-lg text-orange-600"><i class="fas fa-barcode"></i></span> แลกโค้ดรับ Point</h3>
                    <form method="POST" class="flex flex-col sm:flex-row gap-3 relative z-10">
                        <input type="text" name="redeem_code" placeholder="กรอกโค้ดที่นี่..." required
                            class="flex-1 px-6 py-4 rounded-2xl bg-white border-2 border-orange-100 text-lg uppercase font-bold focus:border-orange-500 focus:ring-4 focus:ring-orange-100 outline-none transition-all shadow-sm">
                        <button type="submit" class="bg-green-500 text-white px-8 py-4 rounded-2xl font-bold hover:bg-green-600 shadow-lg shadow-green-500/30 hover:-translate-y-1 transition-all text-lg">
                            <i class="fas fa-check"></i> ยืนยัน
                        </button>
                    </form>
                    <p class="mt-4 text-gray-400 text-sm text-center sm:text-left"><i class="fas fa-info-circle mr-1"></i> กรอกรหัสโค้ดที่ได้รับจากกิจกรรมเพื่อสะสมแต้ม</p>
                </div>

                <!-- 2. Gacha -->
                <div id="gachaCard" class="hidden glass-card rounded-3xl p-8 h-full text-center relative overflow-hidden bg-gradient-to-br from-white to-pink-50">
                    <div class="absolute top-0 left-0 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-20"></div>
                    <h3 class="text-3xl font-extrabold text-pink-600 mb-2 relative z-10">🎰 GACHA PON!</h3>
                    <p class="mb-8 text-gray-600 relative z-10 font-medium">ลุ้นรับแต้มสูงสุด 8 แต้ม เพียงใช้ <b class="text-pink-600 bg-pink-100 px-2 rounded"><?php echo $gacha_cost; ?> Point</b></p>
                    
                    <div class="w-32 h-32 mx-auto bg-white rounded-full flex items-center justify-center shadow-inner mb-8 border-8 border-pink-100 relative z-10 animate-bounce-slow">
                        <span class="text-6xl">🎲</span>
                    </div>

                    <form method="POST" class="relative z-10">
                        <button type="submit" name="gacha_spin" value="1"
                            class="w-full max-w-sm mx-auto py-4 bg-gradient-to-r from-pink-500 to-rose-500 text-white rounded-2xl font-bold text-xl hover:scale-105 hover:shadow-xl hover:shadow-pink-500/40 transition-all">
                            หมุนเลย! (Start)
                        </button>
                    </form>
                </div>

                <!-- 3. Edit Profile -->
                <div id="editProfileCard" class="hidden glass-card rounded-3xl p-8 h-full">
                    <h3 class="text-2xl font-bold text-teal-700 mb-6 border-b pb-4">👤 แก้ไขข้อมูลส่วนตัว</h3>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-gray-500 text-sm font-bold mb-1 ml-1">ชื่อ-นามสกุล</label>
                            <input type="text" name="new_username" value="<?php echo $username; ?>"
                                class="w-full p-4 bg-gray-50 border-transparent focus:bg-white focus:border-teal-500 rounded-xl outline-none transition-all font-bold text-gray-700 shadow-inner">
                        </div>
                        <button type="submit" name="edit_profile" class="w-full py-4 bg-teal-500 text-white rounded-xl font-bold shadow-lg shadow-teal-500/30 hover:bg-teal-600 transition-all">บันทึกการเปลี่ยนแปลง</button>
                    </form>
                </div>

                <!-- 4. Password -->
                <div id="passwordCard" class="hidden glass-card rounded-3xl p-8 h-full">
                    <h3 class="text-2xl font-bold text-blue-700 mb-6 border-b pb-4">🔑 เปลี่ยนรหัสผ่าน</h3>
                    <form method="POST" class="space-y-4">
                        <input type="password" name="current_password" placeholder="รหัสผ่านปัจจุบัน" class="w-full p-4 bg-gray-50 border rounded-xl outline-none focus:ring-2 focus:ring-blue-500 transition-all" required>
                        <div class="grid grid-cols-2 gap-4">
                            <input type="password" name="new_password" placeholder="รหัสผ่านใหม่" class="w-full p-4 bg-gray-50 border rounded-xl outline-none focus:ring-2 focus:ring-blue-500 transition-all" required>
                            <input type="password" name="confirm_password" placeholder="ยืนยันอีกครั้ง" class="w-full p-4 bg-gray-50 border rounded-xl outline-none focus:ring-2 focus:ring-blue-500 transition-all" required>
                        </div>
                        <button type="submit" name="change_password" class="w-full py-4 bg-blue-500 text-white rounded-xl font-bold shadow-lg shadow-blue-500/30 hover:bg-blue-600 transition-all">เปลี่ยนรหัสผ่าน</button>
                    </form>
                </div>

                <!-- 5. History -->
                <div id="historyCard" class="hidden glass-card rounded-3xl p-6 h-full flex flex-col">
                    <h3 class="text-xl font-bold text-purple-800 mb-4 flex items-center gap-2"><i class="fas fa-history"></i> ประวัติล่าสุด</h3>
                    <div class="overflow-y-auto flex-1 pr-2 max-h-[400px]">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-white/90 backdrop-blur-sm">
                                <tr class="text-left py-2 border-b text-purple-600">
                                    <th class="p-3">รายการ</th>
                                    <th class="p-3 text-right">แต้ม</th>
                                    <th class="p-3 text-right">เวลา</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $h): ?>
                                        <tr class="border-b border-gray-100 hover:bg-purple-50 transition-colors">
                                            <td class="p-3 font-medium text-gray-700"><?php echo htmlspecialchars($h['code']); ?></td>
                                            <td class="p-3 text-right font-bold <?php echo $h['points'] > 0 ? 'text-green-600' : 'text-red-500'; ?>">
                                                <?php echo $h['points'] > 0 ? '+' : '';
                                                echo number_format($h['points']); ?>
                                            </td>
                                            <td class="p-3 text-right text-xs text-gray-400"><?php echo date('d/m H:i', strtotime($h['redeemed_at'])); ?></td>
                                        </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Section: Sushi Redemption -->
        <div class="glass-card rounded-3xl p-6 md:p-8 mb-8 relative overflow-hidden">
             <div class="absolute -left-10 -bottom-10 text-9xl opacity-5 rotate-12 pointer-events-none">🍣</div>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-6 border-b border-gray-100 pb-4 gap-4">
                <div class="flex-1">
                    <h3 class="text-2xl font-bold text-orange-800 flex items-center gap-3">
                        <span class="bg-orange-100 text-orange-600 p-2 rounded-xl"><i class="fas fa-utensils"></i></span> 
                        แลกซูชิด้วย Point
                    </h3>
                    <p class="text-gray-500 text-sm mt-1 ml-12">สะสมแต้มแลกความอร่อยฟรี!</p>
                </div>
                
                <!-- Discount Code Input Area -->
                <div class="bg-orange-50 p-3 rounded-2xl flex items-center gap-2 border border-orange-100 shadow-inner w-full md:w-auto">
                    <?php if ($discount_percent > 0): ?>
                                <div class="flex items-center gap-2 text-green-700 font-bold px-2">
                                    <i class="fas fa-tags"></i> ส่วนลด <?php echo $discount_percent; ?>% (<?php echo $discount_code_used; ?>)
                                    <form method="POST" class="inline">
                                        <button type="submit" name="cancel_discount" class="ml-2 text-red-500 hover:text-red-700 text-xs bg-white rounded-full px-2 py-1 shadow-sm"><i class="fas fa-times"></i> ยกเลิก</button>
                                    </form>
                                </div>
                    <?php else: ?>
                                <form method="POST" class="flex gap-2 w-full">
                                    <input type="text" name="discount_code" placeholder="ใส่รหัสส่วนลด..." class="bg-white border text-sm rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-orange-500 flex-1 md:w-32 uppercase" required>
                                    <button type="submit" name="apply_discount" class="text-white bg-orange-500 hover:bg-orange-600 font-medium rounded-lg text-sm px-3 py-1.5 whitespace-nowrap">ใช้โค้ด</button>
                                </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php foreach ($sushi_tiers as $pts => $pcs):
                    $real_cost = $discount_percent > 0 ? floor($pts * (100 - $discount_percent) / 100) : $pts;
                    $avail = $user_points_current >= $real_cost; ?>
                            <div class="relative bg-white border border-gray-100 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all group overflow-hidden">
                                <?php if ($avail): ?>
                                            <div class="absolute top-0 right-0 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-bl-lg z-10">แลกได้</div>
                                <?php endif; ?>
                        
                                <div class="flex justify-between items-start mb-4">
                                    <div class="text-4xl group-hover:scale-110 transition-transform">🍣</div>
                                    <div class="text-right">
                                        <?php if ($discount_percent > 0): ?>
                                                    <div class="text-gray-400 text-sm line-through"><?php echo $pts; ?></div>
                                                    <div class="text-2xl font-extrabold text-green-600"><?php echo $real_cost; ?></div>
                                        <?php else: ?>
                                                    <div class="text-2xl font-extrabold text-orange-600"><?php echo $pts; ?></div>
                                        <?php endif; ?>
                                        <div class="text-xs text-gray-400 font-bold">POINTS</div>
                                    </div>
                                </div>
                        
                                <h4 class="font-bold text-gray-800 text-lg mb-4">ซูชิ <?php echo $pcs; ?> ชิ้น</h4>
                        
                                <?php if ($avail): ?>
                                            <form method="POST" onsubmit="return confirm('ยืนยันการแลกซูชิ <?php echo $pcs; ?> ชิ้น ที่ <?php echo $real_cost; ?> แต้ม?');">
                                                <button type="submit" name="redeem_sushi" value="<?php echo $pts; ?>"
                                                    class="w-full py-2 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-xl font-bold shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-sm">
                                                    แลกเลย
                                                </button>
                                            </form>
                                <?php else: ?>
                                            <button disabled class="w-full py-2 bg-gray-100 text-gray-400 rounded-xl font-bold text-sm cursor-not-allowed">แต้มไม่พอ</button>
                                <?php endif; ?>
                            </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Section: My Claims -->
        <?php if (!empty($my_claims)): ?>
                    <div class="glass-card rounded-3xl p-6 md:p-8">
                        <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2"><i class="fas fa-receipt text-purple-500"></i> รายการของฉัน (My Orders)</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-gray-400 text-sm border-b border-gray-100">
                                        <th class="pb-3 pl-2">ID</th>
                                        <th class="pb-3">รายการ</th>
                                        <th class="pb-3">สถานะ</th>
                                        <th class="pb-3 text-right pr-2">ทำรายการเมื่อ</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <?php foreach ($my_claims as $claim): ?>
                                                <tr class="hover:bg-gray-50/50 transition-colors">
                                                    <td class="py-4 pl-2 font-mono text-gray-400 text-sm">#<?php echo $claim['id']; ?></td>
                                                    <td class="py-4 font-bold text-gray-700">
                                                        <i class="fas fa-fish text-orange-500 mr-2"></i>ซูชิ <?php echo $claim['items_count']; ?> ชิ้น
                                                    </td>
                                                    <td class="py-4">
                                                        <?php
                                                        if ($claim['status'] == 'pending')
                                                            echo '<span class="inline-flex items-center gap-1 text-yellow-700 bg-yellow-50 border border-yellow-200 px-3 py-1 rounded-full text-xs font-bold"><i class="fas fa-clock"></i> รอรับของ</span>';
                                                        elseif ($claim['status'] == 'fulfilled')
                                                            echo '<span class="inline-flex items-center gap-1 text-green-700 bg-green-50 border border-green-200 px-3 py-1 rounded-full text-xs font-bold"><i class="fas fa-check-circle"></i> รับแล้ว</span>';
                                                        else
                                                            echo '<span class="inline-flex items-center gap-1 text-red-700 bg-red-50 border border-red-200 px-3 py-1 rounded-full text-xs font-bold"><i class="fas fa-times"></i> ยกเลิก</span>';
                                                        ?>
                                                    </td>
                                                    <td class="py-4 text-gray-400 text-xs text-right pr-2"><?php echo date('d/m/Y H:i', strtotime($claim['claimed_at'])); ?></td>
                                                </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
        <?php endif; ?>

    </div>


    <script>
        function toggleSection(id) {
            const el = document.getElementById(id);
            const isHidden = el.classList.contains('hidden');
            
            
            // Hide all first
            ['dailyLoginCard', 'redeemCodeCard', 'gachaCard', 'editProfileCard', 'passwordCard', 'historyCard'].forEach(cid => {
                const element = document.getElementById(cid);
                if (element) {
                    element.classList.add('hidden');
                }
            });
            
            // Toggle target
            if (isHidden) {
                el.classList.remove('hidden');
                // Scroll to element on mobile
                if(window.innerWidth < 1024) {
                    el.scrollIntoView({behavior: 'smooth', block: 'start'});
                }
            }
        }

        window.addEventListener('load', function () {
            document.body.classList.add('loaded');
            // Show daily login code card by default
            const dailyCard = document.getElementById('dailyLoginCard');
            if (dailyCard) {
                dailyCard.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>