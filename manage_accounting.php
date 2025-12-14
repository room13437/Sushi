<?php
require_once 'protect_admin.php'; //ป้องกันด้วย MySQL Authentication
require_once 'db_config.php';

// ตั้งค่า locale สำหรับภาษาไทย
date_default_timezone_set('Asia/Bangkok');

// --- SELF-HEALING: Check if table exists, if not create it ---
$checkTable = $conn->query("SHOW TABLES LIKE 'accounting'");
if ($checkTable->num_rows == 0) {
    $sql = "CREATE TABLE IF NOT EXISTS accounting (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_date DATE NOT NULL,
        transaction_type ENUM('income', 'expense') NOT NULL,
        category VARCHAR(100) NOT NULL,
        description VARCHAR(255),
        amount DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_date (transaction_date),
        INDEX idx_type (transaction_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->query($sql);
}
// -----------------------------------------------------------

// กำหนดวันที่ที่จะแสดง (default = วันนี้)
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// จัดการ POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        // เพิ่มรายการใหม่
        $stmt = $conn->prepare("INSERT INTO accounting (transaction_date, transaction_type, category, description, amount) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssd", $_POST['transaction_date'], $_POST['transaction_type'], $_POST['category'], $_POST['description'], $_POST['amount']);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: manage_accounting?date=" . $_POST['transaction_date']);
        exit;
    } elseif ($action === 'edit') {
        // แก้ไขรายการ
        $stmt = $conn->prepare("UPDATE accounting SET transaction_date=?, transaction_type=?, category=?, description=?, amount=? WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("ssssdi", $_POST['transaction_date'], $_POST['transaction_type'], $_POST['category'], $_POST['description'], $_POST['amount'], $_POST['id']);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: manage_accounting?date=" . $_POST['transaction_date']);
        exit;
    } elseif ($action === 'delete') {
        // ลบรายการ
        $stmt = $conn->prepare("DELETE FROM accounting WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("i", $_POST['id']);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: manage_accounting?date=" . $selectedDate);
        exit;
    }
}

// ดึงข้อมูลรายการของวันที่เลือก
$stmt = $conn->prepare("SELECT * FROM accounting WHERE transaction_date = ? ORDER BY created_at DESC");
if ($stmt === false) {
    die("MySQL Error: " . $conn->error);
}
$stmt->bind_param("s", $selectedDate);
$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// คำนวณยอดรวม
$totalIncome = 0;
$totalExpense = 0;
foreach ($transactions as $t) {
    if ($t['transaction_type'] === 'income') {
        $totalIncome += $t['amount'];
    } else {
        $totalExpense += $t['amount'];
    }
}
$balance = $totalIncome - $totalExpense;

