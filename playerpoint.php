<?php
ob_start();
session_start();
require_once 'admin_auth.php';
$isLoggedIn = requireAdminLogin();

// Database Connection
include "db.php";

// Handle Point Update
$update_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id']) && isset($_POST['amount'])) {
    $user_id = (int) $_POST['user_id'];
    $amount = (int) $_POST['amount'];
    $operation = isset($_POST['operation']) ? $_POST['operation'] : 'add';

    if ($operation == 'add') {
        $sql_update = "UPDATE users SET points = points + ? WHERE id = ?";
    } elseif ($operation == 'subtract') {
        $sql_update = "UPDATE users SET points = GREATEST(0, points - ?) WHERE id = ?";
    } else {
        $sql_update = "UPDATE users SET points = ? WHERE id = ?";
    }

    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("ii", $amount, $user_id);

    if ($stmt->execute()) {
        $_SESSION['status'] = "success";
        $_SESSION['msg'] = "บันทึกข้อมูลเรียบร้อยแล้ว!";
        header("Location: playerpoint");
        exit;
    } else {
        $update_message = "Error updating record: " . $conn->error;
    }
}

// Search Logic
$search_term = "";
if (isset($_GET['search'])) {
    $search_term = trim($_GET['search']);
    $sql = "SELECT id, username, points FROM users WHERE username LIKE ? ORDER BY id ASC";
    $stmt = $conn->prepare($sql);
    $search_param = "%" . $search_term . "%";
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Fetch All Users
    $sql = "SELECT id, username, points FROM users ORDER BY id ASC";
    $result = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการแต้มผู้เล่น - Delizio Admin</title>
    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700&display=swap"
        rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-red: #d32f2f;
            --primary-orange: #ff6f00;
            --dark-brown: #3e2723;
            --light-cream: #fffbf0;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.5);
            --shadow-glass: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
            --gradient-warm: linear-gradient(135deg, #fffbf0 0%, #ffe0b2 50%, #ffccbc 100%);
            --success-green: #4caf50;
            --danger-red: #f44336;
            --info-blue: #2196F3;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: var(--gradient-warm);
            background-attachment: fixed;
            margin: 0;
            padding: 0;
            color: var(--dark-brown);
            min-height: 100vh;
        }

        /* ==================== LOGIN STYLES ==================== */
        @keyframes steam {
            0% {
                transform: translateY(0) scale(1);
                opacity: 0;
            }

            50% {
                opacity: 0.3;
            }

            100% {
                transform: translateY(-150px) scale(1.8);
                opacity: 0;
            }
        }

        .steam-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .steam {
            position: absolute;
            bottom: -50px;
            width: 60px;
            height: 60px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.4) 0%, transparent 70%);
            border-radius: 50%;
            animation: steam 10s infinite ease-in-out;
        }

        .steam:nth-child(1) {
            left: 5%;
            animation-delay: 0s;
        }

        .steam:nth-child(2) {
            left: 25%;
            animation-delay: 2s;
        }

        .steam:nth-child(3) {
            left: 45%;
            animation-delay: 4s;
        }

        .steam:nth-child(4) {
            left: 65%;
            animation-delay: 1.5s;
        }

        .steam:nth-child(5) {
            left: 85%;
            animation-delay: 3.5s;
        }

        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 40px;
            background: white;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            border-radius: 20px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 999;
            animation: slideUp 0.5s ease-out;
        }

        .logo {
            text-align: center;
            font-size: 4rem;
            margin-bottom: 10px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .login-container h2 {
            text-align: center;
            background: linear-gradient(90deg, var(--primary-red), var(--primary-orange));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
            font-size: 1.8rem;
            font-family: 'Prompt', sans-serif;
        }

        .login-container input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            margin-bottom: 10px;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            margin-top: 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--success-green), #66bb6a);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        #login-error {
            color: var(--danger-red);
            text-align: center;
            margin-top: 15px;
            padding: 12px;
            background: #ffebee;
            border-radius: 8px;
            border-left: 4px solid var(--danger-red);
        }

        /* ==================== DASHBOARD STYLES ==================== */
        .dashboard-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            display: block;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-radius: 20px;
            box-shadow: var(--shadow-glass);
            border: 1px solid var(--glass-border);
        }

        .admin-header h1 {
            font-family: 'Prompt', sans-serif;
            color: var(--primary-orange);
            font-size: 1.8rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 50px;
            font-family: 'Prompt', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-orange);
            color: var(--primary-orange);
        }

        .btn-outline:hover {
            background: var(--primary-orange);
            color: white;
        }

        .btn-logout {
            background: linear-gradient(135deg, var(--danger-red), #e53935);
            color: white;
        }

        /* Search Box Styles */
        .search-container {
            margin-bottom: 25px;
            background: var(--glass-bg);
            padding: 20px;
            border-radius: 20px;
            box-shadow: var(--shadow-glass);
            border: 1px solid var(--glass-border);
            display: flex;
            justify-content: center;
        }

        .search-form {
            display: flex;
            gap: 10px;
            width: 100%;
            max-width: 600px;
        }

        .search-input {
            flex: 1;
            padding: 12px 20px;
            border-radius: 50px;
            border: 2px solid #ddd;
            font-family: 'Sarabun', sans-serif;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s;
        }

        .search-input:focus {
            border-color: var(--primary-orange);
            box-shadow: 0 0 10px rgba(255, 111, 0, 0.1);
        }

        .search-btn {
            background: linear-gradient(90deg, var(--primary-orange), #ff8f00);
            color: white;
            border: none;
            padding: 0 25px;
            border-radius: 50px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            font-family: 'Prompt', sans-serif;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(255, 111, 0, 0.3);
        }

        /* Unique Table Styles for Points */
        .table-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-glass);
            border: 1px solid var(--glass-border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 20px;
            color: var(--primary-orange);
            font-family: 'Prompt', sans-serif;
            font-size: 1.1rem;
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
        }

        td {
            padding: 20px;
            vertical-align: middle;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-size: 1.05rem;
        }

        tr:hover td {
            background-color: rgba(255, 255, 255, 0.5);
        }

        .point-badge {
            background: linear-gradient(135deg, #ffd700, #ffca28);
            color: var(--dark-brown);
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 700;
            font-family: 'Prompt', sans-serif;
            display: inline-block;
            min-width: 80px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(255, 215, 0, 0.3);
        }

        .edit-btn {
            background: var(--info-blue);
            color: white;
            padding: 8px 15px;
            border-radius: 10px;
            transition: transform 0.2s;
        }

        .edit-btn:hover {
            transform: scale(1.05);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 30px;
            border: 1px solid #888;
            width: 90%;
            max-width: 500px;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        .close:hover {
            color: #000;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 1.1rem;
            box-sizing: border-box;
        }

        .form-group input:focus {
            border-color: var(--primary-orange);
            outline: none;
        }

        .save-btn {
            background: linear-gradient(135deg, var(--success-green), #43a047);
            color: white;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .save-btn:hover {
            transform: translateY(-2px);
        }

        /* Flash Message */
        .flash-message {
            background: linear-gradient(45deg, #43a047, #66bb6a);
            color: white;
            padding: 15px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(67, 160, 71, 0.3);
            animation: slideDown 0.5s ease;
            text-align: center;
            font-weight: 600;
        }

        /* Responsive Mobile Styles */
        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
                padding: 20px;
            }

            .admin-header h1 {
                justify-content: center;
                font-size: 1.5rem;
                flex-wrap: wrap;
            }

            .admin-header div {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }

            .dashboard-container {
                padding: 10px;
                margin-top: 20px;
            }

            .table-container {
                padding: 15px;
            }

            th,
            td {
                padding: 12px;
                font-size: 0.95rem;
            }

            .btn {
                width: 100%;
                /* Full width buttons on mobile */
                justify-content: center;
            }

            .login-container {
                width: 90%;
                padding: 25px;
            }

            .logo {
                font-size: 3rem;
            }
        }
    </style>
    <!-- Three.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="js/three_bg.js"></script>
</head>

<body>

    <!-- Steam BG -->
    <div class="steam-bg">
        <div class="steam"></div>
        <div class="steam"></div>
        <div class="steam"></div>
        <div class="steam"></div>
        <div class="steam"></div>
    </div>

    <?php if (!$isLoggedIn): ?>
        <!-- Login Form -->
        <div class="login-container" style="display: flex;">
            <div class="logo">💎</div>
            <h2>ระบบจัดการแต้มผู้เล่น</h2>
            <?php if (isset($loginError)): ?>
                <div style="color: #f44336; margin-bottom: 15px; padding: 10px; background: #ffebee; border-radius: 8px;">
                    ❌ <?php echo $loginError; ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="text" name="username" required placeholder="ชื่อผู้ใช้" autofocus autocomplete="username"
                    style="width: 100%; padding: 14px; border: 2px solid #e0e0e0; border-radius: 12px; margin-bottom: 10px;">
                <div style="position: relative;">
                    <input type="password" name="password" id="adminPassword" required placeholder="รหัสผ่าน"
                        style="width: 100%; padding: 14px; padding-right: 45px; border: 2px solid #e0e0e0; border-radius: 12px;"
                        autocomplete="current-password">
                    <button type="button" onclick="toggleAdminPassword()"
                        style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.2rem; color: #666;">
                        <i class="fas fa-eye" id="adminToggleIcon"></i>
                    </button>
                </div>
                <button type="submit" name="admin_login" class="login-btn">เข้าสู่ระบบ</button>
            </form>
        </div>
    <?php else: ?>

        <!-- Dashboard -->
        <div class="dashboard-container" id="dashboard-content">

            <header class="admin-header">
                <h1><i class="fas fa-coins"></i> รายชื่อผู้เล่น & แต้มสะสม</h1>
                <div style="display:flex; gap:10px;">
                    <a href="formmenu" class="btn btn-outline"><i class="fas fa-arrow-left"></i> กลับเมนู</a>
                </div>
            </header>

            <?php if (isset($_SESSION['status']) && $_SESSION['status'] == 'success'): ?>
                <div class="flash-message">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['msg']; ?>
                </div>
                <?php unset($_SESSION['status']);
                unset($_SESSION['msg']); ?>
            <?php endif; ?>

            <!-- Search Form -->
            <div class="search-container">
                <form method="GET" action="playerpoint" class="search-form">
                    <input type="text" name="search" class="search-input" placeholder="🔍 ค้นหาชื่อผู้เล่น..."
                        value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit" class="search-btn">ค้นหา</button>
                    <?php if (!empty($search_term)): ?>
                        <a href="playerpoint" class="btn btn-outline"
                            style="border-radius:50px; padding: 10px 20px;">ล้างค่า</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ชื่อผู้ใช้งาน (Username)</th>
                            <th>แต้มสะสม (Points)</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td>
                                        <span class="point-badge">
                                            <i class="fas fa-star" style="font-size: 0.8em;"></i>
                                            <?php echo number_format($row['points']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn edit-btn"
                                            onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['username']); ?>', <?php echo $row['points']; ?>)">
                                            <i class="fas fa-edit"></i> แก้ไขแต้ม
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center;">ไม่พบข้อมูลผู้เล่น</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2 style="text-align:center; color:var(--primary-orange); font-family:'Prompt',sans-serif;">✏️ แก้ไขแต้ม
            </h2>
            <p style="text-align:center; margin-bottom:20px; font-size:1.1rem;">ผู้เล่น: <span id="modal-username"
                    style="font-weight:bold; color:var(--dark-brown);"></span></p>

            <form method="POST" action="playerpoint"
                onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการเปลี่ยนแปลงแต้มนี้?');">
                <input type="hidden" id="modal-user-id" name="user_id">

                <div class="form-group">
                    <label>เลือกการทำงาน:</label>
                    <select name="operation" id="modal-operation"
                        style="width:100%; padding:12px; border-radius:10px; border:2px solid #ddd; font-family:'Sarabun',sans-serif; font-size:1rem;">
                        <option value="add" selected>➕ เพิ่มแต้ม (Add)</option>
                        <option value="subtract">➖ ลดแต้ม (Subtract)</option>
                        <option value="set">✏️ กำหนดค่าใหม่ (Set)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>จำนวนแต้ม:</label>
                    <input type="number" id="modal-points" name="amount" required min="0" placeholder="ระบุจำนวนแต้ม">
                </div>

                <button type="submit" class="save-btn">ยืนยัน</button>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function toggleAdminPassword() {
            const passwordInput = document.getElementById('adminPassword');
            const toggleIcon = document.getElementById('adminToggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // --- Modal Logic ---
        function openEditModal(id, username, points) {
            document.getElementById('modal-user-id').value = id;
            document.getElementById('modal-username').textContent = username + ' (มี ' + points.toLocaleString() + ' แต้ม)';
            document.getElementById('modal-points').value = ''; // Reset input
            document.getElementById('editModal').style.display = "block";
            document.getElementById('modal-points').focus();
        }
        function closeEditModal() {
            document.getElementById('editModal').style.display = "none";
        }
        window.onclick = function (event) {
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
        }

        document.getElementById('password').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') checkPassword(e);
        });
    </script>
</body>

</html>
<?php $conn->close(); ?>