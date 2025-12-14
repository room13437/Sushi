<?php
// 1. ตรวจสอบว่าไฟล์ db.php ถูกรวมเข้ามาอย่างถูกต้อง
include "db.php";

// 2. เริ่ม Session เพื่อเข้าถึงข้อมูลการล็อกอิน
session_start();

// 3. ตรวจสอบสถานะการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header('Location: formlogin');
    exit;
}

// ----------------------------------------------------
// ค่าคงที่และตัวแปร
// ----------------------------------------------------
$user_identifier = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);
$table_name = "users";
$message = "";
$cooldown_seconds = 86400; // กำหนดเวลา Cooldown สำหรับโค้ดรับ Point
$gacha_cost = 5; // กำหนดต้นทุนกาชาเป็น 5 Point
$expected_code = "AdminN_N";

// -------------------------------------------------------------------------
// *** ส่วนประมวลผลการเปลี่ยนรหัสผ่าน ***
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // ตรวจสอบข้อมูล
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "<div class='alert alert-error'>❌ กรุณากรอกข้อมูลให้ครบถ้วน</div>";
    } elseif ($new_password !== $confirm_password) {
        $message = "<div class='alert alert-error'>❌ รหัสผ่านใหม่ไม่ตรงกัน</div>";
    } elseif (strlen($new_password) < 6) {
        $message = "<div class='alert alert-error'>❌ รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร</div>";
    } else {
        // ดึงรหัสผ่านปัจจุบันจากฐานข้อมูล
        $stmt_check_pass = $conn->prepare("SELECT password FROM $table_name WHERE id = ?");
        $stmt_check_pass->bind_param("i", $user_identifier);
        $stmt_check_pass->execute();
        $result_pass = $stmt_check_pass->get_result();

        if ($result_pass->num_rows > 0) {
            $user_data = $result_pass->fetch_assoc();
            $stored_password = $user_data['password'];
            $stmt_check_pass->close();

            // ตรวจสอบรหัสผ่านปัจจุบัน
            if (password_verify($current_password, $stored_password)) {
                // Hash รหัสผ่านใหม่
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

                // อัพเดทรหัสผ่าน
                $stmt_update_pass = $conn->prepare("UPDATE $table_name SET password = ? WHERE id = ?");
                $stmt_update_pass->bind_param("si", $new_password_hash, $user_identifier);

                if ($stmt_update_pass->execute()) {
                    $message = "<div class='alert alert-success'>✅ เปลี่ยนรหัสผ่านสำเร็จ!</div>";
                } else {
                    $message = "<div class='alert alert-error'>❌ เกิดข้อผิดพลาด: " . $stmt_update_pass->error . "</div>";
                }
                $stmt_update_pass->close();
            } else {
                $message = "<div class='alert alert-error'>❌ รหัสผ่านปัจจุบันไม่ถูกต้อง</div>";
            }
        }
    }
}

// ----------------------------------------------------
// 4. ดึงข้อมูลคะแนนปัจจุบัน *ก่อน* ประมวลผล POST เพื่อใช้ในการตรวจสอบต้นทุน
// ----------------------------------------------------
$user_points_current = 0;
$time_left_for_redeem = 0;
$last_redeem_time = 0;

$sql_fetch = "SELECT points, last_redeem_1234_time FROM $table_name WHERE id = ?";
$stmt_fetch = $conn->prepare($sql_fetch);

if ($stmt_fetch) {
    $stmt_fetch->bind_param("i", $user_identifier);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_points_current = (int) $row['points']; // เก็บเป็นตัวเลข
        $last_redeem_time = (int) $row['last_redeem_1234_time'];

        // คำนวณเวลาที่เหลือสำหรับโค้ด
        $time_since_last_redeem = time() - $last_redeem_time;
        if ($time_since_last_redeem < $cooldown_seconds) {
            $time_left_for_redeem = $cooldown_seconds - $time_since_last_redeem;
        }
    }
    $stmt_fetch->close();
}

$current_points_for_display = number_format($user_points_current); // สำหรับแสดงผล