// คำนวณยอดรวมทั้งหมด (all time)
$allTimeResult = $conn->query("SELECT 
    SUM(CASE WHEN transaction_type='income' THEN amount ELSE 0 END) as total_income,
    SUM(CASE WHEN transaction_type='expense' THEN amount ELSE 0 END) as total_expense
    FROM accounting");
$allTime = $allTimeResult->fetch_assoc();
$allTimeIncome = $allTime['total_income'] ?? 0;
$allTimeExpense = $allTime['total_expense'] ?? 0;
$allTimeBalance = $allTimeIncome - $allTimeExpense;

// วันก่อนหน้า/ถัดไป
$prevDate = date('Y-m-d', strtotime($selectedDate . ' -1 day'));
$nextDate = date('Y-m-d', strtotime($selectedDate . ' +1 day'));
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>💰 จัดการบัญชี | Premium Accounting</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&family=Prompt:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Three.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="js/three_bg.js"></script>

    <!-- jsPDF for PDF Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

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
        :root {
            --primary-gradient: linear-gradient(135deg, #FF6F00 0%, #FF8F00 100%);
            --income-gradient: linear-gradient(135deg, #00b09b 0%, #96c93d 100%);
            --expense-gradient: linear-gradient(135deg, #ff5f6d 0%, #ffc371 100%);
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: 1px solid rgba(255, 255, 255, 0.5);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(-45deg, #fff3e0, #ffe0b2, #ffccbc, #fff3e0);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            color: #2d3748;
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

        h1,
        h2,
        h3,
        .font-prompt {
            font-family: 'Prompt', sans-serif;
        }

        /* Glass Card */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: var(--glass-border);
            box-shadow: var(--glass-shadow);
            border-radius: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        /* Summary Cards with 3D effect */
        .summary-card {
            border-radius: 20px;
            color: white;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.2);
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.3);
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 60%);
            transform: rotate(45deg);
            pointer-events: none;
        }

        .bg-income {
            background: var(--income-gradient);
        }

        .bg-expense {
            background: var(--expense-gradient);
        }

        .bg-balance {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .bg-alltime {
            background: linear-gradient(135deg, #2c3e50 0%, #4ca1af 100%);
        }

        /* Floating Action Button for Add */
        .fab-add {
            background: var(--primary-gradient);
            box-shadow: 0 10px 25px rgba(255, 111, 0, 0.4);
            border: none;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .fab-add:hover {
            transform: scale(1.05) translateY(-3px);
            box-shadow: 0 15px 35px rgba(255, 111, 0, 0.5);
        }

        /* Table Styling */
        .custom-table thead th {
            font-family: 'Prompt', sans-serif;
            background: rgba(243, 244, 246, 0.8);
            color: #4a5568;
            padding: 16px;
            font-weight: 600;
        }

        .custom-table tbody tr {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: background 0.2s;
        }

        .custom-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.6);
        }

        .custom-table td {
            padding: 16px;
            vertical-align: middle;
        }

        /* Badges */
        .badge {
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-income {
            background-color: #e6fffa;
            color: #00b09b;
        }

        .badge-expense {
            background-color: #fff5f5;
            color: #ff5f6d;
        }

        /* Inputs */
        .glass-input {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .glass-input:focus {
            border-color: #FF8F00;
            box-shadow: 0 0 0 3px rgba(255, 143, 0, 0.2);
            outline: none;
        }

        /* Modal Animation */
        .modal-bg {
            background-color: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(5px);
        }

        .modal-content-anim {
            animation: modalPop 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes modalPop {
            0% {
                transform: scale(0.9);
                opacity: 0;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Navigation Buttons */
        .nav-btn {
            background: white;
            color: #4a5568;
            border-radius: 50px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.2s;
        }

        .nav-btn:hover {
            background: #fff8e1;
            transform: translateY(-2px);
            color: #FF6F00;
        }
    </style>
</head>

<body class="p-4 md:p-8">

    <div class="max-w-6xl mx-auto" id="main-content">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <a href="formmenu"
                class="group flex items-center gap-2 px-6 py-3 bg-white/80 rounded-full shadow-sm hover:shadow-md transition-all text-gray-700 font-prompt font-medium">
                <i class="fas fa-arrow-left text-orange-500 group-hover:-translate-x-1 transition-transform"></i>
                กลับเมนูหลัก
            </a>

            <div class="glass-card px-8 py-4 flex items-center gap-4">
                <div
                    class="w-12 h-12 rounded-full bg-gradient-to-tr from-orange-400 to-red-500 flex items-center justify-center text-white shadow-lg">
                    <i class="fas fa-calculator text-xl"></i>
                </div>
                <div>
                    <h1
                        class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-orange-600 to-red-600">
                        ระบบบัญชีรายวัน
                    </h1>
                    <p class="text-sm text-gray-500 font-prompt">จัดการรายรับ-รายจ่ายของร้าน</p>
                </div>
            </div>

            <!-- Add Button (Desktop) -->
            <button onclick="openAddModal()"
                class="hidden md:flex fab-add items-center gap-2 px-6 py-3 rounded-full text-white font-prompt font-bold">
                <i class="fas fa-plus"></i> เพิ่มรายการ
            </button>
        </div>

        <!-- Date Navigation -->
        <div class="flex items-center justify-center gap-4 mb-8">
            <a href="?date=<?= $prevDate ?>" class="nav-btn px-4 py-2"><i class="fas fa-chevron-left"></i></a>

            <div class="glass-card px-6 py-2 flex items-center gap-3">
                <i class="far fa-calendar-alt text-orange-500"></i>
                <input type="date" value="<?= $selectedDate ?>" onchange="window.location='?date='+this.value"
                    class="bg-transparent border-none focus:ring-0 font-prompt font-bold text-gray-700 text-lg cursor-pointer">
            </div>

            <a href="?date=<?= $nextDate ?>" class="nav-btn px-4 py-2"><i class="fas fa-chevron-right"></i></a>

            <?php if ($selectedDate !== $today): ?>
                <a href="?date=<?= $today ?>"
                    class="ml-2 px-4 py-2 bg-orange-100 text-orange-600 rounded-full font-bold hover:bg-orange-200 transition-colors text-sm font-prompt">
                    วันนี้
                </a>
            <?php endif; ?>

            <!-- PDF Download Button -->
            <button onclick="generatePDF()"
                class="ml-2 px-4 py-2 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-full font-bold hover:from-red-600 hover:to-pink-600 transition-all text-sm font-prompt shadow-lg hover:shadow-xl transform hover:scale-105">
                <i class="fas fa-file-pdf"></i> ดาวน์โหลด PDF
            </button>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Income -->
            <div class="summary-card bg-income">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-white/80 font-prompt text-sm">รายรับวันนี้</p>
                        <h3 class="text-3xl font-bold mt-1">฿<?= number_format($totalIncome, 2) ?></h3>
                    </div>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                </div>
            </div>

            <!-- Expense -->
            <div class="summary-card bg-expense">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-white/80 font-prompt text-sm">รายจ่ายวันนี้</p>
                        <h3 class="text-3xl font-bold mt-1">฿<?= number_format($totalExpense, 2) ?></h3>
                    </div>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                </div>
            </div>

            <!-- Balance -->
            <div class="summary-card bg-balance">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-white/80 font-prompt text-sm">คงเหลือวันนี้</p>
                        <h3 class="text-3xl font-bold mt-1">฿<?= number_format($balance, 2) ?></h3>
                    </div>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>

            <!-- All Time -->
            <div class="summary-card bg-alltime">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-white/80 font-prompt text-sm">ยอดรวมทั้งหมด</p>
                        <h3 class="text-3xl font-bold mt-1">฿<?= number_format($allTimeBalance, 2) ?></h3>
                    </div>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions List -->
        <div class="glass-card p-6 md:p-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-700 flex items-center gap-2">
                    <i class="fas fa-list text-orange-500"></i>
                    รายการประจำวัน
                </h2>
                <div class="text-sm text-gray-500">
                    <?= count($transactions) ?> รายการ
                </div>
            </div>

            <?php if (empty($transactions)): ?>
                <div class="flex flex-col items-center justify-center py-16 text-gray-400">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-inbox text-3xl"></i>
                    </div>
                    <p class="font-prompt text-lg">ยังไม่มีรายการวันนี้</p>
                    <button onclick="openAddModal()"
                        class="mt-4 px-6 py-2 bg-blue-50 text-blue-600 rounded-full font-medium hover:bg-blue-100 transition-colors">
                        เริ่มบันทึกรายการแรก
                    </button>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full custom-table text-left">
                        <thead>
                            <tr class="rounded-lg overflow-hidden">
                                <th class="rounded-l-lg pl-6">เวลา/ประเภท</th>
                                <th>หมวดหมู่</th>
                                <th>รายละเอียด</th>
                                <th class="text-right">จำนวนเงิน</th>
                                <th class="rounded-r-lg text-center font-prompt">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600">
                            <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td class="pl-6">
                                        <div class="flex items-center gap-3">
                                            <div class="text-xs text-gray-400 font-mono">
                                                <?= date('H:i', strtotime($t['created_at'])) ?>
                                            </div>
                                            <?php if ($t['transaction_type'] === 'income'): ?>
                                                <span class="badge badge-income">
                                                    <i class="fas fa-plus bg-green-200 rounded-full p-1 text-[10px]"></i> รายรับ
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-expense">
                                                    <i class="fas fa-minus bg-red-200 rounded-full p-1 text-[10px]"></i> รายจ่าย
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="font-medium text-gray-800">
                                        <?= htmlspecialchars($t['category']) ?>
                                    </td>
                                    <td class="text-gray-500">
                                        <?= htmlspecialchars($t['description'] ?: '-') ?>
                                    </td>
                                    <td
                                        class="text-right font-bold text-lg <?= $t['transaction_type'] === 'income' ? 'text-green-600' : 'text-red-500' ?>">
                                        <?= $t['transaction_type'] === 'income' ? '+' : '-' ?>
                                        <?= number_format($t['amount'], 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($t)) ?>)"
                                                class="w-8 h-8 rounded-full bg-yellow-50 text-yellow-600 hover:bg-yellow-100 flex items-center justify-center transition-colors">
                                                <i class="fas fa-pen text-xs"></i>
                                            </button>
                                            <button onclick="confirmDelete(<?= $t['id'] ?>)"
                                                class="w-8 h-8 rounded-full bg-red-50 text-red-600 hover:bg-red-100 flex items-center justify-center transition-colors">
                                                <i class="fas fa-trash text-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Floating Action Button (Mobile Only) -->
    <button onclick="openAddModal()"
        class="md:hidden fixed bottom-6 right-6 w-14 h-14 rounded-full fab-add text-white text-xl shadow-lg flex items-center justify-center z-40">
        <i class="fas fa-plus"></i>
    </button>

    <!-- Modal Template (Add/Edit) -->
    <div id="modalOverlay" class="fixed inset-0 z-50 hidden items-center justify-center modal-bg"
        onclick="handleModalClick(event)">
        <div
            class="bg-white rounded-2xl shadow-2xl w-full max-w-md m-4 p-0 overflow-hidden modal-content-anim relative">

            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 id="modalTitle" class="text-lg font-bold text-gray-800 font-prompt">
                    <i class="fas fa-plus-circle text-orange-500 mr-2"></i> สร้างรายการใหม่
                </h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="p-6">
                <!-- Add Form -->
                <form id="accountingForm" method="POST" class="space-y-4">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="entryId">

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-sm font-semibold text-gray-600 pl-1">วันที่</label>
                            <input type="date" name="transaction_date" id="inputDate" required
                                class="w-full glass-input px-4 py-2 text-gray-700">
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm font-semibold text-gray-600 pl-1">ประเภท</label>
                            <select name="transaction_type" id="inputType" required
                                class="w-full glass-input px-4 py-2 text-gray-700">
                                <option value="income">💰 รายรับ</option>
                                <option value="expense">💸 รายจ่าย</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-sm font-semibold text-gray-600 pl-1">หมวดหมู่</label>
                        <div class="relative">
                            <i class="fas fa-tag absolute left-4 top-3 text-gray-400"></i>
                            <input type="text" name="category" id="inputCategory" placeholder="ระบุหมวดหมู่..." required
                                list="categoryList"
                                class="w-full glass-input pl-10 pr-4 py-2 text-gray-700 placeholder-gray-400">
                        </div>
                        <datalist id="categoryList">
                            <option value="ขายอาหาร">
                            <option value="ขายเครื่องดื่ม">
                            <option value="วัตถุดิบ">
                            <option value="ค่าน้ำ/ค่าไฟ">
                            <option value="ค่าเช่าที่">
                            <option value="เงินเดือน">
                            <option value="อื่นๆ">
                        </datalist>
                    </div>

                    <div class="space-y-1">
                        <label class="text-sm font-semibold text-gray-600 pl-1">จำนวนเงิน (บาท)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-2 text-gray-500 font-bold">฿</span>
                            <input type="number" name="amount" id="inputAmount" step="0.01" min="0.01"
                                placeholder="0.00" required
                                class="w-full glass-input pl-8 pr-4 py-2 text-xl font-bold text-gray-800 placeholder-gray-300">
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-sm font-semibold text-gray-600 pl-1">รายละเอียด (ถ้ามี)</label>
                        <textarea name="description" id="inputDescription" rows="2" placeholder="บันทึกเพิ่มเติม..."
                            class="w-full glass-input px-4 py-2 text-gray-700 resize-none"></textarea>
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="button" onclick="closeModal()"
                            class="flex-1 px-4 py-3 rounded-xl border border-gray-200 text-gray-600 font-bold hover:bg-gray-50 transition-colors">
                            ยกเลิก
                        </button>
                        <button type="submit"
                            class="flex-1 px-4 py-3 rounded-xl bg-gradient-to-r from-orange-500 to-red-500 text-white font-bold shadow-lg hover:shadow-orange-500/30 transition-all transform hover:-translate-y-1">
                            <i class="fas fa-save mr-2"></i> บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Hidden Delete Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <script>
        // ===== MODAL AND FORM LOGIC =====
        const modal = document.getElementById('modalOverlay');
        const form = document.getElementById('accountingForm');

        // Modal Logic
        function openAddModal() {
            resetForm();
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle text-orange-500 mr-2"></i> บันทึกรายการใหม่';
            document.getElementById('formAction').value = 'add';
            document.getElementById('inputDate').value = '<?= $selectedDate ?>';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function openEditModal(data) {
            resetForm();
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit text-yellow-500 mr-2"></i> แก้ไขรายการ';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('entryId').value = data.id;

            document.getElementById('inputDate').value = data.transaction_date;
            document.getElementById('inputType').value = data.transaction_type;
            document.getElementById('inputCategory').value = data.category;
            document.getElementById('inputAmount').value = data.amount;
            document.getElementById('inputDescription').value = data.description;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function handleModalClick(e) {
            if (e.target === modal) closeModal();
        }

        function resetForm() {
            form.reset();
            document.getElementById('entryId').value = '';
        }

        // Delete Logic
        function confirmDelete(id) {
            if (confirm('⚠️ คุณแน่ใจหรือไม่ที่จะลบรายการนี้?')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // ===== PDF GENERATION FUNCTION =====
        function generatePDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Add Thai Font Support (using default font for now, as custom fonts require base64 encoding)
            doc.setFont('helvetica');

            // Header
            doc.setFillColor(255, 111, 0); // Orange
            doc.rect(0, 0, 210, 40, 'F');

            doc.setTextColor(255, 255, 255);
            doc.setFontSize(24);
            doc.text('มารุซูชิ', 105, 15, { align: 'center' });

            doc.setFontSize(16);
            doc.text('Daily Accounting Report', 105, 25, { align: 'center' });

            doc.setFontSize(12);
            doc.text('<?= date('d/m/Y', strtotime($selectedDate)) ?>', 105, 33, { align: 'center' });

            // Reset text color
            doc.setTextColor(0, 0, 0);

            // Summary Section
            let yPos = 50;
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.text('Daily Summary', 14, yPos);

            yPos += 10;
            doc.setFontSize(11);
            doc.setFont('helvetica', 'normal');

            // Income
            doc.setTextColor(0, 176, 155);
            doc.text('Total Income:', 20, yPos);
            doc.text('<?= number_format($totalIncome, 2) ?> THB', 190, yPos, { align: 'right' });

            yPos += 7;
            // Expense
            doc.setTextColor(255, 95, 109);
            doc.text('Total Expense:', 20, yPos);
            doc.text('<?= number_format($totalExpense, 2) ?> THB', 190, yPos, { align: 'right' });

            yPos += 7;
            // Balance
            doc.setTextColor(0, 0, 0);
            doc.setFont('helvetica', 'bold');
            doc.text('Net Balance:', 20, yPos);
            doc.setTextColor(<?= $balance >= 0 ? '0, 128, 0' : '255, 0, 0' ?>);
            doc.text('<?= number_format($balance, 2) ?> THB', 190, yPos, { align: 'right' });

            yPos += 12;

            // Transactions Table
            doc.setTextColor(0, 0, 0);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(14);
            doc.text('Transaction Details', 14, yPos);

            yPos += 5;

            // Prepare table data
            const tableData = [
                <?php foreach ($transactions as $t): ?>
                    [
                    '<?= htmlspecialchars($t['category']) ?>',
                    '<?= $t['transaction_type'] === 'income' ? 'Income' : 'Expense' ?>',
                    '<?= htmlspecialchars($t['description']) ?>',
                    '<?= number_format($t['amount'], 2) ?> THB'
                    ],
                <?php endforeach; ?>
            ];

            // Create table
            doc.autoTable({
                startY: yPos,
                head: [['Category', 'Type', 'Description', 'Amount']],
                body: tableData,
                theme: 'striped',
                headStyles: {
                    fillColor: [255, 111, 0],
                    textColor: [255, 255, 255],
                    fontSize: 10,
                    fontStyle: 'bold'
                },
                bodyStyles: {
                    fontSize: 9
                },
                columnStyles: {
                    0: { cellWidth: 35 },
                    1: { cellWidth: 25 },
                    2: { cellWidth: 80 },
                    3: { cellWidth: 30, halign: 'right' }
                },
                didParseCell: function (data) {
                    // Color code the Type column
                    if (data.column.index === 1 && data.cell.section === 'body') {
                        if (data.cell.raw === 'Income') {
                            data.cell.styles.textColor = [0, 176, 155];
                        } else {
                            data.cell.styles.textColor = [255, 95, 109];
                        }
                    }
                }
            });

            // Footer
            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.setTextColor(128, 128, 128);
                doc.text(
                    'Generated on <?= date('d/m/Y H:i:s') ?> | Page ' + i + ' of ' + pageCount,
                    105,
                    290,
                    { align: 'center' }
                );
            }

            // Save PDF
            const fileName = 'Accounting_<?= date('Y-m-d', strtotime($selectedDate)) ?>.pdf';
            doc.save(fileName);
        }
    </script>
</body>

</html>