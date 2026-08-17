<?php
session_start();
require_once 'db.php';

// เปิดแสดงข้อผิดพลาดหากเกิดปัญหาเพื่อการตรวจสอบ
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);
    $user_id = $_SESSION['user_id'];

    if ($booking_id > 0) {
        // 1. ปรับเปลี่ยนชนิดคอลัมน์ status ให้รับค่า 'cancelled' ได้แน่นอน (ป้องกันปัญหา ENUM เดิม)
        @$conn->query("ALTER TABLE bookings MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");

        // 2. อัปเดตสถานะเป็น 'cancelled' เฉพาะรายการของผู้ใช้งานรายนี้
        $sql = "UPDATE bookings SET status = 'cancelled' WHERE booking_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ii", $booking_id, $user_id);
            if ($stmt->execute()) {
                // สำเร็จแล้วส่งกลับไปหน้าประวัติการจอง
                header("Location: my_bookings.php?msg=cancel_success");
                exit();
            } else {
                die("เกิดข้อผิดพลาดขณะบันทึก: " . $stmt->error);
            }
        } else {
            die("เกิดข้อผิดพลาดของคำสั่ง SQL: " . $conn->error);
        }
    }
}

header("Location: my_bookings.php?msg=cancel_error");
exit();
?>