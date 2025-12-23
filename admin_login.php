<?php
require_once 'admin_auth.php';

// ตรวจสอบว่า login แล้วหรือยัง
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// ถ้า login แล้ว redirect ไป formmenu
if ($isLoggedIn) {
    header('Location: formmenu');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="icon/icons.png?v=4">
    <title>🔒 เข้าสู่ระบบผู้ดูแล | Admin Login</title>

    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-orange: #FF6F00;
            --primary-red: #d32f2f;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #FFF9F0 0%, #FFEDD5 50%, #FED7AA 100%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            max-width: 420px;
            width: 100%;
            padding: 45px 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            text-align: center;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .lock-icon {
            font-size: 4rem;
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-red));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
        }

        h2 {
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-red));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.8rem;
            margin: 0 0 8px 0;
            font-weight: 700;
        }

        .subtitle {
            color: #999;
            font-size: 0.9rem;
            margin: 0 0 30px 0;
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
            text-align: left;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1.1rem;
        }

        input {
            width: 100%;
            padding: 14px 14px 14px 48px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: 'Sarabun', sans-serif;
        }

        input:focus {
            outline: none;
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 3px rgba(255, 111, 0, 0.1);
        }

        input:focus~.input-icon {
            color: var(--primary-orange);
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
            color: var(--primary-orange);
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-red));
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
            background: linear-gradient(135deg, var(--primary-red), var(--primary-orange));
            box-shadow: 0 6px 16px rgba(255, 111, 0, 0.4);
            transform: translateY(-2px);
        }

        .home-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--primary-orange);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }

        .home-link:hover {
            color: var(--primary-red);
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="lock-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        <h2>เข้าสู่ระบบผู้ดูแล</h2>
        <p class="subtitle">Admin Control Panel</p>

        <?php if (isset($loginError)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $loginError; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-group">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="username" required placeholder="ชื่อผู้ใช้" autofocus autocomplete="username">
            </div>
            <div class="input-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" id="adminPassword" required placeholder="รหัสผ่าน"
                    autocomplete="current-password">
                <button type="button" onclick="togglePassword()" class="toggle-password">
                    <i class="fas fa-eye" id="toggleIcon"></i>
                </button>
            </div>
            <button type="submit" name="admin_login" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
            </button>
        </form>

        <a href="index" class="home-link">
            <i class="fas fa-home"></i> กลับหน้าหลัก
        </a>
    </div>

    <script>
        function togglePassword() {
            const password = document.getElementById('adminPassword');
            const icon = document.getElementById('toggleIcon');

            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>