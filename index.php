<?php
// เริ่ม Session เพื่อรับข้อมูลที่ส่งมาจากการบันทึก (Flash Message)
session_start();

// ตั้งค่า Timezone เป็นประเทศไทย
date_default_timezone_set('Asia/Bangkok');

// กำหนดค่าการเชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "products";

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    $connection_error = "Connection failed: " . $conn->connect_error;
    $conn = null;
} else {
    $connection_error = null;
}

// รับค่าคำค้นหาจาก GET parameter
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_query_param = "%" . $search_term . "%";

// --- FETCH QUEUE DATA (Mini Board) ---
$conn_q = new mysqli("localhost", "root", "", "queue_system");
$queue_data = [];
if (!$conn_q->connect_error) {
    $today = date('Y-m-d');
    $sql_q = "SELECT queue_number, customer_name, status, created_at FROM daily_queue 
              WHERE queue_date = '$today' AND status = 'Waiting' 
              ORDER BY queue_number ASC LIMIT 5";
    $result_q = $conn_q->query($sql_q);
    if ($result_q) {
        while ($row = $result_q->fetch_assoc()) {
            $queue_data[] = $row;
        }
    }
    $conn_q->close();
}

// --- FETCH PROMOTIONS ---
$promotions = [];
if ($conn && $connection_error === null) {
    $sql_promo = "SELECT * FROM promotions ORDER BY id DESC";
    $result_promo = $conn->query($sql_promo);
    if ($result_promo) {
        while ($row = $result_promo->fetch_assoc()) {
            $promotions[] = $row;
        }
    }
}

// --- FETCH STORE HOURS ---
$store_open_time = '11:00';
$store_close_time = '22:00';
$store_status = 'OPEN'; // Manual override status

if ($conn && $connection_error === null) {
    $sql_hours = "SELECT setting_key, setting_value FROM store_settings WHERE setting_key IN ('store_open_time', 'store_close_time', 'store_status')";
    $result_hours = $conn->query($sql_hours);
    if ($result_hours) {
        while ($row = $result_hours->fetch_assoc()) {
            if ($row['setting_key'] === 'store_open_time') {
                $store_open_time = $row['setting_value'];
            } elseif ($row['setting_key'] === 'store_close_time') {
                $store_close_time = $row['setting_value'];
            } elseif ($row['setting_key'] === 'store_status') {
                $store_status = $row['setting_value'];
            }
        }
    }
}

// --- CHECK IF STORE IS OPEN ---
$current_time = date('H:i');
$is_store_open = false;
$store_status_text = 'ร้านปิดอยู่';
$store_status_icon = '🔴';
$store_status_color = 'bg-red-500';
$show_hours = false; // Control hours visibility

// Check manual status first
if ($store_status === 'CLOSED') {
    // Manually closed - show closed status, hide hours
    $is_store_open = false;
    $store_status_text = 'ร้านปิดอยู่';
    $store_status_icon = '🔴';
   $store_status_color = 'bg-red-500';
    $show_hours = false;
} elseif ($store_status === 'OPEN') {
    // Manually open - check time to determine status and show hours
    $show_hours = true;
    if ($current_time >= $store_open_time && $current_time <= $store_close_time) {
        $is_store_open = true;
        $store_status_text = 'ร้านเปิดอยู่';
        $store_status_icon = '🟢';
        $store_status_color = 'bg-green-500';
    } else {
        $is_store_open = false;
        $store_status_text = 'ร้านปิดอยู่';
        $store_status_icon = '🔴';
        $store_status_color = 'bg-red-500';
    }
}

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

