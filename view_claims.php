<?php
// หน้าร้าน - ดูรายการแลกซูชิ
include "db.php";
session_start();

$message = "";

// อัปเดตสถานะเป็น fulfilled
if (isset($_GET['fulfill'])) {
    $claim_id = (int) $_GET['fulfill'];
    $stmt = $conn->prepare("UPDATE reward_claims SET status = 'fulfilled', fulfilled_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $claim_id);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>✅ ทำรายการสำเร็จ! มอบซูชิแล้ว</div>";
    }
    $stmt->close();
}

// ยกเลิกรายการ
if (isset($_GET['cancel'])) {
    $claim_id = (int) $_GET['cancel'];
    $stmt = $conn->prepare("UPDATE reward_claims SET status = 'cancelled' WHERE id = ?");
    $stmt->bind_param("i", $claim_id);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>✅ ยกเลิกรายการสำเร็จ!</div>";
    }
    $stmt->close();
}

// กำหนดตัวแปร
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$view_detail = isset($_GET['id']) ? (int) $_GET['id'] : null;
$view_mode = isset($_GET['mode']) ? $_GET['mode'] : 'all'; // mode: all, grouped

// Pagination
$items_per_page = 5;
$current_page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($current_page - 1) * $items_per_page;

// ถ้าดูรายละเอียด
$claim_detail = null;
if ($view_detail) {
    $stmt = $conn->prepare("SELECT * FROM reward_claims WHERE id = ?");
    $stmt->bind_param("i", $view_detail);
    $stmt->execute();
    $result = $stmt->get_result();
    $claim_detail = $result->fetch_assoc();
    $stmt->close();
}

// นับจำนวนรายการทั้งหมด (สำหรับ pagination)
$total_items = 0;
if (!$view_detail) {
    if ($view_mode == 'grouped') {
        // นับจำนวนผู้ใช้งานที่มีรายการ
        $count_sql = "SELECT COUNT(DISTINCT user_id) as total FROM reward_claims WHERE status = ?";
        if (!empty($search_query)) {
            $count_sql .= " AND username LIKE ?";
            $stmt_count = $conn->prepare($count_sql);
            $search_param = "%{$search_query}%";
            $stmt_count->bind_param("ss", $status_filter, $search_param);
        } else {
            $stmt_count = $conn->prepare($count_sql);
            $stmt_count->bind_param("s", $status_filter);
        }
    } else {
        // นับจำนวนรายการทั้งหมด
        if (!empty($search_query)) {
            $search_param = "%{$search_query}%";
            $count_sql = "SELECT COUNT(*) as total FROM reward_claims WHERE status = ? AND username LIKE ?";
            $stmt_count = $conn->prepare($count_sql);
            $stmt_count->bind_param("ss", $status_filter, $search_param);
        } else {
            $count_sql = "SELECT COUNT(*) as total FROM reward_claims WHERE status = ?";
            $stmt_count = $conn->prepare($count_sql);
            $stmt_count->bind_param("s", $status_filter);
        }
    }
    $stmt_count->execute();
    $count_result = $stmt_count->get_result();
    $total_items = $count_result->fetch_assoc()['total'];
    $stmt_count->close();
}

$total_pages = ceil($total_items / $items_per_page);

// ดึงรายการทั้งหมด (พร้อมค้นหา + pagination)
$claims = [];
$grouped_claims = [];

