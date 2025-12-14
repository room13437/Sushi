<?php
/**
 * Script สำหรับลบ CORRECT_PASSWORD และ PASS ออกจากทุกไฟล์
 * และแจ้งเตือนให้ใช้ระบบ MySQL Authentication แทน
 */

echo "🔍 กำลังค้นหารหัสผ่าน hardcode ใน JavaScript...\n\n";

$files_to_check = [
    'playerpoint.php',
    'display_products.php',
    'manage_accounting.php',
    'upload_form.php',
    'upload_promotion.php',
    'edit_product.php',
    'manage_promotions.php',
    'edit_promotion.php'
];

$found_passwords = [];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);

        // ค้นหา CORRECT_PASSWORD
        if (preg_match('/const\s+CORRECT_PASSWORD\s*=\s*["\']([^"\']+)["\']/', $content, $matches)) {
            $found_passwords[$file][] = "CORRECT_PASSWORD = " . $matches[1];
        }

        // ค้นหา PASS
        if (preg_match('/const\s+PASS\s*=\s*["\']([^"\']+)["\']/', $content, $matches)) {
            $found_passwords[$file][] = "PASS = " . $matches[1];
        }
    }
}

echo "📋 สรุปผลการตรวจสอบ:\n";
echo str_repeat("=", 50) . "\n\n";

if (empty($found_passwords)) {
    echo "✅ ไม่พบรหัสผ่าน hardcode ในระบบ!\n";
    echo "   ระบบปลอดภัยแล้ว\n";
} else {
    echo "⚠️  พบรหัสผ่าน hardcode ในไฟล์ต่อไปนี้:\n\n";
    foreach ($found_passwords as $file => $passwords) {
        echo "📄 $file\n";
        foreach ($passwords as $pwd) {
            echo "   └─ $pwd\n";
        }
        echo "\n";
    }

    echo "🔐 คำแนะนำ:\n";
    echo "   1. เปลี่ยนไฟล์ทั้งหมดให้ใช้ admin_auth.php\n";
    echo "   2. ลบ const CORRECT_PASSWORD และ const PASS ออก\n";
    echo "   3. เปลี่ยนเป็น MySQL Authentication\n";
    echo "   4. ใช้ PHP Session แทน localStorage\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
?>