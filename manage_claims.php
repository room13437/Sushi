<?php
session_start();
require_once 'db_config.php';
require_once 'admin_auth.php'; // Ensure admin is logged in

// Process Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['confirm_claim'])) {
        $id = (int) $_POST['confirm_claim'];
        $stmt = $conn->prepare("UPDATE reward_claims SET status = 'fulfilled', fulfilled_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['msg'] = "✅ ยืนยันรายการ #$id เรียบร้อยแล้ว";
        }
    }
}

// Params
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'pending';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build Query
$sql_base = "FROM reward_claims rc LEFT JOIN customers c ON rc.user_id = c.id WHERE 1=1 ";
$params = [];
$types = "";

if ($filter == 'pending') {
    $sql_base .= "AND rc.status = 'pending' ";
} elseif ($filter == 'fulfilled') {
    $sql_base .= "AND rc.status = 'fulfilled' ";
}

if (!empty($search)) {
    $sql_base .= "AND (c.full_name LIKE ? OR c.username LIKE ? OR rc.id LIKE ?) ";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $types .= "sss";
}

// Count Total for Pagination
$stmt_count = $conn->prepare("SELECT COUNT(*) as total $sql_base");
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch Data
$sql_data = "SELECT rc.*, c.full_name, c.phone $sql_base ORDER BY rc.claimed_at DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $limit;
$types .= "ii";

