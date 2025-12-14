<?php
require_once 'protect_admin.php'; // ป้องกันด้วย MySQL Authentication
date_default_timezone_set('Asia/Bangkok');
include "db_config.php";

$today = date('Y-m-d');
$message = "";

// --- Handle Actions (Edit Status / Delete) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        // $id variable is only needed for single item actions
        $id = isset($_POST['id']) ? $_POST['id'] : null;

        if ($_POST['action'] == 'update_status') {
            $new_status = $_POST['status'];
            $stmt = $conn->prepare("UPDATE daily_queue SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $id);
            if ($stmt->execute()) {
                $message = "อัพเดทสถานะเรียบร้อยแล้ว!";
            }
        } elseif ($_POST['action'] == 'delete_queue') {
            // 1. Get info of the queue to be deleted
            $stmt_info = $conn->prepare("SELECT queue_number, queue_date FROM daily_queue WHERE id = ?");
            $stmt_info->bind_param("i", $id);
            $stmt_info->execute();
            $res_info = $stmt_info->get_result();

            if ($row_info = $res_info->fetch_assoc()) {
                $deleted_q = $row_info['queue_number'];
                $q_date = $row_info['queue_date'];

                // 2. Delete the queue
                $stmt_del = $conn->prepare("DELETE FROM daily_queue WHERE id = ?");
                $stmt_del->bind_param("i", $id);
                if ($stmt_del->execute()) {
                    // 3. Reorder subsequent queues (Shift Down)
                    // We order by ASC to ensure we shift 3->2 before trying to shift 4->3
                    $stmt_reorder = $conn->prepare("UPDATE daily_queue SET queue_number = queue_number - 1 WHERE queue_date = ? AND queue_number > ? ORDER BY queue_number ASC");
                    $stmt_reorder->bind_param("si", $q_date, $deleted_q);
                    $stmt_reorder->execute();

                    $message = "ลบคิวและจัดลำดับใหม่เรียบร้อยแล้ว!";
                }
            }
        } elseif ($_POST['action'] == 'delete_all_queues') {
            // Delete ALL queues for today
            $stmt_all = $conn->prepare("DELETE FROM daily_queue WHERE queue_date = ?");
            $stmt_all->bind_param("s", $today);
            if ($stmt_all->execute()) {
                $message = "ล้างคิวทั้งหมดของวันนี้เรียบร้อยแล้ว!";
            }
        }
    }
}

