<?php
// จัดการโค้ดแลก Point - Admin Only
require_once 'protect_admin.php'; // ป้องกันด้วย MySQL Authentication
include "db.php";

$message = "";

// === DATABASE MIGRATIONS ===
// Create code_redemptions table for tracking which users redeemed which codes
$create_redemptions_table = "CREATE TABLE IF NOT EXISTS `code_redemptions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `redeemed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_code` (`code_id`, `user_id`),
    KEY `code_id` (`code_id`),
    KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$conn->query($create_redemptions_table);

// Create redeem_codes table with max_uses column
$create_table = "CREATE TABLE IF NOT EXISTS `redeem_codes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `points` INT(11) NOT NULL DEFAULT 10,
    `max_uses` INT(11) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$conn->query($create_table);

// Add max_uses column if it doesn't exist (for existing databases)
$conn->query("ALTER TABLE `redeem_codes` ADD COLUMN `max_uses` INT(11) NOT NULL DEFAULT 1 AFTER `points`");

// เพิ่มโค้ดใหม่
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_code'])) {
    $new_code = strtoupper(trim($_POST['code']));
    $new_points = (int) $_POST['points'];
    $max_uses = isset($_POST['max_uses']) ? (int) $_POST['max_uses'] : 1;

    if (empty($new_code) || $new_points <= 0 || $max_uses <= 0) {
        $message = "<div class='alert alert-error'>❌ กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO redeem_codes (code, points, max_uses) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $new_code, $new_points, $max_uses);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>✅ เพิ่มโค้ด <strong>{$new_code}</strong> ({$new_points} Point, ใช้ได้ {$max_uses} ครั้ง) สำเร็จ!</div>";
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

// ลบโค้ด (เปลี่ยนเป็น POST เพื่อป้องกัน redirect loop)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_code'])) {
    $delete_id = (int) $_POST['delete_code'];

    // ลบการ redemption ที่เกี่ยวข้องก่อน
    $stmt_redeem = $conn->prepare("DELETE FROM code_redemptions WHERE code_id = ?");
    $stmt_redeem->bind_param("i", $delete_id);
    $stmt_redeem->execute();
    $stmt_redeem->close();

    // ลบโค้ด
    $stmt = $conn->prepare("DELETE FROM redeem_codes WHERE id = ?");
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>✅ ลบโค้ดสำเร็จ!</div>";
    }
    $stmt->close();
}

