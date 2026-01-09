<?php
session_start();
require_once 'db_config.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Quick Admin Login Test</title></head><body>";
echo "<h1>Quick Admin Login Test</h1>";

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    echo "<h2>Login Attempt:</h2>";
    echo "<p>Username: " . htmlspecialchars($username) . "</p>";
    echo "<p>Password: " . htmlspecialchars($password) . "</p>";

    $stmt = $conn->prepare("SELECT id, password, full_name FROM admin_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        echo "<p style='color: blue;'>✓ User found: " . htmlspecialchars($user['full_name']) . "</p>";
        echo "<p>Stored password: '" . htmlspecialchars($user['password']) . "'</p>";
        echo "<p>Input password: '" . htmlspecialchars($password) . "'</p>";

        if ($password === $user['password']) {
            echo "<p style='color: green;'><strong>✅ LOGIN SUCCESS!</strong></p>";

            // Set session
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_name'] = $user['full_name'];
            $_SESSION['admin_login_time'] = time();

            echo "<p><a href='formmenu.php' style='background: green; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Panel</a></p>";
        } else {
            echo "<p style='color: red;'><strong>❌ PASSWORD MISMATCH</strong></p>";
            echo "<p>Password comparison failed!</p>";
        }
    } else {
        echo "<p style='color: red;'><strong>❌ USER NOT FOUND</strong></p>";
    }
    $stmt->close();

    echo "<hr>";
}

// Show login form
echo "<h2>Login Form:</h2>";
echo "<form method='POST'>";
echo "<label>Username:</label><br>";
echo "<input type='text' name='username' value='2544' style='padding: 5px; margin: 5px 0;'><br><br>";
echo "<label>Password:</label><br>";
echo "<input type='text' name='password' value='2545' style='padding: 5px; margin: 5px 0;'><br><br>";
echo "<button type='submit' style='background: blue; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test Login</button>";
echo "</form>";

echo "<hr>";
echo "<h2>Database Info:</h2>";
$admins = $conn->query("SELECT id, username, full_name, password FROM admin_users");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Password</th></tr>";
while ($admin = $admins->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $admin['id'] . "</td>";
    echo "<td>" . htmlspecialchars($admin['username']) . "</td>";
    echo "<td>" . htmlspecialchars($admin['full_name']) . "</td>";
    echo "<td>" . htmlspecialchars($admin['password']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "</body></html>";
?>