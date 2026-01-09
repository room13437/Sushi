<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Line Official Account | ซูชิละกัน 🍣</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="icon/icons.png?v=4">
    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700&display=swap"
        rel="stylesheet">
    <!-- CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        :root {
            --primary-orange: #ff6f00;
            --secondary-red: #d32f2f;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            margin: 0;
            overflow: hidden;
            background: #fff;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Glassmorphism Card Style */
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 40px;
            border: 2px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 90%;
            max-width: 420px;
            text-align: center;
            z-index: 10;
            position: relative;
            animation: cardEntrance 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes cardEntrance {
            from {
                opacity: 0;
                transform: scale(0.8) translateY(20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .qr-frame {
            background: white;
            padding: 15px;
            border-radius: 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            display: inline-block;
            transition: transform 0.3s ease;
        }

        .qr-frame:hover {
            transform: scale(1.05);
        }

        .qr-image {
            width: 250px;
            height: 250px;
            object-fit: contain;
            border-radius: 15px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 25px;
            background: white;
            color: #666;
            border-radius: 15px;
            text-decoration: none;
            font-family: 'Prompt', sans-serif;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 20px;
            border: 1px solid #eee;
        }

        .btn-back:hover {
            background: var(--primary-orange);
            color: white;
            border-color: var(--primary-orange);
            transform: translateY(-2px);
        }

        .floating-emoji {
            position: fixed;
            font-size: 2.5rem;
            z-index: 1;
            opacity: 0.6;
            pointer-events: none;
            animation: floatEmoji 8s ease-in-out infinite;
        }

        @keyframes floatEmoji {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-40px) rotate(20deg);
            }
        }

        /* Three.js Background Container */
        #three-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
    </style>
</head>

<body>

    <!-- Three.js Background -->
    <div id="three-bg"></div>

    <!-- Floating Emojis -->
    <div class="floating-emoji" style="top: 15%; left: 10%;">🍣</div>
    <div class="floating-emoji" style="bottom: 20%; right: 12%; animation-delay: -2s;">🍱</div>
    <div class="floating-emoji" style="top: 25%; right: 15%; animation-delay: -4s;">🍤</div>
    <div class="floating-emoji" style="bottom: 15%; left: 15%; animation-delay: -6s;">🍱</div>

    <!-- Content Card -->
    <div class="glass-card">
        <div class="mb-6">
            <h1
                class="text-3xl font-bold bg-gradient-to-r from-orange-600 to-red-600 bg-clip-text text-transparent mb-2 font-display">
                LINE Official Account
            </h1>
            <p class="text-gray-500 font-semibold">ซูชิละกัน Paradise</p>
        </div>

        <div class="qr-frame">
            <!-- QR Code from LINEOA Folder -->
            <img src="LINEOA/LINEOA.jpg" alt="LINE QR Code" class="qr-image">
        </div>

        <div class="text-gray-600 mb-6 font-semibold">
            <p>สแกนเพื่อรับโปรโมชั่นใหม่ๆ</p>
            <p>และสั่งเดลิเวอรี่ผ่าน LINE</p>
        </div>

        <a href="/" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            กลับหน้าหลัก
        </a>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="js/three_bg.js"></script>

    <!-- Anti-Inspect Protection -->
    <script>
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.onkeydown = function (e) {
            if (e.keyCode == 123 || (e.ctrlKey && e.shiftKey && (e.keyCode == 73 || e.keyCode == 74 || e.keyCode == 67)) || (e.ctrlKey && e.keyCode == 85)) {
                return false;
            }
        };
    </script>
</body>

</html>