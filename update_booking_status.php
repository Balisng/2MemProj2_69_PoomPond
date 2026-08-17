<?php
session_start();
require_once 'db.php';

// 1. ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. ตรวจสอบค่าที่ส่งมาจากฟอร์ม (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id']) && isset($_POST['action'])) {
    $user_id = $_SESSION['user_id'];
    $booking_id = intval($_POST['booking_id']);
    $action = $_POST['action'];

    // กำหนดสถานะตามการกดปุ่ม
    $new_status = '';
    if ($action === 'approve') {
        $new_status = 'approved';
    } elseif ($action === 'reject') {
        $new_status = 'rejected';
    }

    if (!empty($new_status)) {
        // 3. ตรวจสอบสิทธิ์ว่า การจองนี้เป็นที่จอดรถของเจ้าของคนนี้จริงหรือไม่ (เพื่อความปลอดภัย)
        $check_sql = "SELECT b.booking_id 
                      FROM bookings b 
                      JOIN parking_spots p ON b.spot_id = p.spot_id 
                      WHERE b.booking_id = ? AND p.user_id = ?";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("ii", $booking_id, $user_id);
        $stmt_check->execute();
        $res = $stmt_check->get_result();

        if ($res->num_rows > 0) {
            // 4. อัปเดตสถานะในตาราง bookings
            $update_sql = "UPDATE bookings SET status = ? WHERE booking_id = ?";
            $stmt_update = $conn->prepare($update_sql);
            $stmt_update->bind_param("si", $new_status, $booking_id);
            
            if ($stmt_update->execute()) {
                // อัปเดตสำเร็จ ส่งกลับไปหน้าเดิมพร้อมข้อความ
                header("Location: owner_bookings.php?msg=updated");
                exit();
            }
        }
    }
}

// ถ้ามีอะไรผิดพลาด ให้ส่งกลับหน้าเดิม
header("Location: owner_bookings.php?msg=error");
exit();
?>