$stmt = $conn->prepare($sql_data);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Stats (Quick Look)
$stats_pending = $conn->query("SELECT COUNT(*) as c FROM reward_claims WHERE status='pending'")->fetch_assoc()['c'];
$stats_today = $conn->query("SELECT COUNT(*) as c FROM reward_claims WHERE DATE(claimed_at) = CURDATE()")->fetch_assoc()['c'];

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการแลกซูชิ | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background: #FFF7ED; }
        h1, h2, h3, .font-prompt { font-family: 'Prompt', sans-serif; }
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="max-w-7xl mx-auto">
        
        <!-- Header & Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Header -->
            <div class="md:col-span-1 flex items-center gap-4">
                <a href="formmenu" class="bg-white p-3 rounded-xl shadow-md text-orange-600 hover:bg-orange-50 transition">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-orange-800 font-prompt">รายการแลกซูชิ</h1>
                    <p class="text-gray-500 text-sm">ระบบจัดการการแลกสินค้า</p>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="md:col-span-2 flex gap-4">
                <div class="flex-1 bg-white p-4 rounded-xl shadow-sm border-l-4 border-yellow-400 flex items-center justify-between">
                    <div>
                        <div class="text-gray-500 text-sm font-bold">รอรับของ (Pending)</div>
                        <div class="text-2xl font-bold text-yellow-600"><?php echo number_format($stats_pending); ?></div>
                    </div>
                    <i class="fas fa-clock text-3xl text-yellow-200"></i>
                </div>
                <div class="flex-1 bg-white p-4 rounded-xl shadow-sm border-l-4 border-blue-400 flex items-center justify-between">
                    <div>
                        <div class="text-gray-500 text-sm font-bold">รายการวันนี้ (Today)</div>
                        <div class="text-2xl font-bold text-blue-600"><?php echo number_format($stats_today); ?></div>
                    </div>
                    <i class="fas fa-calendar-day text-3xl text-blue-200"></i>
                </div>
            </div>
        </div>

        <!-- Controls: Search & Tabs -->
        <div class="bg-white p-4 rounded-2xl shadow-sm mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
            
            <!-- Tabs -->
            <div class="flex bg-gray-100 p-1 rounded-xl">
                <a href="?filter=pending&search=<?php echo $search; ?>" 
                   class="px-4 py-2 rounded-lg text-sm font-bold transition <?php echo $filter == 'pending' ? 'bg-white text-orange-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'; ?>">
                   ⏳ รอรับของ
                </a>
                <a href="?filter=fulfilled&search=<?php echo $search; ?>" 
                   class="px-4 py-2 rounded-lg text-sm font-bold transition <?php echo $filter == 'fulfilled' ? 'bg-white text-green-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'; ?>">
                   ✅ รับแล้ว
                </a>
                <a href="?filter=all&search=<?php echo $search; ?>" 
                   class="px-4 py-2 rounded-lg text-sm font-bold transition <?php echo $filter == 'all' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'; ?>">
                   📋 ทั้งหมด
                </a>
            </div>

            <!-- Search -->
            <form class="flex w-full md:w-auto gap-2">
                <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                <div class="relative flex-1 md:w-64">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="ค้นหาชื่อ, เบอร์, ID..." 
                           class="w-full pl-10 pr-4 py-2 rounded-xl border border-gray-200 focus:border-orange-500 focus:ring-1 focus:ring-orange-500 outline-none transition">
                </div>
                <button type="submit" class="bg-orange-500 text-white px-4 py-2 rounded-xl hover:bg-orange-600 transition">
                    ค้นหา
                </button>
                <?php if ($search): ?>
                        <a href="?filter=<?php echo $filter; ?>" class="bg-gray-200 text-gray-600 px-3 py-2 rounded-xl hover:bg-gray-300 transition flex items-center">
                            <i class="fas fa-times"></i>
                        </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (isset($_SESSION['msg'])): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6 flex items-center gap-3 animate-pulse">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['msg'];
                    unset($_SESSION['msg']); ?>
                </div>
        <?php endif; ?>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-orange-50 text-orange-800 font-prompt text-sm uppercase tracking-wider">
                        <tr>
                            <th class="p-4 text-left font-bold">#</th>
                            <th class="p-4 text-left font-bold">เวลา</th>
                            <th class="p-4 text-left font-bold">ลูกค้า</th>
                            <th class="p-4 text-left font-bold">รายการที่แลก</th>
                            <th class="p-4 text-center font-bold">สถานะ</th>
                            <th class="p-4 text-center font-bold">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr class="hover:bg-orange-50/30 transition group">
                                            <td class="p-4 text-gray-400 font-mono">#<?php echo $row['id']; ?></td>
                                            <td class="p-4 text-sm text-gray-600">
                                                <div class="font-bold"><?php echo date('d/m/Y', strtotime($row['claimed_at'])); ?></div>
                                                <div class="text-xs text-gray-400"><?php echo date('H:i', strtotime($row['claimed_at'])); ?></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($row['username']); ?></div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($row['full_name'] ?? '-'); ?>
                                                    <?php if (!empty($row['phone'])): ?>
                                                            <span class="text-xs bg-gray-100 px-2 py-0.5 rounded ml-1"><i class="fas fa-phone-alt text-gray-400"></i> <?php echo htmlspecialchars($row['phone']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="p-4">
                                                <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-pink-50 text-pink-700 rounded-lg font-bold">
                                                    🍣 <?php echo number_format($row['items_count']); ?> ชิ้น
                                                </span>
                                                <div class="text-xs text-gray-400 mt-1 ml-1">
                                                    <i class="fas fa-coins text-yellow-500"></i> <?php echo number_format($row['points_used']); ?> แต้ม
                                                </div>
                                            </td>
                                            <td class="p-4 text-center">
                                                <?php if ($row['status'] == 'pending'): ?>
                                                        <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-bold border border-yellow-200">
                                                            <i class="fas fa-clock mr-1"></i> รอรับของ
                                                        </span>
                                                <?php elseif ($row['status'] == 'fulfilled'): ?>
                                                        <span class="inline-block px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold border border-green-200">
                                                            <i class="fas fa-check mr-1"></i> รับแล้ว
                                                        </span>
                                                <?php else: ?>
                                                        <span class="inline-block px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold border border-red-200">
                                                            <i class="fas fa-times mr-1"></i> ยกเลิก
                                                        </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-4 text-center">
                                                <?php if ($row['status'] == 'pending'): ?>
                                                        <form method="POST" onsubmit="return confirm('ยืนยันว่าลูกค้าได้รับของแล้ว?');">
                                                            <button type="submit" name="confirm_claim" value="<?php echo $row['id']; ?>"
                                                                class="w-full md:w-auto bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-xl shadow-lg shadow-green-200 font-bold text-sm transition-all transform hover:-translate-y-1">
                                                                <i class="fas fa-check-circle mr-1"></i> ยืนยัน
                                                            </button>
                                                        </form>
                                                <?php elseif ($row['status'] == 'fulfilled'): ?>
                                                        <div class="text-xs text-green-600 opacity-70">
                                                            <i class="fas fa-check-double mb-1"></i><br>
                                                            <?php echo date('d/m H:i', strtotime($row['fulfilled_at'])); ?>
                                                        </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                <?php endwhile; ?>
                        <?php else: ?>
                                <tr>
                                    <td colspan="6" class="p-12 text-center text-gray-400 bg-gray-50">
                                        <div class="text-6xl mb-4 opacity-20">🍱</div>
                                        <p class="text-lg">ไม่พบข้อมูลรายการ</p>
                                        <?php if ($search): ?>
                                                <p class="text-sm mt-2">ลองค้นหาด้วยคำอื่นดูนะครับ</p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                    <div class="p-4 border-t border-gray-100 flex justify-center gap-2">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>&search=<?php echo $search; ?>" 
                                   class="w-10 h-10 flex items-center justify-center rounded-lg font-bold transition <?php echo $i == $page ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                                   <?php echo $i; ?>
                                </a>
                        <?php endfor; ?>
                    </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>