<?php
session_start();
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // Check duplicate
    // Note: We check if username OR email OR phone already exists
    $check = $conn->prepare("SELECT id FROM customers WHERE username = ? OR email = ?");
    $check->bind_param("ss", $username, $email);
    $check->execute();
    if ($check->fetch()) {
        $error = "ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้งานแล้ว";
    } else {
        $stmt = $conn->prepare("INSERT INTO customers (username, password, full_name, email, phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $password, $full_name, $email, $phone);
        if ($stmt->execute()) {
            $_SESSION['customer_id'] = $conn->insert_id;
            $_SESSION['customer_name'] = $full_name;
            header("Location: points");
            exit;
        } else {
            $error = "เกิดข้อผิดพลาดในการสมัครสมาชิก";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="icon/icons.png?v=4">
    <title>สมัครสมาชิกใหม่ | Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary-orange: #FF6F00;
            --primary-red: #d32f2f;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: black;
            /* Dark background for particles to pop */
            overflow-x: hidden;
            overflow-y: auto;
            /* Allow scrolling for registration form */
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        .glass-box {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        /* Loader */
        #loader-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.5s ease-out;
        }

        #loader-wrapper.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .loader {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-top: 5px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
    <!-- Three.js Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="js/three_bg.js"></script>
</head>

<body class="min-h-screen flex items-center justify-center p-6 relative">

    <!-- Loader -->
    <div id="loader-wrapper">
        <div class="loader"></div>
    </div>

    <!-- Register Container -->
    <div class="glass-box p-8 rounded-3xl w-full max-w-lg relative z-10 animate-fade-in-up my-10">
        <div class="text-center mb-6">
            <h2
                class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-orange-600 to-red-600 font-prompt">
                สมัครสมาชิกใหม่
            </h2>
            <p class="text-gray-500 font-prompt mt-1">Create your account</p>
        </div>

        <?php if (isset($error)): ?>
            <div
                class="bg-red-50 border-l-4 border-red-500 text-red-600 p-4 rounded-r-xl mb-6 text-sm font-bold flex items-center shadow-sm">
                <i class="fas fa-exclamation-circle mr-3 text-lg"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="relative group">
                    <label class="block text-gray-700 font-bold mb-2 ml-1 text-sm">ชื่อ-นามสกุล</label>
                    <div class="relative transition-all duration-300 transform group-hover:scale-[1.01]">
                        <i
                            class="fas fa-id-card absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-orange-500 transition-colors"></i>
                        <input type="text" name="full_name"
                            class="w-full pl-12 pr-4 py-3 rounded-xl bg-white/80 border border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 outline-none transition-all"
                            required>
                    </div>
                </div>
                <div class="relative group">
                    <label class="block text-gray-700 font-bold mb-2 ml-1 text-sm">เบอร์โทรศัพท์</label>
                    <div class="relative transition-all duration-300 transform group-hover:scale-[1.01]">
                        <i
                            class="fas fa-phone absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-orange-500 transition-colors"></i>
                        <input type="tel" name="phone" placeholder="08xxxxxxxx"
                            class="w-full pl-12 pr-4 py-3 rounded-xl bg-white/80 border border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 outline-none transition-all"
                            required>
                    </div>
                </div>
            </div>

            <div class="relative group">
                <label class="block text-gray-700 font-bold mb-2 ml-1 text-sm">อีเมล</label>
                <div class="relative transition-all duration-300 transform group-hover:scale-[1.01]">
                    <i
                        class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-orange-500 transition-colors"></i>
                    <input type="email" name="email" placeholder="example@email.com"
                        class="w-full pl-12 pr-4 py-3 rounded-xl bg-white/80 border border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 outline-none transition-all"
                        required>
                </div>
            </div>

            <div class="relative group">
                <label class="block text-gray-700 font-bold mb-2 ml-1 text-sm">ชื่อผู้ใช้ (Username)</label>
                <div class="relative transition-all duration-300 transform group-hover:scale-[1.01]">
                    <i
                        class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-orange-500 transition-colors"></i>
                    <input type="text" name="username"
                        class="w-full pl-12 pr-4 py-3 rounded-xl bg-white/80 border border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 outline-none transition-all"
                        required>
                </div>
            </div>

            <div class="relative group">
                <label class="block text-gray-700 font-bold mb-2 ml-1 text-sm">รหัสผ่าน</label>
                <div class="relative transition-all duration-300 transform group-hover:scale-[1.01]">
                    <i
                        class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-orange-500 transition-colors"></i>
                    <input type="password" name="password"
                        class="w-full pl-12 pr-4 py-3 rounded-xl bg-white/80 border border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 outline-none transition-all"
                        required>
                </div>
            </div>

            <button type="submit"
                class="w-full bg-gradient-to-r from-orange-500 to-red-600 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-orange-500/40 hover:-translate-y-1 hover:scale-[1.02] transition-all duration-300 font-prompt text-lg mt-6">
                <i class="fas fa-user-plus mr-2"></i> ยืนยันการสมัคร
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-100/50 text-center">
            <p class="text-gray-500 text-sm mb-3">มีบัญชีอยู่แล้ว?</p>
            <a href="login"
                class="inline-block px-6 py-2 rounded-full bg-orange-50 text-orange-600 font-bold hover:bg-orange-100 hover:text-orange-700 transition-colors text-sm font-prompt">
                เข้าสู่ระบบ
            </a>
            <div class="mt-6 text-center">
                <a href="/"
                    class="text-gray-400 hover:text-white transition-colors text-sm flex items-center justify-center gap-2 group">
                    <i class="fas fa-home group-hover:scale-110 transition-transform"></i> กลับหน้าหลัก
                </a>
            </div>
        </div>

        <script>
            // Hide loader
            window.addEventListener('load', function () {
                setTimeout(function () {
                    var loader = document.getElementById('loader-wrapper');
                    loader.classList.add('hidden');
                }, 600);
            });
        </script>
</body>

</html>