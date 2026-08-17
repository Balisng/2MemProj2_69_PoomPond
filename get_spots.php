<?php
header('Content-Type: application/json');
require_once 'db.php';

$sql = "SELECT * FROM parking_spots WHERE status = 'available'";
$result = $conn->query($sql);

$spots = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $spots[] = $row;
    }
}

echo json_encode($spots);
$conn->close();
?>