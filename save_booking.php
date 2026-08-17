<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบก่อนทำการจอง']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $spot_id = intval($_POST['spot_id'] ?? 0);
    $hours = intval($_POST['hours'] ?? 1);
    $price_per_hour = floatval($_POST['price_per_hour'] ?? 0);
    $start_time_input = $_POST['start_time'] ?? '';

    if ($spot_id <= 0 || $hours <= 0 || empty($start_time_input)) {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลการจองให้ครบถ้วน']);
        exit;
    }

    // แปลงรูปแบบเวลาให้ตรงกับ MySQL (YYYY-MM-DD HH:MM:SS)
    $start_time = date('Y-m-d H:i:s', strtotime($start_time_input));
    $end_time = date('Y-m-d H:i:s', strtotime("$start_time +$hours hours"));
    $total_price = $hours * $price_per_hour;

    $stmt = $conn->prepare("INSERT INTO bookings (user_id, spot_id, start_time, end_time, total_price, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'SQL Error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("iissd", $user_id, $spot_id, $start_time, $end_time, $total_price);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => "จองสำเร็จ! เริ่มจอง: " . date('d/m/Y H:i', strtotime($start_time)) . " ราคารวม: " . number_format($total_price, 2) . " บาท"]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $stmt->error]);
    }
    $stmt->close();
}
$conn->close();
?>