// ฟังก์ชันสำหรับกำหนดหมวดหมู่จากชื่อสินค้า (สำหรับซูชิ)
function categorizeProduct($productName)
{
    $nameLower = mb_strtolower($productName, 'UTF-8');
    
    // ซูชิหน้าดิบ (Nigiri & Sashimi)
    if (strpos($nameLower, 'ซาชิมิ') !== false || strpos($nameLower, 'sashimi') !== false || 
        strpos($nameLower, 'นิกิริ') !== false || strpos($nameLower, 'nigiri') !== false ||
        strpos($nameLower, 'ปลาดิบ') !== false) {
        return 'ซาชิมิ & นิกิริ';
    }
    
    // ซูชิม้วน (Maki & Roll)
    if (strpos($nameLower, 'มากิ') !== false || strpos($nameLower, 'maki') !== false || 
        strpos($nameLower, 'โรล') !== false || strpos($nameLower, 'roll') !== false ||
        strpos($nameLower, 'แคลิฟอร์เนีย') !== false || strpos($nameLower, 'california') !== false) {
        return 'มากิ & โรล';
    }
    
    // เมนูทอด & เมนูร้อน
    if (strpos($nameLower, 'ทอด') !== false || strpos($nameLower, 'เทมปุระ') !== false || 
        strpos($nameLower, 'tempura') !== false || strpos($nameLower, 'ไก่ทอด') !== false ||
        strpos($nameLower, 'ทาโกะยากิ') !== false || strpos($nameLower, 'ทาโก้') !== false) {
        return 'เมนูทอด & เมนูร้อน';
    }
    
    // เครื่องดื่ม & ของหวาน
    if (strpos($nameLower, 'ชาเขียว') !== false || strpos($nameLower, 'โมจิ') !== false || 
        strpos($nameLower, 'ไอศกรีม') !== false || strpos($nameLower, 'น้ำ') !== false ||
        strpos($nameLower, 'เครื่องดื่ม') !== false || strpos($nameLower, 'ของหวาน') !== false) {
        return 'เครื่องดื่ม & ของหวาน';
    }
    
    return 'เมนูอื่น ๆ';
}

$category_order = ['ซาชิมิ & นิกิริ', 'มากิ & โรล', 'เมนูทอด & เมนูร้อน', 'เครื่องดื่ม & ของหวาน', 'เมนูอื่น ๆ'];
$grouped_products = [];

// --- FETCH STORE LOCATION ---
$store_lat = 13.73972299;
$store_lng = 100.48529231;
$store_address = 'ซูชิละกัน Paradise';
if ($conn && $connection_error === null) {
    $result_loc = $conn->query("SELECT latitude, longitude, address FROM store_locations LIMIT 1");
    if ($result_loc && $row_loc = $result_loc->fetch_assoc()) {
        $store_lat = $row_loc['latitude'];
        $store_lng = $row_loc['longitude'];
        $store_address = $row_loc['address'];
    }
}
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
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Leaflet CSS & Routing -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />

    <!-- Three.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="js/three_bg.js"></script>

    <style>
        #map {
            height: 500px;
            width: 100%;
            z-index: 10;
        }
        .leaflet-routing-container {
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
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
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #FFF8F0; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(180deg, #F97316, #EA580C); border-radius: 10px; }
        
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
            background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(255,237,213,0.5));
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

        .accordion-content.show { display: block; }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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
        .floating { animation: float 4s ease-in-out infinite; }
        .floating-delay { animation: float 4s ease-in-out infinite 1.5s; }
    </style>
</head>

