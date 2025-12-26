<?php
// API สำหรับตรวจสอบโค้ดส่วนลด
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณา login ก่อน']);
    exit;
}

$code = isset($_GET['code']) ? strtoupper(trim($_GET['code'])) : '';
$points = isset($_GET['points']) ? (int) $_GET['points'] : 0;
$user_id = $_SESSION['user_id'];

if (empty($code) || $points <= 0) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

// ตรวจสอบโค้ดส่วนลด
$stmt = $conn->prepare("SELECT id, discount_percent, max_uses, active FROM discount_codes WHERE code = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => '❌ โค้ดส่วนลดไม่ถูกต้อง']);
    $stmt->close();
    $conn->close();
    exit;
}

$discount_row = $result->fetch_assoc();
$code_id = $discount_row['id'];
$discount_percent = (int) $discount_row['discount_percent'];
$max_uses = (int) $discount_row['max_uses'];
$is_active = (int) $discount_row['active'];
$stmt->close();

// ตรวจสอบสถานะ
if ($is_active == 0) {
    echo json_encode(['success' => false, 'message' => '❌ โค้ดส่วนลดนี้ถูกปิดใช้งานแล้ว']);
    $conn->close();
    exit;
}

// นับจำนวนการใช้งานทั้งหมด
$stmt_usage = $conn->prepare("SELECT COUNT(*) as usage_count FROM discount_code_usage WHERE code_id = ?");
$stmt_usage->bind_param("i", $code_id);
$stmt_usage->execute();
$usage_result = $stmt_usage->get_result();
$usage_count = $usage_result->fetch_assoc()['usage_count'];
$stmt_usage->close();

if ($usage_count >= $max_uses) {
    echo json_encode(['success' => false, 'message' => '❌ โค้ดส่วนลดนี้ถูกใช้งานครบจำนวนแล้ว']);
    $conn->close();
    exit;
}

// ตรวจสอบว่าผู้ใช้คนนี้เคยใช้แล้วหรือยัง
$stmt_user = $conn->prepare("SELECT id FROM discount_code_usage WHERE code_id = ? AND user_id = ?");
$stmt_user->bind_param("ii", $code_id, $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();

if ($user_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => '❌ คุณเคยใช้โค้ดส่วนลดนี้ไปแล้ว']);
    $stmt_user->close();
    $conn->close();
    exit;
}
$stmt_user->close();

// คำนวณส่วนลด
$final_points = round($points * (100 - $discount_percent) / 100);
$points_saved = $points - $final_points;
$remaining_uses = $max_uses - $usage_count;

echo json_encode([
    'success' => true,
    'discount_percent' => $discount_percent,
    'original_points' => $points,
    'final_points' => $final_points,
    'points_saved' => $points_saved,
    'remaining_uses' => $remaining_uses,
    'max_uses' => $max_uses,
    'current_uses' => $usage_count
]);

$conn->close();
?>