<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🍣 เข้าสู่ระบบ | มารุซูชิ Premium Sushi</title>

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

    <!-- Three.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="js/three_bg.js"></script>

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
    </style>
</head>

<body class="flex items-center justify-center min-h-screen p-4 relative overflow-hidden">

    <!-- Floating Decorations -->
    <div class="absolute top-20 left-10 text-7xl opacity-30 floating hidden md:block">🍣</div>
    <div class="absolute bottom-20 right-10 text-6xl opacity-25 floating-delay hidden md:block">🍤</div>
    <div class="absolute top-1/3 right-20 text-5xl opacity-20 floating hidden md:block">🥢</div>
    <div class="absolute bottom-1/3 left-20 text-4xl opacity-15 floating-delay hidden md:block">🍱</div>
    <div class="absolute top-10 right-1/4 text-5xl opacity-20 floating hidden lg:block">🍙</div>
    <div class="absolute bottom-10 left-1/4 text-4xl opacity-25 floating-delay hidden lg:block">🍥</div>

    <!-- Loader -->
    <div id="loader"
        class="fixed inset-0 bg-gradient-to-br from-orange-400 to-orange-600 z-50 flex flex-col items-center justify-center transition-opacity duration-500">
        <div class="relative">
            <div class="w-20 h-20 rounded-full border-4 border-white/30 border-t-white animate-spin"></div>
            <div class="absolute inset-0 flex items-center justify-center text-4xl animate-bounce">🍣</div>
        </div>
        <p class="mt-6 text-white font-display text-xl font-bold">มารุซูชิ</p>
    </div>

    <!-- Login Card -->
    <div class="glass-card rounded-3xl p-10 w-full max-w-md relative z-10">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="text-7xl mb-4 sushi-float">🍣</div>
            <h1
                class="text-3xl font-display font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-orange-700">
                เข้าสู่ระบบ
            </h1>
            <p class="text-orange-400 mt-2 font-display">🍱 ยินดีต้อนรับสู่ประสบการณ์ซูชิพรีเมียม</p>
        </div>

        <!-- Form -->
        <form action="login" method="POST" class="space-y-6">
            <!-- Username -->
            <div>
                <label class="block text-orange-700 font-display font-semibold mb-2">
                    <i class="fas fa-user mr-2 text-orange-500"></i> ชื่อผู้ใช้
                </label>
                <div class="relative">
                    <input type="text" name="username" placeholder="กรอกชื่อผู้ใช้ของคุณ" required
                        class="input-field w-full px-5 py-4 pl-12 rounded-2xl border-2 border-orange-200 bg-white text-orange-800 placeholder-orange-300 font-body focus:border-orange-500 focus:outline-none">
                    <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-orange-400 text-lg"></i>
                </div>
            </div>

            <!-- Password -->
            <div>
                <label class="block text-orange-700 font-display font-semibold mb-2">
                    <i class="fas fa-lock mr-2 text-orange-500"></i> รหัสผ่าน
                </label>
                <div class="relative">
                    <input type="password" name="password" placeholder="กรอกรหัสผ่าน" required
                        class="input-field w-full px-5 py-4 pl-12 rounded-2xl border-2 border-orange-200 bg-white text-orange-800 placeholder-orange-300 font-body focus:border-orange-500 focus:outline-none">
                    <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-orange-400 text-lg"></i>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-4 pt-4">
                <button type="button" onclick="history.back()"
                    class="flex-1 py-4 rounded-2xl font-display font-bold text-orange-600 bg-orange-100 border-2 border-orange-200 hover:bg-orange-200 hover:border-orange-300 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-arrow-left"></i> กลับ
                </button>
                <button type="submit"
                    class="flex-1 py-4 rounded-2xl font-display font-bold text-white btn-gradient flex items-center justify-center gap-2">
                    เข้าสู่ระบบ <i class="fas fa-sign-in-alt"></i>
                </button>
            </div>
        </form>

        <!-- Register Link -->
        <div class="text-center mt-8 pt-6 border-t border-orange-100">
            <p class="text-orange-500">
                ยังไม่มีบัญชีใช่ไหม?
                <a href="formregister"
                    class="font-display font-bold text-orange-600 hover:text-orange-700 underline decoration-2 underline-offset-4 hover:decoration-orange-500 transition-colors ml-1">
                    สมัครสมาชิกเลย!
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
        });
    </script>
</body>

</html>