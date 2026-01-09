<?php
// manage_points.php - จัดการระบบแต้มและโค้ด
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

// Check Login
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
if (!$isLoggedIn) {
    header('Location: admin_login');
    exit;
}

$message = '';
$error = '';

// Determine State
$tab_gen = isset($_REQUEST['tab_gen']) ? $_REQUEST['tab_gen'] : 'random'; // POST (create) or GET (maintain?)
$tab_list = isset($_REQUEST['tab_list']) ? $_REQUEST['tab_list'] : 'manual'; // POST (delete) or GET (search)
$search = isset($_REQUEST['search']) ? trim($_REQUEST['search']) : '';

// Delete Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_code'])) {
    $del_id = (int) $_POST['delete_code'];
    if ($conn->query("DELETE FROM point_codes WHERE id = $del_id")) {
        $message = "ลบโค้ดสำเร็จ";
    } else {
        $error = "เกิดข้อผิดพลาดในการลบ: " . $conn->error;
    }
}

// Delete All Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all_codes'])) {
    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM code_redemptions");
        $conn->query("DELETE FROM point_codes");
        $conn->commit();
        $message = "ล้างโค้ดทั้งหมดเรียบร้อยแล้ว (รวมถึงประวัติการใช้โค้ด)";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// Manual Creation Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_custom_code'])) {
    $custom_code = strtoupper(trim($_POST['custom_code']));
    $points = (int) $_POST['points_value'];
    $max_uses = (int) $_POST['max_uses'];

    if (!empty($custom_code) && $points > 0 && $max_uses > 0) {
        $check = $conn->prepare("SELECT id FROM point_codes WHERE code = ?");
        $check->bind_param("s", $custom_code);
        $check->execute();
        if ($check->fetch()) {
            $error = "โค้ด '$custom_code' มีอยู่แล้ว กรุณาใช้ชื่ออื่น";
            $check->close();
        } else {
            $check->close();
            $stmt = $conn->prepare("INSERT INTO point_codes (code, points_value, max_uses, type) VALUES (?, ?, ?, 'manual')");
            $stmt->bind_param("sii", $custom_code, $points, $max_uses);
            if ($stmt->execute()) {
                $message = "สร้างโค้ด '$custom_code' สำเร็จ";
            } else {
                $error = "เกิดข้อผิดพลาด: " . $conn->error;
            }
            $stmt->close();
        }
    } else {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}

// Generator Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_codes'])) {
    $points = (int) $_POST['points_value'];
    $qty = (int) $_POST['quantity'];

    if ($points > 0 && $qty > 0) {
        $success_count = 0;
        $new_codes = [];
        $stmt = $conn->prepare("INSERT INTO point_codes (code, points_value, max_uses, type) VALUES (?, ?, 1, 'random')"); 

        for ($i = 0; $i < $qty; $i++) {
            $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            $stmt->bind_param("si", $code, $points);
            if ($stmt->execute()) {
                $success_count++;
                $new_codes[] = $code;
            }
        }
        $stmt->close();
        $message = "สร้างโค้ดสุ่มสำเร็จ $success_count โค้ด";
        if (!empty($new_codes)) {
            $message .= "<br><div class='mt-2 p-2 bg-white/50 rounded font-mono text-xs break-all text-gray-800 border border-white/20'>" . implode(', ', $new_codes) . "</div>";
        }
        $tab_list = 'random'; // Switch to random list view
    } else {
        $error = "กรุณาระบุจำนวนแต้มและจำนวนโค้ดให้ถูกต้อง";
    }
}

// Fetch Stats
$total_codes_res = $conn->query("SELECT COUNT(*) as count FROM point_codes");
$total_codes = $total_codes_res ? $total_codes_res->fetch_assoc()['count'] : 0;

$used_codes_res = $conn->query("SELECT COUNT(*) as count FROM point_codes WHERE is_used = 1 OR (max_uses > 1 AND (SELECT COUNT(*) FROM code_redemptions WHERE code_id = point_codes.id) >= max_uses)");
$used_codes = $used_codes_res ? $used_codes_res->fetch_assoc()['count'] : 0;

