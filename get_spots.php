<?php
header('Content-Type: application/json');
require_once 'db.php';

$sql = "SELECT p.*, 
               u.full_name AS owner_name, 
               u.phone AS owner_phone,
               COALESCE(AVG(r.rating), 0) AS avg_rating, 
               COUNT(r.review_id) AS total_reviews 
        FROM parking_spots p 
        JOIN users u ON p.user_id = u.user_id 
        WHERE p.status = 'available' 
        GROUP BY p.spot_id";

$result = $conn->query($sql);
$spots = array();

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['avg_rating'] = round(floatval($row['avg_rating']), 1);
        $spots[] = $row;
    }
}

echo json_encode($spots);
$conn->close();
?>