if (!$view_detail) {
    if ($view_mode == 'grouped') {
        // ดึงรายการแบบจัดกลุ่มตามผู้ใช้งาน
        $sql = "SELECT username, user_id, COUNT(*) as claim_count, SUM(items_count) as total_items, SUM(points_used) as total_points, MAX(claimed_at) as last_claim 
                FROM reward_claims WHERE status = ? ";
        if (!empty($search_query)) {
            $sql .= " AND username LIKE ? ";
        }
        $sql .= " GROUP BY user_id ORDER BY last_claim DESC LIMIT ? OFFSET ?";

        $stmt = $conn->prepare($sql);
        if (!empty($search_query)) {
            $search_param = "%{$search_query}%";
            $stmt->bind_param("ssii", $status_filter, $search_param, $items_per_page, $offset);
        } else {
            $stmt->bind_param("sii", $status_filter, $items_per_page, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $grouped_claims[] = $row;
        }
        $stmt->close();
    } else {
        // ดึงรายการแบบปกติ
        if (!empty($search_query)) {
            $search_param = "%{$search_query}%";
            $sql = "SELECT * FROM reward_claims WHERE status = ? AND username LIKE ? ORDER BY claimed_at DESC LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssii", $status_filter, $search_param, $items_per_page, $offset);
        } else {
            $sql = "SELECT * FROM reward_claims WHERE status = ? ORDER BY claimed_at DESC LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sii", $status_filter, $items_per_page, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $claims[] = $row;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🍣 รายการแลกซูชิ | Shop</title>

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

        .claim-card {
            transition: all 0.3s ease;
        }

        .claim-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(249, 115, 22, 0.2);
        }
    </style>
</head>

<body class="p-4 md:p-8">

    <div class="max-w-6xl mx-auto">

        <!-- Header -->
        <div class="glass-card rounded-3xl p-8 mb-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h1
                        class="text-3xl font-display font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-orange-700">
                        🍣 รายการแลกซูชิ (ร้าน)
                    </h1>
                    <p class="text-orange-600 mt-2">จัดการรายการแลกซูชิของลูกค้า</p>
                </div>
                <div class="flex gap-3 flex-wrap justify-center md:justify-end">
                    <?php if ($view_detail): ?>
                        <a href="?status=<?php echo $status_filter; ?>&mode=<?php echo $view_mode; ?>"
                            class="px-5 py-3 rounded-xl bg-gray-100 text-gray-600 font-display font-bold hover:bg-gray-200 transition-all">
                            <i class="fas fa-arrow-left mr-2"></i> กลับ
                        </a>
                    <?php endif; ?>
                    <div class="flex bg-orange-50 p-1 rounded-xl">
                        <a href="?status=<?php echo $status_filter; ?>&mode=all<?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>"
                            class="px-5 py-3 rounded-lg font-display font-bold transition-all <?php echo $view_mode == 'all' ? 'bg-white text-orange-600 shadow-sm' : 'text-orange-400 hover:text-orange-500'; ?>">
                            <i class="fas fa-list mr-2"></i> รายการทั้งหมด
                        </a>
                        <a href="?status=<?php echo $status_filter; ?>&mode=grouped<?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>"
                            class="px-5 py-3 rounded-lg font-display font-bold transition-all <?php echo $view_mode == 'grouped' ? 'bg-white text-orange-600 shadow-sm' : 'text-orange-400 hover:text-orange-500'; ?>">
                            <i class="fas fa-users mr-2"></i> แยกตามรายชื่อ
                        </a>
                    </div>
                    <a href="formmenu"
                        class="px-5 py-3 rounded-xl bg-orange-100 text-orange-600 font-display font-bold hover:bg-orange-200 transition-all">
                        <i class="fas fa-home mr-2"></i> เมนูหลัก
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

        <?php if ($view_detail && $claim_detail): ?>
            <!-- Detail View -->
            <div class="glass-card rounded-3xl p-8">
                <h2 class="text-2xl font-display font-bold text-orange-800 mb-6">
                    <i class="fas fa-receipt text-orange-500 mr-3"></i>
                    รายละเอียดคำสั่งแลก #<?php echo $claim_detail['id']; ?>
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- ข้อมูลลูกค้า -->
                    <div class="bg-orange-50 rounded-2xl p-6">
                        <h3 class="text-lg font-display font-bold text-orange-700 mb-4">👤 ข้อมูลลูกค้า</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-gray-500 text-sm">ชื่อลูกค้า:</span>
                                <div class="font-display font-bold text-orange-800 text-xl">
                                    <?php echo htmlspecialchars($claim_detail['username']); ?>
                                </div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">User ID:</span>
                                <div class="font-mono text-gray-700">#<?php echo $claim_detail['user_id']; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- ข้อมูลสินค้า -->
                    <div class="bg-pink-50 rounded-2xl p-6">
                        <h3 class="text-lg font-display font-bold text-pink-700 mb-4">🍣 รายการสินค้า</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-gray-500 text-sm">ซูชิ:</span>
                                <div class="text-4xl">🍣</div>
                                <div class="font-display font-bold text-pink-600 text-2xl">
                                    <?php echo $claim_detail['items_count']; ?> ชิ้น
                                </div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">Point ที่ใช้:</span>
                                <div class="font-display font-bold text-red-600 text-xl">
                                    -<?php echo $claim_detail['points_used']; ?> Point
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- สถานะและเวลา -->
                    <div class="bg-blue-50 rounded-2xl p-6 md:col-span-2">
                        <h3 class="text-lg font-display font-bold text-blue-700 mb-4">📅 ข้อมูลเวลา</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <span class="text-gray-500 text-sm">เวลาที่แลก:</span>
                                <div class="font-display font-bold text-gray-800">
                                    <?php echo date('d/m/Y H:i:s', strtotime($claim_detail['claimed_at'])); ?>
                                </div>
                            </div>
                            <?php if ($claim_detail['fulfilled_at']): ?>
                                <div>
                                    <span class="text-gray-500 text-sm">เวลาที่มอบของ:</span>
                                    <div class="font-display font-bold text-green-700">
                                        <?php echo date('d/m/Y H:i:s', strtotime($claim_detail['fulfilled_at'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div>
                                <span class="text-gray-500 text-sm">สถานะ:</span>
                                <div class="mt-2">
                                    <?php
                                    if ($claim_detail['status'] == 'pending') {
                                        echo '<span class="px-4 py-2 rounded-full bg-yellow-100 text-yellow-700 font-semibold">🕐 รอมอบของ</span>';
                                    } elseif ($claim_detail['status'] == 'fulfilled') {
                                        echo '<span class="px-4 py-2 rounded-full bg-green-100 text-green-700 font-semibold">✅ มอบแล้ว</span>';
                                    } else {
                                        echo '<span class="px-4 py-2 rounded-full bg-red-100 text-red-600 font-semibold">❌ ยกเลิก</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <?php if ($claim_detail['status'] == 'pending'): ?>
                    <div class="flex gap-4 justify-center pt-6 border-t border-orange-100">
                        <a href="?fulfill=<?php echo $claim_detail['id']; ?>&status=<?php echo $status_filter; ?>"
                            onclick="return confirm('ยืนยันว่ามอบซูชิให้ลูกค้าแล้ว?')"
                            class="px-8 py-4 rounded-2xl bg-gradient-to-r from-green-500 to-emerald-600 text-white font-display font-bold text-lg hover:from-green-600 hover:to-emerald-700 shadow-lg hover:shadow-xl transition-all">
                            <i class="fas fa-check-circle mr-2"></i> ยืนยันมอบของแล้ว
                        </a>
                        <a href="?cancel=<?php echo $claim_detail['id']; ?>&status=<?php echo $status_filter; ?>"
                            onclick="return confirm('ยืนยันการยกเลิก?')"
                            class="px-8 py-4 rounded-2xl bg-gradient-to-r from-red-500 to-rose-600 text-white font-display font-bold text-lg hover:from-red-600 hover:to-rose-700 shadow-lg hover:shadow-xl transition-all">
                            <i class="fas fa-times-circle mr-2"></i> ยกเลิกรายการ
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- List View -->

            <!-- Search & Filter -->
            <div class="glass-card rounded-3xl p-6 mb-6">
                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Search Box -->
                    <div class="flex-1">
                        <form method="GET" action="" class="relative">
                            <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>"
                                placeholder="🔍 ค้นหาชื่อลูกค้า..."
                                class="w-full px-5 py-3 pr-12 rounded-xl border-2 border-orange-200 bg-white text-orange-800 placeholder-orange-300 font-display focus:border-orange-500 focus:outline-none">
                            <button type="submit"
                                class="absolute right-2 top-1/2 -translate-y-1/2 px-4 py-2 rounded-lg bg-orange-500 text-white hover:bg-orange-600 transition-all">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Status Tabs -->
                    <div class="flex gap-2 flex-wrap">
                        <a href="?status=pending<?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>"
                            class="px-4 py-3 rounded-xl font-display font-bold transition-all <?php echo $status_filter == 'pending' ? 'bg-gradient-to-r from-orange-500 to-orange-600 text-white' : 'bg-orange-100 text-orange-600 hover:bg-orange-200'; ?>">
                            🕐 รอมอบของ
                        </a>
                        <a href="?status=fulfilled<?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>"
                            class="px-4 py-3 rounded-xl font-display font-bold transition-all <?php echo $status_filter == 'fulfilled' ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white' : 'bg-green-100 text-green-600 hover:bg-green-200'; ?>">
                            ✅ มอบแล้ว
                        </a>
                        <a href="?status=cancelled<?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>"
                            class="px-4 py-3 rounded-xl font-display font-bold transition-all <?php echo $status_filter == 'cancelled' ? 'bg-gradient-to-r from-red-500 to-rose-600 text-white' : 'bg-red-100 text-red-600 hover:bg-red-200'; ?>">
                            ❌ ยกเลิก
                        </a>
                    </div>
                </div>

                <?php if ($search_query): ?>
                    <div class="mt-4 flex items-center gap-3">
                        <span class="text-orange-600 text-sm">ผลการค้นหา:
                            <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong></span>
                        <a href="?status=<?php echo $status_filter; ?>&mode=<?php echo $view_mode; ?>&page=1"
                            class="text-sm text-red-500 hover:text-red-700 font-semibold">
                            <i class="fas fa-times mr-1"></i> ล้างการค้นหา
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Claims Grid -->
            <div class="glass-card rounded-3xl p-8">
                <h2 class="text-xl font-display font-bold text-orange-800 mb-6 flex items-center gap-3">
                    <i class="fas fa-list text-orange-500"></i> รายการทั้งหมด
                    <span class="text-sm bg-orange-100 text-orange-600 px-3 py-1 rounded-full">
                        <?php echo $total_items; ?> รายการ
                    </span>
                    <?php if ($total_pages > 1): ?>
                        <span class="text-sm text-gray-500">
                            (หน้า <?php echo $current_page; ?>/<?php echo $total_pages; ?>)
                        </span>
                    <?php endif; ?>
                </h2>

                <?php if ($view_mode == 'grouped'): ?>
                    <!-- Grouped View -->
                    <?php if (empty($grouped_claims)): ?>
                        <div class="text-center py-12 text-orange-400">
                            <i class="fas fa-users-slash text-5xl mb-4"></i>
                            <p class="font-display text-lg">ไม่พบรายชื่อลูกค้า</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($grouped_claims as $user): ?>
                                <div class="glass-card rounded-2xl p-6 hover:border-orange-400 transition-all">
                                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-16 h-16 rounded-full bg-orange-100 flex items-center justify-center text-orange-500 text-2xl font-bold">
                                                <?php echo mb_substr($user['username'], 0, 1); ?>
                                            </div>
                                            <div>
                                                <h3 class="text-xl font-display font-bold text-orange-800">
                                                    <?php echo htmlspecialchars($user['username']); ?>
                                                </h3>
                                                <p class="text-sm text-orange-500">
                                                    ID: #<?php echo $user['user_id']; ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-3 gap-6 md:gap-10">
                                            <div class="text-center">
                                                <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">จำนวนครั้ง</div>
                                                <div class="font-display font-bold text-orange-700 text-lg">
                                                    <?php echo $user['claim_count']; ?> ครั้ง
                                                </div>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">รวมซูชิ</div>
                                                <div class="font-display font-bold text-pink-600 text-lg">
                                                    <?php echo $user['total_items']; ?> ชิ้น
                                                </div>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">รวมแต้ม</div>
                                                <div class="font-display font-bold text-red-600 text-lg">
                                                    -<?php echo $user['total_points']; ?></div>
                                            </div>
                                        </div>

                                        <button onclick="toggleUserClaims('user_<?php echo $user['user_id']; ?>', this)"
                                            class="px-6 py-3 rounded-xl bg-orange-500 text-white font-display font-bold hover:bg-orange-600 transition-all flex items-center gap-2">
                                            <span>ดูรายการ</span>
                                            <i class="fas fa-chevron-down ml-1 transition-transform"></i>
                                        </button>
                                    </div>

                                    <!-- Hidden Details (Expandable) -->
                                    <div id="user_<?php echo $user['user_id']; ?>" class="hidden mt-6 pt-6 border-t border-orange-100">
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <?php
                                            // Fetch individual claims for this user
                                            $u_id = $user['user_id'];
                                            $sql_u = "SELECT * FROM reward_claims WHERE user_id = ? AND status = ? ORDER BY claimed_at DESC";
                                            $stmt_u = $conn->prepare($sql_u);
                                            $stmt_u->bind_param("is", $u_id, $status_filter);
                                            $stmt_u->execute();
                                            $claims_result = $stmt_u->get_result();
                                            while ($claim = $claims_result->fetch_assoc()):
                                                ?>
                                                <a href="?id=<?php echo $claim['id']; ?>&status=<?php echo $status_filter; ?>&mode=grouped"
                                                    class="card-orange border border-orange-100 p-4 rounded-xl hover:border-orange-300 transition-all block">
                                                    <div class="flex justify-between items-center mb-2">
                                                        <span
                                                            class="font-mono font-bold text-orange-600 text-sm">#<?php echo $claim['id']; ?></span>
                                                        <span
                                                            class="text-xs text-gray-500"><?php echo date('d/m/H:i', strtotime($claim['claimed_at'])); ?></span>
                                                    </div>
                                                    <div class="flex items-center justify-between text-sm">
                                                        <div class="font-display font-bold text-pink-500">🍣
                                                            <?php echo $claim['items_count']; ?> ชิ้น
                                                        </div>
                                                        <div class="text-red-500">-<?php echo $claim['points_used']; ?> แต้ม</div>
                                                    </div>
                                                </a>
                                            <?php endwhile;
                                            $stmt_u->close(); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <script>
                            function toggleUserClaims(id, btn) {
                                const el = document.getElementById(id);
                                const icon = btn.querySelector('.fa-chevron-down');
                                if (el.classList.contains('hidden')) {
                                    el.classList.remove('hidden');
                                    icon.style.transform = 'rotate(180deg)';
                                    btn.querySelector('span').innerText = 'ปิด';
                                } else {
                                    el.classList.add('hidden');
                                    icon.style.transform = 'rotate(0deg)';
                                    btn.querySelector('span').innerText = 'ดูรายการ';
                                }
                            }
                        </script>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Standard List View (as before) -->
                    <?php if (empty($claims)): ?>
                        <div class="text-center py-12 text-orange-400">
                            <i class="fas fa-inbox text-5xl mb-4"></i>
                            <p class="font-display text-lg">ไม่มีรายการในหมวดนี้</p>
                            <?php if ($search_query): ?>
                                <p class="text-sm mt-2">ลองค้นหาด้วยคำอื่น หรือ <a href="?status=<?php echo $status_filter; ?>&mode=all"
                                        class="text-orange-500 hover:text-orange-700 font-semibold">ล้างการค้นหา</a></p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($claims as $claim): ?>
                                <a href="?id=<?php echo $claim['id']; ?>&status=<?php echo $status_filter; ?>&mode=all<?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>"
                                    class="claim-card glass-card rounded-2xl p-5 hover:cursor-pointer">
                                    <!-- Header -->
                                    <div class="flex justify-between items-start mb-4">
                                        <span class="font-mono font-bold text-orange-700 bg-orange-100 px-3 py-1 rounded-lg text-lg">
                                            #<?php echo $claim['id']; ?>
                                        </span>
                                        <?php
                                        if ($claim['status'] == 'pending') {
                                            echo '<span class="px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-semibold">🕐 รอ</span>';
                                        } elseif ($claim['status'] == 'fulfilled') {
                                            echo '<span class="px-2 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">✅</span>';
                                        } else {
                                            echo '<span class="px-2 py-1 rounded-full bg-red-100 text-red-600 text-xs font-semibold">❌</span>';
                                        }
                                        ?>
                                    </div>

                                    <!-- Customer Name -->
                                    <div class="mb-3">
                                        <div class="text-gray-500 text-xs mb-1">ลูกค้า</div>
                                        <div class="font-display font-bold text-orange-800 text-lg truncate">
                                            <?php echo htmlspecialchars($claim['username']); ?>
                                        </div>
                                    </div>

                                    <!-- Items -->
                                    <div class="flex items-center justify-between mb-3 bg-pink-50 rounded-xl p-3">
                                        <div>
                                            <span class="text-3xl">🍣</span>
                                            <span class="font-display font-bold text-pink-600 text-xl ml-2">
                                                <?php echo $claim['items_count']; ?> ชิ้น
                                            </span>
                                        </div>
                                        <div class="font-display font-bold text-red-600">
                                            -<?php echo $claim['points_used']; ?>
                                        </div>
                                    </div>

                                    <!-- Date -->
                                    <div class="text-orange-500 text-sm">
                                        <i class="far fa-clock mr-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($claim['claimed_at'])); ?>
                                    </div>

                                    <!-- Click to view -->
                                    <div class="mt-3 pt-3 border-t border-orange-100 text-center text-orange-500 text-sm font-semibold">
                                        <i class="fas fa-eye mr-1"></i> กดเพื่อดูรายละเอียด
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-8 flex justify-center items-center gap-2">
                        <?php
                        // สร้าง URL สำหรับ pagination
                        $base_url = "?status={$status_filter}&mode={$view_mode}";
                        if ($search_query) {
                            $base_url .= "&search=" . urlencode($search_query);
                        }

                        // ปุ่มย้อนกลับ
                        if ($current_page > 1):
                            ?>
                            <a href="<?php echo $base_url; ?>&page=<?php echo $current_page - 1; ?>"
                                class="px-4 py-2 rounded-lg bg-orange-100 text-orange-600 font-semibold hover:bg-orange-200 transition-all">
                                <i class="fas fa-chevron-left"></i> ย้อนกลับ
                            </a>
                        <?php else: ?>
                            <span class="px-4 py-2 rounded-lg bg-gray-100 text-gray-400 font-semibold cursor-not-allowed">
                                <i class="fas fa-chevron-left"></i> ย้อนกลับ
                            </span>
                        <?php endif; ?>

                        <!-- หมายเลขหน้า -->
                        <div class="flex gap-1">
                            <?php
                            // แสดงหน้า 5 หน้าก่อนหน้า, หน้าปัจจุบัน, และ 5 หน้าถัดไป
                            $start_page = max(1, $current_page - 5);
                            $end_page = min($total_pages, $current_page + 5);

                            // แสดงหน้าแรก
                            if ($start_page > 1):
                                ?>
                                <a href="<?php echo $base_url; ?>&page=1"
                                    class="px-3 py-2 rounded-lg bg-white text-orange-600 font-semibold hover:bg-orange-100 transition-all">
                                    1
                                </a>
                                <?php if ($start_page > 2): ?>
                                    <span class="px-3 py-2 text-gray-400">...</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <?php if ($i == $current_page): ?>
                                    <span
                                        class="px-3 py-2 rounded-lg bg-gradient-to-r from-orange-500 to-orange-600 text-white font-bold">
                                        <?php echo $i; ?>
                                    </span>
                                <?php else: ?>
                                    <a href="<?php echo $base_url; ?>&page=<?php echo $i; ?>"
                                        class="px-3 py-2 rounded-lg bg-white text-orange-600 font-semibold hover:bg-orange-100 transition-all">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <!-- แสดงหน้าสุดท้าย -->
                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <span class="px-3 py-2 text-gray-400">...</span>
                                <?php endif; ?>
                                <a href="<?php echo $base_url; ?>&page=<?php echo $total_pages; ?>"
                                    class="px-3 py-2 rounded-lg bg-white text-orange-600 font-semibold hover:bg-orange-100 transition-all">
                                    <?php echo $total_pages; ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- ปุ่มถัดไป -->
                        <?php if ($current_page < $total_pages): ?>
                            <a href="<?php echo $base_url; ?>&page=<?php echo $current_page + 1; ?>"
                                class="px-4 py-2 rounded-lg bg-orange-100 text-orange-600 font-semibold hover:bg-orange-200 transition-all">
                                ถัดไป <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="px-4 py-2 rounded-lg bg-gray-100 text-gray-400 font-semibold cursor-not-allowed">
                                ถัดไป <i class="fas fa-chevron-right"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="text-center mt-8 text-orange-400 text-sm">
            <p>🍣 ซูชิละกัน - Shop Panel</p>
        </div>

    </div>

    <?php
    if (isset($conn) && $conn) {
        $conn->close();
    }
    ?>
</body>

</html>