<?php
session_start();
$error_message = $_SESSION['register_error'] ?? '';
$success_message = $_SESSION['register_success'] ?? '';
unset($_SESSION['register_error']);
unset($_SESSION['register_success']);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="icon/icons.png?v=4">
    <title>🍣 สมัครสมาชิก | ซูชิละกัน</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'orange': {
                            50: '#FFF8F0', 100: '#FFEDD5', 200: '#FED7AA', 300: '#FDBA74',
                            400: '#FB923C', 500: '#F97316', 600: '#EA580C', 700: '#C2410C',
                        },
                    },
                    fontFamily: {
                        'display': ['Prompt', 'sans-serif'],
                        'body': ['Sarabun', 'sans-serif'],
                    },
                    animation: {
                        'float': 'float 4s ease-in-out infinite',
                        'float-delay': 'float 4s ease-in-out infinite 2s',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        },
                    }
                }
            }
        }
    </script>

    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #FFF9F0 0%, #FFEDD5 30%, #FED7AA 60%, #FDBA74 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(249, 115, 22, 0.2);
            box-shadow: 0 25px 50px rgba(249, 115, 22, 0.15);
        }

        .input-field {
            transition: all 0.3s ease;
        }

        .input-field:focus {
            transform: scale(1.02);
            box-shadow: 0 0 20px rgba(249, 115, 22, 0.25);
        }

        .btn-gradient {
            background: linear-gradient(135deg, #F97316, #EA580C);
            box-shadow: 0 10px 30px rgba(249, 115, 22, 0.4);
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            background: linear-gradient(135deg, #EA580C, #C2410C);
            box-shadow: 0 15px 40px rgba(234, 88, 12, 0.5);
            transform: translateY(-3px);
        }

        .floating {
            animation: float 4s ease-in-out infinite;
        }

        .floating-delay {
            animation: float 4s ease-in-out infinite 2s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert {
            animation: slideDown 0.5s ease-out;
        }

        .alert-error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>

<body class="flex items-center justify-center min-h-screen p-4 relative overflow-hidden">

    <!-- Floating Decorations -->
    <div class="absolute top-16 left-16 text-7xl opacity-30 floating hidden md:block">🍣</div>
    <div class="absolute bottom-16 right-16 text-6xl opacity-25 floating-delay hidden md:block">🍤</div>
    <div class="absolute top-1/4 right-24 text-5xl opacity-20 floating hidden md:block">🥢</div>
    <div class="absolute bottom-1/4 left-24 text-4xl opacity-15 floating-delay hidden md:block">🍱</div>

    <!-- Loader -->
    <div id="loader"
        class="fixed inset-0 bg-gradient-to-br from-orange-400 to-orange-600 z-50 flex flex-col items-center justify-center transition-opacity duration-500">
        <div class="relative">
            <div class="w-20 h-20 rounded-full border-4 border-white/30 border-t-white animate-spin"></div>
            <div class="absolute inset-0 flex items-center justify-center text-4xl animate-bounce">🍣</div>
        </div>
        <p class="mt-6 text-white font-display text-xl font-bold">ซูชิละกัน</p>
    </div>

    <!-- Register Card -->
    <div class="glass-card rounded-3xl p-10 w-full max-w-md relative z-10">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="text-6xl mb-4">🍣</div>
            <h1
                class="text-3xl font-display font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-orange-700">
                สมัครสมาชิก
            </h1>
            <p class="text-orange-400 mt-2">เข้าร่วมครอบครัวซูชิละกัน</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error" id="alertMessage">
                <i class="fas fa-exclamation-circle text-2xl"></i>
                <span class="font-body font-semibold"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" id="alertMessage">
                <i class="fas fa-check-circle text-2xl"></i>
                <span class="font-body font-semibold"><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="register" method="POST" class="space-y-5">
            <!-- Username -->
            <div>
                <label class="block text-orange-700 font-display font-semibold mb-2">
                    <i class="fas fa-user mr-2 text-orange-500"></i> ชื่อผู้ใช้
                </label>
                <div class="relative">
                    <input type="text" name="username" placeholder="ตั้งชื่อผู้ใช้ของคุณ" required
                        class="input-field w-full px-5 py-4 pl-12 rounded-2xl border-2 border-orange-200 bg-white text-orange-800 placeholder-orange-300 font-body focus:border-orange-500 focus:outline-none">
                    <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-orange-400 text-lg"></i>
                </div>
            </div>

            <!-- Email -->
            <div>
                <label class="block text-orange-700 font-display font-semibold mb-2">
                    <i class="fas fa-envelope mr-2 text-orange-500"></i> อีเมล
                </label>
                <div class="relative">
                    <input type="email" name="email" placeholder="example@email.com" required
                        class="input-field w-full px-5 py-4 pl-12 rounded-2xl border-2 border-orange-200 bg-white text-orange-800 placeholder-orange-300 font-body focus:border-orange-500 focus:outline-none">
                    <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-orange-400 text-lg"></i>
                </div>
            </div>

            <!-- Password -->
            <div>
                <label class="block text-orange-700 font-display font-semibold mb-2">
                    <i class="fas fa-lock mr-2 text-orange-500"></i> รหัสผ่าน
                </label>
                <div class="relative">
                    <input type="password" name="password" placeholder="ตั้งรหัสผ่านที่ปลอดภัย" required
                        class="input-field w-full px-5 py-4 pl-12 rounded-2xl border-2 border-orange-200 bg-white text-orange-800 placeholder-orange-300 font-body focus:border-orange-500 focus:outline-none">
                    <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-orange-400 text-lg"></i>
                </div>
            </div>

            <!-- Confirm Password -->
            <div>
                <label class="block text-orange-700 font-display font-semibold mb-2">
                    <i class="fas fa-shield-alt mr-2 text-orange-500"></i> ยืนยันรหัสผ่าน
                </label>
                <div class="relative">
                    <input type="password" name="confirm_password" placeholder="กรอกรหัสผ่านอีกครั้ง" required
                        class="input-field w-full px-5 py-4 pl-12 rounded-2xl border-2 border-orange-200 bg-white text-orange-800 placeholder-orange-300 font-body focus:border-orange-500 focus:outline-none">
                    <i class="fas fa-shield-alt absolute left-4 top-1/2 -translate-y-1/2 text-orange-400 text-lg"></i>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-4 pt-4">
                <button type="button" onclick="window.location.href='formlogin'"
                    class="flex-1 py-4 rounded-2xl font-display font-bold text-orange-600 bg-orange-100 border-2 border-orange-200 hover:bg-orange-200 hover:border-orange-300 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-arrow-left"></i> กลับ
                </button>
                <button type="submit"
                    class="flex-1 py-4 rounded-2xl font-display font-bold text-white btn-gradient flex items-center justify-center gap-2">
                    สมัครเลย <i class="fas fa-user-plus"></i>
                </button>
            </div>
        </form>

        <!-- Login Link -->
        <div class="text-center mt-8 pt-6 border-t border-orange-100">
            <p class="text-orange-500">
                มีบัญชีอยู่แล้วใช่ไหม?
                <a href="formlogin"
                    class="font-display font-bold text-orange-600 hover:text-orange-700 underline decoration-2 underline-offset-4 hover:decoration-orange-500 transition-colors ml-1">
                    เข้าสู่ระบบเลย!
                </a>
            </p>
        </div>

        <!-- Home Link -->
        <div class="text-center mt-4">
            <a href="index" class="text-sm text-orange-400 hover:text-orange-600 transition-colors">
                <i class="fas fa-home mr-1"></i> กลับหน้าหลัก
            </a>
        </div>
    </div>

    <script>
        window.addEventListener('load', () => {
            setTimeout(() => {
                const loader = document.getElementById('loader');
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 500);
            }, 800);

            // Auto-dismiss alert after 5 seconds
            const alert = document.getElementById('alertMessage');
            if (alert) {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    alert.style.transition = 'all 0.5s ease-out';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            }
        });
    </script>
</body>

</html>