// --- Fetch Queues (With Search) ---
$search = "";
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $sql = "SELECT * FROM daily_queue 
            WHERE queue_date = '$today' 
            AND (customer_name LIKE '%$search%' OR queue_number = '$search') 
            ORDER BY queue_number ASC";
} else {
    $sql = "SELECT * FROM daily_queue WHERE queue_date = '$today' ORDER BY queue_number ASC";
}
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="10"> <!-- Auto Refresh every 10s -->
    <title>จัดการคิว (Admin) - Delizio Sushi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Three.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="js/three_bg.js"></script>

    <style>
        :root {
            --primary-red: #d32f2f;
            --primary-orange: #ff6f00;
            --glass-bg: rgba(255, 255, 255, 0.95);
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #fffbf0 0%, #ffe0b2 50%, #ffccbc 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: var(--glass-bg);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-family: 'Prompt', sans-serif;
            color: var(--primary-red);
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: var(--primary-orange);
            color: white;
            font-family: 'Prompt', sans-serif;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .status-select {
            padding: 5px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .btn-action {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: white;
            font-family: 'Prompt', sans-serif;
            transition: 0.3s;
        }

        .btn-save {
            background: #4caf50;
        }

        .btn-delete {
            background: #f44336;
        }

        .btn-back {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #666;
            color: white;
            text-decoration: none;
            border-radius: 50px;
        }

        /* Login Overlay code from formmenu */
        .login-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
    </style>
</head>

<body>

    <div class="container" id="main-content">
        <!-- Back Button -->
        <div style="margin-bottom: 20px;">
            <a href="formmenu"
                style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: #FF6F00; color: white; text-decoration: none; border-radius: 10px; font-weight: 600; transition: all 0.3s; box-shadow: 0 2px 8px rgba(255, 111, 0, 0.3);"
                onmouseover="this.style.background='#d32f2f';" onmouseout="this.style.background='#FF6F00';">
                <i class="fas fa-arrow-left"></i> กลับเมนูหลัก
            </a>
        </div>

        <h1><i class="fas fa-tasks"></i> จัดการคิวประจำวัน</h1>
        <p style="text-align:center;">วันที่: <?php echo date('d/m/Y'); ?></p>

        <!-- Use GET for search so auto-refresh keeps the param -->
        <form method="GET" style="text-align:center; margin-bottom:20px;">
            <input type="text" name="search" placeholder="ค้นหาชื่อ หรือ เบอร์คิว..."
                value="<?php echo htmlspecialchars($search); ?>"
                style="padding:10px; width:300px; border-radius:50px; border:1px solid #ccc; text-align:center;">
            <button type="submit" class="btn-action btn-save" style="border-radius:50px;"><i class="fas fa-search"></i>
                ค้นหา</button>
            <?php if ($search): ?>
                <a href="manage_queue" class="btn-action btn-delete"
                    style="text-decoration:none; border-radius:50px; padding:9px 15px;">ล้างค่า</a>
            <?php endif; ?>
        </form>

        <!-- Delete All Button -->
        <div style="text-align:right; margin-bottom:10px;">
            <form method="POST"
                onsubmit="return confirm('⚠️ คำเตือน: คุณต้องการลบคิวทั้งหมดของวันนี้ใช่หรือไม่? \nข้อมูลจะไม่สามารถกู้คืนได้!');">
                <input type="hidden" name="action" value="delete_all_queues">
                <button type="submit" class="btn-action btn-delete" style="padding:10px 20px; font-weight:bold;">
                    <i class="fas fa-dumpster-fire"></i> ล้างคิวทั้งหมด
                </button>
            </form>
        </div>

        <?php if ($message): ?>
            <script>Swal.fire('สำเร็จ', '<?php echo $message; ?>', 'success');</script>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Q#</th>
                    <th>ชื่อลูกค้า</th>
                    <th>เบอร์โทร</th>
                    <th>รายละเอียด</th>
                    <th>เวลารับของ</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td style="font-weight:bold; font-size:1.2rem;">
                            <?php echo str_pad($row['queue_number'], 3, '0', STR_PAD_LEFT); ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['details']); ?></td>
                        <td>
                            <?php
                            if (!empty($row['pickup_time'])) {
                                echo '<span style="background:#FFF3E0; color:#F57C00; padding:5px 10px; border-radius:6px; font-weight:700; display:inline-block;">';
                                echo '<i class="fas fa-clock"></i> ' . date('H:i', strtotime($row['pickup_time'])) . ' น.';
                                echo '</span>';
                            } else {
                                echo '<span style="color:#999;">-</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="action" value="update_status">
                                <select name="status" class="status-select" onchange="this.form.submit()">
                                    <option value="Waiting" <?php echo ($row['status'] == 'Waiting') ? 'selected' : ''; ?>>
                                        รอเรียก
                                    </option>
                                    <option value="Called" <?php echo ($row['status'] == 'Called') ? 'selected' : ''; ?>>
                                        เรียกแล้ว
                                    </option>
                                    <option value="Completed" <?php echo ($row['status'] == 'Completed') ? 'selected' : ''; ?>>
                                        เสร็จสิ้น</option>
                                    <option value="Cancelled" <?php echo ($row['status'] == 'Cancelled') ? 'selected' : ''; ?>>
                                        ยกเลิก</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('ยืนยันที่จะลบคิวนี้?');">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="action" value="delete_queue">
                                <button type="submit" class="btn-action btn-delete"><i class="fas fa-trash"></i> ลบ</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div style="text-align:center;">
            <a href="formmenu" class="btn-back">กลับเมนูหลัก</a>
        </div>
    </div>




</body>

</html>