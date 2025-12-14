<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'admin_auth.php';

// ตรวจสอบการ login
if (!requireAdminLogin()) {
    header('Location: formmenu');
    exit;
}

require_once 'db.php';

// จัดการ Actions
$message = '';
$messageType = '';

// เพิ่ม Admin User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $username = trim($_POST['new_username']);
    $password = $_POST['new_password'];
    $fullName = trim($_POST['new_fullname']);

    if (!empty($username) && !empty($password) && !empty($fullName)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admin_users (username, password, full_name) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashedPassword, $fullName);

        if ($stmt->execute()) {
            $message = "เพิ่มผู้ดูแลระบบ '$username' สำเร็จ!";
            $messageType = 'success';
        } else {
            $message = "เกิดข้อผิดพลาด: " . $conn->error;
            $messageType = 'error';
        }
        $stmt->close();
    } else {
        $message = "กรุณากรอกข้อมูลให้ครบถ้วน";
        $messageType = 'error';
    }
}

// ลบ Admin User
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);

    // ตรวจสอบว่ามี admin มากกว่า 1 คน
    $countResult = $conn->query("SELECT COUNT(*) as count FROM admin_users");
    $count = $countResult->fetch_assoc()['count'];

    if ($count > 1) {
        $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
        $stmt->bind_param("i", $deleteId);

        if ($stmt->execute()) {
            $message = "ลบผู้ดูแลระบบสำเร็จ!";
            $messageType = 'success';
        } else {
            $message = "เกิดข้อผิดพลาด: " . $conn->error;
            $messageType = 'error';
        }
        $stmt->close();
    } else {
        $message = "ไม่สามารถลบผู้ดูแลระบบคนสุดท้ายได้!";
        $messageType = 'error';
    }
}

// เปลี่ยนรหัสผ่าน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $userId = intval($_POST['user_id']);
    $newPassword = $_POST['new_password_change'];

    if (!empty($newPassword)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);

        if ($stmt->execute()) {
            $message = "เปลี่ยนรหัสผ่านสำเร็จ!";
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

// แก้ไขข้อมูล Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_admin'])) {
    $userId = intval($_POST['edit_user_id']);
    $fullName = trim($_POST['edit_fullname']);

    if (!empty($fullName)) {
        $stmt = $conn->prepare("UPDATE admin_users SET full_name = ? WHERE id = ?");
        $stmt->bind_param("si", $fullName, $userId);

        if ($stmt->execute()) {
            $message = "แก้ไขข้อมูลสำเร็จ!";
            $messageType = 'success';
        } else {
            $message = "เกิดข้อผิดพลาด: " . $conn->error;
            $messageType = 'error';
        }
        $stmt->close();
    } else {
        $message = "กรุณากรอกข้อมูลให้ครบถ้วน";
        $messageType = 'error';
    }
}

