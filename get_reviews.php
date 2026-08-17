<?php
header('Content-Type: application/json');
require_once 'db.php';

$spot_id = intval($_GET['spot_id'] ?? 0);

$sql = "SELECT r.*, u.full_name 
        FROM reviews r 
        LEFT JOIN users u ON r.user_id = u.user_id 
        WHERE r.spot_id = ? 
        ORDER BY r.review_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $spot_id);
$stmt->execute();
$result = $stmt->get_result();

$reviews = array();
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}

echo json_encode($reviews);
$conn->close();
?>