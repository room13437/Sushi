<?php
require_once 'protect_admin.php'; // ป้องกันด้วย MySQL Authentication

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "products";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM promotions ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการโปรโมชั่น - Delizio Sushi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Responsive Table */
        .table-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            /* Soft shadow for container */
            border-radius: 12px;
        }

        table {
            width: 100%;
            min-width: 600px;
            /* Force min width to trigger scroll on small screens */
            border-collapse: collapse;
        }

        /* Mobile specific adjustments */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 15px;
                width: 95%;
            }

            .admin-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .admin-header h1 {
                font-size: 1.8rem;
            }

            .btn {
                width: 100%;
                /* Full width buttons on mobile */
                justify-content: center;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>จัดการโปรโมชั่น - Delizio Shabu</title>
<link
    href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700&display=swap"
    rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Three.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="js/three_bg.js"></script>

<style>
    :root {
        --primary-red: #d32f2f;
        --primary-orange: #ff6f00;
        --dark-brown: #3e2723;
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
        min-height: 100vh;
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

    .dashboard-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 20px;
        position: relative;
        z-index: 1;
        display: none;
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
        color: var(--primary-red);
        font-size: 1.8rem;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

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

    tr:hover td {
        background-color: rgba(255, 255, 255, 0.5);
    }

    .promo-thumb {
        width: 120px;
        height: 80px;
        object-fit: cover;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

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

    /* Login */
    .login-container {
        max-width: 450px;
        width: 100%;
        padding: 40px;
        background: white;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        border-radius: 20px;
        position: absolute;
        max-width: 450px;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 999;
        text-align: center;
    }

    #login-error {
        color: var(--danger-red);
        background: #ffebee;
        padding: 10px;
        border-radius: 8px;
        margin-top: 15px;
        display: none;
    }

    input[type="password"] {
        width: 100%;
        padding: 12px;
        margin: 10px 0;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
    }

    .login-btn {
        width: 100%;
        padding: 12px;
        background: var(--success-green);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: bold;
        cursor: pointer;
    }
</style>
</head>

<body>

    <div class="steam-bg">
        <div class="steam"></div>
        <div class="steam"></div>
        <div class="steam"></div>
        <div class="steam"></div>
        <div class="steam"></div>
    </div>

    <div class="dashboard-container" id="dashboard-content" style="display: block;">
        <header class="admin-header">
            <h1><i class="fas fa-tags"></i> จัดการโปรโมชั่น</h1>
            <div style="display:flex; gap:10px;">
                <a href="formmenu" class="btn btn-outline"><i class="fas fa-arrow-left"></i> กลับเมนู</a>
                <a href="upload_promotion" class="btn btn-primary"><i class="fas fa-plus"></i> เพิ่มโปรโมชั่น</a>
            </div>
        </header>

        <div class="table-container">
            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>รูปภาพ</th>
                            <th>หัวข้อ</th>
                            <th>รายละเอียด</th>
                            <th>วันที่สร้าง</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if ($row['image_path']): ?>
                                        <img src="<?php echo htmlspecialchars($row['image_path']); ?>" class="promo-thumb">
                                    <?php else: ?>
                                        <span>No IMG</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight:600; font-size:1.1rem;">
                                    <?php echo htmlspecialchars($row['title']); ?>
                                </td>
                                <td style="color:#666; max-width:300px;">
                                    <?php echo htmlspecialchars(mb_strimwidth($row['description'], 0, 80, '...')); ?>
                                </td>
                                <td style="font-size:0.9rem; color:#888;">
                                    <?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?>
                                </td>
                                <td>
                                    <a href="edit_promotion?id=<?php echo $row['id']; ?>" class="action-btn edit-btn"><i
                                            class="fas fa-pen"></i></a>
                                    <a href="delete_promotion?id=<?php echo $row['id']; ?>" class="action-btn del-btn"
                                        onclick="return confirm('ยืนยันการลบโปรโมชั่นนี้?');"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align:center; padding:50px; color:#999;">
                    <i class="fas fa-bullhorn" style="font-size:3rem; margin-bottom:15px;"></i>
                    <h3>ยังไม่มีโปรโมชั่น</h3>
                </div>
            <?php endif; ?>
        </div>
    </div>



</body>

</html>
<?php $conn->close(); ?>