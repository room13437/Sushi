<?php require_once 'protect_admin.php'; // ป้องกันด้วย MySQL Authentication ?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🍣 เพิ่มเมนูซูชิใหม่ - Delizio Sushi</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Prompt:wght@400;600;700&display=swap');

        :root {
            --primary-red: #d32f2f;
            --primary-orange: #ff6f00;
            --cream: #fff8e1;
            --dark-brown: #3e2723;
            --success-green: #4caf50;
            --danger-red: #f44336;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #fffbf0 0%, #ffe0b2 50%, #ffccbc 100%);
            background-attachment: fixed;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Steam Animation Background */
        @keyframes steam {
            0% {
                transform: translateY(0) scale(1);
                opacity: 0;
            }

            50% {
                opacity: 0.3;
            }

            100% {
                transform: translateY(-150px) scale(1.8);
                opacity: 0;
            }
        }

        .steam-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .steam {
            position: absolute;
            bottom: -50px;
            width: 60px;
            height: 60px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.4) 0%, transparent 70%);
            border-radius: 50%;
            animation: steam 10s infinite ease-in-out;
        }

        .steam:nth-child(1) {
            left: 5%;
            animation-delay: 0s;
        }

        .steam:nth-child(2) {
            left: 25%;
            animation-delay: 2s;
        }

        .steam:nth-child(3) {
            left: 45%;
            animation-delay: 4s;
        }

        .steam:nth-child(4) {
            left: 65%;
            animation-delay: 1.5s;
        }

        .steam:nth-child(5) {
            left: 85%;
            animation-delay: 3.5s;
        }

        /* Container & Card Styling */
        .container {
            width: 100%;
            max-width: 550px;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Logo/Icon at top */
        .logo {
            text-align: center;
            font-size: 4rem;
            margin-bottom: 10px;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        /* Header */
        h2 {
            text-align: center;
            background: linear-gradient(90deg, var(--primary-red), var(--primary-orange));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 30px;
            font-size: 2rem;
            font-family: 'Prompt', sans-serif;
            font-weight: 700;
        }

        /* Form Elements */
        label {
            display: block;
            margin-top: 20px;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-brown);
            font-size: 1.05rem;
        }

        input[type="text"],
        input[type="password"],
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 14px 16px;
            margin-top: 5px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            transition: all 0.3s;
            font-size: 1rem;
            font-family: 'Sarabun', sans-serif;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        textarea:focus {
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 3px rgba(255, 111, 0, 0.1);
            outline: none;
            transform: translateY(-2px);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        input[type="file"] {
            padding: 12px;
            background: #f5f5f5;
            cursor: pointer;
        }

        input[type="file"]:hover {
            background: #eeeeee;
        }

        /* Action Buttons Container */
        .button-group {
            margin-top: 30px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* Base Button Style */
        .btn {
            flex: 1;
            min-width: 150px;
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.05rem;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            color: white;
            transition: all 0.3s;
            font-family: 'Prompt', sans-serif;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .btn:active {
            transform: translateY(-1px);
        }

        /* Submit Button (Primary - Green) */
        input[type="submit"],
        .login-btn {
            background: linear-gradient(135deg, #4caf50, #66bb6a);
        }

        input[type="submit"]:hover,
        .login-btn:hover {
            background: linear-gradient(135deg, #388e3c, #4caf50);
        }

        /* Login form specific style */
        .login-btn {
            width: 100%;
            margin-top: 25px;
        }

        /* View Products Link Button (Secondary - Orange) */
        .view-link {
            background: linear-gradient(135deg, var(--primary-orange), #ff9800);
        }

        .view-link:hover {
            background: linear-gradient(135deg, #e65100, var(--primary-orange));
        }

        /* Home Link Button (Tertiary - Red) */
        .home-link {
            background: linear-gradient(135deg, var(--primary-red), #e53935);
        }

        .home-link:hover {
            background: linear-gradient(135deg, #b71c1c, var(--primary-red));
        }

        /* Logout Button */
        .logout-btn {
            background: linear-gradient(135deg, #f44336, #e53935);
        }

        .logout-btn:hover {
            background: linear-gradient(135deg, #c62828, #f44336);
        }

        /* Alert Message */
        #login-error {
            color: var(--danger-red);
            text-align: center;
            margin-top: 15px;
            font-weight: 600;
            padding: 12px;
            background: #ffebee;
            border-radius: 8px;
            border-left: 4px solid var(--danger-red);
        }

        /* Success Message Style */
        .success-message {
            color: var(--success-green);
            background: #e8f5e9;
            border-left: 4px solid var(--success-green);
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            margin-top: 15px;
            font-weight: 600;
        }

        /* Decorative elements */
        .container::before {
            content: '🍲';
            position: absolute;
            top: -30px;
            right: -30px;
            font-size: 5rem;
            opacity: 0.1;
            transform: rotate(15deg);
        }

        .container::after {
            content: '🥢';
            position: absolute;
            bottom: -20px;
            left: -20px;
            font-size: 4rem;
            opacity: 0.1;
            transform: rotate(-25deg);
        }

        /* Responsive */
        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }

            h2 {
                font-size: 1.6rem;
            }

            .btn {
                min-width: 100%;
                flex-basis: 100%;
            }
        }

        /* Loading animation for file input */
        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .loading {
            animation: pulse 1.5s infinite;
        }
    </style>
    <!-- Three.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="js/three_bg.js"></script>
</head>

<body>

    <div class="steam-bg">
        <div class="steam"></div>
        <div class="steam"></div>
        <div class="steam"></div>
        <div class="steam"></div>
        <div class="steam"></div>
    </div>

    <!-- Product Form -->
    <div class="container" id="product-form-container">
        <div class="logo">🍣</div>
        <h2>เพิ่มเมนูซูชิใหม่</h2>

        <form action="process_upload" method="POST" enctype="multipart/form-data">

            <label for="name"> ชื่อเมนูซูชิ:</label>
            <input type="text" id="name" name="name" required
                placeholder="เช่น แซลมอนซาชิมิ, มากุโระนิงิริ, แคลิฟอร์เนียโรล">

            <label for="description">📝 รายละเอียดเมนู:</label>
            <textarea id="description" name="description" rows="4"
                placeholder="อธิบายรสชาติ ส่วนประกอบ และความพิเศษของซูชิเมนูนี้..."></textarea>

            <label for="price">💰 ราคา (บาท):</label>
            <input type="text" id="price" name="price" required pattern="[0-9]+(\.[0-9]{1,2})?"
                placeholder="เช่น 299.00">

            <label for="image">📸 รูปภาพซูชิ:</label>
            <input type="file" id="image" name="product_image" accept="image/jpeg,image/png" required>

            <div class="button-group">
                <input type="submit" value="✅ บันทึกเมนูซูชิ" class="btn">

                <a href="display_products" class="btn view-link">
                    📊 ดูรายการ
                </a>
            </div>

            <div class="button-group" style="margin-top: 15px;">
                <a href="formmenu" class="btn home-link" style="width: 100%;">
                    🏠 กลับเมนูหลัก
                </a>
            </div>

        </form>
    </div>


</body>

</html>