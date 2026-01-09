<?php
/**
 * System Verification Script
 * Checks for core database tables, assets, and standard functions.
 */
require_once 'db_config.php';

echo "<h1>🔍 System Verification Report</h1>";
echo "<p>Generating report on " . date('Y-m-d H:i:s') . "</p>";

// 1. Check Tables
$required_tables = [
    'admin_users',
    'products',
    'store_settings',
    'accounting',
    'promotions',
    'store_locations',
    'daily_queue',
    'bookings',
    'customers',
    'point_codes',
    'redemption_history',
    'reward_claims',
    'sushi_redemption_tiers',
    'code_redemptions',
    'discount_codes'
];

echo "<h2>📊 Database Tables</h2>";
echo "<ul>";
foreach ($required_tables as $table) {
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    if ($res->num_rows > 0) {
        echo "<li>✅ Table <b>$table</b> found.</li>";
    } else {
        echo "<li style='color:red'>❌ Table <b>$table</b> IS MISSING!</li>";
    }
}
echo "</ul>";

// 2. Check Essential Assets
$assets = [
    'icon/icons.png',
    'video/Sushi.mp4',
    'js/three_bg.js'
];

echo "<h2>🖼️ Essential Assets</h2>";
echo "<ul>";
foreach ($assets as $asset) {
    if (file_exists($asset)) {
        echo "<li>✅ Asset <b>$asset</b> found. (" . number_format(filesize($asset) / 1024, 2) . " KB)</li>";
    } else {
        echo "<li style='color:red'>❌ Asset <b>$asset</b> IS MISSING!</li>";
    }
}
echo "</ul>";

// 3. Test Compatibility Logic
echo "<h2>⚙️ Compatibility Tests</h2>";
$test_month = 2; // February
$test_year = 2024; // Leap year
$days = date('t', mktime(0, 0, 0, $test_month, 1, $test_year));
if ($days == 29) {
    echo "<li>✅ Date calculation (date 't') is working correctly ($days days in Feb 2024).</li>";
} else {
    echo "<li style='color:red'>❌ Date calculation (date 't') FAILED! (Expected 29, got $days)</li>";
}

echo "<h2>🎉 Verification Complete</h2>";
echo "<p><a href='formmenu'>Back to Admin Menu</a></p>";

$conn->close();
?>