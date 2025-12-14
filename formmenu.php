<?php
// แสดง error เพื่อ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'admin_auth.php';

// ตรวจสอบว่า login แล้วหรือยัง
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// ถ้ายังไม่ได้ login ให้ redirect ไป admin_login.php
if (!$isLoggedIn) {
    header('Location: admin_login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        /* ==================== LOGIN CONTAINER (CLEAN & SIMPLE) ==================== */
        .login-container {
            max-width: 420px;
            width: 90%;
            padding: 45px 40px;
            background: white;
            border-radius: 20px;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 999;
            text-align: center;
            animation: fadeIn 0.5s ease-out;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translate(-50%, -45%);
            }

            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 25px;
                width: 95%;
            }
        }

        .login-header {
            margin-bottom: 30px;
        }

        .lock-icon {
            font-size: 4rem;
            background: linear-gradient(135deg, #FF6F00, #d32f2f);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
            display: inline-block;
        }

        .login-container h2 {
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, #FF6F00, #d32f2f);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.8rem;
            margin: 0 0 8px 0;
            font-weight: 700;
        }

        .login-subtitle {
            color: #999;
            font-size: 0.9rem;
            margin: 0;
        }

        .error-message {
            color: #f44336;
            background: #ffebee;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #f44336;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
            animation: slideDown 0.3s ease-out;
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

        .input-group {
            position: relative;
            margin-bottom: 18px;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1.1rem;
        }

        .input-group input {
            width: 100%;
            padding: 14px 14px 14px 48px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: all 0.3s;
            font-family: 'Sarabun', sans-serif;
        }

        .input-group input:focus {
            outline: none;
            border-color: #FF6F00;
            box-shadow: 0 0 0 3px rgba(255, 111, 0, 0.1);
        }

        .input-group input:focus~.input-icon {
            color: #FF6F00;
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            color: #999;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: #FF6F00;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #FF6F00, #d32f2f);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Prompt', sans-serif;
            box-shadow: 0 4px 12px rgba(255, 111, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .login-btn:hover {
            background: linear-gradient(135deg, #d32f2f, #FF6F00);
            box-shadow: 0 6px 16px rgba(255, 111, 0, 0.4);
            transform: translateY(-2px);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-container {
            max-width: 420px;
            width: 90%;
            padding: 50px 40px;
            background: linear-gradient(135deg, #ffffff 0%, #fafafa 100%);
            border-radius: 30px;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) perspective(1000px) rotateX(0deg);
            z-index: 999;
            text-align: center;
            animation: float3D 6s ease-in-out infinite, slideUp3D 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);

            /* Multi-layer 3D shadows */
            box-shadow:
                0 2px 4px rgba(0, 0, 0, 0.02),
                0 4px 8px rgba(0, 0, 0, 0.03),
                0 8px 16px rgba(0, 0, 0, 0.04),
                0 16px 32px rgba(0, 0, 0, 0.05),
                0 32px 64px rgba(255, 111, 0, 0.1),
                inset 0 -2px 4px rgba(255, 255, 255, 0.8),
                inset 0 2px 4px rgba(0, 0, 0, 0.05);

            /* 3D border effect */
            border: 3px solid;
            border-image: linear-gradient(135deg,
                    rgba(255, 255, 255, 0.8) 0%,
                    rgba(255, 111, 0, 0.3) 50%,
                    rgba(211, 47, 47, 0.3) 100%) 1;
            border-radius: 30px;

            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .login-container:hover {
            transform: translate(-50%, -52%) perspective(1000px) rotateX(2deg);
            box-shadow:
                0 4px 8px rgba(0, 0, 0, 0.03),
                0 8px 16px rgba(0, 0, 0, 0.04),
                0 16px 32px rgba(0, 0, 0, 0.06),
                0 32px 64px rgba(0, 0, 0, 0.08),
                0 48px 96px rgba(255, 111, 0, 0.15),
                inset 0 -2px 6px rgba(255, 255, 255, 1),
                inset 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        @keyframes float3D {

            0%,
            100% {
                transform: translate(-50%, -50%) perspective(1000px) rotateX(0deg) translateZ(0px);
            }

            50% {
                transform: translate(-50%, -50%) perspective(1000px) rotateX(1deg) translateZ(10px);
            }
        }

        @keyframes slideUp3D {
            from {
                transform: translate(-50%, -30%) perspective(1000px) rotateX(-15deg) scale(0.9);
                opacity: 0;
            }

            to {
                transform: translate(-50%, -50%) perspective(1000px) rotateX(0deg) scale(1);
                opacity: 1;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 25px;
                width: 95%;
            }

            .login-container h2 {
                font-size: 1.5rem;
            }
        }

        .login-header {
            margin-bottom: 30px;
            transform-style: preserve-3d;
        }

        .lock-icon {
            font-size: 4.5rem;
            background: linear-gradient(135deg, #FF6F00, #d32f2f);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
            animation: glow3D 3s ease-in-out infinite;
            display: inline-block;
            transform: translateZ(20px);
            filter: drop-shadow(0 10px 20px rgba(255, 111, 0, 0.3));
        }

        @keyframes glow3D {

            0%,
            100% {
                filter: drop-shadow(0 10px 20px rgba(255, 111, 0, 0.3));
                transform: translateZ(20px) rotateY(0deg);
            }

            50% {
                filter: drop-shadow(0 15px 30px rgba(211, 47, 47, 0.5));
                transform: translateZ(25px) rotateY(5deg);
            }
        }

        .login-container h2 {
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, #FF6F00, #d32f2f);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.9rem;
            margin: 0 0 10px 0;
            font-weight: 800;
            transform: translateZ(15px);
            text-shadow: 0 4px 8px rgba(255, 111, 0, 0.1);
        }

        .login-subtitle {
            color: #999;
            font-size: 0.9rem;
            margin: 0;
            font-weight: 400;
            transform: translateZ(10px);
        }

        .error-message {
            color: #f44336;
            background: linear-gradient(135deg, #ffebee, #ffcdd2);
            padding: 14px 18px;
            border-radius: 15px;
            margin-bottom: 20px;
            border-left: 5px solid #f44336;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
            animation: shake3D 0.6s, slideIn3D 0.4s;
            box-shadow:
                0 4px 8px rgba(244, 67, 54, 0.1),
                0 8px 16px rgba(244, 67, 54, 0.05);
            transform: translateZ(5px);
        }

        @keyframes shake3D {

            0%,
            100% {
                transform: translateX(0) translateZ(5px);
            }

            25% {
                transform: translateX(-10px) translateZ(8px);
            }

            75% {
                transform: translateX(10px) translateZ(8px);
            }
        }

        @keyframes slideIn3D {
            from {
                opacity: 0;
                transform: translateY(-20px) translateZ(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0) translateZ(5px);
            }
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
            transform-style: preserve-3d;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%) translateZ(10px);
            color: #999;
            font-size: 1.1rem;
            z-index: 1;
            transition: all 0.3s;
        }

        .input-group input {
            width: 100%;
            padding: 18px 18px 18px 52px;
            border: 2px solid #e0e0e0;
            border-radius: 18px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-family: 'Sarabun', sans-serif;
            background: white;
            box-shadow:
                inset 0 2px 4px rgba(0, 0, 0, 0.05),
                0 2px 4px rgba(0, 0, 0, 0.02);
            transform: translateZ(0px);
        }

        .input-group input:focus {
            outline: none;
            border-color: #FF6F00;
            box-shadow:
                0 0 0 4px rgba(255, 111, 0, 0.1),
                0 4px 12px rgba(255, 111, 0, 0.15),
                inset 0 2px 4px rgba(0, 0, 0, 0.02);
            transform: translateY(-2px) translateZ(15px) scale(1.01);
        }

        .input-group input:focus~.input-icon {
            color: #FF6F00;
            transform: translateY(-50%) translateZ(15px) scale(1.1);
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%) translateZ(10px);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            color: #999;
            transition: all 0.3s;
            z-index: 2;
        }

        .toggle-password:hover {
            color: #FF6F00;
            transform: translateY(-50%) translateZ(15px) scale(1.2);
        }

        .login-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #FF6F00, #d32f2f);
            color: white;
            border: none;
            border-radius: 18px;
            font-size: 1.15rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-family: 'Prompt', sans-serif;
            box-shadow:
                0 6px 12px rgba(255, 111, 0, 0.3),
                0 12px 24px rgba(211, 47, 47, 0.2),
                inset 0 -2px 4px rgba(0, 0, 0, 0.2),
                inset 0 2px 4px rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transform: translateZ(10px);
            position: relative;
            overflow: hidden;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-4px) translateZ(20px) scale(1.02);
            box-shadow:
                0 10px 20px rgba(255, 111, 0, 0.4),
                0 20px 40px rgba(211, 47, 47, 0.3),
                inset 0 -2px 6px rgba(0, 0, 0, 0.3),
                inset 0 2px 6px rgba(255, 255, 255, 0.4);
            background: linear-gradient(135deg, #d32f2f, #FF6F00);
        }

        .login-btn:active {
            transform: translateY(-1px) translateZ(5px) scale(0.98);
            box-shadow:
                0 4px 8px rgba(255, 111, 0, 0.3),
                0 8px 16px rgba(211, 47, 47, 0.2);
        }

        /* ==================== MENU BOX (GLASS) ==================== */
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
        .menu-buttons {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .menu-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            padding: 18px;
            border: none;
            border-radius: 15px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            font-family: 'Prompt', sans-serif;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        /* 1. System Button */
        .btn-system {
            background: linear-gradient(90deg, #ff6f00, #ff8f00);
            color: white;
        }

        .btn-system:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(255, 111, 0, 0.4);
        }

        /* 2. Point Button */
        .btn-point {
            background: linear-gradient(90deg, #d32f2f, #e53935);
            color: white;
        }

        .btn-point:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(211, 47, 47, 0.4);
        }

        /* 3. SystemQ Button */
        .btn-queue {
            background: linear-gradient(90deg, #9c27b0, #ba68c8);
            color: white;
        }

        .btn-queue:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(156, 39, 176, 0.4);
        }

        /* 4. Back Button */
        .btn-back {
            background: white;
            color: var(--dark-brown);
            border: 2px solid #eee;
            margin-top: 15px;
            font-size: 1rem;
        }

        .btn-back:hover {
            background: #f5f5f5;
            color: var(--primary-red);
            border-color: var(--primary-red);
        }

        /* ==================== LOADER ==================== */
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

    <!-- Anti-Inspect Protection -->
    <script>
        // Disable right-click
        document.addEventListener('contextmenu', event => event.preventDefault());

        // Disable F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
        document.addEventListener('keydown', function (e) {
            // F12
            if (e.key === 'F12') {
                e.preventDefault();
                return false;
            }
            // Ctrl+Shift+I (DevTools)
            if (e.ctrlKey && e.shiftKey && e.key === 'I') {
                e.preventDefault();
                return false;
            }
            // Ctrl+Shift+J (Console)
            if (e.ctrlKey && e.shiftKey && e.key === 'J') {
                e.preventDefault();
                return false;
            }
            // Ctrl+U (View Source)
            if (e.ctrlKey && e.key === 'u') {
                e.preventDefault();
                return false;
            }
            // Ctrl+Shift+C (Inspect)
            if (e.ctrlKey && e.shiftKey && e.key === 'C') {
                e.preventDefault();
                return false;
            }
        });

        // Detect DevTools
        setInterval(function () {
            if (window.outerWidth - window.innerWidth > 160 || window.outerHeight - window.innerHeight > 160) {
                document.body.innerHTML = '<div style="display:flex;justify-content:center;align-items:center;height:100vh;font-family:Arial;font-size:24px;color:#f44336;">⚠️ กรุณาปิด Developer Tools</div>';
            }
        }, 1000);

        // Disable text selection
        document.addEventListener('selectstart', event => event.preventDefault());
    </script>
</head>

<body>

    <div id="loader-wrapper">
        <div class="loader"></div>
    </div>


    <!-- Main Menu Content -->
    <div class="menu-box" id="menu-content" style="max-width: 900px; padding: 35px;">
        <!-- Header with Icon -->
        <div style="text-align: center; margin-bottom: 35px;">
            <div style="font-size: 3.5rem; margin-bottom: 10px; animation: bounce 2s infinite;">🛠️</div>
            <h2
                style="margin: 0; font-size: 2.2rem; background: linear-gradient(135deg, #FF6F00, #d32f2f); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                เมนูผู้ดูแลระบบ</h2>
            <p style="color: #666; font-size: 0.95rem; margin-top: 5px;">Admin Control Panel</p>
        </div>

        <!-- Menu Grid -->
        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px;">
            <!-- Menu Management -->
            <a href="upload_form" class="menu-card"
                style="background: linear-gradient(135deg, #FF6F00 0%, #FF8F00 100%);">
                <div class="menu-icon">🍽️</div>
                <div class="menu-title">จัดการเมนู</div>
                <div class="menu-subtitle">Menu Management</div>
            </a>

            <!-- Points Management -->
            <a href="playerpoint" class="menu-card"
                style="background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);">
                <div class="menu-icon">💎</div>
                <div class="menu-title">จัดการคะแนน</div>
                <div class="menu-subtitle">Points System</div>
            </a>

            <!-- Queue Management -->
            <a href="manage_queue" class="menu-card"
                style="background: linear-gradient(135deg, #9C27B0 0%, #BA68C8 100%);">
                <div class="menu-icon">📋</div>
                <div class="menu-title">จัดการคิว</div>
                <div class="menu-subtitle">Queue Management</div>
            </a>

            <!-- Promotions -->
            <a href="manage_promotions" class="menu-card"
                style="background: linear-gradient(135deg, #E91E63 0%, #F44336 100%);">
                <div class="menu-icon">📢</div>
                <div class="menu-title">จัดการโปรโมชั่น</div>
                <div class="menu-subtitle">Promotions</div>
            </a>

            <!-- Code Management -->
            <a href="manage_codes" class="menu-card"
                style="background: linear-gradient(135deg, #FF5722 0%, #FF9800 100%);">
                <div class="menu-icon">🎁</div>
                <div class="menu-title">โค้ดแลก Point</div>
                <div class="menu-subtitle">Redeem Codes</div>
            </a>

            <!-- Sushi Claims -->
            <a href="view_claims" class="menu-card"
                style="background: linear-gradient(135deg, #EC407A 0%, #F06292 100%);">
                <div class="menu-icon">🍣</div>
                <div class="menu-title">รายการแลกซูชิ</div>
                <div class="menu-subtitle">Sushi Claims</div>
            </a>

            <!-- Accounting -->
            <a href="manage_accounting" class="menu-card"
                style="background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);">
                <div class="menu-icon">💰</div>
                <div class="menu-title">จัดการบัญชี</div>
                <div class="menu-subtitle">Accounting</div>
            </a>

            <!-- Admin Users Management -->
            <a href="manage_admin_users" class="menu-card"
                style="background: linear-gradient(135deg, #2196F3 0%, #03A9F4 100%);">
                <div class="menu-icon">👥</div>
                <div class="menu-title">จัดการผู้ดูแลระบบ</div>
                <div class="menu-subtitle">Admin Users</div>
            </a>
        </div>

        <!-- Footer Actions -->
        <div style="display: flex; gap: 15px; margin-top: 30px;">
            <a href="index" class="footer-btn"
                style="flex: 1; background: white; color: #666; border: 2px solid #e0e0e0;">
                <i class="fas fa-home"></i> กลับหน้าหลัก
            </a>
            <a href="?logout=1" class="footer-btn"
                style="flex: 1; background: linear-gradient(135deg, #f44336, #e91e63); color: white; border: none; text-decoration: none; display: flex; justify-content: center; align-items: center;">
                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
            </a>
        </div>
    </div>

    <style>
        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

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

        /* ==================== RESPONSIVE DESIGN ==================== */

        /* Large Desktop (1200px+) */
        @media (min-width: 1200px) {
            .menu-box {
                max-width: 1000px !important;
            }
        }

        /* Tablet Landscape (1024px - 1199px) */
        @media (max-width: 1199px) and (min-width: 1024px) {
            .menu-box {
                max-width: 900px !important;
                padding: 30px !important;
            }
        }

        /* Tablet Portrait (768px - 1023px) */
        @media (max-width: 1023px) and (min-width: 768px) {
            .menu-box {
                max-width: 700px !important;
                padding: 30px 25px !important;
            }

            .menu-card {
                padding: 25px 18px;
            }

            .menu-icon {
                font-size: 2.8rem;
            }

            .menu-title {
                font-size: 1.15rem;
            }

            h2 {
                font-size: 2rem !important;
            }
        }

        /* Large Mobile (641px - 767px) */
        @media (max-width: 767px) and (min-width: 641px) {
            .menu-box {
                max-width: 95% !important;
                padding: 25px 20px !important;
            }

            h2 {
                font-size: 1.8rem !important;
            }

            .menu-card {
                padding: 22px 15px;
            }

            .menu-icon {
                font-size: 2.6rem;
                margin-bottom: 12px;
            }

            .menu-title {
                font-size: 1.1rem;
            }

            .menu-subtitle {
                font-size: 0.8rem;
            }

            .footer-btn {
                font-size: 1rem;
                padding: 13px 20px;
            }
        }

        /* Standard Mobile (481px - 640px) */
        @media (max-width: 640px) and (min-width: 481px) {
            body {
                padding: 10px;
            }

            .menu-box {
                padding: 25px 20px !important;
                max-width: 95% !important;
            }

            h2 {
                font-size: 1.7rem !important;
            }

            [style*="margin-bottom: 35px"] {
                margin-bottom: 25px !important;
            }

            .menu-card {
                padding: 25px 15px;
            }

            .menu-icon {
                font-size: 2.5rem;
                margin-bottom: 10px;
            }

            .menu-title {
                font-size: 1.1rem;
            }

            .menu-subtitle {
                font-size: 0.8rem;
            }

            .footer-btn {
                font-size: 0.95rem;
                padding: 12px 18px;
                gap: 8px;
            }
        }

        /* Small Mobile (320px - 480px) */
        @media (max-width: 480px) {
            body {
                padding: 8px;
                overflow-y: auto;
                height: auto;
                min-height: 100vh;
            }

            .menu-box {
                padding: 20px 15px !important;
                max-width: 98% !important;
                margin: 10px auto;
            }

            [style*="font-size: 3.5rem"] {
                font-size: 2.8rem !important;
            }

            h2 {
                font-size: 1.5rem !important;
                line-height: 1.3;
            }

            [style*="margin-bottom: 35px"] {
                margin-bottom: 20px !important;
            }

            /* Single column layout for very small screens */
            [style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
                gap: 15px !important;
            }

            .menu-card {
                padding: 20px 15px;
            }

            .menu-icon {
                font-size: 2.2rem;
                margin-bottom: 8px;
            }

            .menu-title {
                font-size: 1rem;
            }

            .menu-subtitle {
                font-size: 0.75rem;
            }

            .footer-btn {
                font-size: 0.9rem;
                padding: 12px 15px;
                gap: 6px;
            }

            /* Stack footer buttons on very small screens */
            [style*="display: flex; gap: 15px"] {
                flex-direction: column !important;
                gap: 10px !important;
            }
        }

        /* Extra Small Mobile (< 360px) */
        @media (max-width: 359px) {
            .menu-box {
                padding: 15px 12px !important;
            }

            h2 {
                font-size: 1.3rem !important;
            }

            .menu-card {
                padding: 18px 12px;
            }

            .menu-icon {
                font-size: 2rem;
            }

            .menu-title {
                font-size: 0.95rem;
            }

            .footer-btn {
                font-size: 0.85rem;
                padding: 10px 12px;
            }
        }

        /* Landscape Orientation Adjustments */
        @media (max-height: 600px) and (orientation: landscape) {
            body {
                height: auto;
                min-height: 100vh;
                overflow-y: auto;
            }

            .menu-box {
                margin: 20px auto;
            }

            [style*="font-size: 3.5rem"] {
                font-size: 2.5rem !important;
                margin-bottom: 5px !important;
            }

            [style*="margin-bottom: 35px"] {
                margin-bottom: 15px !important;
            }

            .menu-card {
                padding: 20px 15px;
            }

            .menu-icon {
                font-size: 2rem;
                margin-bottom: 8px;
            }
        }
    </style>

    <script>
        // Toggle password visibility
        function toggleAdminPassword() {
            const passwordInput = document.getElementById('adminPassword');
            const toggleIcon = document.getElementById('adminToggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

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