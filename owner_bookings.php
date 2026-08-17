<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$owner_id = $_SESSION['user_id'];

// จัดการกดอนุมัติ หรือ ปฏิเสธ
if (isset($_GET['action']) && isset($_GET['id'])) {
    $booking_id = intval($_GET['id']);
    $action = $_GET['action'];

    // ตรวจสอบว่ารายการจองนี้เป็นของสถานที่จอดรถที่เป็นของเจ้าของคนนี้จริงไหม
    $check_stmt = $conn->prepare("SELECT b.booking_id FROM bookings b JOIN parking_spots p ON b.spot_id = p.spot_id WHERE b.booking_id = ? AND p.user_id = ?");
    $check_stmt->bind_param("ii", $booking_id, $owner_id);
    $check_stmt->execute();

    if ($check_stmt->get_result()->num_rows > 0) {
        if ($action === 'approve') {
            $update = $conn->prepare("UPDATE bookings SET status = 'approved', payment_status = 'paid' WHERE booking_id = ?");
            $update->bind_param("i", $booking_id);
            $update->execute();
        } elseif ($action === 'reject') {
            $update = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?");
            $update->bind_param("i", $booking_id);
            $update->execute();
        }
    }
    header("Location: owner_bookings.php");
    exit;
}

// ดึงรายการจองทั้งหมดที่เกิดขึ้นกับที่จอดรถของเจ้าของคนนี้
$sql = "SELECT b.*, p.title, u.full_name, u.phone 
        FROM bookings b 
        JOIN parking_spots p ON b.spot_id = p.spot_id 
        JOIN users u ON b.user_id = u.user_id 
        WHERE p.user_id = ? 
        ORDER BY b.booking_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายการจองสถานที่ของฉัน</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: middle; }
        th { background-color: #f2f2f2; }
        .btn { padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 13px; color: white; display: inline-block; }
        .btn-approve { background: #28a745; }
        .btn-reject { background: #dc3545; }
        .badge { padding: 3px 6px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .bg-pending { background: #fff3cd; color: #856404; }
        .bg-approved { background: #d4edda; color: #155724; }
        .bg-cancelled { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h2>📥 รายการที่มีผู้จองเข้ามา</h2>
    <p>
        <a href="index.php">← กลับหน้าหลัก</a> | 
        <a href="my_spots.php">🅿️ จัดการสถานที่จอดรถ</a>
    </p>

    <table>
        <tr>
            <th>รหัสจอง</th>
            <th>สถานที่</th>
            <th>ผู้จอง (เบอร์โทร)</th>
            <th>ช่วงเวลา</th>
            <th>ราคารวม</th>
            <th>หลักฐานสลิป</th>
            <th>สถานะ</th>
            <th>การจัดการ</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>#<?= $row['booking_id'] ?></td>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?><br><small>(<?= htmlspecialchars($row['phone']) ?>)</small></td>
                    <td><?= date('d/m/H:i', strtotime($row['start_time'])) ?> - <?= date('H:i', strtotime($row['end_time'])) ?></td>
                    <td><?= number_format($row['total_price'], 2) ?> บาท</td>
                    <td>
                        <?php if ($row['slip_image']): ?>
                            <a href="uploads/<?= $row['slip_image'] ?>" target="_blank">
                                <img src="uploads/<?= $row['slip_image'] ?>" width="50" height="70" style="object-fit:cover; border:1px solid #ccc; border-radius:4px;">
                            </a>
                        <?php else: ?>
                            <span style="color:#aaa;">ยังไม่แนบสลิป</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'pending'): ?>
                            <span class="badge bg-pending">⏳ รอตรวจสอบ</span>
                        <?php elseif ($row['status'] === 'approved'): ?>
                            <span class="badge bg-approved">✅ อนุมัติแล้ว</span>
                        <?php else: ?>
                            <span class="badge bg-cancelled">❌ ยกเลิก/ปฏิเสธ</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'pending'): ?>
                            <a href="owner_bookings.php?action=approve&id=<?= $row['booking_id'] ?>" class="btn btn-approve" onclick="return confirm('ยืนยันอนุมัติการจองนี้?')">อนุมัติ</a>
                            <a href="owner_bookings.php?action=reject&id=<?= $row['booking_id'] ?>" class="btn btn-reject" onclick="return confirm('ปฏิเสธการจองนี้?')">ปฏิเสธ</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8" style="text-align:center;">ยังไม่มีรายการจองเข้ามา</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>