// ดึงข้อมูล Admin Users ทั้งหมด
$admins = $conn->query("SELECT id, username, full_name, created_at, last_login FROM admin_users ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>👥 จัดการผู้ดูแลระบบ | Admin Management</title>
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
            background: linear-gradient(-45deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #4facfe 75%, #00f2fe 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2),
                0 0 0 1px rgba(255, 255, 255, 0.5);
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
            filter: drop-shadow(0 10px 20px rgba(102, 126, 234, 0.4));
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
            background: linear-gradient(135deg, #667eea, #764ba2);
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

        .section-title {
            font-family: 'Prompt', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
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
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
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
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
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
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
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

        .admin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .admin-table thead th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 18px 15px;
            font-family: 'Prompt', sans-serif;
            font-weight: 600;
            font-size: 1.05rem;
            text-align: left;
        }

        .admin-table thead th:first-child {
            border-radius: 15px 0 0 15px;
        }

        .admin-table thead th:last-child {
            border-radius: 0 15px 15px 0;
        }

        .admin-table tbody tr {
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .admin-table tbody tr:hover {
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }

        .admin-table tbody td {
            padding: 20px 15px;
            vertical-align: middle;
        }

        .admin-table tbody td:first-child {
            border-radius: 15px 0 0 15px;
        }

        .admin-table tbody td:last-child {
            border-radius: 0 15px 15px 0;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }

        .badge-gray {
            background: linear-gradient(135deg, #e9ecef, #dee2e6);
            color: #495057;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
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
            color: #667eea;
            margin-bottom: 25px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .glass-card {
                padding: 25px 20px;
            }

            .page-title {
                font-size: 2rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .admin-table {
                display: block;
                overflow-x: auto;
            }

            .actions {
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
                <div class="page-icon">👥</div>
                <h1 class="page-title">จัดการผู้ดูแลระบบ</h1>
                <p class="page-subtitle">Admin User Management</p>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i
                        class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Add New Admin Form -->
            <div class="section-title">
                <i class="fas fa-user-plus"></i>
                เพิ่มผู้ดูแลระบบใหม่
            </div>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" name="new_username" class="form-input" placeholder="username" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">รหัสผ่าน</label>
                        <input type="password" name="new_password" class="form-input" placeholder="••••••••" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" name="new_fullname" class="form-input" placeholder="ชื่อเต็ม" required>
                    </div>
                </div>
                <button type="submit" name="add_admin" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i>
                    เพิ่มผู้ดูแลระบบ
                </button>
            </form>
        </div>

        <!-- Admin Users List -->
        <div class="glass-card">
            <div class="section-title">
                <i class="fas fa-users"></i>
                รายการผู้ดูแลระบบ
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ชื่อผู้ใช้</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>วันที่สร้าง</th>
                        <th>เข้าสู่ระบบล่าสุด</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($admin = $admins->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo $admin['id']; ?></strong></td>
                            <td>
                                <i class="fas fa-user-shield" style="color: #667eea; margin-right: 8px;"></i>
                                <strong><?php echo htmlspecialchars($admin['username']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                            <td>
                                <i class="fas fa-calendar-plus" style="color: #999; margin-right: 6px;"></i>
                                <?php echo date('d/m/Y H:i', strtotime($admin['created_at'])); ?>
                            </td>
                            <td>
                                <?php if ($admin['last_login']): ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-check"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($admin['last_login'])); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-gray">
                                        <i class="fas fa-minus"></i>
                                        ยังไม่เคยเข้าสู่ระบบ
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <button
                                        onclick="openEditModal(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['full_name']); ?>')"
                                        class="btn btn-secondary btn-sm">
                                        <i class="fas fa-edit"></i> แก้ไข
                                    </button>
                                    <button onclick="openPasswordModal(<?php echo $admin['id']; ?>)"
                                        class="btn btn-primary btn-sm">
                                        <i class="fas fa-key"></i> เปลี่ยนรหัส
                                    </button>
                                    <a href="?delete_id=<?php echo $admin['id']; ?>"
                                        onclick="return confirm('ต้องการลบผู้ดูแลระบบนี้หรือไม่?')"
                                        class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> ลบ
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Back Button -->
        <div style="text-align: center;">
            <a href="formmenu" class="btn btn-back">
                <i class="fas fa-arrow-left"></i>
                กลับเมนูหลัก
            </a>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-edit"></i> แก้ไขข้อมูล
            </div>
            <form method="POST" action="">
                <input type="hidden" name="edit_user_id" id="edit_user_id">
                <div class="form-group">
                    <label class="form-label">ชื่อ-นามสกุล</label>
                    <input type="text" name="edit_fullname" id="edit_fullname" class="form-input" required>
                </div>
                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <button type="button" onclick="closeModal('editModal')" class="btn btn-secondary" style="flex: 1;">
                        ยกเลิก
                    </button>
                    <button type="submit" name="edit_admin" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-save"></i> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Password Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-key"></i> เปลี่ยนรหัสผ่าน
            </div>
            <form method="POST" action="">
                <input type="hidden" name="user_id" id="password_user_id">
                <div class="form-group">
                    <label class="form-label">รหัสผ่านใหม่</label>
                    <input type="password" name="new_password_change" class="form-input" placeholder="••••••••"
                        required>
                </div>
                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <button type="button" onclick="closeModal('passwordModal')" class="btn btn-secondary"
                        style="flex: 1;">
                        ยกเลิก
                    </button>
                    <button type="submit" name="change_password" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-check"></i> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, fullName) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_fullname').value = fullName;
            document.getElementById('editModal').classList.add('active');
        }

        function openPasswordModal(id) {
            document.getElementById('password_user_id').value = id;
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
    </script>
</body>

</html>