<?php
// เริ่ม Session
session_start();

// ตั้งค่า Timezone
date_default_timezone_set('Asia/Bangkok');

// นำเข้าการเชื่อมต่อฐานข้อมูล
require_once 'db_config.php';

// ตรวจสอบการเชื่อมต่อ
$connection_error = null;
if ($conn->connect_error) {
    $connection_error = "Connection failed: " . $conn->connect_error;
}

// รับค่าคำค้นหา
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_query_param = "%" . $search_term . "%";

// === Visitor Counter Logic ===
$counter_file = 'visitor_count.txt';
if (!file_exists($counter_file)) {
    file_put_contents($counter_file, '0');
}
if (!isset($_SESSION['has_visited'])) {
    $current_visitors = (int) file_get_contents($counter_file);
    $current_visitors++;
    file_put_contents($counter_file, $current_visitors);
    $_SESSION['has_visited'] = true;
} else {
    $current_visitors = (int) file_get_contents($counter_file);
}

// === Member Counter Logic ===
$member_count = 0;
if ($conn && !$conn->connect_error) {
    $count_sql = "SELECT COUNT(*) as total FROM customers";
    $count_result = $conn->query($count_sql);
    if ($count_result) {
        $row = $count_result->fetch_assoc();
        $member_count = $row['total'];
    }
}

// === Store Hours Logic ===
$store_open_time = '10:00';
$store_close_time = '20:00';
$manual_override = 'auto';
$is_store_open = false;
$store_status_text = '';
$store_status_color = '';

if ($conn && !$conn->connect_error) {
    $store_result = $conn->query("SELECT setting_key, setting_value FROM store_settings WHERE setting_key IN ('store_open_time', 'store_close_time', 'manual_override')");
    if ($store_result) {
        while ($store_row = $store_result->fetch_assoc()) {
            if ($store_row['setting_key'] == 'store_open_time') {
                $store_open_time = $store_row['setting_value'];
            } elseif ($store_row['setting_key'] == 'store_close_time') {
                $store_close_time = $store_row['setting_value'];
            } elseif ($store_row['setting_key'] == 'manual_override') {
                $manual_override = $store_row['setting_value'];
            }
        }
    }
}

// Calculate store status
$current_time = date('H:i');
if ($manual_override == 'force_open') {
    $is_store_open = true;
    $store_status_text = 'เปิดทำการ';
    $store_status_color = '#10b981';
} elseif ($manual_override == 'force_closed') {
    $is_store_open = false;
    $store_status_text = 'ปิดทำการ';
    $store_status_color = '#ef4444';
} else {
    // Auto mode - check time
    if ($current_time >= $store_open_time && $current_time < $store_close_time) {
        $is_store_open = true;
        $store_status_text = 'เปิดทำการ';
        $store_status_color = '#10b981';
    } else {
        $is_store_open = false;
        $store_status_text = 'ปิดทำการ';
        $store_status_color = '#ef4444';
    }
}


// ฟังก์ชันสำหรับกำหนดหมวดหมู่จากชื่อสินค้า (สำหรับซูชิ)
function categorizeProduct($productName)
{
    $nameLower = mb_strtolower($productName, 'UTF-8');

    // ซูชิหน้าดิบ (Nigiri & Sashimi)
    if (
        strpos($nameLower, 'ซาชิมิ') !== false || strpos($nameLower, 'sashimi') !== false ||
        strpos($nameLower, 'นิกิริ') !== false || strpos($nameLower, 'nigiri') !== false ||
        strpos($nameLower, 'ปลาดิบ') !== false
    ) {
        return 'ซาชิมิ & นิกิริ';
    }

    // ซูชิม้วน (Maki & Roll)
    if (
        strpos($nameLower, 'มากิ') !== false || strpos($nameLower, 'maki') !== false ||
        strpos($nameLower, 'โรล') !== false || strpos($nameLower, 'roll') !== false ||
        strpos($nameLower, 'แคลิฟอร์เนีย') !== false || strpos($nameLower, 'california') !== false
    ) {
        return 'มากิ & โรล';
    }

    // เมนูทอด & เมนูร้อน
    if (
        strpos($nameLower, 'ทอด') !== false || strpos($nameLower, 'เทมปุระ') !== false ||
        strpos($nameLower, 'tempura') !== false || strpos($nameLower, 'ไก่ทอด') !== false ||
        strpos($nameLower, 'ทาโกะยากิ') !== false || strpos($nameLower, 'ทาโก้') !== false
    ) {
        return 'เมนูทอด & เมนูร้อน';
    }

    // เครื่องดื่ม & ของหวาน
    if (
        strpos($nameLower, 'ชาเขียว') !== false || strpos($nameLower, 'โมจิ') !== false ||
        strpos($nameLower, 'ไอศกรีม') !== false || strpos($nameLower, 'น้ำ') !== false ||
        strpos($nameLower, 'เครื่องดื่ม') !== false || strpos($nameLower, 'ของหวาน') !== false
    ) {
        return 'เครื่องดื่ม & ของหวาน';
    }

    return 'เมนูหน้าร้าน';
}

