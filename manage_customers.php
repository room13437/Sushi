<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'admin_auth.php';

// ตรวจสอบการ login
if (!requireAdminLogin()) {
    header('Location: admin_login');
    exit;
}

require_once 'db_config.php';

// จัดการ Actions
$message = '';
$messageType = '';

// แก้ไขชื่อผู้ใช้
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_username'])) {
    $userId = intval($_POST['user_id']);
    $newUsername = trim($_POST['new_username']);

    if (!empty($newUsername)) {
        // ตรวจสอบชื่อซ้ำ
        $checkStmt = $conn->prepare("SELECT id FROM customers WHERE username = ? AND id != ?");
        $checkStmt->bind_param("si", $newUsername, $userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $message = "ชื่อผู้ใช้นี้ถูกใช้งานแล้ว!";
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("UPDATE customers SET username = ? WHERE id = ?");
            $stmt->bind_param("si", $newUsername, $userId);

            if ($stmt->execute()) {
                $message = "แก้ไขชื่อผู้ใช้สำเร็จ!";
                $messageType = 'success';
            } else {
                $message = "เกิดข้อผิดพลาด: " . $conn->error;
                $messageType = 'error';
            }
            $stmt->close();
        }
        $checkStmt->close();
    } else {
        $message = "กรุณากรอกชื่อผู้ใช้";
        $messageType = 'error';
    }
}

// แก้ไขคะแนน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_points'])) {
    $userId = intval($_POST['user_id']);
    $newPoints = intval($_POST['new_points']);

    $stmt = $conn->prepare("UPDATE customers SET points = ? WHERE id = ?");
    $stmt->bind_param("ii", $newPoints, $userId);

    if ($stmt->execute()) {
        $message = "แก้ไขคะแนนสำเร็จ!";
        $messageType = 'success';
    } else {
        $message = "เกิดข้อผิดพลาด: " . $conn->error;
        $messageType = 'error';
    }
    $stmt->close();
}

// รีเซ็ตรหัสผ่านลูกค้า
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $userId = intval($_POST['user_id']);
    $newPassword = $_POST['new_password'];

    if (!empty($newPassword)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE customers SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);

        if ($stmt->execute()) {
            $message = "รีเซ็ตรหัสผ่านสำเร็จ!";
            $messageType = 'success';
        } else {
            $message = "เกิดข้อผิดพลาด: " . $conn->error;
            $messageType = 'error';
        }
        $stmt->close();
    } else {
        $message = "กรุณากรอกรหัสผ่านใหม่";
        $messageType = 'error';
    }
}

