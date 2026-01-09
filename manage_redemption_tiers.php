<?php
// manage_redemption_tiers.php - จัดการอัตราแลกเปลี่ยนซูชิ
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

// Add Tier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tier'])) {
    $points = (int) $_POST['points'];
    $pieces = (int) $_POST['pieces'];

    if ($points > 0 && $pieces > 0) {
        // Check duplicate points
        $check = $conn->prepare("SELECT id FROM sushi_redemption_tiers WHERE points = ?");
        $check->bind_param("i", $points);
        $check->execute();
        if ($check->fetch()) {
            $error = "มีจำนวนแต้ม $points ในระบบแล้ว กรุณาลบอันเก่าก่อนหากต้องการแก้ไข";
        } else {
            $stmt = $conn->prepare("INSERT INTO sushi_redemption_tiers (points, pieces) VALUES (?, ?)");
            $stmt->bind_param("ii", $points, $pieces);
            if ($stmt->execute()) {
                $message = "เพิ่มอัตราแลกเปลี่ยนสำเร็จ: $points แต้ม = $pieces ชิ้น";
            } else {
                $error = "เกิดข้อผิดพลาด: " . $conn->error;
            }
        }
    } else {
        $error = "กรุณากรอกข้อมูลให้ถูกต้อง";
    }
}

// Delete Tier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_tier'])) {
    $del_id = (int) $_POST['delete_tier'];
    if ($conn->query("DELETE FROM sushi_redemption_tiers WHERE id = $del_id")) {
        $message = "ลบรายการสำเร็จ";
    } else {
        $error = "เกิดข้อผิดพลาดในการลบ: " . $conn->error;
    }
}

// Fetch Tiers
$tiers = $conn->query("SELECT * FROM sushi_redemption_tiers ORDER BY points ASC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="icon/icons.png?v=4">
    <title>ตั้งค่าการแลกซูชิ | Admin System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; background: linear-gradient(-45deg, #fffbf0, #ffe0b2, #ffccbc, #fffbf0); background-size: 400% 400%; animation: gradientBG 15s ease infinite; min-height: 100vh; padding: 20px; }
        @keyframes gradientBG { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        .glass-box { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px); border-radius: 25px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); padding: 30px; }
        h2, h3 { font-family: 'Prompt', sans-serif; }
    </style>
</head>
<body>
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <a href="formmenu" class="bg-white/80 p-3 rounded-xl hover:bg-white transition-all shadow-sm"><i class="fas fa-arrow-left text-gray-600"></i></a>
            <h2 class="text-3xl font-bold text-orange-700 mx-auto">⚖️ ตั้งค่าการแลกซูชิ</h2>
            <div class="w-10"></div>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg shadow-sm animate-bounce"><i class="fas fa-check-circle mr-2"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg shadow-sm"><i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Form -->
            <div class="md:col-span-1">
                <div class="glass-box h-full">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 text-center">เพิ่มอัตราใหม่</h3>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-600 mb-1">ใช้แต้ม (Points)</label>
                            <input type="number" name="points" placeholder="เช่น 50" class="w-full p-3 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-orange-500" required>
                        </div>
                        <div class="flex justify-center text-gray-400"><i class="fas fa-arrow-down"></i></div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-600 mb-1">แลกได้ (ชิ้น)</label>
                            <input type="number" name="pieces" placeholder="เช่น 5" class="w-full p-3 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-orange-500" required>
                        </div>
                        <button type="submit" name="add_tier" class="w-full py-3 rounded-xl font-bold font-display shadow-lg bg-gradient-to-r from-orange-500 to-red-500 text-white hover:translate-y-1 transition-all">✨ เพิ่มรายการ</button>
                    </form>
                </div>
            </div>

            <!-- List -->
            <div class="md:col-span-2">
                <div class="glass-box">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">รายการปัจจุบัน</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="text-gray-500 border-b border-gray-200">
                                <tr>
                                    <th class="pb-3 text-center">แต้ม</th>
                                    <th class="pb-3 text-center"></th>
                                    <th class="pb-3 text-center">จำนวนชิ้น</th>
                                    <th class="pb-3 text-right">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if ($tiers && $tiers->num_rows > 0): ?>
                                    <?php while ($row = $tiers->fetch_assoc()): ?>
                                        <tr class="hover:bg-orange-50/50 transition-colors">
                                            <td class="py-4 text-center font-bold text-orange-700 text-lg"><?php echo number_format($row['points']); ?> P</td>
                                            <td class="py-4 text-center text-gray-400"><i class="fas fa-exchange-alt"></i></td>
                                            <td class="py-4 text-center font-bold text-gray-700 text-lg"><?php echo number_format($row['pieces']); ?> 🍣</td>
                                            <td class="py-4 text-right">
                                                <form method="POST" onsubmit="return confirm('ยืนยันลบรายการนี้?');">
                                                    <button type="submit" name="delete_tier" value="<?php echo $row['id']; ?>" class="text-red-400 hover:text-red-600 transition-colors bg-red-50 p-2 rounded-lg hover:bg-red-100"><i class="fas fa-trash-alt"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="py-8 text-center text-gray-400">ยังไม่มีการตั้งค่า</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
