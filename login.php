<?php
session_start();
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = $_POST['username']; // Can be username or email
    $password = $_POST['password'];

    // Check against Username OR Email
    $stmt = $conn->prepare("SELECT id, password, full_name, points FROM customers WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $login_input, $login_input);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password, $full_name, $points);
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION['customer_id'] = $id;
            $_SESSION['customer_name'] = $full_name;
            header("Location: points");
            exit;
        } else {
            $error = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $error = "ไม่พบชื่อผู้ใช้หรืออีเมลนี้";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="icon/icons.png?v=4">
    <title>เข้าสู่ระบบสมาชิก | Member Login</title>
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
            overflow: hidden;
            /* Prevent scrolls from particles */
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

    <!-- Login Container -->
    <div class="glass-box p-8 rounded-3xl w-full max-w-md relative z-10 animate-fade-in-up">
        <div class="text-center mb-8">
            <div class="text-7xl mb-4 drop-shadow-lg">🍣</div>
            <h2
                class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-orange-600 to-red-600 font-prompt">
                สมาชิกร้านซูชิละกัน
            </h2>
            <p class="text-gray-500 font-prompt mt-1">Welcome Back!</p>
        </div>

        <?php if (isset($error)): ?>
            <div
                class="bg-red-50 border-l-4 border-red-500 text-red-600 p-4 rounded-r-xl mb-6 text-sm font-bold flex items-center shadow-sm">
                <i class="fas fa-exclamation-circle mr-3 text-lg"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div class="relative group">
                <label class="block text-gray-700 font-bold mb-2 ml-1 text-sm">ชื่อผู้ใช้ หรือ อีเมล</label>
                <div class="relative transition-all duration-300 transform group-hover:scale-[1.01]">
                    <i
                        class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-orange-500 transition-colors"></i>
                    <input type="text" name="username"
                        class="w-full pl-12 pr-4 py-3.5 rounded-xl bg-white/80 border border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 outline-none transition-all"
                        placeholder="Username / Email" required>
                </div>
            </div>

            <div class="relative group">
                <label class="block text-gray-700 font-bold mb-2 ml-1 text-sm">รหัสผ่าน (Password)</label>
                <div class="relative transition-all duration-300 transform group-hover:scale-[1.01]">
                    <i
                        class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-hover:text-orange-500 transition-colors"></i>
                    <input type="password" name="password"
                        class="w-full pl-12 pr-4 py-3.5 rounded-xl bg-white/80 border border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 outline-none transition-all"
                        placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit"
                class="w-full bg-gradient-to-r from-orange-500 to-red-600 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-orange-500/40 hover:-translate-y-1 hover:scale-[1.02] transition-all duration-300 font-prompt text-lg">
                <i class="fas fa-sign-in-alt mr-2"></i> เข้าสู่ระบบ
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-100/50 text-center">
            <p class="text-gray-500 text-sm mb-3">ยังไม่เป็นสมาชิก?</p>
            <a href="register"
                class="inline-block px-6 py-2 rounded-full bg-orange-50 text-orange-600 font-bold hover:bg-orange-100 hover:text-orange-700 transition-colors text-sm font-prompt">
                สมัครสมาชิกใหม่
            </a>
        </div>

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