<body class="antialiased min-h-screen flex flex-col">

    <!-- 🍊 PREMIUM LOADER -->
    <div id="page-loader" class="fixed inset-0 bg-gradient-to-br from-orange-400 via-orange-500 to-orange-600 z-[9999] flex flex-col justify-center items-center">
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
            <span class="flex items-center gap-2"><i class="far fa-calendar-alt"></i> <span id="current-date"></span></span>
            <span class="flex items-center gap-2"><i class="far fa-clock"></i> <span id="current-time"></span></span>
            <span class="flex items-center gap-2"><i class="fas fa-users"></i> <?php echo number_format($current_visitors); ?> ผู้เข้าชม</span>
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
                <li><a href="#home" class="text-orange-800 hover:text-orange-500 transition-colors py-2 flex items-center gap-2"><i class="fas fa-home"></i> หน้าแรก</a></li>
                <li><a href="#menu" class="text-orange-800 hover:text-orange-500 transition-colors py-2 flex items-center gap-2"><i class="fas fa-utensils"></i> เมนู</a></li>
                <li>
                    <a href="formlogin" class="btn-orange-gradient text-white px-7 py-3 rounded-full font-bold hover:scale-105 transition-all flex items-center gap-2">
                        <i class="fas fa-gift"></i> รับโค้ดส่วนลด
                    </a>
                </li>
            </ul>

            <!-- Mobile Menu Button -->
            <button class="md:hidden text-3xl text-orange-500" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                <i class="fas fa-bars"></i>
            </button>
        </nav>

        <!-- Mobile Nav -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-orange-100 p-6 flex flex-col gap-4 font-display font-bold text-center shadow-lg">
            <a href="#home" class="py-3 text-orange-700 hover:text-orange-500 rounded-xl hover:bg-orange-50">🏠 หน้าแรก</a>
            <a href="#menu" class="py-3 text-orange-700 hover:text-orange-500 rounded-xl hover:bg-orange-50">🍱 เมนู</a>
            <a href="formlogin" class="btn-orange-gradient text-white py-4 rounded-2xl mt-2">💎 รับโค้ดส่วนลด</a>
        </div>
    </header>

    <!-- 🏠 HERO SECTION -->
    <section id="home" class="relative min-h-[90vh] flex items-center justify-center text-center px-6 overflow-hidden py-16">
        <!-- Background Decorations -->
        <div class="absolute top-20 left-10 text-8xl opacity-30 floating hidden lg:block">🍣</div>
        <div class="absolute bottom-24 right-16 text-7xl opacity-25 floating-delay hidden lg:block">🍤</div>
        <div class="absolute top-1/3 right-1/4 text-6xl opacity-20 floating hidden lg:block">🥢</div>
        <div class="absolute bottom-1/3 left-1/4 text-5xl opacity-15 floating-delay hidden lg:block">🍱</div>

        <!-- Hero Content -->
        <div class="relative z-10 max-w-5xl mx-auto">
            <div class="inline-block bg-orange-100 text-orange-600 px-6 py-2 rounded-full font-display font-bold text-sm mb-6 animate-pulse-slow">
                ✨ PREMIUM JAPANESE RESTAURANT ✨
            </div>
            
            <h1 class="text-5xl md:text-7xl lg:text-8xl font-display font-extrabold mb-6 leading-tight">
                <span class="text-orange-600">ซูชิละกัน</span><br>
                <span class="text-orange-800">SUSHI PARADISE</span>
            </h1>
            
            <p class="text-xl md:text-2xl text-orange-700/80 mb-10 max-w-2xl mx-auto leading-relaxed">
                🍣 ซูชิพรีเมียม วัตถุดิบสดใหม่ทุกวัน<br>
                รสชาติต้นตำรับญี่ปุ่นแท้ๆ ในบรรยากาศอบอุ่น
            </p>

            <div class="flex flex-col sm:flex-row gap-5 justify-center items-center mb-12">
                <a href="#menu" class="group btn-orange-gradient text-white text-xl font-display font-bold px-12 py-5 rounded-full hover:scale-105 transition-all flex items-center gap-3">
                    <span>🔥 ดูเมนูยอดนิยม</span>
                    <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </a>
                <a href="queue_reservation" class="glass-orange text-orange-600 border-2 border-orange-400 text-xl font-display font-bold px-10 py-5 rounded-full hover:bg-orange-50 hover:scale-105 transition-all flex items-center gap-3">
                    <i class="fas fa-ticket-alt"></i>
                    <span>จองคิวออนไลน์</span>
                </a>
            </div>

            <!-- Queue Mini Board -->
            <div class="card-orange p-8 max-w-lg mx-auto">
                <h3 class="text-orange-600 font-display font-bold text-lg mb-5 flex items-center justify-center gap-3">
                    <i class="fas fa-stopwatch text-orange-500 animate-pulse"></i> 
                    <span>คิวที่รอเข้ารับบริการวันนี้</span>
                </h3>
                <div class="flex flex-wrap justify-center gap-3">
                    <?php if (empty($queue_data)): ?>
                        <div class="text-orange-400 py-3 flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-500"></i> ว่าง - ไม่มีคิวรอ
                        </div>
                    <?php else: ?>
                        <?php foreach ($queue_data as $q): ?>
                            <div class="bg-gradient-to-r from-orange-500 to-orange-600 text-white px-6 py-3 rounded-full font-display font-bold shadow-lg animate-pulse-slow">
                                Q<?php echo str_pad($q['queue_number'], 2, '0', STR_PAD_LEFT); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <a href="queue_board" class="inline-block mt-5 text-sm text-orange-500 hover:text-orange-700 font-semibold transition-colors">
                    ดูคิวทั้งหมด <i class="fas fa-chevron-right text-xs"></i>
                </a>
            </div>

            <!-- Hours & Status -->
            <div class="mt-10 flex flex-col items-center gap-4">
                <!-- Store Status -->
                <div class="inline-flex items-center gap-3 px-6 py-3 rounded-full <?php echo $is_store_open ? 'bg-green-500' : 'bg-red-500'; ?> shadow-lg animate-pulse-slow">
                    <span class="text-2xl"><?php echo $store_status_icon; ?></span>
                    <span class="text-white font-display font-bold text-lg"><?php echo $store_status_text; ?></span>
                </div>
                
                <!-- Store Hours (only show when not manually closed) -->
                <?php if ($show_hours): ?>
                <div class="inline-flex items-center gap-3 bg-white/80 backdrop-blur-sm px-8 py-4 rounded-full border border-orange-200 shadow-sm">
                    <i class="fas fa-clock text-orange-500 text-xl"></i>
                    <span class="text-orange-800 font-display font-semibold">เปิดทุกวัน: <span class="text-orange-600 font-bold"><?php echo htmlspecialchars($store_open_time); ?> - <?php echo htmlspecialchars($store_close_time); ?> น.</span></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- 🎬 VIDEO SHOWCASE SECTION -->
    <section class="relative py-20 overflow-hidden">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12">
                <div class="inline-block bg-orange-100 text-orange-600 px-4 py-1 rounded-full text-sm font-bold mb-3 animate-pulse-slow">
                    🎬 VIDEO SHOWCASE
                </div>
                <h2 class="text-4xl md:text-5xl font-display font-extrabold text-orange-800 mb-4">
                    ชมบรรยากาศซูชิสุดพรีเมียม
                </h2>
                <div class="w-32 h-1.5 bg-gradient-to-r from-orange-400 to-orange-600 mx-auto rounded-full"></div>
            </div>

            <div class="max-w-5xl mx-auto">
                <div class="relative rounded-3xl overflow-hidden shadow-2xl border-4 border-orange-200 group">
                    <!-- Video -->
                    <video 
                        id="sushiVideo"
                        class="w-full h-auto object-cover"
                        autoplay 
                        loop 
                        muted 
                        playsinline
                        poster="video/Sushi.mp4"
                    >
                        <source src="video/Sushi.mp4" type="video/mp4">
                        เบราว์เซอร์ของคุณไม่รองรับการเล่นวิดีโอ
                    </video>
                    
                    <!-- Overlay Gradient -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    
                    <!-- Floating Badge -->
                    <div class="absolute top-6 right-6 bg-gradient-to-r from-orange-500 to-orange-600 text-white px-6 py-3 rounded-full font-display font-bold shadow-lg backdrop-blur-sm flex items-center gap-2">
                        <i class="fas fa-play-circle animate-pulse"></i>
                        <span>AUTO PLAY</span>
                    </div>

                    <!-- Mute/Unmute Button -->
                    <button 
                        id="muteBtn"
                        onclick="toggleMute()"
                        class="absolute bottom-6 right-6 w-14 h-14 bg-white/90 backdrop-blur-sm text-orange-600 rounded-full shadow-lg hover:bg-orange-500 hover:text-white transition-all flex items-center justify-center group/btn hover:scale-110"
                        title="เปิด/ปิดเสียง"
                    >
                        <i id="muteIcon" class="fas fa-volume-mute text-xl"></i>
                    </button>
                </div>

                <!-- Caption -->
                <div class="mt-8 text-center glass-orange rounded-2xl p-6 max-w-2xl mx-auto">
                    <p class="text-orange-700 font-display text-lg leading-relaxed">
                        <i class="fas fa-quote-left text-orange-400 mr-2"></i>
                        <span class="font-semibold">เห็นแล้วหิว! </span>
                        ชมวิดีโอซูชิสดใหม่ทำสดใหม่ทุกวัน จากมือเชฟมืออาชีพ 
                        <i class="fas fa-quote-right text-orange-400 ml-2"></i>
                    </p>
                </div>
            </div>
        </div>

        <!-- Decorative Elements -->
        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-orange-200 rounded-full blur-3xl opacity-30"></div>
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-orange-300 rounded-full blur-3xl opacity-30"></div>
    </section>

    <!-- 🔥 PROMOTIONS SECTION -->
    <section id="promotions" class="py-20 container mx-auto px-6">
        <div class="text-center mb-16">
            <div class="inline-block bg-red-100 text-red-500 px-4 py-1 rounded-full text-sm font-bold mb-3">
                🔥 HOT DEALS
            </div>
            <h2 class="text-4xl md:text-5xl font-display font-extrabold text-gradient-orange mb-4">โปรโมชั่นสุดพิเศษ</h2>
            <div class="w-32 h-1.5 bg-gradient-to-r from-orange-400 to-orange-600 mx-auto rounded-full"></div>
        </div>

        <?php if (count($promotions) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($promotions as $promo): ?>
                    <div class="card-orange group overflow-hidden">
                        <!-- Hot Badge -->
                        <div class="absolute top-4 right-4 z-10 badge-hot text-white text-xs font-bold px-4 py-1.5 rounded-full shadow-lg">
                            🔥 HOT
                        </div>
                        
                        <!-- Image -->
                        <div class="relative h-56 overflow-hidden">
                            <?php if (!empty($promo['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($promo['image_path']); ?>" alt="Promo" 
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <?php else: ?>
                                <div class="w-full h-full bg-gradient-to-br from-orange-100 to-orange-200 flex items-center justify-center">
                                    <i class="fas fa-gift text-6xl text-orange-300"></i>
                                </div>
                            <?php endif; ?>
                            <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-transparent"></div>
                        </div>

                        <!-- Content -->
                        <div class="p-6">
                            <h3 class="text-xl font-display font-bold text-orange-700 mb-3 group-hover:text-orange-500 transition-colors">
                                <?php echo htmlspecialchars($promo['title']); ?>
                            </h3>
                            <p class="text-orange-600/70 leading-relaxed mb-5 text-sm line-clamp-2">
                                <?php echo nl2br(htmlspecialchars($promo['description'])); ?>
                            </p>
                            <button class="w-full bg-orange-100 text-orange-600 font-display font-bold py-3 rounded-xl hover:bg-orange-500 hover:text-white transition-all">
                                <i class="fas fa-hand-pointer mr-2"></i> รับสิทธิ์เลย
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-16 card-orange">
                <i class="fas fa-tags text-6xl text-orange-200 mb-6"></i>
                <p class="text-xl text-orange-400 font-display">เร็วๆ นี้! ติดตามโปรโมชั่นใหม่ได้ที่นี่</p>
            </div>
        <?php endif; ?>
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
                    <input type="text" name="search" placeholder="🔍 ค้นหาเมนูที่ต้องการ..." value="<?php echo htmlspecialchars($search_term); ?>"
                        class="w-full pl-6 pr-14 py-4 rounded-2xl bg-white border-2 border-orange-200 text-orange-800 placeholder-orange-300 font-display focus:border-orange-500 focus:ring-4 focus:ring-orange-200 outline-none transition-all shadow-sm">
                    <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 bg-orange-500 text-white w-10 h-10 rounded-xl hover:bg-orange-600 transition-colors">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <?php if (isset($_SESSION['upload_status']) && $_SESSION['upload_status'] == 'success'): ?>
                <div class="max-w-2xl mx-auto mb-10 bg-green-50 border-l-4 border-green-500 text-green-700 p-5 rounded-xl flex items-center gap-4 shadow-sm">
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
                    $sql = "SELECT id, name, price, description, image_path FROM products";
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
                            if (strpos($category, 'ซาชิมิ') !== false) $icon = '🍣';
                            elseif (strpos($category, 'มากิ') !== false || strpos($category, 'โรล') !== false) $icon = '🍱';
                            elseif (strpos($category, 'ทอด') !== false || strpos($category, 'ร้อน') !== false) $icon = '🍤';
                            elseif (strpos($category, 'เครื่องดื่ม') !== false || strpos($category, 'ของหวาน') !== false) $icon = '🍵';
                            ?>
                            <div class="accordion-item">
                                <button class="accordion-btn <?php echo $isOpen ? 'active' : ''; ?>" onclick="toggleAccordion('<?php echo $category_id; ?>', this)">
                                    <span class="flex items-center gap-4">
                                        <span class="text-3xl"><?php echo $icon; ?></span>
                                        <span><?php echo $category; ?></span>
                                    </span>
                                    <i class="fas fa-chevron-down transition-transform duration-300 <?php echo $isOpen ? 'rotate-180' : ''; ?>"></i>
                                </button>
                                
                                <div id="<?php echo $category_id; ?>" class="accordion-content <?php echo $isOpen ? 'show' : ''; ?>">
                                    <div class="category-grid">
                                        <?php foreach ($grouped_products[$category] as $row): ?>
                                            <div class="card-orange group cursor-pointer overflow-hidden" 
                                                onclick="openModal('<?php echo htmlspecialchars($row['image_path']); ?>', '<?php echo htmlspecialchars($row['name']); ?>', '<?php echo htmlspecialchars(addslashes($row['description'])); ?>', '<?php echo number_format($row['price']); ?>')">
                                                
                                                <div class="relative h-52 overflow-hidden">
                                                    <?php if (!empty($row['image_path'])): ?>
                                                        <img src="<?php echo htmlspecialchars($row['image_path']); ?>" 
                                                            alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                                    <?php else: ?>
                                                        <div class="w-full h-full bg-gradient-to-br from-orange-50 to-orange-100 flex items-center justify-center">
                                                            <i class="fas fa-utensils text-5xl text-orange-200"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Price Badge -->
                                                    <div class="absolute top-4 right-4 bg-gradient-to-r from-orange-500 to-orange-600 text-white font-display font-bold px-4 py-2 rounded-full shadow-lg">
                                                        <?php echo number_format($row['price']); ?>฿
                                                    </div>
                                                </div>

                                                <div class="p-5">
                                                    <h4 class="font-display font-bold text-lg text-orange-800 mb-2 group-hover:text-orange-500 transition-colors">
                                                        <?php echo htmlspecialchars($row['name']); ?>
                                                    </h4>
                                                    <p class="text-sm text-orange-600/60 line-clamp-2 mb-4">
                                                        <?php echo htmlspecialchars($row['description']); ?>
                                                    </p>
                                                    <button class="w-full bg-orange-100 text-orange-600 font-display font-bold py-3 rounded-xl group-hover:bg-orange-500 group-hover:text-white transition-all">
                                                        <i class="fas fa-cart-plus mr-2"></i> สั่งเลย
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

    <!-- 🗺️ CONTACT / MAP -->
    <section id="contact" class="py-16 text-center container mx-auto px-6">
        <div class="mb-10">
            <div class="inline-block bg-orange-100 text-orange-600 px-4 py-1 rounded-full text-sm font-bold mb-3">📍 FIND US</div>
            <h2 class="text-3xl md:text-4xl font-display font-extrabold text-orange-800 mb-2">ที่ตั้งและเส้นทางมาร้าน</h2>
            <p class="text-orange-600">เดินทางมาหาเราได้ง่ายๆ ด้วยระบบนำทางด้านล่าง</p>
        </div>

        <button onclick="toggleRouteMap()" id="btn-toggle-map"
            class="bg-white border-2 border-orange-400 text-orange-600 px-10 py-4 rounded-full font-display font-bold hover:bg-orange-50 hover:scale-105 transition-all inline-flex items-center gap-3 shadow-lg mb-8">
            <i class="fas fa-location-arrow text-xl" id="toggle-icon"></i> <span id="toggle-text">ดูเส้นทางจากตำแหน่งของคุณ</span>
        </button>

        <div id="map-section" class="max-w-5xl mx-auto rounded-3xl overflow-hidden shadow-2xl border-4 border-orange-200 relative hidden mb-8">
            <div id="map"></div>
            <div id="loading-map" class="absolute inset-0 bg-white/80 z-20 flex flex-col items-center justify-center">
                <div class="w-12 h-12 border-4 border-orange-500 border-t-transparent animate-spin rounded-full mb-4"></div>
                <p class="font-display font-bold text-orange-600">กำลังคำนวณเส้นทางจากตำแหน่งของคุณ...</p>
            </div>
        </div>

        <div class="mt-8 glass-orange p-6 rounded-2xl max-w-2xl mx-auto flex items-center justify-center gap-4 border border-orange-200 shadow-sm">
            <div class="w-12 h-12 bg-orange-500 text-white rounded-xl flex items-center justify-center text-xl">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="text-left">
                <p class="text-sm text-orange-500 font-bold uppercase tracking-wider">ที่อยู่ร้าน</p>
                <p class="text-orange-800 font-display font-semibold"><?php echo htmlspecialchars($store_address); ?></p>
            </div>
        </div>
    </section>

    <!-- 🍊 FOOTER -->
    <footer class="bg-gradient-to-r from-orange-500 to-orange-600 py-16 mt-auto">
        <div class="container mx-auto px-6 text-center text-white">
            <div class="text-6xl mb-6">🍣</div>
            <h2 class="text-3xl font-display font-bold mb-4">ซูชิละกัน PARADISE</h2>
            <p class="text-white/80 mb-8 max-w-md mx-auto">Premium Japanese Restaurant<br>อร่อย สด สะอาด ต้องที่ซูชิละกัน 🍣</p>
            
            <div class="flex justify-center gap-5 mb-10">
                <a href="https://www.facebook.com/share/1Bod121vTg/" class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center hover:bg-white hover:text-orange-500 transition-all text-xl"><i class="fab fa-facebook-f"></i></a>
                <a href="line-oa" class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center hover:bg-white hover:text-orange-500 transition-all text-xl"><i class="fab fa-line"></i></a>
            </div>

            <p class="text-white/60 text-sm">© 2026 ซูชิละกัน Paradise. All Rights Reserved.</p>
            <a href="formmenu" class="inline-block mt-4 text-white/40 hover:text-white text-sm transition-colors">
                <i class="fas fa-lock"></i>
            </a>
        </div>
    </footer>

    <!-- 🛒 PRODUCT MODAL -->
    <div id="productModal" class="fixed inset-0 z-[99999] hidden flex justify-center items-center bg-black/60 backdrop-blur-md p-6">
        <div class="bg-white rounded-3xl p-8 max-w-lg w-full relative shadow-2xl transform scale-100 transition-all border-2 border-orange-100">
            <button onclick="closeModal()" class="absolute top-4 right-4 w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center text-orange-500 hover:bg-orange-500 hover:text-white transition-all text-xl font-bold">
                &times;
            </button>
            
            <div class="flex flex-col items-center text-center">
                <div class="w-full h-64 bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl mb-6 flex items-center justify-center overflow-hidden">
                    <img id="modalImg" src="" alt="" class="max-w-full max-h-full object-contain drop-shadow-lg">
                </div>
                
                <h3 id="modalName" class="text-2xl font-display font-bold text-orange-700 mb-3"></h3>
                <p id="modalDesc" class="text-orange-600/60 mb-6 leading-relaxed"></p>
                <div id="modalPrice" class="text-4xl font-display font-extrabold text-gradient-orange mb-6"></div>
                
                <button onclick="window.location.href='queue_reservation'"
                    class="w-full btn-orange-gradient text-white font-display font-bold text-xl py-4 rounded-2xl hover:scale-[1.02] transition-transform">
                    🛒 สั่งเลยตอนนี้
                </button>
            </div>
        </div>
    </div>

    <!-- Leaflet & Routing JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>

    <script>
        let map;
        let routingControl;
        const storeLat = <?php echo $store_lat; ?>;
        const storeLng = <?php echo $store_lng; ?>;

        function toggleRouteMap() {
            const mapSection = document.getElementById('map-section');
            const toggleText = document.getElementById('toggle-text');
            const toggleIcon = document.getElementById('toggle-icon');
            
            if (mapSection.classList.contains('hidden')) {
                // Open Map
                mapSection.classList.remove('hidden');
                toggleText.innerText = "ปิดแผนที่นำทาง";
                toggleIcon.classList.remove('fa-location-arrow');
                toggleIcon.classList.add('fa-times-circle');
                
                if (!map) {
                    initRouteMap();
                } else {
                    // Refresh map size if it was initialized while hidden
                    setTimeout(() => map.invalidateSize(), 100);
                }
            } else {
                // Close Map
                mapSection.classList.add('hidden');
                toggleText.innerText = "ดูเส้นทางจากตำแหน่งของคุณ";
                toggleIcon.classList.remove('fa-times-circle');
                toggleIcon.classList.add('fa-location-arrow');
            }
        }

        function initRouteMap() {
            const mapSection = document.getElementById('map-section');
            const loading = document.getElementById('loading-map');
            
            loading.classList.remove('hidden');
            
            if (!map) {
                map = L.map('map').setView([storeLat, storeLng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(map);

                // Fix Leaflet Default Icon path
                delete L.Icon.Default.prototype._getIconUrl;
                L.Icon.Default.mergeOptions({
                    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                });

                // Show Store Marker Immediately
                L.marker([storeLat, storeLng]).addTo(map)
                    .bindPopup('🍣 <b>ร้านซูชิละกัน</b><br><?php echo addslashes($store_address); ?>')
                    .openPopup();
            }

            // Get User Location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const userLat = position.coords.latitude;
                        const userLng = position.coords.longitude;
                        
                        loading.classList.add('hidden');

                        if (routingControl) {
                            map.removeControl(routingControl);
                        }

                        routingControl = L.Routing.control({
                            waypoints: [
                                L.latLng(userLat, userLng),
                                L.latLng(storeLat, storeLng)
                            ],
                            routeWhileDragging: true,
                            language: 'th',
                            lineOptions: {
                                styles: [{ color: '#F97316', weight: 6, opacity: 0.8 }]
                            },
                            createMarker: function(i, wp, nWps) {
                                if (i === 0) {
                                    return L.marker(wp.latLng).bindPopup('📍 <b>คุณอยู่ที่นี่</b>');
                                }
                                return null;
                            }
                        }).addTo(map);
                        
                        map.scrollIntoView({ behavior: 'smooth' });
                    },
                    (error) => {
                        console.error('Geolocation error:', error);
                        loading.classList.add('hidden');
                        alert("ไม่สามารถเข้าถึงตำแหน่งของคุณได้ กรุณาตรวจสอบการอนุญาตระบุตำแหน่งในเบราว์เซอร์");
                    }
                );
            } else {
                loading.classList.add('hidden');
                alert("เบราว์เซอร์ของคุณไม่รองรับการแสดงพิกัดตำแหน่ง");
            }
        }

        // Auto-initialize map on load is removed - back to manual button

        // Toggle Mute/Unmute for Video
        function toggleMute() {
            const video = document.getElementById('sushiVideo');
            const icon = document.getElementById('muteIcon');
            const btn = document.getElementById('muteBtn');
            
            if (video.muted) {
                video.muted = false;
                icon.classList.remove('fa-volume-mute');
                icon.classList.add('fa-volume-up');
                btn.classList.add('ring-4', 'ring-green-400/50');
                setTimeout(() => btn.classList.remove('ring-4', 'ring-green-400/50'), 500);
            } else {
                video.muted = true;
                icon.classList.remove('fa-volume-up');
                icon.classList.add('fa-volume-mute');
                btn.classList.add('ring-4', 'ring-red-400/50');
                setTimeout(() => btn.classList.remove('ring-4', 'ring-red-400/50'), 500);
            }
        }

        // Loader
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
        function openModal(img, name, desc, price) {
            document.getElementById('modalImg').src = img || '';
            document.getElementById('modalName').textContent = name;
            document.getElementById('modalDesc').textContent = desc || 'ไม่มีคำอธิบาย';
            document.getElementById('modalPrice').textContent = price + '.-';
            document.getElementById('productModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('productModal').classList.add('hidden');
        }

        document.getElementById('productModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
<?php
if ($conn !== null) {
    $conn->close();
}
?>