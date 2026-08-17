<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$booking_id = intval($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['slip'])) {
    $file = $_FILES['slip'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed)) {
            // บังคับให้เซฟไฟล์ลงในโฟลเดอร์ uploads/slips/
            $upload_dir = 'uploads/slips/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = 'slip_' . $booking_id . '_' . time() . '.' . $ext;
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // บันทึกชื่อไฟล์ลง DB
                $stmt = $conn->prepare("UPDATE bookings SET slip_image = ? WHERE booking_id = ?");
                $stmt->bind_param("si", $new_filename, $booking_id);
                $stmt->execute();
                
                header("Location: my_bookings.php");
                exit;
            } else {
                $error = 'ไม่สามารถย้ายไฟล์เข้าโฟลเดอร์ uploads/slips ได้';
            }
        } else {
            $error = 'อนุญาตเฉพาะไฟล์รูปภาพ (JPG, PNG, WEBP) เท่านั้น';
        }
    } else {
        $error = 'กรุณาเลือกไฟล์สลิปก่อนกดบันทึก';
    }
}

// ดึงข้อมูลการจอง
$stmt = $conn->prepare("SELECT b.*, p.title, p.price_per_hour FROM bookings b JOIN parking_spots p ON b.spot_id = p.spot_id WHERE b.booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แนบสลิปการชำระเงิน</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; background-color: #f8fafc; } </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h4 class="fw-bold text-center mb-3">แนบสลิปการชำระเงิน</h4>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 fs-7"><?= $error ?></div>
                    <?php endif; ?>

                    <?php if ($booking): ?>
                        <div class="bg-light p-3 rounded-3 mb-3 border fs-7">
                            <div class="mb-1"><b>สถานที่:</b> <?= htmlspecialchars($booking['title']) ?></div>
                            <div><b>ยอดชำระ:</b> <span class="text-success fw-bold"><?= number_format($booking['total_price'], 2) ?> บาท</span></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold">เลือกไฟล์สลิป</label>
                            <input type="file" name="slip" class="form-control" accept="image/*" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold rounded-pill shadow-sm">
                            <i class="fa-solid fa-cloud-arrow-up me-1"></i> อัปโหลดสลิป
                        </button>
                        <a href="my_bookings.php" class="btn btn-outline-secondary w-100 rounded-pill mt-2">ย้อนกลับ</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>