// เริ่ม session สำหรับเก็บข้อความ (เช็คก่อนว่า start แล้วหรือยัง)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ลบลูกค้า (ใช้ POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_customer'])) {
    $deleteId = intval($_POST['customer_id']);

    // เริ่ม transaction
    $conn->begin_transaction();

    try {
        // ลบข้อมูลที่เกี่ยวข้องก่อน
        $relatedTables = [
            'redemption_history',
            'reward_claims',
            'code_redemptions',
            'gacha_history',
            'queue_reservations',
            'daily_logins'  // เพิ่มตารางนี้ด้วย (ถ้ายังมี)
        ];

        foreach ($relatedTables as $table) {
            // ตรวจสอบว่าตารางมีอยู่จริงก่อนลบ
            $checkTable = $conn->query("SHOW TABLES LIKE '$table'");
            if ($checkTable && $checkTable->num_rows > 0) {
                $sql = "DELETE FROM $table WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("i", $deleteId);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // ลบลูกค้า
        $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->bind_param("i", $deleteId);

        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['delete_message'] = "ลบลูกค้าและข้อมูลที่เกี่ยวข้องสำเร็จ!";
            $_SESSION['delete_type'] = 'success';
        } else {
            throw new Exception($conn->error);
        }
        $stmt->close();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['delete_message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        $_SESSION['delete_type'] = 'error';
    }

    // Redirect to clean URL
    header('Location: manage_customers');
    exit();
}

// แสดงข้อความจาก session
if (isset($_SESSION['delete_message'])) {
    $message = $_SESSION['delete_message'];
    $messageType = $_SESSION['delete_type'];
    unset($_SESSION['delete_message']);
    unset($_SESSION['delete_type']);
}

// ค้นหาลูกค้า
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchParam = "%{$search}%";

// Pagination
$perPage = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;


// นับจำนวนลูกค้าทั้งหมด
if (!empty($search)) {
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM customers WHERE username LIKE ?");
    if (!$countStmt) {
        die("Prepare failed: " . $conn->error);
    }
    $countStmt->bind_param("s", $searchParam);
} else {
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM customers");
    if (!$countStmt) {
        die("Prepare failed: " . $conn->error);
    }
}
$countStmt->execute();
$totalCustomers = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ceil($totalCustomers / $perPage);

// ดึงข้อมูลลูกค้า (เพิ่ม full_name, email, phone เพื่อแสดงผล)
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT id, username, full_name, password, points, email, phone FROM customers WHERE username LIKE ? ORDER BY id ASC LIMIT ? OFFSET ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("sii", $searchParam, $perPage, $offset);
} else {
    $stmt = $conn->prepare("SELECT id, username, full_name, password, points, email, phone FROM customers ORDER BY id ASC LIMIT ? OFFSET ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ii", $perPage, $offset);
}
$stmt->execute();
$customers = $stmt->get_result();
$stmt->close();

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>👤 จัดการลูกค้า | Customer Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #FFF9F0 0%, #FFEDD5 30%, #FED7AA 60%, #FDBA74 100%);
            background-attachment: fixed;
            min-height: 100vh;
            padding: 20px;
        }

        @keyframes gradientShift {
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

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 40px;
            border: 1px solid rgba(249, 115, 22, 0.15);
            box-shadow: 0 20px 40px rgba(249, 115, 22, 0.1);
            margin-bottom: 30px;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-icon {
            font-size: 4.5rem;
            margin-bottom: 15px;
            animation: float 3s ease-in-out infinite;
            filter: drop-shadow(0 10px 20px rgba(249, 115, 22, 0.2));
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

        .page-title {
            font-family: 'Prompt', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #F97316, #EA580C);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: #666;
            font-size: 1.1rem;
        }

        .alert {
            padding: 18px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.05rem;
            font-weight: 500;
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 2px solid #28a745;
        }

        .alert-error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 2px solid #dc3545;
        }

        .search-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .search-input {
            flex: 1;
            padding: 14px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Sarabun', sans-serif;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .search-input:focus {
            outline: none;
            border-color: #F97316;
            background: white;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 12px;
            font-family: 'Prompt', sans-serif;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #F97316, #C2410C);
            color: white;
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(249, 115, 22, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 6px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 6px 15px rgba(245, 158, 11, 0.3);
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(245, 158, 11, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
            box-shadow: 0 6px 15px rgba(255, 107, 107, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 107, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #C2410C;
            border: 2px solid #C2410C;
        }

        .btn-secondary:hover {
            background: #C2410C;
            color: white;
        }

        .btn-back {
            background: linear-gradient(135deg, #868686, #434343);
            color: white;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
        }

        .customer-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .customer-table thead th {
            background: linear-gradient(135deg, #F97316, #EA580C);
            color: white;
            padding: 18px 12px;
            font-family: 'Prompt', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            text-align: left;
        }

        .customer-table thead th:first-child {
            border-radius: 15px 0 0 15px;
        }

        .customer-table thead th:last-child {
            border-radius: 0 15px 15px 0;
        }

        .customer-table tbody tr {
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .customer-table tbody tr:hover {
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.15);
            transform: translateY(-2px);
        }

        .customer-table tbody td {
            padding: 15px 12px;
            vertical-align: middle;
            font-size: 0.95rem;
        }

        .customer-table tbody td:first-child {
            border-radius: 15px 0 0 15px;
        }

        .customer-table tbody td:last-child {
            border-radius: 0 15px 15px 0;
        }

        .points-badge {
            background: linear-gradient(135deg, #ffd700, #ffca28);
            color: #333;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .password-hash {
            font-family: monospace;
            font-size: 0.75rem;
            color: #999;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.3s;
        }

        .password-hash:hover {
            color: #F97316;
        }

        .actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 25px;
            padding: 35px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.4s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-header {
            font-family: 'Prompt', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: #C2410C;
            margin-bottom: 25px;
            text-align: center;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }

        .form-input {
            padding: 14px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Sarabun', sans-serif;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-input:focus {
            outline: none;
            border-color: #F97316;
            background: white;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination a,
        .pagination span {
            padding: 10px 16px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .pagination a {
            background: white;
            color: #C2410C;
            text-decoration: none;
            border: 2px solid #C2410C;
        }

        .pagination a:hover {
            background: #C2410C;
            color: white;
        }

        .pagination .current {
            background: linear-gradient(135deg, #F97316, #C2410C);
            color: white;
            border: 2px solid transparent;
        }

        @media (max-width: 768px) {
            .glass-card {
                padding: 25px 20px;
            }

            .page-title {
                font-size: 2rem;
            }

            .customer-table {
                display: block;
                overflow-x: auto;
            }

            .actions {
                flex-direction: column;
            }

            .search-bar {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="glass-card">
            <div class="page-header">
                <div class="page-icon">👤</div>
                <h1 class="page-title">จัดการลูกค้า</h1>
                <p class="page-subtitle">Customer Management - ครบทุกฟีเจอร์</p>
                <a href="formmenu" class="btn btn-back" style="margin-top: 20px;">
                    <i class="fas fa-arrow-left"></i> กลับเมนูหลัก
                </a>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i
                        class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Search Bar -->
            <form method="GET" action="" class="search-bar">
                <input type="text" name="search" class="search-input" placeholder="🔍 ค้นหาชื่อลูกค้า..."
                    value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    ค้นหา
                </button>
                <?php if (!empty($search)): ?>
                    <a href="manage_customers" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        ล้าง
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Customer List -->
        <div class="glass-card">
            <h2
                style="font-family: 'Prompt', sans-serif; font-size: 1.8rem; font-weight: 700; color: #C2410C; margin-bottom: 25px; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-users text-orange-500"></i>
                รายการลูกค้า (<?php echo number_format($totalCustomers); ?> คน)
            </h2>
            <div style="overflow-x: auto;">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ชื่อผู้ใช้</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>อีเมล</th>
                            <th>เบอร์โทรศัพท์</th>
                            <th>คะแนน</th>
                            <th>รหัสผ่าน</th>
                            <th style="min-width: 350px;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($customers->num_rows > 0): ?>
                            <?php while ($customer = $customers->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo $customer['id']; ?></strong></td>
                                    <td>
                                        <i class="fas fa-user-circle" style="color: #F97316; margin-right: 8px;"></i>
                                        <strong><?php echo htmlspecialchars($customer['username']); ?></strong>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: #1F2937;">
                                            <?php echo htmlspecialchars($customer['full_name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.85rem; color: #4B5563;">
                                            <i class="fas fa-envelope" style="color: #3B82F6; margin-right: 5px;"></i>
                                            <?php echo htmlspecialchars($customer['email']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.85rem; color: #4B5563;">
                                            <i class="fas fa-phone" style="color: #10B981; margin-right: 5px;"></i>
                                            <?php echo htmlspecialchars($customer['phone']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="points-badge">
                                            <i class="fas fa-star"></i>
                                            <?php echo number_format($customer['points']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="password-hash"
                                            title="<?php echo htmlspecialchars($customer['password']); ?>"
                                            onclick="copyToClipboard('<?php echo htmlspecialchars($customer['password']); ?>')">
                                            <i class="fas fa-lock"></i>
                                            <?php echo substr($customer['password'], 0, 10); ?>...
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button
                                                onclick="openEditUsernameModal(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['username']); ?>')"
                                                class="btn btn-success btn-sm">
                                                <i class="fas fa-user-edit"></i> แก้ไขชื่อ
                                            </button>
                                            <button
                                                onclick="openEditPointsModal(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['username']); ?>', <?php echo $customer['points']; ?>)"
                                                class="btn btn-warning btn-sm">
                                                <i class="fas fa-coins"></i> แก้ไขคะแนน
                                            </button>
                                            <button
                                                onclick="openPasswordModal(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['username']); ?>')"
                                                class="btn btn-primary btn-sm">
                                                <i class="fas fa-key"></i> รีเซ็ตรหัส
                                            </button>
                                            <form method="POST" style="display: inline;"
                                                onsubmit="return confirm('ต้องการลบลูกค้านี้หรือไม่? การดำเนินการนี้จะลบข้อมูลทั้งหมดของลูกค้า')">
                                                <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                <button type="submit" name="delete_customer" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i> ลบ
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                                    <i class="fas fa-inbox"
                                        style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                                    ไม่พบข้อมูลลูกค้า
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a
                            href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                        if ($i == $page):
                            ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a
                            href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modals -->

    <!-- Edit Username Modal -->
    <div id="editUsernameModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-user-edit"></i> แก้ไขชื่อผู้ใช้
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="edit_username_id">
                <input type="hidden" name="edit_username" value="1">
                <div class="form-group">
                    <label class="form-label">ชื่อผู้ใช้ใหม่</label>
                    <input type="text" name="new_username" id="edit_new_username" class="form-input" required>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" style="flex: 1;"
                        onclick="closeModal('editUsernameModal')">ยกเลิก</button>
                    <button type="submit" class="btn btn-success" style="flex: 1;">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Points Modal -->
    <div id="editPointsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-coins"></i> แก้ไขคะแนน
            </div>
            <p id="edit_points_username" style="text-align: center; margin-bottom: 20px; color: #666;"></p>
            <form method="POST">
                <input type="hidden" name="user_id" id="edit_points_id">
                <input type="hidden" name="edit_points" value="1">
                <div class="form-group">
                    <label class="form-label">คะแนนใหม่</label>
                    <input type="number" name="new_points" id="edit_new_points" class="form-input" required>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" style="flex: 1;"
                        onclick="closeModal('editPointsModal')">ยกเลิก</button>
                    <button type="submit" class="btn btn-warning" style="flex: 1;">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Password Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-key"></i> รีเซ็ตรหัสผ่าน
            </div>
            <p id="reset_pass_username" style="text-align: center; margin-bottom: 20px; color: #666;"></p>
            <form method="POST">
                <input type="hidden" name="user_id" id="reset_pass_id">
                <input type="hidden" name="reset_password" value="1">
                <div class="form-group">
                    <label class="form-label">รหัสผ่านใหม่</label>
                    <input type="text" name="new_password" class="form-input" required placeholder="กรอกรหัสผ่านใหม่">
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" style="flex: 1;"
                        onclick="closeModal('passwordModal')">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditUsernameModal(id, username) {
            document.getElementById('edit_username_id').value = id;
            document.getElementById('edit_new_username').value = username;
            document.getElementById('editUsernameModal').classList.add('active');
        }

        function openEditPointsModal(id, username, points) {
            document.getElementById('edit_points_id').value = id;
            document.getElementById('edit_points_username').textContent = 'ลูกค้า: ' + username;
            document.getElementById('edit_new_points').value = points;
            document.getElementById('editPointsModal').classList.add('active');
        }

        function openPasswordModal(id, username) {
            document.getElementById('reset_pass_id').value = id;
            document.getElementById('reset_pass_username').textContent = 'ลูกค้า: ' + username;
            document.getElementById('passwordModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function () {
                alert('Copy รหัสผ่านแล้ว: ' + text);
            }, function (err) {
                console.error('Async: Could not copy text: ', err);
            });
        }
    </script>
</body>

</html>