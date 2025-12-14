<?php
// Test Database Connection
require_once 'db.php';

echo "<h2>Database Connection Test</h2>";
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Connection failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Database connected successfully!</p>";
}

// Test admin_users table
echo "<h2>Admin Users Table Test</h2>";
$result = $conn->query("SHOW TABLES LIKE 'admin_users'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Table 'admin_users' exists</p>";

    // Show admin users
    $users = $conn->query("SELECT id, username, full_name, created_at, last_login FROM admin_users");
    echo "<h3>Admin Users:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Created At</th><th>Last Login</th></tr>";
    while ($user = $users->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['username'] . "</td>";
        echo "<td>" . $user['full_name'] . "</td>";
        echo "<td>" . $user['created_at'] . "</td>";
        echo "<td>" . ($user['last_login'] ?? 'Never') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Test password
    echo "<h3>Password Test:</h3>";
    $testUser = $conn->query("SELECT password FROM admin_users WHERE username = 'admin'")->fetch_assoc();
    if ($testUser) {
        $testPassword = 'AdminN_N';
        $verified = password_verify($testPassword, $testUser['password']);
        if ($verified) {
            echo "<p style='color: green;'>✅ Password 'AdminN_N' is correct!</p>";
        } else {
            echo "<p style='color: red;'>❌ Password verification failed!</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ Table 'admin_users' does not exist</p>";
}

// Test Session
session_start();
echo "<h2>Session Test</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? '✅ Active' : '❌ Inactive') . "</p>";

$conn->close();
?>