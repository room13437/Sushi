<?php
// จัดการอัตราการแลกซูชิ - Admin Only
require_once 'protect_admin.php';
include "db.php";

$message = "";

// เพิ่ม Tier ใหม่
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_tier'])) {
    $points = (int) $_POST['points'];
    $pieces = (int) $_POST['pieces'];

    if ($points <= 0 || $pieces <= 0) {
        $message = "<div class='alert alert-error'>❌ กรุณากรอกจำนวนให้ถูกต้อง</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO sushi_redemption_tiers (points, pieces) VALUES (?, ?) ON DUPLICATE KEY UPDATE pieces = ?");
        $stmt->bind_param("iii", $points, $pieces, $pieces);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>✅ บันทึกข้อมูลสำเร็จ! ({$points} Point = {$pieces} ชิ้น)</div>";
        } else {
            $message = "<div class='alert alert-error'>❌ เกิดข้อผิดพลาด: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}

// ลบ Tier
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_tier'])) {
    $delete_id = (int) $_POST['delete_tier'];
    $stmt = $conn->prepare("DELETE FROM sushi_redemption_tiers WHERE id = ?");
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>✅ ลบรายการสำเร็จ!</div>";
    }
    $stmt->close();
}

// ดึงรายการทั้งหมด
$tiers = [];
$result = $conn->query("SELECT * FROM sushi_redemption_tiers ORDER BY points ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tiers[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🍣 จัดการอัตราการแลกซูชิ | Admin</title>

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
            transform: translateY(-2px);
        }

        .alert-success {
            background: #D1FAE5;
            border: 1px solid #A7F3D0;
            color: #065F46;
            padding: 1rem;
            border-radius: 1rem;
            margin-bottom: 1rem;
        }

        .alert-error {
            background: #FEE2E2;
            border: 1px solid #FECACA;
            color: #B91C1C;
            padding: 1rem;
            border-radius: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body class="p-4 md:p-8">

    <div class="max-w-4xl mx-auto">

        <!-- Header -->
        <div class="glass-card rounded-3xl p-8 mb-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h1
                        class="text-3xl font-display font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-orange-700">
                        🍣 จัดการอัตราการแลกซูชิ
                    </h1>
                    <p class="text-orange-600 mt-2">ตั้งค่า Point ที่ต้องใช้แลกซูชิแต่ละจำนวน</p>
                </div>
                <div class="flex gap-3">
                    <a href="formmenu"
                        class="px-5 py-3 rounded-xl bg-orange-100 text-orange-600 font-display font-bold hover:bg-orange-200 transition-all">
                        <i class="fas fa-arrow-left mr-2"></i> กลับ
                    </a>
                </div>
            </div>
        </div>

        <!-- Alert -->
        <?php if (!empty($message)): ?>
            <div class="mb-6 font-display font-semibold">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div class="glass-card rounded-3xl p-8 mb-6">
            <h2 class="text-xl font-display font-bold text-orange-800 mb-6 flex items-center gap-3">
                <i class="fas fa-plus-circle text-orange-500"></i> เพิ่ม/แก้ไข อัตราการแลก
            </h2>

            <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-orange-700 font-display font-semibold mb-2">จำนวน Point ที่ใช้</label>
                    <input type="number" name="points" placeholder="เช่น 100" required min="1"
                        class="w-full px-5 py-4 rounded-2xl border-2 border-orange-200 bg-white text-orange-800 placeholder-orange-300 font-display focus:border-orange-500 focus:outline-none">
                    <p class="text-xs text-orange-400 mt-1">หากใส่ Point ซ้ำจะเป็นการอัปเดตจำนวนชิ้น</p>
                </div>
                <div>
                    <label class="block text-orange-700 font-display font-semibold mb-2">จำนวนซูชิที่ได้รับ</label>
                    <input type="number" name="pieces" placeholder="เช่น 1" required min="1"
                        class="w-full px-5 py-4 rounded-2xl border-2 border-orange-200 bg-white text-orange-800 placeholder-orange-300 font-display focus:border-orange-500 focus:outline-none">
                </div>
                <div class="flex items-end">
                    <button type="submit" name="add_tier" value="1"
                        class="w-full py-4 rounded-2xl btn-gradient text-white font-display font-bold">
                        <i class="fas fa-save mr-2"></i> บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>

        <!-- Tier List -->
        <div class="glass-card rounded-3xl p-8">
            <h2 class="text-xl font-display font-bold text-orange-800 mb-6 flex items-center gap-3">
                <i class="fas fa-list text-orange-500"></i> รายการอัตราการแลกปัจจุบัน
                <span class="text-sm bg-orange-100 text-orange-600 px-3 py-1 rounded-full"><?php echo count($tiers); ?>
                    รายการ</span>
            </h2>

            <?php if (empty($tiers)): ?>
                <div class="text-center py-12 text-orange-400">
                    <i class="fas fa-inbox text-5xl mb-4"></i>
                    <p class="font-display text-lg">ยังไม่มีข้อมูล - เพิ่มข้อมูลแรกของคุณด้านบน!</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr
                                class="text-left text-sm text-orange-500 uppercase tracking-wider border-b border-orange-200">
                                <th class="pb-4 px-4">Point ที่ใช้</th>
                                <th class="pb-4 px-4">ซูชิที่ได้รับ</th>
                                <th class="pb-4 px-4 text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-orange-100">
                            <?php foreach ($tiers as $tier): ?>
                                <tr class="hover:bg-orange-50 transition-colors">
                                    <td class="py-4 px-4">
                                        <span
                                            class="font-display font-bold text-orange-700"><?php echo number_format($tier['points']); ?>
                                            Point</span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="font-display font-bold text-pink-600">🍣 <?php echo $tier['pieces']; ?>
                                            ชิ้น</span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <form method="POST" style="display: inline;"
                                            onsubmit="return confirm('ยืนยันการลบรายการนี้?')">
                                            <input type="hidden" name="delete_tier" value="<?php echo $tier['id']; ?>">
                                            <button type="submit"
                                                class="px-4 py-2 rounded-xl bg-red-100 text-red-500 font-semibold hover:bg-red-500 hover:text-white transition-all text-sm">
                                                <i class="fas fa-trash mr-1"></i> ลบ
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>

</html>