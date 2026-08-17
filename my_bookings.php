<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
// ดึงพิกัด latitude, longitude เพิ่มเติมเพื่อใช้นำทาง
$sql = "SELECT b.*, p.title, p.latitude, p.longitude FROM bookings b 
        JOIN parking_spots p ON b.spot_id = p.spot_id 
        WHERE b.user_id = ? ORDER BY b.booking_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ประวัติการจองของฉัน</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .status-pending { color: orange; font-weight: bold; }
        .btn-map { color: #007bff; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <h2>📋 ประวัติการจองของ <?= htmlspecialchars($_SESSION['full_name']) ?></h2>
    <p><a href="index.php">← กลับหน้าแผนที่</a></p>

    <table>
        <tr>
            <th>รหัสการจอง</th>
            <th>สถานที่</th>
            <th>เวลาเริ่ม</th>
            <th>เวลาสิ้นสุด</th>
            <th>ราคารวม</th>
            <th>สถานะ</th>
            <th>นำทาง</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>#<?= $row['booking_id'] ?></td>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= $row['start_time'] ?></td>
                    <td><?= $row['end_time'] ?></td>
                    <td><?= number_format($row['total_price'], 2) ?> บาท</td>
                    <td><span class="status-pending"><?= $row['status'] ?></span></td>
                    <td>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $row['latitude'] ?>,<?= $row['longitude'] ?>" target="_blank" class="btn-map">🗺️ นำทาง</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center;">ยังไม่มีรายการจอง</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>