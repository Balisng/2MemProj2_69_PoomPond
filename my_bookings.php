<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
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
        .btn { padding: 4px 8px; text-decoration: none; border-radius: 4px; font-size: 13px; color: white; display: inline-block; }
        .btn-pay { background: #28a745; }
        .btn-cancel { background: #dc3545; }
        .btn-map { color: #007bff; text-decoration: none; font-weight: bold; }
        .badge { padding: 3px 6px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .bg-pending { background: #fff3cd; color: #856404; }
        .bg-approved { background: #d4edda; color: #155724; }
        .bg-cancelled { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h2>📋 ประวัติการจองของ <?= htmlspecialchars($_SESSION['full_name']) ?></h2>
    <p><a href="index.php">← กลับหน้าแผนที่</a></p>

    <table>
        <tr>
            <th>รหัสจอง</th>
            <th>สถานที่</th>
            <th>ช่วงเวลา</th>
            <th>ราคารวม</th>
            <th>สถานะจอง</th>
            <th>การชำระเงิน</th>
            <th>จัดการ</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>#<?= $row['booking_id'] ?></td>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['start_time'])) ?> - <?= date('H:i', strtotime($row['end_time'])) ?></td>
                    <td><?= number_format($row['total_price'], 2) ?> บาท</td>
                    <td>
                        <?php if ($row['status'] === 'pending'): ?>
                            <span class="badge bg-pending">⏳ รอการยืนยัน</span>
                        <?php elseif ($row['status'] === 'approved'): ?>
                            <span class="badge bg-approved">✅ อนุมัติแล้ว</span>
                        <?php else: ?>
                            <span class="badge bg-cancelled">❌ ยกเลิกแล้ว</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['payment_status'] === 'unpaid'): ?>
                            <span style="color:red;">ยังไม่ชำระ</span>
                        <?php elseif ($row['payment_status'] === 'pending_approval'): ?>
                            <span style="color:orange;">ส่งสลิปแล้ว (รอตรวจ)</span>
                        <?php else: ?>
                            <span style="color:green;">ชำระแล้ว</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $row['latitude'] ?>,<?= $row['longitude'] ?>" target="_blank" class="btn-map">🗺️ นำทาง</a>
                        
                        <?php if ($row['status'] === 'pending'): ?>
                            <?php if ($row['payment_status'] === 'unpaid'): ?>
                                | <a href="upload_slip.php?id=<?= $row['booking_id'] ?>" class="btn btn-pay">💳 จ่ายเงิน</a>
                            <?php endif; ?>
                            | <a href="cancel_booking.php?id=<?= $row['booking_id'] ?>" class="btn btn-cancel" onclick="return confirm('ยืนยันยกเลิกการจอง?')">ยกเลิก</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center;">ยังไม่มีรายการจอง</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>