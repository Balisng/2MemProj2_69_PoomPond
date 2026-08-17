<?php
require_once 'db.php';

$spot_id = isset($_GET['spot_id']) ? intval($_GET['spot_id']) : 0;

if ($spot_id > 0) {
    // ดึงรีวิวทั้งหมดของสถานที่นี้ พร้อมชื่อผู้รีวิว
    $sql = "SELECT r.*, u.full_name 
            FROM reviews r 
            JOIN users u ON r.user_id = u.user_id 
            WHERE r.spot_id = ? 
            ORDER BY r.review_id DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $spot_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($reviews);
}
?>