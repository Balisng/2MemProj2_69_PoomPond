<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $spot_id = intval($_POST['spot_id'] ?? 0);
    $start_time = $_POST['start_time'] ?? '';
    $hours = intval($_POST['hours'] ?? 1);

    // ดึงราคาต่อชั่วโมง
    $stmt_price = $conn->prepare("SELECT price_per_hour FROM parking_spots WHERE spot_id = ?");
    $stmt_price->bind_param("i", $spot_id);
    $stmt_price->execute();
    $spot = $stmt_price->get_result()->fetch_assoc();

    if ($spot && $hours > 0 && !empty($start_time)) {
        $total_price = $hours * floatval($spot['price_per_hour']);
        $status = 'pending';

        $stmt = $conn->prepare("INSERT INTO bookings (user_id, spot_id, start_time, hours, total_price, status) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iisids", $user_id, $spot_id, $start_time, $hours, $total_price, $status);
            if ($stmt->execute()) {
                $booking_id = $stmt->insert_id;
                header("Location: upload_slip.php?booking_id=" . $booking_id);
                exit;
            }
        }
        
        // หากในโครงสร้าง DB ไม่มีคอลัมน์ hours ให้ลองบันทึกแบบย่อ
        $stmt_alt = $conn->prepare("INSERT INTO bookings (user_id, spot_id, start_time, total_price, status) VALUES (?, ?, ?, ?, ?)");
        if ($stmt_alt) {
            $stmt_alt->bind_param("iisds", $user_id, $spot_id, $start_time, $total_price, $status);
            if ($stmt_alt->execute()) {
                header("Location: upload_slip.php?booking_id=" . $stmt_alt->insert_id);
                exit;
            }
        }
    }
}

header("Location: index.php");
exit;