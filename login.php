<?php
// login.php :: Backend Login Processing with Styled Error Pages

session_start();
include "db.php"; 

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Receive Form Data
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // 2. Prepare SQL
    $sql = "SELECT id, username, password FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // 3. Verify Password
        if (password_verify($password, $user['password'])) {
            
            // *** Login Success ***
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Redirect to dashboard/home
            header('Location: codeall'); 
            exit; 

        } else {
            $error_message = "รหัสผ่านไม่ถูกต้อง!";
        }
    } else {
        $error_message = "ไม่พบบัญชีนี้ในระบบ!";
    }

    $stmt->close();
} else {
    // If accessed directly, redirect to login form
    header('Location: formlogin');
    exit;
}

$conn->close();

// If we are here, there was an error. Display it beautifully.
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งเตือน - Delizio Shabu</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&family=Prompt:wght@600;700&display=swap" rel="stylesheet">
    <style>
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
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .error-box {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 50px 40px;
            border-radius: 25px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
            border: 1px solid rgba(255,255,255,0.8);
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }

        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }

        .icon-circle {
            width: 80px; height: 80px;
            background: #ffebee;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 25px;
            color: #d32f2f;
            font-size: 40px;
            box-shadow: 0 5px 15px rgba(211, 47, 47, 0.2);
        }

        h2 {
            font-family: 'Prompt', sans-serif;
            color: #d32f2f;
            margin: 0 0 15px;
            font-size: 1.8rem;
        }

        p {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 30px;
        }

        .btn-back {
            display: inline-block;
            background: linear-gradient(90deg, #d32f2f, #ff6f00);
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-family: 'Prompt', sans-serif;
            font-weight: bold;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 5px 15px rgba(211, 47, 47, 0.3);
        }

        .btn-back:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(211, 47, 47, 0.4);
        }
    </style>
</head>
<body>

    <div class="error-box">
        <div class="icon-circle">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2>เกิดข้อผิดพลาด!</h2>
        <p><?php echo $error_message; ?></p>
        <a href="formlogin" class="btn-back">
            <i class="fas fa-redo-alt"></i> ลองใหม่อีกครั้ง
        </a>
    </div>

</body>
</html>