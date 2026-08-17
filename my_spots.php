<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ที่จอดรถของฉัน</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; }
        .card-custom { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); background: white; }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h3 class="fw-bold m-0"><i class="fa-solid fa-map-pin text-info me-2"></i>จัดการที่จอดรถของฉัน</h3>
            <div class="d-flex gap-2">
                <a href="owner_bookings.php" class="btn btn-warning fw-bold text-dark rounded-pill"><i class="fa-solid fa-list-check me-1"></i> รายการคำขอจอง</a>
                <a href="add_spot.php" class="btn btn-success rounded-pill"><i class="fa-solid fa-plus me-1"></i> เพิ่มที่จอดรถ</a>
                <a href="index.php" class="btn btn-outline-secondary rounded-pill"><i class="fa-solid fa-house"></i></a>
            </div>
        </div>

        <?php if ($result->num_rows === 0): ?>
            <div class="card card-custom p-5 text-center text-muted">
                <i class="fa-solid fa-square-parking display-4 mb-3"></i>
                <p class="fs-6">คุณยังไม่ได้ลงทะเบียนที่จอดรถไว้ในระบบ</p>
                <div><a href="add_spot.php" class="btn btn-success rounded-pill px-4">เพิ่มที่จอดรถแรกของคุณ</a></div>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php while ($spot = $result->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-custom h-100 p-3">
                            <?php if ($spot['image']): ?>
                                <img src="uploads/<?= $spot['image'] ?>" class="rounded-3 mb-3" style="height:150px; object-fit:cover; width:100%;">
                            <?php endif; ?>
                            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($spot['title']) ?></h5>
                            <p class="text-secondary fs-7 mb-2"><i class="fa-solid fa-phone text-primary me-1"></i> <?= htmlspecialchars($spot['contact_phone'] ?? '-') ?></p>
                            <p class="text-muted fs-7 mb-3 bg-light p-2 rounded-3"><?= htmlspecialchars($spot['description'] ?: 'ไม่มีรายละเอียดเพิ่มเติม') ?></p>
                            
                            <div class="mt-auto d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="fw-bold text-success"><i class="fa-solid fa-tag me-1"></i><?= $spot['price_per_hour'] ?> บาท/ชม.</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">ว่างพร้อมให้บริการ</span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>