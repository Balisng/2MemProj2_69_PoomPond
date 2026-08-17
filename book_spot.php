<!-- <?php
session_start();
require_once 'db.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$spot_id = intval($_GET['spot_id'] ?? 0);
$user_id = $_SESSION['user_id'];

// ดึงข้อมูลสถานที่จอดรถ
$stmt = $conn->prepare("SELECT * FROM parking_spots WHERE spot_id = ?");
$stmt->bind_param("i", $spot_id);
$stmt->execute();
$spot = $stmt->get_result()->fetch_assoc();

if (!$spot) {
    echo "<script>alert('ไม่พบข้อมูลสถานที่จอดรถ'); window.location.href='index.php';</script>";
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_time = $_POST['start_time'] ?? '';
    $hours = intval($_POST['hours'] ?? 1);
    
    if (empty($start_time) || $hours < 1) {
        $error = 'กรุณาระบุวัน เวลา และจำนวนชั่วโมงให้ถูกต้อง';
    } else {
        $total_price = $hours * $spot['price_per_hour'];
        $status = 'pending'; // รอเจ้าของอนุมัติ/ชำระเงิน

        // บันทึกลงฐานข้อมูล (รองรับทั้งคอลัมน์ hours หรือ duration)
        $sql = "INSERT INTO bookings (user_id, spot_id, start_time, hours, total_price, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql);
        
        // ถ้าคอลัมน์ใน DB ชื่อ hours
        if ($stmt_insert) {
            $stmt_insert->bind_param("iisids", $user_id, $spot_id, $start_time, $hours, $total_price, $status);
            if ($stmt_insert->execute()) {
                $booking_id = $stmt_insert->insert_id;
                header("Location: upload_slip.php?booking_id=" . $booking_id);
                exit;
            } else {
                // หากโครงสร้าง DB ไม่มีคอลัมน์ hours ให้ลองแบบไม่รวม hours
                $sql_alt = "INSERT INTO bookings (user_id, spot_id, start_time, total_price, status) VALUES (?, ?, ?, ?, ?)";
                $stmt_alt = $conn->prepare($sql_alt);
                $stmt_alt->bind_param("iisds", $user_id, $spot_id, $start_time, $total_price, $status);
                if ($stmt_alt->execute()) {
                    header("Location: upload_slip.php?booking_id=" . $stmt_alt->insert_id);
                    exit;
                } else {
                    $error = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $conn->error;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จองที่จอดรถ - <?= htmlspecialchars($spot['title']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f8fafc; }
        .card-custom { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); background: #ffffff; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card card-custom p-4">
                    <div class="d-flex align-items-center mb-3">
                        <a href="index.php" class="btn btn-light btn-sm rounded-circle me-3"><i class="fa-solid fa-arrow-left"></i></a>
                        <h4 class="fw-bold m-0 text-dark">จองที่จอดรถ</h4>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 fs-7 mb-3"><?= $error ?></div>
                    <?php endif; ?>

                    <div class="bg-light p-3 rounded-3 mb-4 border">
                        <h5 class="fw-bold text-primary mb-1"><?= htmlspecialchars($spot['title']) ?></h5>
                        <p class="text-muted fs-7 mb-2"><i class="fa-solid fa-location-dot text-danger me-1"></i><?= htmlspecialchars($spot['description'] ?? 'ไม่มีรายละเอียดเพิ่มเติม') ?></p>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center fs-6">
                            <span>อัตราค่าบริการ:</span>
                            <span class="fw-bold text-success fs-5"><?= number_format($spot['price_per_hour'], 0) ?> บาท / ชม.</span>
                        </div>
                    </div>

                    <form method="POST" id="bookingForm">
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fa-regular fa-calendar-days text-primary me-1"></i> วันและเวลาที่เริ่มจอด</label>
                            <input type="datetime-local" name="start_time" id="start_time" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="fa-regular fa-clock text-primary me-1"></i> จำนวนระยะเวลา (ชั่วโมง)</label>
                            <select name="hours" id="hours" class="form-select" onchange="calculateTotal()">
                                <?php for ($i = 1; $i <= 24; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?> ชั่วโมง</option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="p-3 mb-4 rounded-3 text-white" style="background: linear-gradient(135deg, #1e293b, #0f172a);">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>ราคารวมทั้งสิ้น:</span>
                                <span class="fw-bold fs-3 text-warning"><span id="total_price"><?= number_format($spot['price_per_hour'], 2) ?></span> ฿</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100 fw-bold rounded-pill text-white shadow">
                            <i class="fa-solid fa-circle-check me-1"></i> ยืนยันการจอง
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        var pricePerHour = <?= floatval($spot['price_per_hour']) ?>;
        function calculateTotal() {
            var hours = parseInt(document.getElementById('hours').value) || 1;
            var total = hours * pricePerHour;
            document.getElementById('total_price').innerText = total.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    </script>
</body>
</html> -->