$category_order = ['ซาชิมิ & นิกิริ', 'มากิ & โรล', 'เมนูทอด & เมนูร้อน', 'เครื่องดื่ม & ของหวาน', 'เมนูอื่น ๆ'];
$grouped_products = [];
?>
<!DOCTYPE html>

<html lang="th" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="icon/icons.png?v=4">
    <title>🍣 ซูชิละกัน | ร้านซูชิพรีเมียม</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'orange': {
                            50: '#FFF8F0',
                            100: '#FFEDD5',
                            200: '#FED7AA',
                            300: '#FDBA74',
                            400: '#FB923C',
                            500: '#F97316',
                            600: '#EA580C',
                            700: '#C2410C',
                            800: '#9A3412',
                            900: '#7C2D12',
                        },
                        'cream': '#FFF9F0',
                        'warm-white': '#FFFCF7',
                    },
                    fontFamily: {
                        'display': ['Prompt', 'sans-serif'],
                        'body': ['Sarabun', 'sans-serif'],
                    },
                    animation: {
                        'float': 'float 4s ease-in-out infinite',
                        'pulse-slow': 'pulse 3s ease-in-out infinite',
                        'bounce-slow': 'bounce 2s ease-in-out infinite',
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

    <!-- Leaflet CSS & Routing -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />

    <style>
        #map {
            height: 500px;
            width: 100%;
            z-index: 10;
        }

        .leaflet-routing-container {
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-radius: 1rem;
            border: none;
            padding: 1rem;
            font-family: 'Sarabun', sans-serif;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>

    <style>
        /* Scrollbar */
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
        }

        /* Glass Effect */
        .glass-orange {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(249, 115, 22, 0.2);
        }

        .glass-white {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
        }

        /* Gradient Text */
        .text-gradient-orange {
            background: linear-gradient(135deg, #F97316, #EA580C, #C2410C);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Card Styles */
        .card-orange {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(249, 115, 22, 0.1);
            border: 1px solid rgba(249, 115, 22, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .card-orange:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(249, 115, 22, 0.25);
            border-color: #F97316;
        }

        /* Button Gradient */
        .btn-orange-gradient {
            background: linear-gradient(135deg, #F97316, #EA580C);
            box-shadow: 0 10px 30px rgba(249, 115, 22, 0.4);
        }

        .btn-orange-gradient:hover {
            background: linear-gradient(135deg, #EA580C, #C2410C);
            box-shadow: 0 15px 40px rgba(234, 88, 12, 0.5);
        }

        /* Accordion */
        .accordion-btn {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 237, 213, 0.5));
            border: 2px solid rgba(249, 115, 22, 0.2);
            border-radius: 20px;
            padding: 18px 28px;
            width: 100%;
            text-align: left;
            font-family: 'Prompt', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            color: #9A3412;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .accordion-btn:hover {
            background: linear-gradient(135deg, #FFEDD5, #FED7AA);
            border-color: #F97316;
        }

        .accordion-btn.active {
            background: linear-gradient(135deg, #F97316, #EA580C);
            color: white;
            border-color: transparent;
            border-radius: 20px 20px 0 0;
            box-shadow: 0 8px 25px rgba(249, 115, 22, 0.35);
        }

        .accordion-content {
            display: none;
            background: rgba(255, 255, 255, 0.8);
            border: 2px solid rgba(249, 115, 22, 0.15);
            border-top: none;
            border-radius: 0 0 20px 20px;
            animation: slideDown 0.4s ease;
        }

        .accordion-content.show {
            display: block;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Category Grid */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
            padding: 28px;
        }

        /* Promo Badge */
        .badge-hot {
            background: linear-gradient(135deg, #DC2626, #F97316);
            animation: pulse 2s ease-in-out infinite;
        }

        Z

        /* Floating Animation */
        .floating {
            animation: float 4s ease-in-out infinite;
        }

        .floating-delay {
            animation: float 4s ease-in-out infinite 1.5s;
        }
    </style>
</head>

<body class="antialiased min-h-screen flex flex-col">

    <!-- 🍊 PREMIUM LOADER -->
    <div id="page-loader"
        class="fixed inset-0 bg-gradient-to-br from-orange-400 via-orange-500 to-orange-600 z-[9999] flex flex-col justify-center items-center">
        <div class="relative">
            <div class="w-24 h-24 rounded-full border-4 border-white/30 border-t-white animate-spin"></div>
            <div class="absolute inset-0 flex items-center justify-center text-5xl animate-bounce-slow">🍣</div>
        </div>
        <p class="mt-8 text-white font-display text-2xl font-bold tracking-wider drop-shadow-lg">ซูชิละกัน</p>
        <p class="text-white/80 text-lg mt-2">🔥 กำลังเตรียมความอร่อย...</p>
    </div>

    <!-- 🎀 TOP INFO BAR -->
    <div class="bg-gradient-to-r from-orange-500 to-orange-600 py-3 text-center text-white shadow-lg relative z-50">
        <div class="container mx-auto flex flex-wrap justify-center gap-6 md:gap-12 text-sm font-display">
            <span class="flex items-center gap-2"><i class="far fa-calendar-alt"></i> <span
                    id="current-date"></span></span>
            <span class="flex items-center gap-2"><i class="far fa-clock"></i> <span id="current-time"></span></span>
            <span class="flex items-center gap-2"><i class="fas fa-users"></i>
                <?php echo number_format($current_visitors); ?> ผู้เข้าชม</span>
            <span class="flex items-center gap-2"><i class="fas fa-user-plus"></i>
                <?php echo number_format($member_count); ?> สมาชิก</span>
        </div>
    </div>

    <!-- 🍣 STICKY HEADER -->
    <header class="sticky top-0 z-40 glass-white shadow-md border-b border-orange-100">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <!-- Logo -->
            <a href="#" class="flex items-center gap-3 group">
                <span class="text-4xl group-hover:animate-bounce transition-transform">🍣</span>
                <div class="hidden md:block">
                    <span class="text-2xl font-display font-extrabold text-gradient-orange">ซูชิละกัน</span>
                    <span class="text-xs text-orange-600 block -mt-1 font-semibold">SUSHI PARADISE</span>
                </div>
            </a>

            <!-- Desktop Nav -->
            <ul class="hidden md:flex items-center gap-8 font-display font-semibold">
                <li><a href="#home"
                        class="text-orange-800 hover:text-orange-500 transition-colors py-2 flex items-center gap-2"><i
                            class="fas fa-home"></i> หน้าแรก</a></li>
                <li><a href="#menu"
                        class="text-orange-800 hover:text-orange-500 transition-colors py-2 flex items-center gap-2"><i
                            class="fas fa-utensils"></i> เมนู</a></li>
                <li>
                    <a href="login"
                        class="btn-orange-gradient text-white px-7 py-3 rounded-full font-bold hover:scale-105 transition-all flex items-center gap-2 shadow-lg shadow-orange-500/30">
                        <i class="fas fa-gift"></i> รับส่วนลด
                    </a>
                </li>
            </ul>


            <!-- Mobile Menu Button -->
            <button class="md:hidden text-3xl text-orange-500"
                onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                <i class="fas fa-bars"></i>
            </button>
        </nav>

        <!-- Mobile Nav -->
        <div id="mobile-menu"
            class="hidden md:hidden bg-white border-t border-orange-100 p-6 flex flex-col gap-4 font-display font-bold text-center shadow-lg">
            <a href="#home" class="py-3 text-orange-700 hover:text-orange-500 rounded-xl hover:bg-orange-50">🏠
                หน้าแรก</a>
            <a href="#menu" class="py-3 text-orange-700 hover:text-orange-500 rounded-xl hover:bg-orange-50">🍱 เมนู</a>
            <a href="login" class="btn-orange-gradient text-white py-4 rounded-2xl mt-2 shadow-lg"><i
                    class="fas fa-gift mr-2"></i> รับส่วนลด</a>
        </div>

    </header>


    <!-- 🏠 HERO SECTION -->
    <section id="home"
        class="relative min-h-[90vh] flex items-center justify-center text-center px-6 overflow-hidden py-16">

        <!-- VIDEO BACKGROUND -->
        <div id="video-container" class="absolute inset-0 w-full h-full z-0">
            <video id="hero-video" autoplay muted loop playsinline class="absolute inset-0 w-full h-full object-cover">
                <source src="video/Sushi.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <!-- Overlay for readability -->
            <div class="absolute inset-0 bg-black/40 z-10"></div>

            <!-- 🖼️ ELEGANT FRAME -->
            <div
                class="absolute inset-0 z-20 pointer-events-none border-[10px] border-orange-500/40 shadow-[inset_0_0_30px_rgba(0,0,0,0.5)]">
            </div>
            <div class="absolute inset-6 z-20 pointer-events-none border-[1px] border-white/40 rounded-3xl"></div>

            <!-- VIDEO CONTROLS -->
            <div class="absolute bottom-8 right-8 z-50 flex flex-col gap-3">
                <!-- Mute/Unmute -->
                <button onclick="toggleMute()"
                    class="w-12 h-12 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center text-orange-600 hover:bg-orange-500 hover:text-white transition-all shadow-lg"
                    title="เปิด/ปิดเสียง">
                    <i id="mute-icon" class="fas fa-volume-mute"></i>
                </button>
            </div>
        </div>

        <!-- Background Decorations (Fallback) -->
        <div class="absolute top-20 left-10 text-8xl opacity-30 floating hidden lg:block z-0 pointer-events-none">🍣
        </div>
        <div
            class="absolute bottom-24 right-16 text-7xl opacity-25 floating-delay hidden lg:block z-0 pointer-events-none">
            �</div>

        <!-- Hero Content -->
        <div class="relative z-20 max-w-5xl mx-auto text-white">
            <div
                class="inline-block bg-orange-100/90 text-orange-600 px-6 py-2 rounded-full font-display font-bold text-sm mb-6 animate-pulse-slow backdrop-blur-sm">
                ✨ PREMIUM JAPANESE RESTAURANT ✨
            </div>

            <!-- 🍣 STORE STATUS BANNER - JAPANESE STYLE -->
            <div class="mb-8 flex justify-center">
                <div class="relative">
                    <!-- Subtle glow effect -->
                    <div class="absolute inset-0 bg-red-300 rounded-2xl blur-lg opacity-20 animate-pulse-slow"></div>

                    <!-- Main card - Japanese style -->
                    <div class="relative rounded-2xl px-8 py-5 shadow-2xl border-3 backdrop-blur-xl" style="background: linear-gradient(135deg, #FFF9F0 0%, #FFEDD5 100%); 
                                border: 3px solid #F97316;">
                        <div class="flex flex-col md:flex-row items-center gap-5">
                            <!-- Status Icon with circle background -->
                            <div class="relative">
                                <div class="w-16 h-16 rounded-full flex items-center justify-center" style="background: <?php echo $is_store_open ? 'linear-gradient(135deg, #86efac, #22c55e)' : 'linear-gradient(135deg, #fca5a5, #ef4444)'; ?>; 
                                            box-shadow: 0 4px 15px rgba(0,0,0,0.15);">
                                    <span class="text-3xl">
                                        <?php echo $is_store_open ? '✓' : '✕'; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Status Text -->
                            <div class="text-center md:text-left flex-1">
                                <!-- Status -->
                                <div class="text-2xl md:text-3xl font-display font-extrabold mb-2" style="color: <?php echo $is_store_open ? '#059669' : '#dc2626'; ?>; 
                                            font-family: 'Prompt', sans-serif;">
                                    <?php echo $store_status_text; ?>
                                </div>

                                <?php if ($manual_override == 'auto'): ?>
                                    <!-- Opening Hours - แสดงเฉพาะโหมดอัตโนมัติ -->
                                    <div
                                        class="flex items-center justify-center md:justify-start gap-2 text-orange-800 text-lg md:text-xl font-semibold">
                                        <i class="fas fa-clock text-orange-600"></i>
                                        <span>
                                            เปิด <?php echo $store_open_time; ?> - <?php echo $store_close_time; ?> น.
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Decorative sushi emoji -->
                            <div class="hidden md:block text-4xl opacity-60">
                                🍣
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // Update current time in hero section
                function updateHeroTime() {
                    const now = new Date();
                    const timeStr = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    const heroTimeEl = document.getElementById('hero-current-time');
                    if (heroTimeEl) {
                        heroTimeEl.textContent = timeStr;
                    }
                }
                setInterval(updateHeroTime, 1000);
                updateHeroTime();
            </script>

            <h1 class="text-5xl md:text-7xl lg:text-8xl font-display font-extrabold mb-6 leading-tight drop-shadow-lg">
                <span class="text-white text-gradient-orange-on-dark">ซูชิละกัน</span><br>
                <span class="text-white/90">SUSHI PARADISE</span>
            </h1>

            <p class="text-xl md:text-2xl text-white/90 mb-10 max-w-2xl mx-auto leading-relaxed drop-shadow-md">
                🍣 ซูชิพรีเมียม วัตถุดิบสดใหม่ทุกวัน<br>
                รสชาติต้นตำรับญี่ปุ่นแท้ๆ ในบรรยากาศอบอุ่น
            </p>

            <div class="flex flex-col sm:flex-row gap-5 justify-center items-center mb-12">
                <a href="#menu"
                    class="group btn-orange-gradient text-white text-xl font-display font-bold px-12 py-5 rounded-full hover:scale-105 transition-all flex items-center gap-3 shadow-orange-500/50 shadow-lg">
                    <span>🔥 ดูเมนูซูชิ</span>
                    <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        </div>

        <script>
            const video = document.getElementById('hero-video');
            const muteIcon = document.getElementById('mute-icon');

            // Ensure autoplay works
            window.addEventListener('load', () => {
                video.play().catch(error => {
                    console.log("Autoplay prevented:", error);
                });
            });

            function toggleMute() {
                video.muted = !video.muted;
                if (video.muted) {
                    muteIcon.className = 'fas fa-volume-mute';
                } else {
                    muteIcon.className = 'fas fa-volume-up';
                }
            }
        </script>
    </section>


    <!-- 🍱 MENU SECTION -->
    <section id="menu" class="relative py-24 bg-gradient-to-b from-transparent via-orange-50/50 to-transparent">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <div class="inline-block bg-orange-100 text-orange-600 px-4 py-1 rounded-full text-sm font-bold mb-3">
                    🍱 SIGNATURE MENU
                </div>
                <h2 class="text-4xl md:text-5xl font-display font-extrabold text-orange-800 mb-4">เมนูแนะนำยอดนิยม</h2>
                <div class="w-32 h-1.5 bg-gradient-to-r from-orange-400 to-orange-600 mx-auto rounded-full mb-10"></div>

                <!-- Search Box -->
                <form action="#menu" method="GET" class="max-w-md mx-auto relative">
                    <input type="text" name="search" placeholder="🔍 ค้นหาเมนูที่ต้องการ..."
                        value="<?php echo htmlspecialchars($search_term); ?>"
                        class="w-full pl-6 pr-14 py-4 rounded-2xl bg-white border-2 border-orange-200 text-orange-800 placeholder-orange-300 font-display focus:border-orange-500 focus:ring-4 focus:ring-orange-200 outline-none transition-all shadow-sm">
                    <button type="submit"
                        class="absolute right-3 top-1/2 -translate-y-1/2 bg-orange-500 text-white w-10 h-10 rounded-xl hover:bg-orange-600 transition-colors">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <?php if (isset($_SESSION['upload_status']) && $_SESSION['upload_status'] == 'success'): ?>
                <div
                    class="max-w-2xl mx-auto mb-10 bg-green-50 border-l-4 border-green-500 text-green-700 p-5 rounded-xl flex items-center gap-4 shadow-sm">
                    <i class="fas fa-check-circle text-3xl text-green-500"></i>
                    <div>
                        <p class="font-bold">✅ บันทึกสินค้าใหม่เรียบร้อยแล้ว!</p>
                        <p class="text-sm text-green-600/70">คุณสามารถดูสินค้าที่เพิ่งเพิ่มได้ด้านล่าง</p>
                    </div>
                </div>
                <?php unset($_SESSION['upload_status']); ?>
            <?php endif; ?>

            <div class="max-w-6xl mx-auto space-y-6">
                <?php
                if ($conn && $connection_error === null) {
                    $sql = "SELECT id, name, price, description, image_path, stock_quantity FROM products";
                    if (!empty($search_term)) {
                        $sql .= " WHERE name LIKE ? OR description LIKE ?";
                    }
                    $sql .= " ORDER BY id DESC";

                    $stmt = $conn->prepare($sql);
                    if (!empty($search_term)) {
                        $stmt->bind_param("ss", $search_query_param, $search_query_param);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $category = categorizeProduct($row["name"]);
                            $grouped_products[$category][] = $row;
                        }

                        $displayed_categories = array_keys($grouped_products);
                        $sorted_categories = [];
                        foreach ($category_order as $cat) {
                            if (in_array($cat, $displayed_categories)) {
                                $sorted_categories[] = $cat;
                                $key = array_search($cat, $displayed_categories);
                                unset($displayed_categories[$key]);
                            }
                        }
                        $sorted_categories = array_merge($sorted_categories, $displayed_categories);
                        $category_count = 0;

                        foreach ($sorted_categories as $category) {
                            $category_count++;
                            $category_id = "collapse" . $category_count;
                            $isOpen = $category_count == 1;

                            $icon = '✨';
                            if (strpos($category, 'ซาชิมิ') !== false)
                                $icon = '🍣';
                            elseif (strpos($category, 'มากิ') !== false || strpos($category, 'โรล') !== false)
                                $icon = '🍱';
                            elseif (strpos($category, 'ทอด') !== false || strpos($category, 'ร้อน') !== false)
                                $icon = '🍤';
                            elseif (strpos($category, 'เครื่องดื่ม') !== false || strpos($category, 'ของหวาน') !== false)
                                $icon = '🍵';
                            ?>
                            <div class="accordion-item">
                                <button class="accordion-btn <?php echo $isOpen ? 'active' : ''; ?>"
                                    onclick="toggleAccordion('<?php echo $category_id; ?>', this)">
                                    <span class="flex items-center gap-4">
                                        <span class="text-3xl"><?php echo $icon; ?></span>
                                        <span><?php echo $category; ?></span>
                                    </span>
                                    <i
                                        class="fas fa-chevron-down transition-transform duration-300 <?php echo $isOpen ? 'rotate-180' : ''; ?>"></i>
                                </button>

                                <div id="<?php echo $category_id; ?>"
                                    class="accordion-content <?php echo $isOpen ? 'show' : ''; ?>">
                                    <div class="category-grid">
                                        <?php foreach ($grouped_products[$category] as $row): ?>
                                            <div class="card-orange group cursor-pointer overflow-hidden"
                                                onclick="openModal('<?php echo htmlspecialchars($row['image_path']); ?>', '<?php echo htmlspecialchars($row['name']); ?>', '<?php echo htmlspecialchars(addslashes($row['description'])); ?>', '<?php echo number_format($row['price']); ?>', '<?php echo $row['stock_quantity']; ?>')">

                                                <div class="relative h-52 overflow-hidden">
                                                    <?php if (!empty($row['image_path'])): ?>
                                                        <img src="<?php echo htmlspecialchars($row['image_path']); ?>"
                                                            alt="<?php echo htmlspecialchars($row['name']); ?>"
                                                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                                    <?php else: ?>
                                                        <div
                                                            class="w-full h-full bg-gradient-to-br from-orange-50 to-orange-100 flex items-center justify-center">
                                                            <i class="fas fa-utensils text-5xl text-orange-200"></i>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Price Badge -->
                                                    <div
                                                        class="absolute top-4 right-4 bg-gradient-to-r from-orange-500 to-orange-600 text-white font-display font-bold px-4 py-2 rounded-full shadow-lg">
                                                        <?php echo number_format($row['price']); ?>฿
                                                    </div>
                                                </div>

                                                <div class="p-5">
                                                    <h4
                                                        class="font-display font-bold text-lg text-orange-800 mb-2 group-hover:text-orange-500 transition-colors">
                                                        <?php echo htmlspecialchars($row['name']); ?>
                                                    </h4>
                                                    <div class="flex justify-between items-center mb-4">
                                                        <p class="text-sm text-orange-600/60 line-clamp-2">
                                                            <?php echo htmlspecialchars($row['description']); ?>
                                                        </p>
                                                    </div>

                                                    <!-- Stock Display -->
                                                    <div class="mb-3 flex items-center gap-2 text-sm font-bold">
                                                        <?php if ($row['stock_quantity'] > 0): ?>
                                                            <span class="text-green-600 bg-green-100 px-3 py-1 rounded-full">
                                                                <i class="fas fa-box-open mr-1"></i>
                                                                <?php echo number_format($row['stock_quantity']); ?> ชิ้น
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-red-600 bg-red-100 px-3 py-1 rounded-full">
                                                                <i class="fas fa-times-circle mr-1"></i> สินค้าหมด
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <button
                                                        class="w-full bg-orange-100 text-orange-600 font-display font-bold py-3 rounded-xl group-hover:bg-orange-500 group-hover:text-white transition-all">
                                                        <i class="fas fa-list mr-2"></i> รายละเอียด
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo "<div class='text-center py-16 card-orange'><i class='fas fa-search text-5xl text-orange-200 mb-4'></i><p class='text-orange-400 font-display text-xl'>ไม่พบเมนูที่ค้นหา</p></div>";
                    }
                    $stmt->close();
                }
                ?>
            </div>
        </div>
    </section>



    <!-- 🍊 FOOTER -->
    <footer class="bg-gradient-to-r from-orange-500 to-orange-600 py-16 mt-auto">
        <div class="container mx-auto px-6 text-center text-white">
            <div class="text-6xl mb-6">🍣</div>
            <h2 class="text-3xl font-display font-bold mb-4">ซูชิละกัน PARADISE</h2>
            <p class="text-white/80 mb-8 max-w-md mx-auto">Premium Japanese Restaurant<br>อร่อย สด สะอาด
                ต้องที่ซูชิละกัน 🍣</p>

            <div class="flex justify-center gap-5 mb-10">
                <a href="https://www.facebook.com/share/1Bod121vTg/"
                    class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center hover:bg-white hover:text-orange-500 transition-all text-xl"><i
                        class="fab fa-facebook-f"></i></a>
                <a href="line-oa"
                    class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center hover:bg-white hover:text-orange-500 transition-all text-xl"><i
                        class="fab fa-line"></i></a>
                <a href="https://script.google.com/macros/s/AKfycbxm-bY7nKXI5Hzy76QZ_zOc0dlv_a140AML8piJiBpj_dGcBNuXQP4iLaJCuM6TWR13/exec"
                    class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center hover:bg-white hover:text-orange-500 transition-all text-xl">
                    <i class="fa-solid fa-comments"></i></a>
            </div>



            <p class="text-white/60 text-sm">© 2026 ซูชิละกัน Paradise. All Rights Reserved.</p>
            <a href="admin_login" class="inline-block mt-4 text-white/20 hover:text-white/40 text-xs transition-colors">
                <i class="fas fa-lock"></i>
            </a>
        </div>
    </footer>


    <!-- 🛒 PRODUCT MODAL -->
    <div id="productModal"
        class="fixed inset-0 z-[99999] hidden flex justify-center items-center bg-black/60 backdrop-blur-md p-6">
        <div
            class="bg-white rounded-3xl p-8 max-w-lg w-full relative shadow-2xl transform scale-100 transition-all border-2 border-orange-100">
            <button onclick="closeModal()"
                class="absolute top-4 right-4 w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center text-orange-500 hover:bg-orange-500 hover:text-white transition-all text-xl font-bold">
                &times;
            </button>

            <div class="flex flex-col items-center text-center">
                <div
                    class="w-full h-64 bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl mb-6 flex items-center justify-center overflow-hidden">
                    <img id="modalImg" src="" alt="" class="max-w-full max-h-full object-contain drop-shadow-lg">
                </div>

                <h3 id="modalName" class="text-2xl font-display font-bold text-orange-700 mb-3"></h3>
                <p id="modalDesc" class="text-orange-600/60 mb-4 leading-relaxed"></p>

                <div id="modalStock" class="mb-6 font-bold text-lg"></div>

                <div id="modalPrice" class="text-4xl font-display font-extrabold text-gradient-orange mb-6"></div>

                <button onclick="closeModal()"
                    class="w-full btn-orange-gradient text-white font-display font-bold text-xl py-4 rounded-2xl hover:scale-[1.02] transition-transform">
                    ปิดหน้าต่าง
                </button>
            </div>
        </div>
    </div>

    <script>

        window.addEventListener('load', () => {
            const loader = document.getElementById('page-loader');
            setTimeout(() => {
                loader.style.opacity = '0';
                loader.style.transition = 'opacity 0.5s ease';
                setTimeout(() => loader.style.display = 'none', 500);
            }, 1500);
        });

        // Clock
        function updateTime() {
            const now = new Date();
            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').textContent = now.toLocaleDateString('th-TH', dateOptions);
            document.getElementById('current-time').textContent = now.toLocaleTimeString('th-TH');
        }
        setInterval(updateTime, 1000);
        updateTime();

        // Accordion
        function toggleAccordion(id, btn) {
            const content = document.getElementById(id);
            const icon = btn.querySelector('i');
            const isHidden = !content.classList.contains('show');

            if (isHidden) {
                content.classList.add('show');
                btn.classList.add('active');
                icon.classList.add('rotate-180');
            } else {
                content.classList.remove('show');
                btn.classList.remove('active');
                icon.classList.remove('rotate-180');
            }
        }

        // Modal
        function openModal(img, name, desc, price, stock) {
            document.getElementById('modalImg').src = img || '';
            document.getElementById('modalName').textContent = name;
            document.getElementById('modalDesc').textContent = desc || 'ไม่มีคำอธิบาย';
            document.getElementById('modalPrice').textContent = price + '.-';

            const stockEl = document.getElementById('modalStock');
            if (stock > 0) {
                stockEl.innerHTML = `<span class="text-green-600 bg-green-100 px-4 py-2 rounded-full"><i class="fas fa-box-open mr-2"></i> ${stock} ชิ้น</span>`;
            } else {
                stockEl.innerHTML = `<span class="text-red-600 bg-red-100 px-4 py-2 rounded-full"><i class="fas fa-times-circle mr-2"></i> สินค้าหมด</span>`;
            }

            document.getElementById('productModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('productModal').classList.add('hidden');
        }

        document.getElementById('productModal').addEventListener('click', function (e) {
            if (e.target === this) closeModal();
        });
    </script>

    <!-- Three.js - Load at end for better performance -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="js/three_bg.js"></script>
</body>

</html>
<?php
if ($conn !== null) {
    $conn->close();
}
?>