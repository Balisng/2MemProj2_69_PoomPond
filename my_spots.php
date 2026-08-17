<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// สลับสถานะ available <-> full
if (isset($_GET['toggle_id'])) {
    $spot_id = intval($_GET['toggle_id']);
    $current_status = $_GET['current_status'];
    $new_status = ($current_status === 'available') ? 'full' : 'available';

    $stmt = $conn->prepare("UPDATE parking_spots SET status = ? WHERE spot_id = ? AND user_id = ?");
    $stmt->bind_param("sii", $new_status, $spot_id, $user_id);
    $stmt->execute();
    header("Location: my_spots.php");
    exit;
}

$sql = "SELECT * FROM parking_spots WHERE user_id = ? ORDER BY spot_id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการที่จอดรถของฉัน</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { padding: 5px 10px; text-decoration: none; border-radius: 4px; color: white; font-size: 14px; }
        .btn-available { background: #28a745; }
        .btn-full { background: #dc3545; }
        .btn-add { background: #007bff; display: inline-block; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2>🅿️ จัดการที่จอดรถของฉัน</h2>
    <p>
        <a href="index.php">← กลับหน้าหลัก</a> | 
        <a href="add_spot.php" class="btn btn-add">+ เพิ่มที่จอดรถใหม่</a>
    </p>

    <table>
        <tr>
            <th>รูป</th>
            <th>ชื่อสถานที่</th>
            <th>ราคา/ชม.</th>
            <th>พิกัด</th>
            <th>สถานะปัจจุบัน</th>
            <th>จัดการ</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?php if ($row['image']): ?>
                            <img src="uploads/<?= $row['image'] ?>" width="60" height="40" style="object-fit:cover; border-radius:4px;">
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= number_format($row['price_per_hour'], 2) ?> บาท</td>
                    <td><?= $row['latitude'] ?>, <?= $row['longitude'] ?></td>
                    <td><b><?= $row['status'] === 'available' ? '🟢ว่าง' : '🔴เต็ม' ?></b></td>
                    <td>
                        <a href="my_spots.php?toggle_id=<?= $row['spot_id'] ?>&current_status=<?= $row['status'] ?>" 
                           class="btn <?= $row['status'] === 'available' ? 'btn-full' : 'btn-available' ?>">
                            สลับเป็น <?= $row['status'] === 'available' ? 'เต็ม' : 'ว่าง' ?>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align:center;">คุณยังไม่ได้ลงทะเบียนที่จอดรถ</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>