$unused_codes = $total_codes - $used_codes;

// Fetch Codes (Apply Search)
$sql_manual = "SELECT *, (SELECT COUNT(*) FROM code_redemptions WHERE code_id = point_codes.id) as used_count FROM point_codes WHERE type='manual'";
$sql_random = "SELECT *, (SELECT COUNT(*) FROM code_redemptions WHERE code_id = point_codes.id) as used_count FROM point_codes WHERE type='random'";

if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $sql_manual .= " AND code LIKE '%$search_safe%'";
    $sql_random .= " AND code LIKE '%$search_safe%'";
}

$sql_manual .= " ORDER BY created_at DESC LIMIT 50"; // Increase limit for search results
$sql_random .= " ORDER BY created_at DESC LIMIT 50";

$manual_codes = $conn->query($sql_manual);
$random_codes = $conn->query($sql_random);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="icon/icons.png?v=4">
    <title>จัดการแต้มสะสม | Admin Point System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; background: linear-gradient(-45deg, #fffbf0, #ffe0b2, #ffccbc, #fffbf0); background-size: 400% 400%; animation: gradientBG 15s ease infinite; min-height: 100vh; padding: 20px; }
        @keyframes gradientBG { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        .glass-box { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px); border-radius: 25px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); padding: 30px; }
        h2, h3 { font-family: 'Prompt', sans-serif; }
        .btn-generate { background: linear-gradient(135deg, #FF6F00, #d32f2f); color: white; transition: all 0.3s; }
        .btn-generate:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(211, 47, 47, 0.4); }
    </style>
</head>
<body>
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <a href="formmenu" class="bg-white/80 p-3 rounded-xl hover:bg-white transition-all shadow-sm"><i class="fas fa-arrow-left text-gray-600"></i></a>
            <h2 class="text-3xl font-bold text-red-700 mx-auto">🎁 จัดการระบบแต้ม (Points)</h2>
            <div class="w-10"></div>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r-lg shadow-sm animate-bounce"><i class="fas fa-check-circle mr-2"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r-lg shadow-sm"><i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Left: Generator -->
            <div class="md:col-span-1 space-y-6">
                <div class="glass-box p-2 flex gap-2">
                    <button onclick="toggleTab('random')" id="btnTabRandom" class="flex-1 py-2 rounded-xl font-bold text-sm transition-all <?php echo $tab_gen === 'random' ? 'bg-orange-100 text-orange-700' : 'text-gray-500 hover:bg-gray-100'; ?>">🎲 สุ่มโค้ด</button>
                    <button onclick="toggleTab('manual')" id="btnTabManual" class="flex-1 py-2 rounded-xl font-bold text-sm transition-all <?php echo $tab_gen === 'manual' ? 'bg-orange-100 text-orange-700' : 'text-gray-500 hover:bg-gray-100'; ?>">✍️ สร้างเอง</button>
                </div>

                <!-- Random Form -->
                <div id="tabRandom" class="glass-box <?php echo $tab_gen === 'random' ? '' : 'hidden'; ?>">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-random text-orange-500"></i> สุ่มโค้ดชุดใหญ่</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="tab_gen" value="random">
                        <input type="hidden" name="tab_list" value="<?php echo $tab_list; ?>"> <!-- Keep list tab same -->
                        <div><label class="block text-sm font-semibold text-gray-600 mb-1">มูลค่าแต้ม</label><input type="number" name="points_value" value="50" class="w-full p-3 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-orange-500" required></div>
                        <div><label class="block text-sm font-semibold text-gray-600 mb-1">จำนวนโค้ด</label><input type="number" name="quantity" value="10" class="w-full p-3 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-orange-500" required></div>
                        <button type="submit" name="generate_codes" class="w-full btn-generate py-3 rounded-xl font-bold font-display shadow-lg">🚀 สุ่มโค้ดทันที</button>
                    </form>
                </div>

                <!-- Manual Form -->
                <div id="tabManual" class="glass-box <?php echo $tab_gen === 'manual' ? '' : 'hidden'; ?>">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-pen-fancy text-purple-500"></i> สร้างโค้ดเอง</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="tab_gen" value="manual">
                        <input type="hidden" name="tab_list" value="<?php echo $tab_list; ?>"> <!-- Keep list tab same -->
                        <div><label class="block text-sm font-semibold text-gray-600 mb-1">ชื่อโค้ด</label><input type="text" name="custom_code" placeholder="เช่น HELLO2025" style="text-transform:uppercase" class="w-full p-3 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-purple-500 font-bold text-purple-700" required></div>
                        <div><label class="block text-sm font-semibold text-gray-600 mb-1">มูลค่าแต้ม</label><input type="number" name="points_value" value="100" class="w-full p-3 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-purple-500" required></div>
                        <div><label class="block text-sm font-semibold text-gray-600 mb-1">จำนวนสิทธิ์ (ครั้ง)</label><input type="number" name="max_uses" value="100" class="w-full p-3 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-purple-500" required></div>
                        <button type="submit" name="create_custom_code" class="w-full py-3 rounded-xl font-bold font-display shadow-lg bg-gradient-to-r from-purple-500 to-indigo-600 text-white hover:translate-y-1 transition-all">✨ สร้างโค้ดนี้</button>
                    </form>
                </div>

                <!-- Stats -->
                <div class="glass-box bg-white/60">
                    <h3 class="text-lg font-bold text-gray-700 mb-4 flex justify-between items-center">
                        <span>📊 สถิติรวม</span>
                        <form method="POST" onsubmit="return confirm('⚠️ ยืนยันการล้างโค้ดทั้งหมด?\nการดำเนินการนี้จะลบโค้ดและประวัติการใช้งานทั้งหมดและไม่สามารถเรียกคืนได้!');" class="inline">
                            <button type="submit" name="delete_all_codes" class="text-xs bg-red-100 text-red-600 px-3 py-1.5 rounded-lg hover:bg-red-600 hover:text-white transition-all font-bold">
                                <i class="fas fa-trash-sweep mr-1"></i> ล้างทั้งหมด
                            </button>
                        </form>
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center p-3 bg-white rounded-lg shadow-sm"><span class="text-gray-600">โค้ดทั้งหมด</span><span class="font-bold text-xl text-blue-600"><?php echo number_format($total_codes); ?></span></div>
                        <div class="flex justify-between items-center p-3 bg-white rounded-lg shadow-sm"><span class="text-gray-600">ยังไม่ใช้</span><span class="font-bold text-xl text-green-600"><?php echo number_format($unused_codes); ?></span></div>
                        <div class="flex justify-between items-center p-3 bg-white rounded-lg shadow-sm"><span class="text-gray-600">ใช้ไปแล้ว</span><span class="font-bold text-xl text-red-600"><?php echo number_format($used_codes); ?></span></div>
                    </div>
                </div>
            </div>

            <!-- Right: Lists -->
            <div class="md:col-span-2">

                <div class="glass-box h-full flex flex-col">
                    
                    <!-- Search Bar -->
                    <form method="GET" class="mb-4">
                        <div class="relative">
                            <input type="hidden" name="tab_list" id="searchTabList" value="<?php echo htmlspecialchars($tab_list); ?>">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="🔍 ค้นหาโค้ด..." 
                                class="w-full pl-10 pr-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:bg-white focus:ring-2 focus:ring-orange-300 outline-none transition-all">
                            <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                            <?php if(!empty($search)): ?>
                                <a href="manage_points?tab_list=<?php echo $tab_list; ?>" class="absolute right-3 top-2.5 text-xs bg-gray-200 hover:bg-gray-300 text-gray-600 px-2 py-1 rounded-lg">ล้างค่า</a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <!-- Tabs -->
                    <div class="flex gap-4 mb-4 border-b border-gray-100 pb-2">
                        <button onclick="toggleList('manual')" id="btnListManual" class="px-4 py-2 font-bold transition-all <?php echo $tab_list === 'manual' ? 'text-purple-600 border-b-2 border-purple-500' : 'text-gray-400 border-b-2 border-transparent hover:text-purple-500'; ?>"><i class="fas fa-pen-fancy"></i> รายการสร้างเอง</button>
                        <button onclick="toggleList('random')" id="btnListRandom" class="px-4 py-2 font-bold transition-all <?php echo $tab_list === 'random' ? 'text-orange-600 border-b-2 border-orange-500' : 'text-gray-400 border-b-2 border-transparent hover:text-orange-500'; ?>"><i class="fas fa-random"></i> รายการสุ่ม</button>
                    </div>

                    <!-- Manual List -->
                    <div id="listManual" class="<?php echo $tab_list === 'manual' ? '' : 'hidden'; ?>">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-gray-500 border-b border-gray-200">
                                        <th class="pb-3 pl-2">CODE</th>
                                        <th class="pb-3">แต้ม</th>
                                        <th class="pb-3">ใช้ไป/ทั้งหมด</th>
                                        <th class="pb-3">สถานะ</th>
                                        <th class="pb-3 text-right pr-2">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if ($manual_codes && $manual_codes->num_rows > 0): ?>
                                        <?php while ($row = $manual_codes->fetch_assoc()): 
                                            $percent = ($row['max_uses'] > 0) ? ($row['used_count'] / $row['max_uses']) * 100 : 0;
                                            $is_full = ($row['max_uses'] > 0) && ($row['used_count'] >= $row['max_uses']);
                                        ?>
                                            <tr class="hover:bg-purple-50/50 transition-colors">
                                                <td class="py-3 pl-2 font-mono font-bold text-purple-700"><?php echo htmlspecialchars($row['code']); ?></td>
                                                <td class="py-3"><span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-bold"><?php echo $row['points_value']; ?> P</span></td>
                                                <td class="py-3 text-sm">
                                                    <?php echo $row['used_count'] . " / " . $row['max_uses']; ?>
                                                    <div class="w-20 h-1.5 bg-gray-100 rounded-full mt-1 overflow-hidden"><div class="h-full bg-purple-500" style="width: <?php echo $percent; ?>%"></div></div>
                                                </td>
                                                <td class="py-3">
                                                    <?php echo $is_full ? '<span class="text-red-500 text-sm font-bold"><i class="fas fa-times-circle"></i> หมด</span>' : '<span class="text-green-500 text-sm font-bold"><i class="fas fa-check-circle"></i> ว่าง</span>'; ?>
                                                </td>
                                                <td class="py-3 text-right pr-2">
                                                    <form method="POST" onsubmit="return confirm('ยืนยันลบโค้ดนี้?');">
                                                        <input type="hidden" name="tab_list" value="manual">
                                                        <input type="hidden" name="tab_gen" value="<?php echo $tab_gen; ?>">
                                                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                                        <button type="submit" name="delete_code" value="<?php echo $row['id']; ?>" class="text-red-400 hover:text-red-600 transition-colors"><i class="fas fa-trash-alt"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="py-8 text-center text-gray-400">ไม่มีข้อมูล</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Random List -->
                    <div id="listRandom" class="<?php echo $tab_list === 'random' ? '' : 'hidden'; ?>">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-gray-500 border-b border-gray-200">
                                        <th class="pb-3 pl-2">CODE</th>
                                        <th class="pb-3">แต้ม</th>
                                        <th class="pb-3">ใช้ไป/ทั้งหมด</th>
                                        <th class="pb-3">สถานะ</th>
                                        <th class="pb-3 text-right pr-2">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if ($random_codes && $random_codes->num_rows > 0): ?>
                                        <?php while ($row = $random_codes->fetch_assoc()): 
                                            // Random stats
                                            $percent = ($row['max_uses'] > 0) ? ($row['used_count'] / $row['max_uses']) * 100 : 0;
                                            $is_full = ($row['max_uses'] > 0) && ($row['used_count'] >= $row['max_uses']);
                                        ?>
                                            <tr class="hover:bg-orange-50/50 transition-colors">
                                                <td class="py-3 pl-2 font-mono font-bold text-gray-700"><?php echo htmlspecialchars($row['code']); ?></td>
                                                <td class="py-3"><span class="bg-orange-100 text-orange-700 px-2 py-1 rounded text-xs font-bold"><?php echo $row['points_value']; ?> P</span></td>
                                                <td class="py-3 text-sm">
                                                    <?php echo $row['used_count'] . " / " . $row['max_uses']; ?>
                                                    <div class="w-20 h-1.5 bg-gray-100 rounded-full mt-1 overflow-hidden"><div class="h-full bg-orange-500" style="width: <?php echo $percent; ?>%"></div></div>
                                                </td>
                                                <td class="py-3">
                                                    <?php echo $is_full ? '<span class="text-red-500 text-sm font-bold"><i class="fas fa-times-circle"></i> ใช้แล้ว</span>' : '<span class="text-green-500 text-sm font-bold"><i class="far fa-circle"></i> ว่าง</span>'; ?>
                                                </td>
                                                <td class="py-3 text-right pr-2">
                                                    <form method="POST" onsubmit="return confirm('ยืนยันลบโค้ดนี้?');">
                                                        <input type="hidden" name="tab_list" value="random">
                                                        <input type="hidden" name="tab_gen" value="<?php echo $tab_gen; ?>">
                                                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                                        <button type="submit" name="delete_code" value="<?php echo $row['id']; ?>" class="text-red-400 hover:text-red-600 transition-colors"><i class="fas fa-trash-alt"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="py-8 text-center text-gray-400">ไม่มีข้อมูล</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function toggleTab(tab) {
            if(tab === 'random') {
                document.getElementById('tabRandom').classList.remove('hidden'); document.getElementById('tabManual').classList.add('hidden');
                document.getElementById('btnTabRandom').classList.add('bg-orange-100', 'text-orange-700'); document.getElementById('btnTabRandom').classList.remove('text-gray-500', 'hover:bg-gray-100');
                document.getElementById('btnTabManual').classList.remove('bg-orange-100', 'text-orange-700'); document.getElementById('btnTabManual').classList.add('text-gray-500', 'hover:bg-gray-100');
            } else {
                document.getElementById('tabRandom').classList.add('hidden'); document.getElementById('tabManual').classList.remove('hidden');
                document.getElementById('btnTabManual').classList.add('bg-orange-100', 'text-orange-700'); document.getElementById('btnTabManual').classList.remove('text-gray-500', 'hover:bg-gray-100');
                document.getElementById('btnTabRandom').classList.remove('bg-orange-100', 'text-orange-700'); document.getElementById('btnTabRandom').classList.add('text-gray-500', 'hover:bg-gray-100');
            }
        }
        function toggleList(type) {
            const btnManual = document.getElementById('btnListManual'); const btnRandom = document.getElementById('btnListRandom');
            const listManual = document.getElementById('listManual'); const listRandom = document.getElementById('listRandom');
            const searchTabList = document.getElementById('searchTabList');

            if (type === 'manual') {
                listManual.classList.remove('hidden'); listRandom.classList.add('hidden');
                btnManual.classList.add('text-purple-600', 'border-purple-500'); btnManual.classList.remove('text-gray-400', 'border-transparent');
                btnRandom.classList.add('text-gray-400', 'border-transparent'); btnRandom.classList.remove('text-orange-600', 'border-orange-500');
                searchTabList.value = 'manual';
            } else {
                listManual.classList.add('hidden'); listRandom.classList.remove('hidden');
                btnRandom.classList.add('text-orange-600', 'border-orange-500'); btnRandom.classList.remove('text-gray-400', 'border-transparent');
                btnManual.classList.add('text-gray-400', 'border-transparent'); btnManual.classList.remove('text-purple-600', 'border-purple-500');
                searchTabList.value = 'random';
            }
        }
    </script>
</body>
</html>