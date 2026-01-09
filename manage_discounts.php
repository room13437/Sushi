<?php
require_once 'admin_auth.php';

// Ensure Admin is logged in
if (!requireAdminLogin()) {
    header('Location: admin_login');
    exit;
}

$message = "";

// Handle Messages from Session
if (isset($_SESSION['discount_message'])) {
    $message = $_SESSION['discount_message'];
    unset($_SESSION['discount_message']);
}

// Handle Add Code
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_code'])) {
    $code = strtoupper(trim($_POST['code']));
    $percent = (int) $_POST['percent'];

    if (empty($code) || $percent <= 0 || $percent > 100) {
        $message = "<div class='bg-red-100 text-red-700 p-4 rounded-xl mb-6 flex items-center gap-3'><i class='fas fa-exclamation-circle text-xl'></i> ข้อมูลไม่ถูกต้อง (เปอร์เซ็นต์ต้องอยู่ระหว่าง 1-100)</div>";
    } else {
        $max_uses = (int) $_POST['max_uses'];
        $stmt = $conn->prepare("INSERT INTO discount_codes (code, percent, max_uses) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $code, $percent, $max_uses);

        try {
            if ($stmt->execute()) {
                $message = "<div class='bg-green-100 text-green-700 p-4 rounded-xl mb-6 flex items-center gap-3'><i class='fas fa-check-circle text-xl'></i> สร้างโค้ดส่วนลด <b>$code</b> ($percent%) สำเร็จ!</div>";
            } else {
                $message = "<div class='bg-red-100 text-red-700 p-4 rounded-xl mb-6 flex items-center gap-3'><i class='fas fa-times-circle text-xl'></i> โค้ดซ้ำหรือเกิดข้อผิดพลาด</div>";
            }
        } catch (mysqli_sql_exception $e) {
            $message = "<div class='bg-red-100 text-red-700 p-4 rounded-xl mb-6 flex items-center gap-3'><i class='fas fa-times-circle text-xl'></i> โค้ดนี้มีอยู่แล้ว</div>";
        }
    }
}

// Handle Delete Code
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM discount_codes WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['discount_message'] = "<div class='bg-green-100 text-green-700 p-4 rounded-xl mb-6 flex items-center gap-3'><i class='fas fa-check-circle text-xl'></i> ลบโค้ดส่วนลดเรียบร้อยแล้ว</div>";
            } else {
                $_SESSION['discount_message'] = "<div class='bg-yellow-100 text-yellow-700 p-4 rounded-xl mb-6 flex items-center gap-3'><i class='fas fa-info-circle text-xl'></i> ไม่พบข้อมูลที่ต้องการลบ (อาจถูกลบไปแล้ว)</div>";
            }
        } else {
            $_SESSION['discount_message'] = "<div class='bg-red-100 text-red-700 p-4 rounded-xl mb-6 flex items-center gap-3'><i class='fas fa-times-circle text-xl'></i> ไม่สามารถลบข้อมูลได้เนื่องจากระบบขัดข้อง: " . htmlspecialchars($conn->error) . "</div>";
        }
        $stmt->close();
    }
    header("Location: manage_discounts");
    exit;
}

// Fetch Codes
$codes = $conn->query("SELECT * FROM discount_codes ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการโค้ดส่วนลด | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #F3F4F6;
        }

        h1,
        h2,
        h3,
        button {
            font-family: 'Prompt', sans-serif;
        }
    </style>
</head>

<body class="p-6">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">🎟️ จัดการโค้ดส่วนลด (Discount Codes)</h1>
            <a href="formmenu" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition"><i
                    class="fas fa-arrow-left"></i> กลับเมนู</a>
        </div>

        <?php if ($message)
            echo $message; ?>

        <!-- Add Code Form -->
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-8 border border-gray-100">
            <h2 class="text-xl font-bold text-teal-600 mb-4 flex items-center gap-2"><i class="fas fa-plus-circle"></i>
                สร้างโค้ดใหม่</h2>
            <form method="POST" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 w-full">
                    <label class="block text-gray-700 font-bold mb-2">รหัสโค้ด (Code)</label>
                    <input type="text" name="code" placeholder="เช่น LOVE20, SAVE10"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-teal-500 focus:ring-2 focus:ring-teal-200 outline-none uppercase font-bold"
                        required>
                </div>
                <div class="w-full md:w-32">
                    <label class="block text-gray-700 font-bold mb-2">ลด (%)</label>
                    <input type="number" name="percent" min="1" max="100" placeholder="%"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-teal-500 focus:ring-2 focus:ring-teal-200 outline-none font-bold text-center"
                        required>
                </div>
                <div class="w-full md:w-32">
                    <label class="block text-gray-700 font-bold mb-2">จำนวนสิทธิ์</label>
                    <input type="number" name="max_uses" min="1" value="1" placeholder="1 = ครั้งเดียว"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-teal-500 focus:ring-2 focus:ring-teal-200 outline-none font-bold text-center"
                        required title="1 หมายถึงใช้หน้างานครั้งเดียวแล้วลบทิ้ง">
                </div>
                <button type="submit" name="add_code"
                    class="w-full md:w-auto bg-teal-500 text-white px-8 py-3 rounded-xl font-bold hover:bg-teal-600 shadow-lg hover:shadow-teal-500/30 transition-all h-[50px]">
                    <i class="fas fa-save mr-2"></i> บันทึก
                </button>
            </form>
        </div>

        <!-- Codes List -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="p-4 font-bold text-gray-600">Code</th>
                        <th class="p-4 font-bold text-gray-600 text-center">ส่วนลด</th>
                        <th class="p-4 font-bold text-gray-600 text-center">การใช้งาน</th>
                        <th class="p-4 font-bold text-gray-600 hidden md:table-cell">สร้างเมื่อ</th>
                        <th class="p-4 font-bold text-gray-600 text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php while ($row = $codes->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-4 font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($row['code']); ?>
                            </td>
                            <td class="p-4 text-center">
                                <span
                                    class="bg-teal-100 text-teal-700 font-bold px-3 py-1 rounded-full text-sm"><?php echo $row['percent']; ?>%
                                    OFF</span>
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex flex-col items-center">
                                    <span class="font-bold text-gray-700"><?php echo $row['used_count']; ?> /
                                        <?php echo $row['max_uses'] > 0 ? $row['max_uses'] : '∞'; ?></span>
                                    <div class="w-20 bg-gray-200 rounded-full h-1.5 mt-1">
                                        <?php
                                        $prog = ($row['max_uses'] > 0) ? min(100, ($row['used_count'] / $row['max_uses']) * 100) : 0;
                                        $barColor = $prog >= 100 ? 'bg-red-500' : 'bg-teal-500';
                                        ?>
                                        <div class="<?php echo $barColor; ?> h-1.5 rounded-full"
                                            style="width: <?php echo $prog; ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-gray-500 text-sm hidden md:table-cell">
                                <?php echo date('d M Y', strtotime($row['created_at'])); ?>
                            </td>
                            <td class="p-4 text-right">
                                <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('ยืนยันการลบโค้ดนี้?');"
                                    class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-2 rounded-lg transition-colors">
                                    <i class="fas fa-trash-alt"></i> ลบ
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if ($codes->num_rows == 0): ?>
                        <tr>
                            <td colspan="4" class="p-8 text-center text-gray-400">ยังไม่มีโค้ดส่วนลด</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>