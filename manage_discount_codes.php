<?php
// จัดการโค้ดส่วนลด - Admin Only
require_once 'protect_admin.php';
include "db.php";

$message = "";

// === DATABASE MIGRATIONS ===
// Create discount_codes table
$create_discount_codes = "CREATE TABLE IF NOT EXISTS `discount_codes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `discount_percent` INT(11) NOT NULL,
    `max_uses` INT(11) NOT NULL DEFAULT 1,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$conn->query($create_discount_codes);

// Create discount_code_usage table
$create_usage_table = "CREATE TABLE IF NOT EXISTS `discount_code_usage` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `points_saved` INT(11) NOT NULL,
    `used_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_code` (`code_id`, `user_id`),
    KEY `code_id` (`code_id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$conn->query($create_usage_table);

// เพิ่มโค้ดส่วนลดใหม่
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_discount'])) {
    $new_code = strtoupper(trim($_POST['code']));
    $discount_percent = (int) $_POST['discount_percent'];
    $max_uses = isset($_POST['max_uses']) ? (int) $_POST['max_uses'] : 1;

    if (empty($new_code) || $discount_percent <= 0 || $discount_percent > 100 || $max_uses <= 0) {
        $message = "<div class='alert alert-error'>❌ กรุณากรอกข้อมูลให้ถูกต้อง (ส่วนลด 1-100%)</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO discount_codes (code, discount_percent, max_uses) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $new_code, $discount_percent, $max_uses);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>✅ เพิ่มโค้ดส่วนลด <strong>{$new_code}</strong> (-{$discount_percent}%, ใช้ได้ {$max_uses} ครั้ง) สำเร็จ!</div>";
        } else {
            if ($conn->errno == 1062) {
                $message = "<div class='alert alert-error'>❌ โค้ดนี้มีอยู่แล้ว</div>";
            } else {
                $message = "<div class='alert alert-error'>❌ เกิดข้อผิดพลาด: " . $stmt->error . "</div>";
            }
        }
        $stmt->close();
    }
}

// ลบโค้ดส่วนลด
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_discount'])) {
    $delete_id = (int) $_POST['delete_discount'];

    // ลบการใช้งานที่เกี่ยวข้องก่อน
    $stmt_usage = $conn->prepare("DELETE FROM discount_code_usage WHERE code_id = ?");
    $stmt_usage->bind_param("i", $delete_id);
    $stmt_usage->execute();
    $stmt_usage->close();

    // ลบโค้ด
    $stmt = $conn->prepare("DELETE FROM discount_codes WHERE id = ?");
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>✅ ลบโค้ดส่วนลดสำเร็จ!</div>";
    }
    $stmt->close();
}

// สลับสถานะเปิด/ปิดโค้ด
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_active'])) {
    $toggle_id = (int) $_POST['toggle_active'];

    $stmt = $conn->prepare("UPDATE discount_codes SET active = NOT active WHERE id = ?");
    $stmt->bind_param("i", $toggle_id);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>✅ เปลี่ยนสถานะโค้ดสำเร็จ!</div>";
    }
    $stmt->close();
}

// ดึงรายการโค้ดทั้งหมดพร้อมสถิติการใช้งาน
$discounts = [];
$sql = "SELECT d.*, 
        (SELECT COUNT(*) FROM discount_code_usage WHERE code_id = d.id) as usage_count
        FROM discount_codes d 
        ORDER BY d.created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $discounts[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏷️ จัดการโค้ดส่วนลด | Admin</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'cyan': {
                            50: '#ECFEFF', 100: '#CFFAFE', 200: '#A5F3FC', 300: '#67E8F9',
                            400: '#22D3EE', 500: '#06B6D4', 600: '#0891B2', 700: '#0E7490',
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
            background: linear-gradient(135deg, #ECFEFF 0%, #CFFAFE 30%, #A5F3FC 60%, #67E8F9 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(6, 182, 212, 0.15);
            box-shadow: 0 20px 40px rgba(6, 182, 212, 0.1);
        }

        .btn-gradient {
            background: linear-gradient(135deg, #06B6D4, #0891B2);
            box-shadow: 0 10px 25px rgba(6, 182, 212, 0.35);
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            background: linear-gradient(135deg, #0891B2, #0E7490);
            transform: translateY(-2px);
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
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="js/three_bg.js"></script>

    <script>
        // Search functionality
        function searchDiscounts() {
            const searchInput = document.getElementById('searchInput').value.toUpperCase();
            const table = document.getElementById('discountsTable');
            const rows = table.getElementsByTagName('tr');

            let visibleCount = 0;

            for (let i = 0; i < rows.length; i++) {
                const codeCell = rows[i].getElementsByTagName('td')[0];
                if (codeCell) {
                    const codeText = codeCell.textContent || codeCell.innerText;
                    if (codeText.toUpperCase().indexOf(searchInput) > -1) {
                        rows[i].style.display = '';
                        visibleCount++;
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }

            // Update counter
            document.getElementById('discountCounter').textContent = visibleCount + ' รายการ';

            // Show/hide no results message
            const noResults = document.getElementById('noResults');
            if (visibleCount === 0 && searchInput !== '') {
                noResults.style.display = 'block';
            } else {
                noResults.style.display = 'none';
            }
        }
    </script>
</head>

<body class="p-4 md:p-8">

    <div class="max-w-4xl mx-auto">

        <!-- Header -->
        <div class="glass-card rounded-3xl p-8 mb-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h1
                        class="text-3xl font-display font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-cyan-500 to-cyan-700">
                        🏷️ จัดการโค้ดส่วนลด
                    </h1>
                    <p class="text-cyan-600 mt-2">สร้างและจัดการโค้ดส่วนลดสำหรับลดพอยท์</p>
                </div>
                <div class="flex gap-3">
                    <a href="formmenu"
                        class="px-5 py-3 rounded-xl bg-cyan-100 text-cyan-600 font-display font-bold hover:bg-cyan-200 transition-all">
                        <i class="fas fa-arrow-left mr-2"></i> กลับ
                    </a>
                </div>
            </div>
        </div>

        <!-- Alert -->
        <?php if (!empty($message)): ?>
            <div class="mb-6 p-5 rounded-2xl font-display font-semibold">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add New Discount Code Form -->
        <div class="glass-card rounded-3xl p-8 mb-6">
            <h2 class="text-xl font-display font-bold text-cyan-800 mb-6 flex items-center gap-3">
                <i class="fas fa-plus-circle text-cyan-500"></i> เพิ่มโค้ดส่วนลดใหม่
            </h2>

            <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-cyan-700 font-display font-semibold mb-2">รหัสโค้ด</label>
                    <input type="text" name="code" placeholder="เช่น SAVE10" required maxlength="50"
                        class="w-full px-5 py-4 rounded-2xl border-2 border-cyan-200 bg-white text-cyan-800 placeholder-cyan-300 font-display uppercase focus:border-cyan-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-cyan-700 font-display font-semibold mb-2">ส่วนลด (%)</label>
                    <input type="number" name="discount_percent" placeholder="10" required min="1" max="100"
                        class="w-full px-5 py-4 rounded-2xl border-2 border-cyan-200 bg-white text-cyan-800 placeholder-cyan-300 font-display focus:border-cyan-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-cyan-700 font-display font-semibold mb-2">ใช้ได้กี่ครั้ง</label>
                    <input type="number" name="max_uses" placeholder="1" value="1" required min="1" max="999"
                        class="w-full px-5 py-4 rounded-2xl border-2 border-cyan-200 bg-white text-cyan-800 placeholder-cyan-300 font-display focus:border-cyan-500 focus:outline-none">
                </div>
                <div class="flex items-end">
                    <button type="submit" name="add_discount" value="1"
                        class="w-full py-4 rounded-2xl btn-gradient text-white font-display font-bold">
                        <i class="fas fa-plus mr-2"></i> เพิ่มโค้ด
                    </button>
                </div>
            </form>
        </div>

        <!-- Discount Codes List -->
        <div class="glass-card rounded-3xl p-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <h2 class="text-xl font-display font-bold text-cyan-800 flex items-center gap-3">
                    <i class="fas fa-list text-cyan-500"></i> รายการโค้ดส่วนลดทั้งหมด
                    <span id="discountCounter"
                        class="text-sm bg-cyan-100 text-cyan-600 px-3 py-1 rounded-full"><?php echo count($discounts); ?>
                        รายการ</span>
                </h2>

                <?php if (!empty($discounts)): ?>
                    <div class="relative w-full md:w-64">
                        <input type="text" id="searchInput" onkeyup="searchDiscounts()" placeholder="🔍 ค้นหาโค้ด..."
                            class="w-full px-5 py-3 rounded-2xl border-2 border-cyan-200 bg-white text-cyan-800 placeholder-cyan-300 font-display focus:border-cyan-500 focus:outline-none">
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($discounts)): ?>
                <div class="text-center py-12 text-cyan-400">
                    <i class="fas fa-inbox text-5xl mb-4"></i>
                    <p class="font-display text-lg">ยังไม่มีโค้ดส่วนลด - เพิ่มโค้ดแรกของคุณด้านบน!</p>
                </div>
            <?php else: ?>
                <!-- No results message (hidden by default) -->
                <div id="noResults" class="text-center py-8 text-cyan-400" style="display: none;">
                    <i class="fas fa-search text-4xl mb-3"></i>
                    <p class="font-display text-lg">ไม่พบโค้ดที่ตรงกับการค้นหา</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full" id="discountsTable">
                        <thead>
                            <tr class="text-left text-sm text-cyan-500 uppercase tracking-wider border-b border-cyan-200">
                                <th class="pb-4 px-4">โค้ด</th>
                                <th class="pb-4 px-4">ส่วนลด</th>
                                <th class="pb-4 px-4">การใช้งาน</th>
                                <th class="pb-4 px-4">สถานะ</th>
                                <th class="pb-4 px-4">วันที่สร้าง</th>
                                <th class="pb-4 px-4 text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cyan-100" id="discountsTableBody">
                            <?php foreach ($discounts as $discount): ?>
                                <?php
                                $used = (int) $discount['usage_count'];
                                $max = (int) $discount['max_uses'];
                                $percentage = $max > 0 ? ($used / $max) * 100 : 0;
                                $is_active = (int) $discount['active'] == 1;

                                // Color coding based on usage
                                if ($used >= $max) {
                                    $usage_color = 'text-red-600 bg-red-100';
                                } elseif ($percentage >= 75) {
                                    $usage_color = 'text-orange-600 bg-orange-100';
                                } elseif ($percentage >= 50) {
                                    $usage_color = 'text-yellow-600 bg-yellow-100';
                                } else {
                                    $usage_color = 'text-green-600 bg-green-100';
                                }
                                ?>
                                <tr class="hover:bg-cyan-50 transition-colors">
                                    <td class="py-4 px-4">
                                        <span class="font-mono font-bold text-cyan-700 bg-cyan-100 px-3 py-1 rounded-lg">
                                            <?php echo htmlspecialchars($discount['code']); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span
                                            class="font-display font-bold text-green-600">-<?php echo $discount['discount_percent']; ?>%</span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span
                                            class="font-display font-bold <?php echo $usage_color; ?> px-3 py-1 rounded-full text-sm">
                                            <?php echo $used; ?> / <?php echo $max; ?> ครั้ง
                                        </span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <?php if ($is_active): ?>
                                            <span class="px-3 py-1 rounded-full bg-green-100 text-green-600 text-sm font-semibold">
                                                ✅ เปิดใช้งาน
                                            </span>
                                        <?php else: ?>
                                            <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-500 text-sm font-semibold">
                                                ❌ ปิดใช้งาน
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4 text-cyan-500 text-sm">
                                        <?php echo date('d/m/Y H:i', strtotime($discount['created_at'])); ?>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <div class="flex gap-2 justify-center">
                                            <!-- Toggle Active -->
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="toggle_active"
                                                    value="<?php echo $discount['id']; ?>">
                                                <button type="submit"
                                                    class="px-3 py-2 rounded-xl <?php echo $is_active ? 'bg-gray-100 text-gray-500' : 'bg-green-100 text-green-600'; ?> font-semibold hover:opacity-80 transition-all text-sm cursor-pointer border-0">
                                                    <i class="fas fa-<?php echo $is_active ? 'ban' : 'check'; ?> mr-1"></i>
                                                    <?php echo $is_active ? 'ปิด' : 'เปิด'; ?>
                                                </button>
                                            </form>
                                            <!-- Delete -->
                                            <form method="POST" style="display: inline;"
                                                onsubmit="return confirm('ยืนยันการลบโค้ดนี้? (จะลบประวัติการใช้งานด้วย)')">
                                                <input type="hidden" name="delete_discount"
                                                    value="<?php echo $discount['id']; ?>">
                                                <button type="submit"
                                                    class="px-3 py-2 rounded-xl bg-red-100 text-red-500 font-semibold hover:bg-red-500 hover:text-white transition-all text-sm cursor-pointer border-0">
                                                    <i class="fas fa-trash mr-1"></i> ลบ
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-cyan-400 text-sm">
            <p>🍣 ซูชิละกัน - Admin Panel</p>
        </div>

    </div>

</body>

</html>