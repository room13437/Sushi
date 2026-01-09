<?php
// แสดง error เพื่อ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'admin_auth.php';

// ตรวจสอบว่า login แล้วหรือยัง
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// ถ้ายังไม่ได้ login ให้ redirect ไป admin_login
if (!$isLoggedIn) {
    header('Location: admin_login');
    exit;
}

// Fetch Daily Points for Display
$daily_res = $conn->query("SELECT setting_value FROM store_settings WHERE setting_key = 'daily_login_points'");
$daily_points_display = ($daily_res && $daily_res->num_rows > 0) ? (int) $daily_res->fetch_assoc()['setting_value'] : 10;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="icon/icons.png?v=4">
    <title>🍲 เมนูผู้ดูแลระบบ | Admin Panel 🛠️</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* ==================== GLOBAL & FONT ==================== */
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700&display=swap');

        :root {
            --primary-red: #d32f2f;
            --primary-orange: #ff6f00;
            --dark-brown: #3e2723;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.6);
            --shadow-soft: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(-45deg, #fffbf0, #ffe0b2, #ffccbc, #fffbf0);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .menu-box {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            padding: 50px 40px;
            width: 100%;
            max-width: 400px;
            border-radius: 25px;
            box-shadow: var(--shadow-soft);
            text-align: center;
            position: relative;
            animation: fadeInUp 0.8s ease-out forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h2 {
            font-family: 'Prompt', sans-serif;
            color: var(--primary-red);
            font-size: 2rem;
            margin-bottom: 30px;
            font-weight: 700;
            text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.5);
            letter-spacing: -0.5px;
        }

        /* ==================== MENU BUTTONS ==================== */
        .menu-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
            border-radius: 20px;
            text-decoration: none;
            color: white;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .menu-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0) 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .menu-card:hover::before {
            opacity: 1;
        }

        .menu-card:hover {
            transform: translateY(-12px) scale(1.03);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        }

        .menu-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        .menu-title {
            font-family: 'Prompt', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .menu-subtitle {
            font-size: 0.85rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .footer-btn {
            padding: 15px 25px;
            border-radius: 15px;
            font-family: 'Prompt', sans-serif;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        /* Responsive */
        @media (min-width: 1200px) {
            .menu-box {
                max-width: 1000px !important;
            }
        }

        @media (max-width: 1199px) and (min-width: 1024px) {
            .menu-box {
                max-width: 900px !important;
                padding: 30px !important;
            }
        }

        @media (max-width: 1023px) {
            .menu-box {
                max-width: 95% !important;
                padding: 25px 20px !important;
            }

            .menu-card {
                padding: 25px 15px;
            }

            h2 {
                font-size: 1.8rem !important;
            }

            .menu-icon {
                font-size: 2.5rem;
            }

            .menu-title {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 480px) {
            body {
                height: auto;
                padding: 20px 0;
                overflow-y: auto;
            }

            [style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
            }

            .footer-btn {
                font-size: 0.9rem;
                padding: 12px 15px;
            }
        }

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="js/three_bg.js"></script>
</head>

<body>

    <div id="loader-wrapper">
        <div class="loader"></div>
    </div>

    <!-- Main Menu Content -->
    <div class="menu-box" id="menu-content">
        <!-- Header with Icon -->
        <div style="text-align: center; margin-bottom: 35px;">
            <div style="font-size: 3.5rem; margin-bottom: 10px; animation: bounce 2s infinite;">🛠️</div>
            <h2
                style="margin: 0; background: linear-gradient(135deg, #FF6F00, #d32f2f); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                เมนูผู้ดูแลระบบ</h2>
            <p style="color: #666; font-size: 0.95rem; margin-top: 5px;">Admin Control Panel</p>
        </div>

        <!-- Search Bar -->
        <div style="margin-bottom: 30px; position: relative;">
            <input type="text" id="menuSearch" placeholder="🔍 ค้นหาเมนู (เช่น จัดการสมาชิก, Points, เวลา เปิด-ปิด)..."
                style="width: 100%; padding: 15px 50px 15px 25px; border-radius: 50px; border: 2px solid rgba(255,111,0,0.2); 
                       background: rgba(255,255,255,0.9); font-family: 'Prompt'; font-size: 1rem; color: #333; 
                       box-shadow: 0 5px 15px rgba(0,0,0,0.05); outline: none; transition: all 0.3s;">
            <i class="fas fa-search"
                style="position: absolute; right: 25px; top: 50%; transform: translateY(-50%); color: #FF6F00; font-size: 1.2rem;"></i>
        </div>

        <!-- Menu Grid (Points System Only) -->
        <div id="gridContainer"
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px;">

            <!-- Points Management -->
            <a href="manage_points" class="menu-card"
                style="background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);">
                <div class="menu-icon">💎</div>
                <div class="menu-title">จัดการคะแนน</div>
                <div class="menu-subtitle">Points System</div>
            </a>

            <!-- Daily Reward Management -->
            <a href="manage_daily_reward" class="menu-card"
                style="background: linear-gradient(135deg, #42A5F5 0%, #1E88E5 100%);">
                <div class="menu-icon">📅</div>
                <div class="menu-title">ตั้งค่าเช็คอินรายวัน</div>
                <div class="menu-subtitle">Daily Rewards (<?php echo $daily_points_display; ?> P)</div>
            </a>

            <!-- Code Management (Mapped to manage_points) -->
            <a href="manage_points" class="menu-card"
                style="background: linear-gradient(135deg, #FF5722 0%, #FF9800 100%);">
                <div class="menu-icon">🎁</div>
                <div class="menu-title">โค้ดแลก Point</div>
                <div class="menu-subtitle">Redeem Codes</div>
            </a>

            <!-- Sushi Claims -->
            <a href="manage_claims" class="menu-card"
                style="background: linear-gradient(135deg, #EC407A 0%, #F06292 100%);">
                <div class="menu-icon">🍣</div>
                <div class="menu-title">รายการแลกซูชิ</div>
                <div class="menu-subtitle">Sushi Claims</div>
            </a>

            <!-- Redemption Tiers Management -->
            <a href="manage_redemption_tiers" class="menu-card"
                style="background: linear-gradient(135deg, #FF7043 0%, #F4511E 100%);">
                <div class="menu-icon">⚙️</div>
                <div class="menu-title">อัตราแลกซูชิ</div>
                <div class="menu-subtitle">Redemption Tiers</div>
            </a>

            <!-- Discount Codes Management -->
            <a href="manage_discounts" class="menu-card"
                style="background: linear-gradient(135deg, #8E24AA 0%, #AB47BC 100%);">
                <div class="menu-icon">🎟️</div>
                <div class="menu-title">จัดการโค้ดส่วนลด</div>
                <div class="menu-subtitle">Discount Codes</div>
            </a>

            <!-- Product/Menu Management -->
            <a href="manage_products" class="menu-card"
                style="background: linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%);">
                <div class="menu-icon">🍱</div>
                <div class="menu-title">จัดการสินค้า</div>
                <div class="menu-subtitle">Product Menu</div>
            </a>

            <!-- Customers -->
            <a href="manage_customers" class="menu-card"
                style="background: linear-gradient(135deg, #00BCD4 0%, #4DD0E1 100%);">
                <div class="menu-icon">👥</div>
                <div class="menu-title">จัดการสมาชิก</div>
                <div class="menu-subtitle">ดูรายชื่อลูกค้าทั้งหมด</div>
            </a>

            <!-- Admin Users Management -->
            <a href="manage_admin_users" class="menu-card"
                style="background: linear-gradient(135deg, #607D8B 0%, #90A4AE 100%);">
                <div class="menu-icon">🔐</div>
                <div class="menu-title">จัดการแอดมิน</div>
                <div class="menu-subtitle">Admin Users</div>
            </a>

            <!-- Store Hours Management -->
            <a href="manage_store" class="menu-card"
                style="background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);">
                <div class="menu-icon">🕐</div>
                <div class="menu-title">เวลาเปิด-ปิดร้าน</div>
                <div class="menu-subtitle">Store Hours</div>
            </a>
        </div>

        <!-- Footer Actions -->
        <div style="display: flex; gap: 15px; margin-top: 30px;">
            <a href="/" class="footer-btn" style="flex: 1; background: white; color: #666; border: 2px solid #e0e0e0;">
                <i class="fas fa-home"></i> กลับหน้าหลัก
            </a>
            <a href="?logout=1" class="footer-btn"
                style="flex: 1; background: linear-gradient(135deg, #f44336, #e91e63); color: white; border: none; text-decoration: none; display: flex; justify-content: center; align-items: center;">
                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
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

        // Search Filter
        document.getElementById('menuSearch').addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll('#gridContainer .menu-card');
            let visibleCount = 0;

            cards.forEach(function (card) {
                const title = card.querySelector('.menu-title')?.textContent.toLowerCase() || '';
                const subtitle = card.querySelector('.menu-subtitle')?.textContent.toLowerCase() || '';

                if (title.includes(query) || subtitle.includes(query)) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Show/hide no results message
            let noResultsMsg = document.getElementById('noResultsMsg');
            if (visibleCount === 0 && query.length > 0) {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.id = 'noResultsMsg';
                    noResultsMsg.style.cssText = 'text-align: center; padding: 40px; color: #999; font-size: 1.2rem;';
                    noResultsMsg.innerHTML = '😢 ไม่พบเมนูที่ค้นหา';
                    document.getElementById('gridContainer').appendChild(noResultsMsg);
                }
                noResultsMsg.style.display = 'block';
            } else if (noResultsMsg) {
                noResultsMsg.style.display = 'none';
            }
        });
    </script>
</body>

</html>