<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $spot_id = intval($_POST['spot_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');

    if ($spot_id > 0 && $booking_id > 0 && $rating >= 1 && $rating <= 5) {
        // เพิ่มคอลัมน์ booking_id ในตาราง reviews อัตโนมัติ (หากยังไม่มี)
        @$conn->query("ALTER TABLE reviews ADD COLUMN booking_id INT NULL AFTER spot_id");

        // บันทึกลงตาราง reviews พร้อมผูก booking_id
        $sql = "INSERT INTO reviews (spot_id, booking_id, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiis", $spot_id, $booking_id, $user_id, $rating, $comment);
        
        if ($stmt->execute()) {
            header("Location: my_bookings.php?msg=review_success");
            exit();
        }
    }
}

header("Location: my_bookings.php?msg=review_error");
exit();
?>