// ดึงรายการโค้ดทั้งหมดพร้อมสถิติการใช้งาน
$codes = [];
$sql = "SELECT r.*, 
        (SELECT COUNT(*) FROM code_redemptions WHERE code_id = r.id) as redemption_count
        FROM redeem_codes r 
        ORDER BY r.created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $codes[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎁 จัดการโค้ดแลก Point | Admin</title>

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

    <!-- Anti-Inspect Protection -->
    <script>
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('keydown', function (e) {
            if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J' || e.key === 'C')) || (e.ctrlKey && e.key === 'u')) {
                e.preventDefault();
                return false;
            }
        });
        setInterval(() => {
            if (window.outerWidth - window.innerWidth > 160 || window.outerHeight - window.innerHeight > 160) {
                document.body.innerHTML = '<div style="display:flex;justify-content:center;align-items:center;height:100vh;font-size:24px;color:#f44336;">⚠️ กรุณาปิด Developer Tools</div>';
            }
        }, 1000);
        document.addEventListener('selectstart', e => e.preventDefault());
    </script>

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
        function searchCodes() {
            const searchInput = document.getElementById('searchInput').value.toUpperCase();
            const table = document.getElementById('codesTable');
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
            document.getElementById('codeCounter').textContent = visibleCount + ' รายการ';

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
                        class="text-3xl font-display font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-orange-700">
                        🎁 จัดการโค้ดแลก Point
                    </h1>
                    <p class="text-orange-600 mt-2">สร้างและจัดการโค้ดสำหรับลูกค้าแลก Point</p>
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
            <div class="mb-6 p-5 rounded-2xl font-display font-semibold">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add New Code Form -->
        <div class="glass-card rounded-3xl p-8 mb-6">
            <h2 class="text-xl font-display font-bold text-orange-800 mb-6 flex items-center gap-3">
                <i class="fas fa-plus-circle text-orange-500"></i> เพิ่มโค้ดใหม่
            </h2>

            <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-orange-700 font-display font-semibold mb-2">รหัสโค้ด</label>
                    <input type="text" name="code" placeholder="เช่น WELCOME50" required maxlength="50"
                        class="w-full px-5 py-4 rounded-2xl border-2 border-orange-200 bg-white text-orange-800 placeholder-orange-300 font-display uppercase focus:border-orange-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-orange-700 font-display font-semibold mb-2">จำนวน Point</label>
                    <input type="number" name="points" placeholder="50" required min="1" max="10000"
                        class="w-full px-5 py-4 rounded-2xl border-2 border-orange-200 bg-white text-orange-800 placeholder-orange-300 font-display focus:border-orange-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-orange-700 font-display font-semibold mb-2">ใช้ได้กี่ครั้ง</label>
                    <input type="number" name="max_uses" placeholder="1" value="1" required min="1" max="999"
                        class="w-full px-5 py-4 rounded-2xl border-2 border-orange-200 bg-white text-orange-800 placeholder-orange-300 font-display focus:border-orange-500 focus:outline-none">
                </div>
                <div class="flex items-end">
                    <button type="submit" name="add_code" value="1"
                        class="w-full py-4 rounded-2xl btn-gradient text-white font-display font-bold">
                        <i class="fas fa-plus mr-2"></i> เพิ่มโค้ด
                    </button>
                </div>
            </form>
        </div>

        <!-- Codes List -->
        <div class="glass-card rounded-3xl p-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <h2 class="text-xl font-display font-bold text-orange-800 flex items-center gap-3">
                    <i class="fas fa-list text-orange-500"></i> รายการโค้ดทั้งหมด
                    <span id="codeCounter"
                        class="text-sm bg-orange-100 text-orange-600 px-3 py-1 rounded-full"><?php echo count($codes); ?>
                        รายการ</span>
                </h2>

                <?php if (!empty($codes)): ?>
                    <div class="relative w-full md:w-64">
                        <input type="text" id="searchInput" onkeyup="searchCodes()" placeholder="🔍 ค้นหาโค้ด..."
                            class="w-full px-5 py-3 rounded-2xl border-2 border-orange-200 bg-white text-orange-800 placeholder-orange-300 font-display focus:border-orange-500 focus:outline-none">
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($codes)): ?>
                <div class="text-center py-12 text-orange-400">
                    <i class="fas fa-inbox text-5xl mb-4"></i>
                    <p class="font-display text-lg">ยังไม่มีโค้ด - เพิ่มโค้ดแรกของคุณด้านบน!</p>
                </div>
            <?php else: ?>
                <!-- No results message (hidden by default) -->
                <div id="noResults" class="text-center py-8 text-orange-400" style="display: none;">
                    <i class="fas fa-search text-4xl mb-3"></i>
                    <p class="font-display text-lg">ไม่พบโค้ดที่ตรงกับการค้นหา</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full" id="codesTable">
                        <thead>
                            <tr
                                class="text-left text-sm text-orange-500 uppercase tracking-wider border-b border-orange-200">
                                <th class="pb-4 px-4">โค้ด</th>
                                <th class="pb-4 px-4">Point</th>
                                <th class="pb-4 px-4">การใช้งาน</th>
                                <th class="pb-4 px-4">วันที่สร้าง</th>
                                <th class="pb-4 px-4 text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-orange-100" id="codesTableBody">
                            <?php foreach ($codes as $code): ?>
                                <?php
                                $used = (int) $code['redemption_count'];
                                $max = (int) $code['max_uses'];
                                $percentage = $max > 0 ? ($used / $max) * 100 : 0;

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
                                <tr class="hover:bg-orange-50 transition-colors">
                                    <td class="py-4 px-4">
                                        <span class="font-mono font-bold text-orange-700 bg-orange-100 px-3 py-1 rounded-lg">
                                            <?php echo htmlspecialchars($code['code']); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span
                                            class="font-display font-bold text-green-600">+<?php echo number_format($code['points']); ?>
                                            Point</span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span
                                            class="font-display font-bold <?php echo $usage_color; ?> px-3 py-1 rounded-full text-sm">
                                            <?php echo $used; ?> / <?php echo $max; ?> ครั้ง
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-orange-500 text-sm">
                                        <?php echo date('d/m/Y H:i', strtotime($code['created_at'])); ?>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <form method="POST" style="display: inline;"
                                            onsubmit="return confirm('ยืนยันการลบโค้ดนี้? (จะลบประวัติการใช้งานด้วย)')">
                                            <input type="hidden" name="delete_code" value="<?php echo $code['id']; ?>">
                                            <button type="submit"
                                                class="px-4 py-2 rounded-xl bg-red-100 text-red-500 font-semibold hover:bg-red-500 hover:text-white transition-all text-sm cursor-pointer border-0">
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

        <!-- Footer -->
        <div class="text-center mt-8 text-orange-400 text-sm">
            <p>🍣 ซูชิละกัน - Admin Panel</p>
        </div>

    </div>

</body>

</html>