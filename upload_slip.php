<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = '';
$booking_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// ดึงรายละเอียดการจอง
$stmt = $conn->prepare("SELECT b.*, p.title FROM bookings b JOIN parking_spots p ON b.spot_id = p.spot_id WHERE b.booking_id = ? AND b.user_id = ?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    die("ไม่พบรายการจองนี้");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['slip_image']) && $_FILES['slip_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION);
        $slip_name = 'slip_' . time() . '_' . uniqid() . '.' . $ext;
        
        if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }
        
        if (move_uploaded_file($_FILES['slip_image']['tmp_name'], 'uploads/' . $slip_name)) {
            $update = $conn->prepare("UPDATE bookings SET slip_image = ?, payment_status = 'pending_approval' WHERE booking_id = ? AND user_id = ?");
            $update->bind_param("sii", $slip_name, $booking_id, $user_id);
            $update->execute();

            echo "<script>alert('แนบสลิปเรียบร้อย รอเจ้าของอนุมัติการจอง'); window.location.href='my_bookings.php';</script>";
            exit;
        }
    } else {
        $message = "กรุณาเลือกไฟล์สลิปโอนเงิน";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ชำระเงิน - Parking App</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .card { background: white; padding: 25px; border-radius: 8px; max-width: 420px; margin: 0 auto; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
        .qr-box { background: #f1f3f5; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .btn { background: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>💳 ชำระเงินค่าจองที่จอดรถ</h2>
        <p>สถานที่: <b><?= htmlspecialchars($booking['title']) ?></b></p>
        <h3 style="color:#007bff; margin:10px 0;">ยอดชำระ: <?= number_format($booking['total_price'], 2) ?> บาท</h3>
        
        <div class="qr-box">
            <p style="margin-top:0;"><b>สแกน QR Code ด้านล่างเพื่อโอนเงิน</b></p>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=PromptPay_ParkingApp_<?= $booking['total_price'] ?>" alt="QR Code">
            <p style="font-size:12px; color:#666; margin-bottom:0;">(ระบบจำลอง PromptPay)</p>
        </div>

        <?php if ($message): ?><p style="color:red;"><?= $message ?></p><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div style="text-align:left; margin-bottom:10px;">
                <label><b>แนบรูปภาพสลิปโอนเงิน:</b></label>
                <input type="file" name="slip_image" accept="image/*" required style="width:100%; margin-top:5px;">
            </div>
            <button type="submit" class="btn">ยืนยันส่งสลิป</button>
        </form>
        <br>
        <a href="my_bookings.php" style="color:#666; text-decoration:none; font-size:14px;">← กลับหน้าประวัติการจอง</a>
    </div>
</body>
</html>