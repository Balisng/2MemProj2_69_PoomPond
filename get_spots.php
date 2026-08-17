<?php
header('Content-Type: application/json');
require_once 'db.php';

$sql = "SELECT p.*, 
               COALESCE(NULLIF(p.contact_phone, ''), u.phone, '-') AS owner_phone,
               COALESCE(u.full_name, 'ไม่ระบุผู้ดูแล') AS owner_name, 
               COALESCE(AVG(r.rating), 0) AS avg_rating, 
               COUNT(r.review_id) AS total_reviews 
        FROM parking_spots p 
        LEFT JOIN users u ON p.user_id = u.user_id 
        LEFT JOIN reviews r ON p.spot_id = r.spot_id 
        WHERE p.status = 'available' 
        GROUP BY p.spot_id, u.full_name, u.phone";

$result = $conn->query($sql);
$spots = array();

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['avg_rating'] = round(floatval($row['avg_rating']), 1);
        $row['description'] = $row['description'] ?? '';
        $spots[] = $row;
    }
}

echo json_encode($spots);
$conn->close();
?>