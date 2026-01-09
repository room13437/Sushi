<?php
session_start();
require_once 'db_config.php';
require_once 'admin_auth.php';

// Verify admin login
if (!requireAdminLogin()) {
    header('Location: admin_login');
    exit;
}

$message = "";
$messageType = "";

// Image Upload Handler
function uploadImage($file)
{
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK)
        return null;
    $target_dir = "uploads/products/";
    if (!file_exists($target_dir))
        mkdir($target_dir, 0777, true);

    $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
    $filename = time() . "_" . uniqid() . "." . $ext;
    $target_file = $target_dir . $filename;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file;
    }
    return null;
}

// CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $name = $_POST['name'];
        $price = $_POST['price'];
        $stock = intval($_POST['stock']);
        $desc = $_POST['desc'];
        $img = isset($_FILES['image']) ? uploadImage($_FILES['image']) : null;

        $stmt = $conn->prepare("INSERT INTO products (name, price, stock_quantity, description, image_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sdiss", $name, $price, $stock, $desc, $img);
        if ($stmt->execute()) {
            $message = "เพิ่มสินค้า " . htmlspecialchars($name) . " สำเร็จ!";
            $messageType = "success";
        }
        $stmt->close();
    } elseif (isset($_POST['update'])) {
        $id = intval($_POST['id']);
        $name = $_POST['name'];
        $price = $_POST['price'];
        $stock = intval($_POST['stock']);
        $desc = $_POST['desc'];
        $old_img = $_POST['old_image'];

        $img = (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) ? uploadImage($_FILES['image']) : $old_img;

        if ($img !== $old_img && $old_img && file_exists($old_img)) {
            unlink($old_img);
        }

        $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, stock_quantity = ?, description = ?, image_path = ? WHERE id = ?");
        $stmt->bind_param("sdissi", $name, $price, $stock, $desc, $img, $id);
        if ($stmt->execute()) {
            $message = "แก้ไขสินค้าสำเร็จ!";
            $messageType = "success";
        }
        $stmt->close();
    } elseif (isset($_POST['delete'])) {
        $id = intval($_POST['id']);
        $res = $conn->query("SELECT image_path FROM products WHERE id = $id");
        if ($row = $res->fetch_assoc()) {
            if ($row['image_path'] && file_exists($row['image_path']))
                unlink($row['image_path']);
        }
        $conn->query("DELETE FROM products WHERE id = $id");
        $message = "ลบสินค้าสำเร็จ!";
        $messageType = "success";
    }
}

