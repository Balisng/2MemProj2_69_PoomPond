<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงรายการประวัติการจอง
$sql = "SELECT b.*, p.title AS spot_title, p.price_per_hour 
        FROM bookings b 
        JOIN parking_spots p ON b.spot_id = p.spot_id 
        WHERE b.user_id = ? 
        ORDER BY b.booking_id DESC";

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
    <title>ประวัติการจองของฉัน - Parking App</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        * { font-family: 'Prompt', sans-serif; }
        body { background-color: #f8fafc; color: #334155; }
        .card-custom { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .star-rating { display: inline-flex; flex-direction: row-reverse; justify-content: center; }
        .star-rating input { display: none; }
        .star-rating label { font-size: 2rem; color: #cbd5e1; cursor: pointer; padding: 0 4px; }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label { color: #f59e0b; }
    </style>
</head>
<body>

    <div class="container py-4 my-3">
        <!-- Notification Alerts -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'cancel_success'): ?>
            <div class="alert alert-warning alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> ยกเลิกรายการจองเรียบร้อยแล้ว
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'review_success'): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> ขอบคุณสำหรับการรีวิวและให้คะแนน!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold m-0 text-dark">
                <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>ประวัติการจองของฉัน
            </h4>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
                <i class="fa-solid fa-house me-1"></i> หน้าหลัก
            </a>
        </div>

        <div class="row g-4">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                    $status = strtolower(trim($row['status'] ?? 'pending'));
                    $is_approved = in_array($status, ['approved', 'confirmed', '1', 'success']);
                    $is_rejected = in_array($status, ['rejected']);
                    $is_cancelled = in_array($status, ['cancelled', 'cancel']);
                    $is_pending = !$is_approved && !$is_rejected && !$is_cancelled;

                    // เช็กการรีวิวจาก booking_id (การจองรายการนี้)
                    $booking_id = $row['booking_id'];
                    $has_reviewed = false;
                    
                    $rev_check = $conn->prepare("SELECT review_id FROM reviews WHERE booking_id = ? LIMIT 1");
                    if ($rev_check) {
                        $rev_check->bind_param("i", $booking_id);
                        $rev_check->execute();
                        $has_reviewed = $rev_check->get_result()->num_rows > 0;
                    }
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-custom h-100 bg-white p-3 border">
                            <div class="card-body p-2 d-flex flex-column">
                                
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="fw-bold text-dark m-0 me-2"><?= htmlspecialchars($row['spot_title']) ?></h5>
                                    <div>
                                        <?php if ($is_approved): ?>
                                            <span class="badge bg-success rounded-pill px-3 py-2"><i class="fa-solid fa-circle-check me-1"></i>อนุมัติแล้ว</span>
                                        <?php elseif ($is_rejected): ?>
                                            <span class="badge bg-danger rounded-pill px-3 py-2"><i class="fa-solid fa-circle-xmark me-1"></i>ปฏิเสธ</span>
                                        <?php elseif ($is_cancelled): ?>
                                            <span class="badge bg-secondary rounded-pill px-3 py-2"><i class="fa-solid fa-ban me-1"></i>ยกเลิกแล้ว</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="fa-solid fa-clock me-1"></i>รออนุมัติ</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <hr class="text-muted opacity-25 my-2">

                                <div class="text-secondary fs-7 mb-3">
                                    <p class="mb-1"><i class="fa-regular fa-calendar me-2 text-primary"></i><strong>เริ่มจอด:</strong> <?= date('d/m/Y H:i', strtotime($row['start_time'])) ?></p>
                                    <p class="mb-1"><i class="fa-regular fa-clock me-2 text-primary"></i><strong>ระยะเวลา:</strong> <?= $row['hours'] ?> ชั่วโมง</p>
                                    <p class="mb-0 fw-bold text-success fs-6 mt-2"><i class="fa-solid fa-baht-sign me-1"></i>ราคารวม: <?= number_format($row['total_price'], 2) ?> บาท</p>
                                </div>

                                <div class="mt-auto pt-2">
                                    <?php if ($is_approved): ?>
                                        <?php if ($has_reviewed): ?>
                                            <button class="btn btn-light text-success border border-success rounded-pill w-100 fw-bold fs-7" disabled>
                                                <i class="fa-solid fa-circle-check me-1"></i> รีวิวแล้ว
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-warning text-dark rounded-pill w-100 fw-bold fs-7 shadow-sm" 
                                                    onclick="openReviewModal(<?= $row['booking_id'] ?>, <?= $row['spot_id'] ?>, '<?= htmlspecialchars($row['spot_title'], ENT_QUOTES) ?>')">
                                                <i class="fa-solid fa-star me-1"></i> เขียนรีวิว
                                            </button>
                                        <?php endif; ?>
                                    <?php elseif ($is_pending): ?>
                                        <!-- ปุ่มยกเลิกการจอง -->
                                        <form action="cancel_booking.php" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการยกเลิกการจองนี้?');">
                                            <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger rounded-pill w-100 fw-bold fs-7">
                                                <i class="fa-solid fa-xmark me-1"></i> ยกเลิกการจอง
                                            </button>
                                        </form>
                                    <?php elseif ($is_cancelled): ?>
                                        <div class="text-center text-muted fs-7 py-1 bg-light rounded-pill">
                                            <small><i class="fa-solid fa-ban me-1"></i>รายการนี้ถูกยกเลิกแล้ว</small>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center text-muted fs-7 py-1 bg-light rounded-pill">
                                            <small><i class="fa-solid fa-circle-info me-1"></i>รายการถูกปฏิเสธ</small>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="fa-solid fa-calendar-xmark display-3 text-secondary mb-3 d-block"></i>
                    <h5 class="text-muted fw-normal">คุณยังไม่มีประวัติการจองที่จอดรถ</h5>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal เขียนรีวิว -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-star text-warning me-2"></i>ให้คะแนนและรีวิว</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="add_review.php" method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="booking_id" id="review_booking_id">
                        <input type="hidden" name="spot_id" id="review_spot_id">
                        
                        <h6 class="fw-bold mb-3 text-center text-dark" id="review_spot_title">สถานที่</h6>
                        
                        <div class="text-center mb-4">
                            <label class="form-label d-block fw-bold text-muted mb-1">ความพึงพอใจการใช้บริการ</label>
                            <div class="star-rating">
                                <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="5 ดาว"><i class="fa-solid fa-star"></i></label>
                                <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 ดาว"><i class="fa-solid fa-star"></i></label>
                                <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 ดาว"><i class="fa-solid fa-star"></i></label>
                                <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 ดาว"><i class="fa-solid fa-star"></i></label>
                                <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 ดาว"><i class="fa-solid fa-star"></i></label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="comment" class="form-label fw-bold">ความคิดเห็นเกี่ยวกับที่จอดรถ</label>
                            <textarea name="comment" id="comment" rows="3" class="form-control rounded-3" placeholder="เขียนความประทับใจ การบริการ..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light rounded-bottom-4">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold"><i class="fa-solid fa-paper-plane me-1"></i>ส่งรีวิว</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openReviewModal(bookingId, spotId, spotTitle) {
            document.getElementById('review_booking_id').value = bookingId;
            document.getElementById('review_spot_id').value = spotId;
            document.getElementById('review_spot_title').innerText = spotTitle;
            
            var reviewModal = new bootstrap.Modal(document.getElementById('reviewModal'));
            reviewModal.show();
        }
    </script>
</body>
</html>