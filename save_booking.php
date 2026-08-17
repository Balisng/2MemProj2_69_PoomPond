<?php
session_start(); // เพิ่มบรรทัดนี้ด้านบนสุด
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบก่อนทำการจอง']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = 1; // สมมติเป็น ID ผู้ใช้ปัจจุบัน (รอเชื่อมระบบ Login)
    $spot_id = intval($_POST['spot_id'] ?? 0);
    $hours = intval($_POST['hours'] ?? 1);
    $price_per_hour = floatval($_POST['price_per_hour'] ?? 0);

    $start_time = date('Y-m-d H:i:s');
    $end_time = date('Y-m-d H:i:s', strtotime("+$hours hours"));
    $total_price = $hours * $price_per_hour;

    $stmt = $conn->prepare("INSERT INTO bookings (user_id, spot_id, start_time, end_time, total_price, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iissd", $user_id, $spot_id, $start_time, $end_time, $total_price);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => "จองสำเร็จ! ราคารวม: $total_price บาท"]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล']);
    }
    $stmt->close();
}
$conn->close();
?>