$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🍱 จัดการเมนูอาหาร - Sushi Lagan Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, #fff5f0 0%, #ffe8dc 100%);
        }

        .card-premium {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 140, 0, 0.1);
            box-shadow: 0 8px 32px rgba(255, 140, 0, 0.08);
        }

        .input-premium {
            transition: all 0.3s ease;
            border: 2px solid #f3f4f6;
        }

        .input-premium:focus {
            border-color: #ff8c00;
            box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.1);
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 140, 0, 0.3);
        }

        .table-row:hover {
            background: rgba(255, 140, 0, 0.05);
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        .badge-stock {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.8;
            }
        }

        .gradient-text {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6b00 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>

<body class="min-h-screen p-4 md:p-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="card-premium rounded-3xl p-6 mb-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h1 class="text-4xl font-bold gradient-text mb-2">🍱 จัดการเมนูอาหาร</h1>
                    <p class="text-gray-500">Sushi Lagan Product Management</p>
                </div>
                <a href="formmenu"
                    class="px-6 py-3 bg-white rounded-xl shadow-md text-gray-700 hover:text-orange-600 hover:shadow-lg transition-all flex items-center gap-2 font-semibold">
                    <i class="fas fa-arrow-left"></i> กลับเมนูหลัก
                </a>
            </div>
        </div>

        <!-- Success/Error Message -->
        <?php if ($message): ?>
            <div
                class="card-premium rounded-2xl p-5 mb-8 border-l-4 <?= $messageType === 'success' ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50' ?>">
                <div class="flex items-center gap-3">
                    <i
                        class="fas <?= $messageType === 'success' ? 'fa-check-circle text-green-600' : 'fa-exclamation-circle text-red-600' ?> text-2xl"></i>
                    <p class="font-bold <?= $messageType === 'success' ? 'text-green-700' : 'text-red-700' ?>">
                        <?= $message ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Add Product Form -->
        <div class="card-premium rounded-3xl p-8 mb-10">
            <div class="flex items-center gap-3 mb-8">
                <div
                    class="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-plus text-white text-xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">เพิ่มเมนูใหม่</h2>
                    <p class="text-gray-500 text-sm">Add New Product</p>
                </div>
            </div>

            <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-utensils text-orange-500 mr-2"></i>ชื่ออาหาร
                        </label>
                        <input type="text" name="name" required
                            class="input-premium w-full px-4 py-3 rounded-xl outline-none bg-gray-50"
                            placeholder="เช่น ซูชิแซลมอน">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-tag text-orange-500 mr-2"></i>ราคา (฿)
                        </label>
                        <input type="number" step="0.01" name="price" required
                            class="input-premium w-full px-4 py-3 rounded-xl outline-none bg-gray-50"
                            placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-boxes text-orange-500 mr-2"></i>จำนวนสินค้า (ชิ้น)
                        </label>
                        <input type="number" name="stock" required value="0" min="0"
                            class="input-premium w-full px-4 py-3 rounded-xl outline-none bg-gray-50" placeholder="0">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-align-left text-orange-500 mr-2"></i>คำอธิบาย
                    </label>
                    <textarea name="desc" rows="4"
                        class="input-premium w-full px-4 py-3 rounded-xl outline-none bg-gray-50 resize-none"
                        placeholder="ระบุรายละเอียดอาหาร..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-image text-orange-500 mr-2"></i>รูปภาพสินค้า
                    </label>
                    <input type="file" name="image" accept="image/*"
                        class="input-premium w-full px-4 py-3 rounded-xl bg-gray-50 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-orange-100 file:text-orange-700 file:font-semibold hover:file:bg-orange-200">
                </div>

                <button type="submit" name="add"
                    class="btn-primary w-full py-4 rounded-xl text-white font-bold text-lg shadow-lg">
                    <i class="fas fa-save mr-2"></i> บันทึกข้อมูล
                </button>
            </form>
        </div>

        <!-- Products Table -->
        <div class="card-premium rounded-3xl overflow-hidden">
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-6">
                <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                    <i class="fas fa-list"></i> รายการสินค้าทั้งหมด
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b-2 border-orange-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                รูปภาพ</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                รายการอาหาร</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                ราคา</th>
                            <th class="px-6 py-4 text-center text-sm font-bold text-gray-700 uppercase tracking-wider">
                                คงเหลือ</th>
                            <th class="px-6 py-4 text-center text-sm font-bold text-gray-700 uppercase tracking-wider">
                                จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if ($products->num_rows > 0): ?>
                            <?php while ($row = $products->fetch_assoc()): ?>
                                <tr class="table-row">
                                    <td class="px-6 py-4">
                                        <div class="w-20 h-20 rounded-2xl overflow-hidden shadow-md border-2 border-orange-100">
                                            <img src="<?= $row['image_path'] ?: 'icon/icons.png' ?>"
                                                class="w-full h-full object-cover">
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-gray-800 text-lg mb-1"><?= htmlspecialchars($row['name']) ?>
                                        </p>
                                        <p class="text-sm text-gray-500 line-clamp-2">
                                            <?= htmlspecialchars($row['description']) ?: 'ไม่มีคำอธิบาย' ?>
                                        </p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-orange-600 font-bold text-xl">
                                            <?= number_format($row['price'], 2) ?> ฿
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="badge-stock inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold <?= $row['stock_quantity'] > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                            <i
                                                class="fas <?= $row['stock_quantity'] > 0 ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                                            <?= number_format($row['stock_quantity']) ?> ชิ้น
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-center gap-3">
                                            <button onclick='editProduct(<?= json_encode($row) ?>)'
                                                class="px-4 py-2 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-100 hover:shadow-md transition-all font-semibold">
                                                <i class="fas fa-edit mr-1"></i> แก้ไข
                                            </button>
                                            <form action="" method="POST" onsubmit="return confirm('ยืนยันการลบรายการนี้?')"
                                                class="inline">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="submit" name="delete"
                                                    class="px-4 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 hover:shadow-md transition-all font-semibold">
                                                    <i class="fas fa-trash mr-1"></i> ลบ
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-16 text-center">
                                    <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                                    <p class="text-gray-400 text-xl font-semibold">ไม่พบข้อมูลอาหารในขณะนี้</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
        <div class="card-premium w-full max-w-2xl rounded-3xl shadow-2xl p-8 relative max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-edit text-white text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">แก้ไขเมนูอาหาร</h2>
                        <p class="text-gray-500 text-sm">Edit Product</p>
                    </div>
                </div>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form action="" method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="id" id="edit-id">
                <input type="hidden" name="old_image" id="edit-old-image">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่ออาหาร</label>
                        <input type="text" name="name" id="edit-name" required
                            class="input-premium w-full px-4 py-3 rounded-xl outline-none bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ราคา (฿)</label>
                        <input type="number" step="0.01" name="price" id="edit-price" required
                            class="input-premium w-full px-4 py-3 rounded-xl outline-none bg-gray-50">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">จำนวนสินค้า (ชิ้น)</label>
                    <input type="number" name="stock" id="edit-stock" required min="0"
                        class="input-premium w-full px-4 py-3 rounded-xl outline-none bg-gray-50">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">คำอธิบาย</label>
                    <textarea name="desc" id="edit-desc" rows="4"
                        class="input-premium w-full px-4 py-3 rounded-xl outline-none bg-gray-50 resize-none"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">รูปภาพใหม่
                        (ถ้าไม่เปลี่ยนให้เว้นว่าง)</label>
                    <input type="file" name="image" accept="image/*"
                        class="input-premium w-full px-4 py-3 rounded-xl bg-gray-50 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-100 file:text-blue-700 file:font-semibold hover:file:bg-blue-200">
                    <img id="edit-preview" src=""
                        class="mt-4 h-32 w-32 object-cover rounded-2xl border-2 border-blue-200 hidden">
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="closeModal()"
                        class="flex-1 py-3 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition-all">
                        ยกเลิก
                    </button>
                    <button type="submit" name="update"
                        class="flex-1 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white font-bold rounded-xl hover:shadow-lg transition-all">
                        <i class="fas fa-save mr-2"></i>บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editProduct(product) {
            document.getElementById('edit-id').value = product.id;
            document.getElementById('edit-name').value = product.name;
            document.getElementById('edit-price').value = product.price;
            document.getElementById('edit-stock').value = product.stock_quantity;
            document.getElementById('edit-desc').value = product.description;
            document.getElementById('edit-old-image').value = product.image_path;

            const preview = document.getElementById('edit-preview');
            if (product.image_path) {
                preview.src = product.image_path;
                preview.classList.remove('hidden');
            } else {
                preview.classList.add('hidden');
            }

            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }

        window.onclick = function (event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) closeModal();
        }
    </script>
</body>

</html>