<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $booking_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // ปรับสถานะเป็น cancelled เฉพาะรายการที่ยังอยู่ในสถานะ pending
    $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ? AND user_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
}

header("Location: my_bookings.php");
exit;
?>