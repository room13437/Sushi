<?php
session_start();
require_once 'admin_auth.php';
$isLoggedIn = requireAdminLogin();

// Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "products";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Products (Will be hidden by JS if not logged in)
$sql = "SELECT * FROM products ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการรายการสินค้า - Delizio Admin</title>
    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700&display=swap"
        rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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

        /* ==================== LOGIN STYLES (MATCHING EDIT_PRODUCT) ==================== */
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
            /* Centered */
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

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-10px);
            }

            75% {
                transform: translateX(10px);
            }
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        /* Header */
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
            color: var(--primary-red);
            font-size: 1.8rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        /* Buttons */
        .btn {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 50px;
            font-family: 'Prompt', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-red), var(--primary-orange));
            color: white;
            box-shadow: 0 4px 15px rgba(211, 47, 47, 0.3);
        }

        /* Responsive Mobile Styles */
        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                padding: 20px;
            }

            .header-actions {
                flex-wrap: wrap;
                justify-content: center;
                width: 100%;
            }

            .admin-header h1 {
                justify-content: center;
                font-size: 1.5rem;
            }

            .btn {
                flex: 1;
                min-width: 120px;
                justify-content: center;
            }

            .table-container {
                padding: 15px;
            }

            td,
            th {
                padding: 10px;
            }

            .dashboard-container {
                padding: 10px;
                margin-top: 20px;
            }
        }

        background: linear-gradient(45deg, var(--primary-red), var(--primary-orange));
        color: white;
        box-shadow: 0 4px 15px rgba(211, 47, 47, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(211, 47, 47, 0.4);
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
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        /* Data Table */
        .table-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-glass);
            border: 1px solid var(--glass-border);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
            /* Force scroll on small screens */
        }

        th {
            text-align: left;
            padding: 20px;
            color: var(--primary-red);
            font-family: 'Prompt', sans-serif;
            font-size: 1.1rem;
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
        }

        td {
            padding: 20px;
            vertical-align: middle;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: rgba(255, 255, 255, 0.5);
        }

        /* Product Image */
        .product-thumb {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .product-thumb:hover {
            transform: scale(1.1) rotate(2deg);
        }

        /* Status Badge */
        .price-tag {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-family: 'Prompt', sans-serif;
        }

        /* Action Buttons */
        .action-btn {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            margin: 0 3px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: 0.2s;
        }

        .edit-btn {
            background: #ffa000;
        }

        .del-btn {
            background: #d32f2f;
        }

        .action-btn:hover {
            transform: scale(1.1);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #999;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ddd;
        }

        /* Flash Message */
        .flash-message {
            background: linear-gradient(45deg, #43a047, #66bb6a);
            color: white;
            padding: 15px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(67, 160, 71, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Search Box Styles */
        .search-container {
            margin-bottom: 25px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .search-box {
            width: 100%;
            max-width: 500px;
            padding: 14px 20px 14px 50px;
            border: 2px solid #e0e0e0;
            border-radius: 50px;
            font-size: 1rem;
            font-family: 'Sarabun', sans-serif;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .search-box:focus {
            outline: none;
            border-color: var(--primary-orange);
            box-shadow: 0 4px 20px rgba(255, 111, 0, 0.2);
        }

        .search-wrapper {
            position: relative;
            width: 100%;
            max-width: 500px;
        }

        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-orange);
            font-size: 1.1rem;
            pointer-events: none;
        }

        .clear-search {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary-red);
            color: white;
            border: none;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: all 0.2s;
        }

        .clear-search:hover {
            background: var(--primary-orange);
            transform: translateY(-50%) scale(1.1);
        }

        .no-results {
            text-align: center;
            padding: 50px;
            color: #999;
            display: none;
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #ddd;
        }

        @media (max-width: 768px) {
            .search-container {
                padding: 0 10px;
            }

            .search-box {
                font-size: 0.9rem;
                padding: 12px 18px 12px 45px;
            }
        }
    </style>
</head>

<body>

    <!-- Steam Background -->
    <div class="steam-bg">
        <div class="steam"></div>
        <div class="steam"></div>
        <div class="steam"></div>
        <div class="steam"></div>
        <div class="steam"></div>
    </div>


    <!-- Main Dashboard Container -->
    <div class="dashboard-container" id="dashboard-content">

        <!-- Header -->
        <header class="admin-header">
            <h1>
                <i class="fas fa-utensils"></i>
                จัดการเมนูอาหาร
            </h1>
            <div class="header-actions">
                <a href="/" class="btn btn-outline">
                    <i class="fas fa-home"></i> หน้าหลัก
                </a>
                <a href="upload_form" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> เพิ่มเมนูใหม่
                </a>
                <a href="formmenu" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> กลับเมนูหลัก
                </a>
            </div>
        </header>

        <!-- Flash Message -->
        <?php if (isset($_SESSION['upload_status']) && $_SESSION['upload_status'] == 'success'): ?>
            <div class="flash-message">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>บันทึกสำเร็จ!</strong><br>
                    เพิ่มเมนูเรียบร้อยแล้ว
                </div>
            </div>
            <?php unset($_SESSION['upload_status']); ?>
        <?php endif; ?>

        <!-- Search Box -->
        <div class="search-container">
            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" class="search-box"
                    placeholder="🔍 ค้นหาเมนู (ชื่อ, รายละเอียด, หมวดหมู่, ราคา)..." oninput="searchProducts()">
                <button class="clear-search" id="clearSearch" onclick="clearSearch()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Product Table -->
        <div class="table-container">
            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th width="100">รูปภาพ</th>
                            <th>ชื่อเมนู</th>
                            <th>รายละเอียด</th>
                            <th>ราคา</th>
                            <th>หมวดหมู่ (Auto)</th>
                            <th width="120">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                            // Simple Helper for Category (Same logic as index.php)
                            $nameLower = mb_strtolower($row['name'], 'UTF-8');
                            $category = 'ทั่วไป';
                            if (strpos($nameLower, 'เนื้อ') !== false || strpos($nameLower, 'วัว') !== false)
                                $category = 'เนื้อวัว';
                            elseif (strpos($nameLower, 'หมู') !== false)
                                $category = 'เนื้อหมู';
                            elseif (strpos($nameLower, 'กุ้ง') !== false || strpos($nameLower, 'ปลา') !== false || strpos($nameLower, 'ทะเล') !== false)
                                $category = 'ซีฟู้ด';
                            elseif (strpos($nameLower, 'ผัก') !== false)
                                $category = 'ผัก';

                            $imgSrc = !empty($row['image_path']) ? $row['image_path'] : 'https://via.placeholder.com/100x100?text=No+Img';
                            ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="img" class="product-thumb">
                                </td>
                                <td>
                                    <div style="font-weight: 600; font-size: 1.1rem;">
                                        <?php echo htmlspecialchars($row['name']); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #888;">ID: <?php echo $row['id']; ?></div>
                                </td>
                                <td style="color: #555; max-width: 300px;">
                                    <?php echo htmlspecialchars(mb_strimwidth($row['description'], 0, 80, "...")); ?>
                                </td>
                                <td>
                                    <span class="price-tag"><?php echo number_format($row['price']); ?> ฿</span>
                                </td>
                                <td>
                                    <span
                                        style="font-size: 0.9rem; background: rgba(0,0,0,0.05); padding: 5px 10px; border-radius: 10px;">
                                        <?php echo $category; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit_product?id=<?php echo $row['id']; ?>" class="action-btn edit-btn"
                                        title="แก้ไข">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <a href="delete_product?id=<?php echo $row['id']; ?>" class="action-btn del-btn" title="ลบ"
                                        onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบเมนู: <?php echo addslashes($row['name']); ?>?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="no-results" id="noResults">
                    <i class="fas fa-search"></i>
                    <h3>ไม่พบผลลัพธ์</h3>
                    <p>ลองค้นหาด้วยคำอื่น หรือล้างการค้นหา</p>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="far fa-folder-open"></i>
                    <h3>ยังไม่มีข้อมูลเมนู</h3>
                    <p>เริ่มต้นด้วยการเพิ่มเมนูใหม่เข้าระบบ</p>
                    <a href="upload_form" class="btn btn-primary" style="margin-top: 15px;">เพิ่มเมนูแรก</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="text-align: center; margin-top: 30px; font-size: 0.9rem; color: #888;">
        Delizio Shabu Admin Dashboard &copy; 2024
    </div>

    <script>
        const SESSION_TIMEOUT_MS = 15 * 60 * 1000; // 15 Minutes
        const CORRECT_PASSWORD = "AdminN_N";

        function showLogin() {
            document.getElementById('login-form-container').style.display = 'block';
            document.getElementById('dashboard-content').style.display = 'none';
            document.getElementById('password').value = '';
            const errorElement = document.getElementById('login-error');
            errorElement.style.display = 'none';
            errorElement.textContent = '';
        }

        function showDashboard() {
            document.getElementById('login-form-container').style.display = 'none';
            document.getElementById('dashboard-content').style.display = 'block';
        }

        function checkPassword(event) {
            event.preventDefault();

            const inputPassword = document.getElementById('password').value;
            const errorElement = document.getElementById('login-error');

            if (inputPassword === CORRECT_PASSWORD) {
                localStorage.setItem('loginTime', Date.now());
                showDashboard();
            } else {
                errorElement.textContent = '❌ รหัสผ่านไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
                errorElement.style.display = 'block';

                // Add Shake Effect
                const loginBox = document.querySelector('.login-container');
                loginBox.style.animation = 'none';
                setTimeout(() => {
                    loginBox.style.animation = 'shake 0.5s';
                }, 10);
            }
        }

        function checkLoginSession() {
            const loginTime = localStorage.getItem('loginTime');
            const currentTime = Date.now();

            if (loginTime) {
                const elapsed = currentTime - parseInt(loginTime);

                if (elapsed < SESSION_TIMEOUT_MS) {
                    showDashboard();
                } else {
                    localStorage.removeItem('loginTime');
                    showLogin();
                }
            } else {
                showLogin();
            }
        }

        function logoutSession() {
            localStorage.removeItem('loginTime');
            showLogin();

            const errorElement = document.getElementById('login-error');
            errorElement.textContent = '✅ ออกจากระบบเรียบร้อยแล้ว';
            errorElement.style.display = 'block';
            errorElement.style.background = '#e8f5e9';
            errorElement.style.color = '#4caf50';
            errorElement.style.borderLeft = '4px solid #4caf50';

            setTimeout(() => {
                errorElement.style.display = 'none';
            }, 3000);
        }

        document.getElementById('password').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                checkPassword(e);
            }
        });

        // Search Functionality
        function searchProducts() {
            const searchInput = document.getElementById('searchInput');
            const filter = searchInput.value.toLowerCase().trim();
            const table = document.querySelector('table tbody');
            const clearBtn = document.getElementById('clearSearch');
            const noResults = document.getElementById('noResults');

            if (!table) return;

            const rows = table.getElementsByTagName('tr');
            let visibleCount = 0;

            // Show/hide clear button
            clearBtn.style.display = filter ? 'flex' : 'none';

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const nameCell = row.cells[1]?.textContent || '';
                const descCell = row.cells[2]?.textContent || '';
                const priceCell = row.cells[3]?.textContent || '';
                const categoryCell = row.cells[4]?.textContent || '';

                const searchText = (nameCell + ' ' + descCell + ' ' + priceCell + ' ' + categoryCell).toLowerCase();

                if (searchText.includes(filter)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }

            // Show/hide "no results" message
            const tableElement = document.querySelector('table');
            if (visibleCount === 0 && filter) {
                tableElement.style.display = 'none';
                noResults.style.display = 'block';
            } else {
                tableElement.style.display = 'table';
                noResults.style.display = 'none';
            }
        }

        function clearSearch() {
            document.getElementById('searchInput').value = '';
            searchProducts();
            document.getElementById('searchInput').focus();
        }
    </script>

</body>

</html>

<?php $conn->close(); ?>