<?php
session_start();
require_once 'db.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$spot_id = isset($_GET['spot_id']) ? intval($_GET['spot_id']) : 0;

// ดึงรายการคำขอจองของสถานที่ที่เป็นของเจ้าของรายนี้
if ($spot_id > 0) {
    $sql = "SELECT b.*, p.title AS spot_title, u.full_name AS renter_name, u.phone AS renter_phone 
            FROM bookings b 
            JOIN parking_spots p ON b.spot_id = p.spot_id 
            JOIN users u ON b.user_id = u.user_id 
            WHERE p.user_id = ? AND b.spot_id = ?
            ORDER BY b.booking_id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $spot_id);
} else {
    $sql = "SELECT b.*, p.title AS spot_title, u.full_name AS renter_name, u.phone AS renter_phone 
            FROM bookings b 
            JOIN parking_spots p ON b.spot_id = p.spot_id 
            JOIN users u ON b.user_id = u.user_id 
            WHERE p.user_id = ? 
            ORDER BY b.booking_id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการคำขอจองที่จอดรถ - Parking App</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        * { font-family: 'Prompt', sans-serif; }
        body { background-color: #f8fafc; color: #334155; }
        .card-custom { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .table > :not(caption) > * > * { padding: 1rem 0.75rem; vertical-align: middle; }
    </style>
</head>
<body>

    <div class="container py-4 my-3">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h4 class="fw-bold m-0 text-dark">
                <i class="fa-solid fa-list-check text-primary me-2"></i>รายการคำขอจองที่จอดรถ
            </h4>
            <div class="d-flex gap-2">
                <a href="my_spots.php" class="btn btn-outline-secondary rounded-pill px-3 fw-bold">
                    <i class="fa-solid fa-arrow-left me-1"></i> ย้อนกลับ
                </a>
                <a href="index.php" class="btn btn-primary rounded-pill px-3 fw-bold">
                    <i class="fa-solid fa-house me-1"></i> หน้าหลัก
                </a>
            </div>
        </div>

        <!-- Table Card -->
        <div class="card card-custom p-3 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-secondary fs-7">
                            <th scope="col">สถานที่</th>
                            <th scope="col">ผู้จอง / เบอร์โทร</th>
                            <th scope="col">เวลาเริ่มจอด</th>
                            <th scope="col">ระยะเวลา</th>
                            <th scope="col">ยอดเงิน</th>
                            <th scope="col">หลักฐานโอนเงิน</th>
                            <th scope="col">สถานะ</th>
                            <th scope="col" class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($row['spot_title']) ?></td>

                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($row['renter_name']) ?></div>
                                        <small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($row['renter_phone'] ?? '-') ?></small>
                                    </td>

                                    <td><?= date('d/m/Y H:i', strtotime($row['start_time'])) ?></td>

                                    <td><?= $row['hours'] ?> ชม.</td>

                                    <td class="fw-bold text-success"><?= number_format($row['total_price'], 2) ?> ฿</td>

                                    <td>
                                        <?php if (!empty($row['slip_image'])): ?>
                                            <button class="btn btn-info btn-sm text-white rounded-pill px-3 fw-bold" 
                                                    onclick="showSlipModal('<?= htmlspecialchars($row['slip_image'], ENT_QUOTES) ?>')">
                                                <i class="fa-solid fa-image me-1"></i> ดูสลิป
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted fs-7">ยังไม่อัปโหลด</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php
                                        $status = $row['status'] ?? 'pending';
                                        if ($status === 'approved' || $status === 'confirmed') {
                                            echo '<span class="badge bg-success rounded-pill px-3 py-2">อนุมัติแล้ว</span>';
                                        } elseif ($status === 'rejected') {
                                            echo '<span class="badge bg-danger rounded-pill px-3 py-2">ปฏิเสธ</span>';
                                        } elseif ($status === 'cancelled') {
                                            echo '<span class="badge bg-secondary rounded-pill px-3 py-2">ยกเลิกแล้ว</span>';
                                        } else {
                                            echo '<span class="badge bg-warning text-dark rounded-pill px-3 py-2">รออนุมัติ</span>';
                                        }
                                        ?>
                                    </td>

                                    <td class="text-center">
                                        <?php if ($status === 'pending'): ?>
                                            <div class="d-flex justify-content-center gap-1">
                                                <form action="update_booking_status.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-2 fw-bold" onclick="return confirm('ยืนยันอนุมัติการจองนี้?')">
                                                        <i class="fa-solid fa-check"></i> อนุมัติ
                                                    </button>
                                                </form>
                                                <form action="update_booking_status.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-danger btn-sm rounded-pill px-2 fw-bold" onclick="return confirm('ยืนยันปฏิเสธการจองนี้?')">
                                                        <i class="fa-solid fa-xmark"></i> ปฏิเสธ
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fa-solid fa-inbox display-4 mb-2 d-block text-secondary"></i>
                                    ยังไม่มีรายการคำขอจองเข้ามาในขณะนี้
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับดูภาพสลิป -->
    <div class="modal fade" id="slipModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-file-invoice-dollar text-warning me-2"></i>หลักฐานการชำระเงิน</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-3">
                    <div id="slipErrorAlert" class="alert alert-warning d-none mb-2 fs-7"></div>
                    <img id="slipImage" src="" class="img-fluid rounded-3 shadow-sm" style="max-height: 480px; object-fit: contain;" alt="สลิปการโอนเงิน">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showSlipModal(rawPath) {
            if (!rawPath) return;

            var img = document.getElementById('slipImage');
            var alertBox = document.getElementById('slipErrorAlert');
            alertBox.classList.add('d-none');
            
            var path = rawPath.trim();
            // ดึงเฉพาะชื่อไฟล์ท้ายสุด เช่น slip_12345.png
            var filename = path.split('/').pop();

            // สร้างรายการพาธที่เป็นไปได้ทั้งหมด
            var candidates = [
                path,
                'uploads/slips/' + filename,
                'uploads/' + filename,
                'slips/' + filename
            ];

            // กรองตัวเลือกที่ซ้ำกันออก
            var uniqueCandidates = [];
            candidates.forEach(function(c) {
                if (c && uniqueCandidates.indexOf(c) === -1) {
                    uniqueCandidates.push(c);
                }
            });

            var currentIndex = 0;

            // เมื่อระบบโหลดภาพไม่พบ จะสลับไปลองพาธถัดไปอัตโนมัติ
            img.onerror = function() {
                currentIndex++;
                if (currentIndex < uniqueCandidates.length) {
                    img.src = uniqueCandidates[currentIndex];
                } else {
                    img.onerror = null;
                    img.style.display = 'none';
                    alertBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i> ไม่พบไฟล์สลิปในโฟลเดอร์เซิร์ฟเวอร์<br><small class="text-muted">(ชื่อไฟล์: ' + filename + ')</small>';
                    alertBox.classList.remove('d-none');
                }
            };

            img.onload = function() {
                img.style.display = 'inline-block';
            };

            // เริ่มลองพาธแรก
            img.src = uniqueCandidates[0];

            var slipModal = new bootstrap.Modal(document.getElementById('slipModal'));
            slipModal.show();
        }
    </script>
</body>
</html>