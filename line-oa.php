<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="icon/icons.png?v=4">
    <title>🍣 Line Official | ซูชิละกัน</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'display': ['Prompt', 'sans-serif'],
                        'body': ['Sarabun', 'sans-serif'],
                    },
                    animation: {
                        'float': 'float 4s ease-in-out infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-15px)' },
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
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #FFF8F0;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #F97316, #EA580C);
            border-radius: 10px;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(180deg, #FFF9F0 0%, #FFEDD5 50%, #FED7AA 100%);
            background-attachment: fixed;
            color: #7C2D12;
            min-height: 100vh;
        }

        .card-orange {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(249, 115, 22, 0.1);
            border: 1px solid rgba(249, 115, 22, 0.1);
        }

        .text-gradient-orange {
            background: linear-gradient(135deg, #F97316, #EA580C, #C2410C);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .floating {
            animation: float 4s ease-in-out infinite;
        }
    </style>
</head>

<body class="antialiased flex items-center justify-center p-6 relative overflow-hidden">

    <!-- Background Decorations -->
    <div class="absolute top-20 left-10 text-8xl opacity-20 floating hidden lg:block">🍣</div>
    <div class="absolute bottom-24 right-16 text-7xl opacity-15 floating hidden lg:block"
        style="animation-delay: 1.5s;">🍱</div>

    <div class="max-w-md w-full relative z-10">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <a href="/"
                class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-3xl shadow-lg mb-4 transform hover:scale-110 transition-all duration-300 cursor-pointer group">
                <span class="text-4xl group-hover:animate-bounce">🍣</span>
            </a>
            <h1 class="text-3xl font-display font-extrabold text-gradient-orange mb-1">ซูชิละกัน</h1>
            <p class="text-orange-600 font-display font-semibold">SUSHI PARADISE</p>
        </div>

        <!-- Main Card -->
        <div class="card-orange p-8 text-center">
            <!-- Line Badge -->
            <div
                class="mb-6 inline-flex items-center gap-2 bg-[#06C755] text-white px-6 py-2 rounded-full font-display font-bold text-sm shadow-lg">
                <i class="fab fa-line text-lg"></i>
                <span>LINE OFFICIAL</span>
            </div>

            <!-- QR Code -->
            <div class="mb-6">
                <img src="LINEOA/LINEOA.jpg" alt="Line QR Code"
                    class="w-full h-auto rounded-2xl shadow-lg mx-auto max-w-[280px]">
            </div>

            <p class="text-orange-600 mb-6">สแกนเพื่อเพิ่มเพื่อน</p>

            <!-- Back Button -->
            <a href="/"
                class="inline-flex items-center gap-2 text-orange-500 hover:text-orange-600 font-display font-semibold transition-colors group">
                <i class="fas fa-arrow-left group-hover:-translate-x-1 transition-transform"></i>
                กลับหน้าหลัก
            </a>
        </div>

        <!-- Footer -->
        <p class="text-center mt-6 text-orange-400 text-sm">
            © 2026 ซูชิละกัน Paradise
        </p>
    </div>

</body>

</html>