// -------------------------------------------------------------------------
// 5. *** สร้างตาราง redemption_history ถ้ายังไม่มี ***
// -------------------------------------------------------------------------
$conn->query("CREATE TABLE IF NOT EXISTS `redemption_history` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `code` VARCHAR(100) NOT NULL,
    `points` INT(11) NOT NULL,
    `type` VARCHAR(20) DEFAULT 'code',
    `redeemed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// *** สร้างตาราง reward_claims สำหรับเก็บรายการแลกซูชิ ***
$conn->query("CREATE TABLE IF NOT EXISTS `reward_claims` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `username` VARCHAR(100) NOT NULL,
    `points_used` INT(11) NOT NULL,
    `items_count` INT(11) NOT NULL,
    `status` ENUM('pending', 'fulfilled', 'cancelled') DEFAULT 'pending',
    `claimed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `fulfilled_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// 8. *** ส่วนประมวลผลการใส่โค้ดรับ Point (Multi-Use System) ***
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['redeem_code'])) {
    $input_code = strtoupper(trim($_POST['redeem_code']));

    // ค้นหาโค้ดในฐานข้อมูล พร้อมนับจำนวนการใช้งาน
    $stmt_check = $conn->prepare("SELECT id, points, max_uses, 
                                   (SELECT COUNT(*) FROM code_redemptions WHERE code_id = redeem_codes.id) as current_uses
                                   FROM redeem_codes WHERE code = ?");
    $stmt_check->bind_param("s", $input_code);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // พบโค้ด - ดึงข้อมูล
        $code_row = $result_check->fetch_assoc();
        $code_id = $code_row['id'];
        $points_to_add = (int) $code_row['points'];
        $max_uses = (int) $code_row['max_uses'];
        $current_uses = (int) $code_row['current_uses'];
        $stmt_check->close();

        // ตรวจสอบว่าโค้ดถูกใช้หมดแล้วหรือยัง
        if ($current_uses >= $max_uses) {
            $message = "<div class='alert alert-error'>❌ โค้ดนี้ถูกใช้งานครบจำนวนแล้ว ({$max_uses} ครั้ง)</div>";
        } else {
            // ตรวจสอบว่าผู้ใช้คนนี้เคยใช้โค้ดนี้หรือยัง
            $stmt_user_check = $conn->prepare("SELECT id FROM code_redemptions WHERE code_id = ? AND user_id = ?");
            $stmt_user_check->bind_param("ii", $code_id, $user_identifier);
            $stmt_user_check->execute();
            $result_user = $stmt_user_check->get_result();

            if ($result_user->num_rows > 0) {
                // ผู้ใช้เคยใช้โค้ดนี้แล้ว
                $stmt_user_check->close();
                $message = "<div class='alert alert-error'>❌ คุณเคยใช้โค้ดนี้ไปแล้ว! แต่ละโค้ดใช้ได้เพียงครั้งเดียวต่อบัญชี</div>";
            } else {
                // ผู้ใช้ยังไม่เคยใช้โค้ดนี้ และโค้ดยังมีจำนวนครั้งเหลือ - ดำเนินการแลก
                $stmt_user_check->close();

                // 1. อัปเดตคะแนนผู้ใช้
                $sql_update = "UPDATE $table_name SET points = points + ? WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);

                if ($stmt_update) {
                    $stmt_update->bind_param("ii", $points_to_add, $user_identifier);

                    if ($stmt_update->execute()) {
                        // 2. บันทึกการใช้โค้ดในตาราง code_redemptions
                        $stmt_redeem = $conn->prepare("INSERT INTO code_redemptions (code_id, user_id) VALUES (?, ?)");
                        $stmt_redeem->bind_param("ii", $code_id, $user_identifier);
                        $stmt_redeem->execute();
                        $stmt_redeem->close();

                        // 3. บันทึกประวัติการแลก
                        $stmt_history = $conn->prepare("INSERT INTO redemption_history (user_id, code, points, type) VALUES (?, ?, ?, 'code')");
                        $stmt_history->bind_param("isi", $user_identifier, $input_code, $points_to_add);
                        $stmt_history->execute();
                        $stmt_history->close();

                        // อัปเดตคะแนนในหน่วยความจำ
                        $user_points_current += $points_to_add;
                        $current_points_for_display = number_format($user_points_current);

                        $remaining = $max_uses - ($current_uses + 1);
                        $message = "<div class='alert alert-success'>✅ ยอดเยี่ยม! คุณได้รับ {$points_to_add} Point จากโค้ด {$input_code}! (โค้ดนี้เหลือใช้ได้อีก {$remaining} ครั้ง)</div>";
                    } else {
                        $message = "<div class='alert alert-error'>❌ เกิดข้อผิดพลาด: " . $stmt_update->error . "</div>";
                    }
                    $stmt_update->close();
                }
            }
        }
    } else {
        $stmt_check->close();
        $message = "<div class='alert alert-error'>❌ โค้ดไม่ถูกต้อง กรุณาลองใหม่</div>";
    }
}


// -------------------------------------------------------------------------
// 6. *** ส่วนประมวลผลระบบกาชา (Gacha System: Cost 5, Reward 0 or 2-7) ***
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['gacha_spin'])) {

    // A. ตรวจสอบคะแนน
    if ($user_points_current < $gacha_cost) {
        $message = "<div class='alert alert-error'>💸 คะแนนไม่เพียงพอ! คุณมี {$current_points_for_display} Point ต้องใช้ {$gacha_cost} Point</div>";
    } else {
        // ... (โค้ด Gacha เดิม)
        $roll = rand(1, 100);
        $points_gained = 0;

        if ($roll <= 60) {
            $points_gained = rand(4, 8);
        } else {
            $points_gained = 0;
        }
        $net_change = $points_gained - $gacha_cost;

        $sql_gacha_update = "UPDATE $table_name SET points = points + ? WHERE id = ?";
        $stmt_gacha_update = $conn->prepare($sql_gacha_update);

        if ($stmt_gacha_update) {
            $stmt_gacha_update->bind_param("ii", $net_change, $user_identifier);

            if ($stmt_gacha_update->execute()) {
                $user_points_current += $net_change;
                $current_points_for_display = number_format($user_points_current);

                if ($points_gained == 0) {
                    $message = "<div class='alert alert-error'>🎰 เสียใจด้วย! คุณไม่ได้ Point (เสีย {$gacha_cost} Point)</div>";
                } else {
                    if ($net_change > 0) {
                        $message = "<div class='alert alert-success'>🎰 ยินดีด้วย! ได้รับ {$points_gained} Point (+{$net_change} สุทธิ)</div>";
                    } elseif ($net_change == 0) {
                        $message = "<div class='alert alert-warning'>🎰 เสมอตัว! ได้รับ {$points_gained} Point</div>";
                    } else {
                        $message = "<div class='alert alert-warning'>🎰 ได้รับ {$points_gained} Point (เสีย " . abs($net_change) . " สุทธิ)</div>";
                    }
                }

                // บันทึกประวัติกาชา
                $gacha_desc = "กาชา: " . ($points_gained > 0 ? "ได้ {$points_gained}" : "ไม่ได้") . " (สุทธิ {$net_change})";
                $stmt_history = $conn->prepare("INSERT INTO redemption_history (user_id, code, points, type) VALUES (?, ?, ?, 'gacha')");
                $stmt_history->bind_param("isi", $user_identifier, $gacha_desc, $net_change);
                $stmt_history->execute();
                $stmt_history->close();
            } else {
                $message = "<div class='alert alert-error'>❌ เกิดข้อผิดพลาด: " . $stmt_gacha_update->error . "</div>";
            }
            $stmt_gacha_update->close();
        }
    }
}



// -------------------------------------------------------------------------
// 8. *** ส่วนประมวลผลการแลกซูชิด้วย Point ***
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['redeem_sushi'])) {
    $points_to_use = (int) $_POST['redeem_sushi'];

    // กำหนด Tier การแลกซูชิ
    $sushi_tiers = [
        100 => 1,  // 100 Point = 1 ชิ้น
        200 => 2,  // 200 Point = 2 ชิ้น
        300 => 4   // 300 Point = 4 ชิ้น
    ];

    // A. ตรวจสอบว่า Tier ที่เลือกมีอยู่จริง
    if (!isset($sushi_tiers[$points_to_use])) {
        $message = "<div class='alert alert-error'>❌ อัตราการแลกไม่ถูกต้อง</div>";
    } else {
        $sushi_count = $sushi_tiers[$points_to_use];

        // B. ตรวจสอบคะแนน
        if ($user_points_current < $points_to_use) {
            $message = "<div class='alert alert-error'>💸 คะแนนไม่เพียงพอ! คุณมี {$current_points_for_display} Point ต้องใช้ {$points_to_use} Point</div>";
        } else {

            $points_to_deduct = -1 * $points_to_use; // ลบ Point

            // C. อัปเดตคะแนน
            $sql_sushi_update = "UPDATE $table_name SET points = points + ? WHERE id = ?";
            $stmt_sushi_update = $conn->prepare($sql_sushi_update);

            if ($stmt_sushi_update) {
                $stmt_sushi_update->bind_param("ii", $points_to_deduct, $user_identifier);

                if ($stmt_sushi_update->execute()) {

                    // D. บันทึกรายการแลกซูชิในตาราง reward_claims
                    $stmt_claim = $conn->prepare("INSERT INTO reward_claims (user_id, username, points_used, items_count, status) VALUES (?, ?, ?, ?, 'pending')");
                    $stmt_claim->bind_param("isii", $user_identifier, $username, $points_to_use, $sushi_count);
                    $stmt_claim->execute();
                    $claim_id = $stmt_claim->insert_id;
                    $stmt_claim->close();

                    $user_points_current += $points_to_deduct;
                    $current_points_for_display = number_format($user_points_current);

                    // E. บันทึกประวัติการแลก
                    $sushi_desc = "แลกซูชิ: {$sushi_count} ชิ้น (#" . $claim_id . ")";
                    $stmt_history = $conn->prepare("INSERT INTO redemption_history (user_id, code, points, type) VALUES (?, ?, ?, 'sushi')");
                    $stmt_history->bind_param("isi", $user_identifier, $sushi_desc, $points_to_deduct);
                    $stmt_history->execute();
                    $stmt_history->close();

                    $message = "<div class='alert alert-success'>🍣 แลกสำเร็จ! คุณใช้ {$points_to_use} Point แลกซูชิ {$sushi_count} ชิ้น<br>กรุณารับของที่ร้าน (รหัส: #{$claim_id})</div>";

                } else {
                    $message = "<div class='alert alert-error'>❌ เกิดข้อผิดพลาด: " . $stmt_sushi_update->error . "</div>";
                }
                $stmt_sushi_update->close();
            }
        }
    }
}

// -------------------------------------------------------------------------
// ดึงประวัติการแลกของผู้ใช้ (ล่าสุด 20 รายการ)
// -------------------------------------------------------------------------
$history = [];
$stmt_hist = $conn->prepare("SELECT code, points, type, redeemed_at FROM redemption_history WHERE user_id = ? ORDER BY redeemed_at DESC LIMIT 20");
if ($stmt_hist) {
    $stmt_hist->bind_param("i", $user_identifier);
    $stmt_hist->execute();
    $result_hist = $stmt_hist->get_result();
    while ($row = $result_hist->fetch_assoc()) {
        $history[] = $row;
    }
    $stmt_hist->close();
}

// -------------------------------------------------------------------------
// ดึงรายการแลกซูชิของผู้ใช้ (ล่าสุด - พร้อม Pagination)
// -------------------------------------------------------------------------
$claims_per_page = 5;
$claims_page = isset($_GET['claims_page']) ? max(1, (int) $_GET['claims_page']) : 1;
$claims_offset = ($claims_page - 1) * $claims_per_page;

// นับจำนวนรายการทั้งหมด
$total_my_claims = 0;
$stmt_count_claims = $conn->prepare("SELECT COUNT(*) as total FROM reward_claims WHERE user_id = ?");
if ($stmt_count_claims) {
    $stmt_count_claims->bind_param("i", $user_identifier);
    $stmt_count_claims->execute();
    $count_result = $stmt_count_claims->get_result();
    $total_my_claims = $count_result->fetch_assoc()['total'];
    $stmt_count_claims->close();
}

$total_claims_pages = ceil($total_my_claims / $claims_per_page);

$my_claims = [];
$stmt_claims = $conn->prepare("SELECT id, points_used, items_count, status, claimed_at, fulfilled_at FROM reward_claims WHERE user_id = ? ORDER BY claimed_at DESC LIMIT ? OFFSET ?");
if ($stmt_claims) {
    $stmt_claims->bind_param("iii", $user_identifier, $claims_per_page, $claims_offset);
    $stmt_claims->execute();
    $result_claims = $stmt_claims->get_result();
    while ($row = $result_claims->fetch_assoc()) {
        $my_claims[] = $row;
    }
    $stmt_claims->close();
}

// ----------------------------------------------------
// ปิดการเชื่อมต่อฐานข้อมูล
// ----------------------------------------------------
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎁 ศูนย์รวม Point | มารุซูชิ</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'orange': {
                            50: '#FFF8F0', 100: '#FFEDD5', 200: '#FED7AA', 300: '#FDBA74',
                            400: '#FB923C', 500: '#F97316', 600: '#EA580C', 700: '#C2410C',
                        },
                    },
                    fontFamily: {
                        'display': ['Prompt', 'sans-serif'],
                        'body': ['Sarabun', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #FFF9F0 0%, #FFEDD5 30%, #FED7AA 60%, #FDBA74 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(249, 115, 22, 0.15);
            box-shadow: 0 20px 40px rgba(249, 115, 22, 0.1);
        }

        .btn-gradient {
            background: linear-gradient(135deg, #F97316, #EA580C);
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.35);
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            background: linear-gradient(135deg, #EA580C, #C2410C);
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(234, 88, 12, 0.45);
        }

        .feature-card {
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: scale(1.02);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: #D1FAE5;
            border: 2px solid #6EE7B7;
            color: #065F46;
        }

        .alert-error {
            background: #FEE2E2;
            border: 2px solid #FCA5A5;
            color: #991B1B;
        }

        .floating {
            animation: float 4s ease-in-out infinite;
        }

        .floating-delay {
            animation: float 4s ease-in-out infinite 2s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-15px);
            }
        }

        .alert-success {
            background: #D1FAE5;
            border: 1px solid #A7F3D0;
            color: #065F46;
        }

        .alert-error {
            background: #FEE2E2;
            border: 1px solid #FECACA;
            color: #B91C1C;
        }

        .alert-warning {
            background: #FEF3C7;
            border: 1px solid #FDE68A;
            color: #92400E;
        }
    </style>
</head>

<body class="p-4 md:p-8 relative overflow-x-hidden">

    <!-- Floating Decorations -->
    <div class="fixed top-20 left-10 text-7xl opacity-20 floating hidden lg:block pointer-events-none">🎁</div>
    <div class="fixed bottom-20 right-10 text-6xl opacity-15 floating-delay hidden lg:block pointer-events-none">💰
    </div>
    <div class="fixed top-1/3 right-20 text-5xl opacity-10 floating hidden lg:block pointer-events-none">🎰</div>

    <!-- Main Container -->
    <div class="max-w-4xl mx-auto relative z-10">

        <!-- Header -->
        <div class="glass-card rounded-3xl p-8 mb-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="text-center md:text-left">
                    <div class="text-5xl mb-3">🍣</div>
                    <h1
                        class="text-3xl md:text-4xl font-display font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-orange-700 mb-2">
                        ศูนย์รวม Point
                    </h1>
                    <p class="text-orange-600">
                        สวัสดี <span class="font-bold text-orange-700"><?php echo $username; ?></span> 👋
                    </p>
                </div>

                <div class="flex gap-3">
                    <a href="index"
                        class="px-6 py-3 rounded-2xl bg-orange-100 text-orange-600 font-display font-bold hover:bg-orange-200 transition-all flex items-center gap-2">
                        <i class="fas fa-home"></i> หน้าหลัก
                    </a>
                    <a href="logout"
                        class="px-6 py-3 rounded-2xl bg-red-100 text-red-500 font-display font-bold hover:bg-red-200 transition-all flex items-center gap-2">
                        <i class="fas fa-sign-out-alt"></i> ออก
                    </a>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="mb-6">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Control Buttons -->
        <div class="glass-card rounded-2xl p-4 mb-6">
            <div class="flex gap-3 justify-center flex-wrap">
                <button onclick="toggleRedeemCode()" id="toggleRedeemBtn"
                    class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-orange-500 to-orange-600 text-white font-display font-semibold hover:from-orange-600 hover:to-orange-700 shadow-md hover:shadow-lg transition-all text-sm">
                    <i class="fas fa-gift mr-2"></i>แลกโค้ด
                </button>
                <button onclick="toggleGacha()" id="toggleGachaBtn"
                    class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-pink-500 to-rose-500 text-white font-display font-semibold hover:from-pink-600 hover:to-rose-600 shadow-md hover:shadow-lg transition-all text-sm">
                    <i class="fas fa-dice mr-2"></i>สุ่มกาชา
                </button>
                <button onclick="togglePasswordForm()" id="togglePasswordBtn"
                    class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-500 to-blue-600 text-white font-display font-semibold hover:from-blue-600 hover:to-blue-700 shadow-md hover:shadow-lg transition-all text-sm">
                    <i class="fas fa-key mr-2"></i>เปลี่ยนรหัสผ่าน
                </button>
                <button onclick="toggleHistory()" id="toggleHistoryBtn"
                    class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-purple-500 to-purple-600 text-white font-display font-semibold hover:from-purple-600 hover:to-purple-700 shadow-md hover:shadow-lg transition-all text-sm">
                    <i class="fas fa-history mr-2"></i>ดูประวัติ
                </button>
            </div>
        </div>

        <!-- Points Display -->
        <div class="glass-card rounded-3xl p-8 mb-6 text-center border-2 border-orange-300">
            <p class="text-orange-500 font-display font-semibold mb-2">💎 คะแนนสะสมของคุณ</p>
            <div
                class="text-6xl md:text-7xl font-display font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-orange-600">
                <?php echo $current_points_for_display; ?>
            </div>
            <p class="text-orange-400 text-lg mt-2">POINTS</p>
        </div>

        <!-- Feature Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

            <!-- Redeem Code Card (Hidden by default) -->
            <div id="redeemCodeCard" class="feature-card glass-card rounded-3xl p-6" style="display: none;">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-green-100 flex items-center justify-center text-2xl">🎁
                        </div>
                        <h3 class="text-xl font-display font-bold text-orange-800">แลกโค้ดรับ Point</h3>
                    </div>
                    <button onclick="toggleRedeemCode()" class="text-gray-400 hover:text-red-500 transition-colors">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <form method="POST" action="" class="space-y-4">
                    <input type="text" name="redeem_code" placeholder="ใส่โค้ดที่นี่..." required maxlength="50"
                        class="w-full px-5 py-4 rounded-2xl border-2 border-orange-200 bg-white text-orange-800 placeholder-orange-300 font-display uppercase focus:border-orange-500 focus:outline-none">
                    <button type="submit"
                        class="w-full py-4 rounded-2xl font-display font-bold text-white bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 shadow-lg hover:shadow-xl transition-all">
                        <i class="fas fa-gift mr-2"></i> แลกคะแนน
                    </button>
                    <p class="text-center text-sm text-orange-400">โค้ดแต่ละตัวใช้ได้หลายครั้ง แต่ 1
                        บัญชีใช้ได้เพียงครั้งเดียว</p>
                </form>
            </div>


            <!-- Gacha Card (Hidden by default) -->
            <div id="gachaCard" class="feature-card glass-card rounded-3xl p-6" style="display: none;">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-pink-100 flex items-center justify-center text-2xl">🎰
                        </div>
                        <h3 class="text-xl font-display font-bold text-orange-800">สุ่มกาชาลุ้น Point</h3>
                    </div>
                    <button onclick="toggleGacha()" class="text-gray-400 hover:text-red-500 transition-colors">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <div class="bg-gradient-to-r from-orange-50 to-pink-50 rounded-2xl p-4 mb-5">
                    <p class="text-sm text-orange-600 text-center">
                        ใช้ <span class="font-bold text-lg text-orange-700"><?php echo $gacha_cost; ?></span> Point
                        ต่อครั้ง<br>
                        <span class="text-orange-400">โอกาสได้ 4-8 Point (60%)</span>
                    </p>
                </div>

                <form method="POST" action="">
                    <button type="submit" name="gacha_spin" value="1"
                        class="w-full py-4 rounded-2xl font-display font-bold text-white bg-gradient-to-r from-pink-500 to-rose-500 hover:from-pink-600 hover:to-rose-600 shadow-lg hover:shadow-xl transition-all text-lg">
                        🎲 สุ่มเลย!
                    </button>
                </form>
            </div>
        </div>

        <!-- Sushi Redemption Card -->
        <div class="glass-card rounded-3xl p-8 mt-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-pink-100 flex items-center justify-center text-2xl">🍣</div>
                <h3 class="text-xl font-display font-bold text-orange-800">แลกซูชิด้วย Point</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-sm text-orange-500 uppercase tracking-wider">
                            <th class="pb-4 px-4">Point ที่ใช้</th>
                            <th class="pb-4 px-4">ซูชิที่ได้รับ</th>
                            <th class="pb-4 px-4">สถานะ</th>
                            <th class="pb-4 px-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-orange-100">
                        <?php
                        $sushi_tiers = [100 => 1, 200 => 2, 300 => 4];
                        foreach ($sushi_tiers as $points => $pieces):
                            $is_available = $user_points_current >= $points;
                            ?>
                            <tr class="<?php echo $is_available ? '' : 'opacity-50'; ?>">
                                <td class="py-4 px-4 font-display font-bold text-orange-700"><?php echo $points; ?> Point
                                </td>
                                <td class="py-4 px-4 font-display font-bold text-pink-600">🍣 <?php echo $pieces; ?> ชิ้น
                                </td>
                                <td class="py-4 px-4">
                                    <?php if ($is_available): ?>
                                        <span class="px-3 py-1 rounded-full bg-green-100 text-green-600 text-sm font-semibold">✅
                                            พร้อมแลก</span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-400 text-sm font-semibold">❌
                                            ไม่พอ</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-4">
                                    <?php if ($is_available): ?>
                                        <form method="POST" action="" class="inline">
                                            <input type="hidden" name="redeem_sushi" value="<?php echo $points; ?>">
                                            <button type="submit"
                                                class="px-5 py-2 rounded-xl bg-gradient-to-r from-pink-500 to-rose-500 text-white font-display font-bold text-sm hover:from-pink-600 hover:to-rose-600 transition-all">
                                                แลกเลย
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Info Message -->
            <div class="mt-6 pt-6 border-t border-orange-100 text-center">
                <p class="text-orange-400 text-sm">
                    <i class="fas fa-info-circle mr-2"></i>แลกแล้วกรุณาแสดงรหัสการแลกเพื่อรับซูชิที่ร้าน
                </p>
            </div>
        </div>

        <!-- My Sushi Claims Dashboard -->
        <?php if (!empty($my_claims)): ?>
            <div class="glass-card rounded-3xl p-8 mt-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-purple-100 flex items-center justify-center text-2xl">📦</div>
                    <h3 class="text-xl font-display font-bold text-orange-800">รายการแลกซูชิของฉัน</h3>
                    <span class="ml-auto text-sm bg-purple-100 text-purple-600 px-3 py-1 rounded-full font-semibold">
                        <?php echo count($my_claims); ?> รายการ
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr
                                class="text-left text-sm text-orange-500 uppercase tracking-wider border-b border-orange-200">
                                <th class="pb-3 px-3">รหัส</th>
                                <th class="pb-3 px-3">ซูชิ</th>
                                <th class="pb-3 px-3">Point</th>
                                <th class="pb-3 px-3">วันที่แลก</th>
                                <th class="pb-3 px-3">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-orange-50">
                            <?php foreach ($my_claims as $claim):
                                // กำหนดสี badge ตามสถานะ
                                if ($claim['status'] == 'pending') {
                                    $status_badge = '<span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-semibold">🕐 รอรับของ</span>';
                                } elseif ($claim['status'] == 'fulfilled') {
                                    $status_badge = '<span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">✅ ได้รับแล้ว</span>';
                                } else {
                                    $status_badge = '<span class="px-3 py-1 rounded-full bg-red-100 text-red-600 text-xs font-semibold">❌ ยกเลิก</span>';
                                }
                                ?>
                                <tr class="hover:bg-orange-50 transition-colors">
                                    <td class="py-3 px-3">
                                        <span
                                            class="font-mono font-bold text-orange-700 bg-orange-100 px-2 py-1 rounded text-sm">
                                            #<?php echo $claim['id']; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="text-2xl">🍣</span>
                                        <span class="font-display font-bold text-pink-600">
                                            <?php echo $claim['items_count']; ?> ชิ้น
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 font-display font-bold text-red-600">
                                        -<?php echo $claim['points_used']; ?>
                                    </td>
                                    <td class="py-3 px-3 text-orange-500 text-sm">
                                        <?php echo date('d/m/Y H:i', strtotime($claim['claimed_at'])); ?>
                                    </td>
                                    <td class="py-3 px-3">
                                        <?php echo $status_badge; ?>
                                        <?php if ($claim['status'] == 'fulfilled' && $claim['fulfilled_at']): ?>
                                            <div class="text-xs text-gray-400 mt-1">
                                                <?php echo date('d/m H:i', strtotime($claim['fulfilled_at'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 p-4 bg-orange-50 rounded-xl text-sm text-orange-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>รอรับของ:</strong> กรุณาแสดงรหัสการแลกเพื่อรับซูชิที่ร้าน |
                    <strong>ได้รับแล้ว:</strong> ได้รับซูชิเรียบร้อยแล้ว
                </div>

                <!-- Pagination -->
                <?php if ($total_claims_pages > 1): ?>
                    <div class="mt-6 flex justify-center items-center gap-2 flex-wrap">
                        <!-- ปุ่มย้อนกลับ -->
                        <?php if ($claims_page > 1): ?>
                            <a href="?claims_page=<?php echo $claims_page - 1; ?>"
                                class="px-3 py-2 rounded-lg bg-purple-100 text-purple-600 font-semibold hover:bg-purple-200 transition-all text-sm">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-2 rounded-lg bg-gray-100 text-gray-400 font-semibold cursor-not-allowed text-sm">
                                <i class="fas fa-chevron-left"></i>
                            </span>
                        <?php endif; ?>

                        <!-- หมายเลขหน้า -->
                        <?php
                        $start = max(1, $claims_page - 2);
                        $end = min($total_claims_pages, $claims_page + 2);

                        for ($i = $start; $i <= $end; $i++):
                            if ($i == $claims_page):
                                ?>
                                <span
                                    class="px-3 py-2 rounded-lg bg-gradient-to-r from-purple-500 to-purple-600 text-white font-bold text-sm">
                                    <?php echo $i; ?>
                                </span>
                            <?php else: ?>
                                <a href="?claims_page=<?php echo $i; ?>"
                                    class="px-3 py-2 rounded-lg bg-white text-purple-600 font-semibold hover:bg-purple-100 transition-all text-sm">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <!-- ปุ่มถัดไป -->
                        <?php if ($claims_page < $total_claims_pages): ?>
                            <a href="?claims_page=<?php echo $claims_page + 1; ?>"
                                class="px-3 py-2 rounded-lg bg-purple-100 text-purple-600 font-semibold hover:bg-purple-200 transition-all text-sm">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-2 rounded-lg bg-gray-100 text-gray-400 font-semibold cursor-not-allowed text-sm">
                                <i class="fas fa-chevron-right"></i>
                            </span>
                        <?php endif; ?>

                        <span class="ml-2 text-xs text-gray-500">
                            หน้า <?php echo $claims_page; ?>/<?php echo $total_claims_pages; ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Change Password Card (Hidden by default) -->
        <div id="passwordFormCard" class="glass-card rounded-3xl p-8 mt-6" style="display: none;">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-blue-100 flex items-center justify-center text-2xl">🔐</div>
                    <h3 class="text-xl font-display font-bold text-orange-800">เปลี่ยนรหัสผ่าน</h3>
                </div>
                <button onclick="togglePasswordForm()" class="text-gray-400 hover:text-red-500 transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <form method="POST" action="" class="max-w-md mx-auto">
                <div class="space-y-4">
                    <!-- Current Password -->
                    <div>
                        <label class="block text-sm font-semibold text-orange-700 mb-2">
                            <i class="fas fa-key mr-2"></i>รหัสผ่านปัจจุบัน
                        </label>
                        <input type="password" name="current_password" required
                            class="w-full px-4 py-3 rounded-xl border-2 border-orange-200 bg-white text-orange-800 placeholder-orange-300 font-display focus:border-orange-500 focus:outline-none"
                            placeholder="ใส่รหัสผ่านปัจจุบัน">
                    </div>

                    <!-- New Password -->
                    <div>
                        <label class="block text-sm font-semibold text-orange-700 mb-2">
                            <i class="fas fa-lock mr-2"></i>รหัสผ่านใหม่
                        </label>
                        <input type="password" name="new_password" required minlength="6"
                            class="w-full px-4 py-3 rounded-xl border-2 border-orange-200 bg-white text-orange-800 placeholder-orange-300 font-display focus:border-orange-500 focus:outline-none"
                            placeholder="อย่างน้อย 6 ตัวอักษร">
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-sm font-semibold text-orange-700 mb-2">
                            <i class="fas fa-check-circle mr-2"></i>ยืนยันรหัสผ่านใหม่
                        </label>
                        <input type="password" name="confirm_password" required minlength="6"
                            class="w-full px-4 py-3 rounded-xl border-2 border-orange-200 bg-white text-orange-800 placeholder-orange-300 font-display focus:border-orange-500 focus:outline-none"
                            placeholder="ใส่รหัสผ่านใหม่อีกครั้ง">
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" name="change_password"
                        class="w-full px-6 py-4 rounded-2xl bg-gradient-to-r from-blue-500 to-blue-600 text-white font-display font-bold text-lg hover:from-blue-600 hover:to-blue-700 shadow-lg hover:shadow-xl transition-all">
                        <i class="fas fa-sync-alt mr-2"></i>เปลี่ยนรหัสผ่าน
                    </button>
                </div>

                <!-- Info -->
                <div class="mt-4 p-3 bg-blue-50 rounded-xl text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร
                </div>
            </form>
        </div>

        <script>
            function toggleRedeemCode() {
                const redeem = document.getElementById('redeemCodeCard');
                const btn = document.getElementById('toggleRedeemBtn');

                if (redeem.style.display === 'none') {
                    redeem.style.display = 'block';
                    btn.classList.add('opacity-50');
                    redeem.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    redeem.style.display = 'none';
                    btn.classList.remove('opacity-50');
                }
            }

            function toggleGacha() {
                const gacha = document.getElementById('gachaCard');
                const btn = document.getElementById('toggleGachaBtn');

                if (gacha.style.display === 'none') {
                    gacha.style.display = 'block';
                    btn.classList.add('opacity-50');
                    gacha.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    gacha.style.display = 'none';
                    btn.classList.remove('opacity-50');
                }
            }

            function togglePasswordForm() {
                const form = document.getElementById('passwordFormCard');
                const btn = document.getElementById('togglePasswordBtn');

                if (form.style.display === 'none') {
                    form.style.display = 'block';
                    btn.classList.add('opacity-50');
                    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    form.style.display = 'none';
                    btn.classList.remove('opacity-50');
                }
            }

            function toggleHistory() {
                const history = document.getElementById('historySection');
                const btn = document.getElementById('toggleHistoryBtn');

                if (history.style.display === 'none') {
                    history.style.display = 'block';
                    btn.classList.add('opacity-50');
                    history.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    history.style.display = 'none';
                    btn.classList.remove('opacity-50');
                }
            }

            window.addEventListener('load', function () {
                const message = document.querySelector('.alert');
                if (message && message.textContent.includes('รหัสผ่าน')) {
                    togglePasswordForm();
                }
            });
        </script>

        <!-- History Section (Hidden by default) -->
        <div id="historySection" class="glass-card rounded-3xl p-8 mt-6" style="display: none;">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-purple-100 flex items-center justify-center text-2xl">📜</div>
                    <h3 class="text-xl font-display font-bold text-orange-800">ประวัติการทำรายการ</h3>
                </div>
                <button onclick="toggleHistory()" class="text-gray-400 hover:text-red-500 transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
                <span
                    class="ml-auto text-sm bg-orange-100 text-orange-600 px-3 py-1 rounded-full"><?php echo count($history); ?>
                    รายการล่าสุด</span>
            </div>

            <?php if (empty($history)): ?>
                <div class="text-center py-8 text-orange-400">
                    <i class="fas fa-history text-4xl mb-3"></i>
                    <p class="font-display">ยังไม่มีประวัติการทำรายการ</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr
                                class="text-left text-sm text-orange-500 uppercase tracking-wider border-b border-orange-200">
                                <th class="pb-3 px-3">ประเภท</th>
                                <th class="pb-3 px-3">รายละเอียด</th>
                                <th class="pb-3 px-3">Point</th>
                                <th class="pb-3 px-3">วันที่</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-orange-50">
                            <?php foreach ($history as $h):
                                $type_icon = '🎁';
                                $type_text = 'โค้ด';
                                $type_color = 'bg-green-100 text-green-600';

                                if ($h['type'] == 'gacha') {
                                    $type_icon = '🎰';
                                    $type_text = 'กาชา';
                                    $type_color = 'bg-purple-100 text-purple-600';
                                } elseif ($h['type'] == 'exchange') {
                                    $type_icon = '💸';
                                    $type_text = 'แลกเงิน';
                                    $type_color = 'bg-blue-100 text-blue-600';
                                }

                                $points_display = $h['points'];
                                $points_color = $h['points'] >= 0 ? 'text-green-600' : 'text-red-500';
                                $points_prefix = $h['points'] >= 0 ? '+' : '';
                                ?>
                                <tr class="hover:bg-orange-50 transition-colors">
                                    <td class="py-3 px-3">
                                        <span class="<?php echo $type_color; ?> px-3 py-1 rounded-full text-xs font-semibold">
                                            <?php echo $type_icon . ' ' . $type_text; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-orange-700 text-sm">
                                        <?php echo htmlspecialchars($h['code']); ?>
                                    </td>
                                    <td class="py-3 px-3 font-display font-bold <?php echo $points_color; ?>">
                                        <?php echo $points_prefix . number_format($h['points']); ?>
                                    </td>
                                    <td class="py-3 px-3 text-orange-400 text-sm">
                                        <?php echo date('d/m H:i', strtotime($h['redeemed_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-orange-400 text-sm">
            <p>🍣 มารุซูชิ - Point Rewards Center</p>
        </div>
    </div>

</body>

</html>