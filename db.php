<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "parking_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Auto-Decline: เปลี่ยนสถานะการจองที่รออนุมัติ (pending) เกิน 15 นาที เป็น 'rejected'
$auto_decline_sql = "UPDATE bookings 
                     SET status = 'rejected' 
                     WHERE status = 'pending' 
                     AND created_at <= NOW() - INTERVAL 15 MINUTE";
$conn->query($auto_decline_sql);
?>