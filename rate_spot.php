<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$booking_id = intval($_GET['booking_id'] ?? 0);
$user_id = $_SESSION['user_id'];

// ตรวจสอบความถูกต้องของการจอง
$stmt = $conn->prepare("SELECT b.*, p.title FROM bookings b JOIN parking_spots p ON b.spot_id = p.spot_id WHERE b.booking_id = ? AND b.user_id = ? AND b.status = 'approved'");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    die("ไม่พบรายการจองนี้ หรือยังไม่ได้รับการอนุมัติ");
}

// ตรวจสอบว่าเคยรีวิวไปแล้วหรือยัง
$check = $conn->prepare("SELECT review_id FROM reviews WHERE booking_id = ?");
$check->bind_param("i", $booking_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo "<script>alert('คุณเคยให้คะแนนรายการนี้ไปแล้ว'); window.location.href='my_bookings.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    $insert = $conn->prepare("INSERT INTO reviews (booking_id, user_id, spot_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    $insert->bind_param("iiiis", $booking_id, $user_id, $booking['spot_id'], $rating, $comment);
    
    if ($insert->execute()) {
        echo "<script>alert('ขอบคุณสำหรับรีวิว!'); window.location.href='my_bookings.php';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รีวิวที่จอดรถ - Parking App</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .card { background: white; padding: 25px; border-radius: 8px; max-width: 400px; margin: 0 auto; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .star-rating { font-size: 28px; direction: rtl; display: inline-flex; justify-content: center; width: 100%; margin: 15px 0; }
        .star-rating input { display: none; }
        .star-rating label { color: #ccc; cursor: pointer; padding: 0 5px; }
        .star-rating input:checked ~ label, .star-rating label:hover, .star-rating label:hover ~ label { color: #ffc107; }
        textarea { width: 100%; height: 80px; padding: 8px; box-sizing: border-box; margin-bottom: 15px; border-radius: 4px; border: 1px solid #ccc; }
        button { background: #007bff; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; }
    </style>
</head>
<body>
    <div class="card">
        <h3>⭐ ให้คะแนนที่จอดรถ</h3>
        <p>สถานที่: <b><?= htmlspecialchars($booking['title']) ?></b></p>
        
        <form method="POST">
            <label><b>เลือกคะแนน (1 - 5 ดาว):</b></label>
            <div class="star-rating">
                <input type="radio" id="star5" name="rating" value="5" required><label for="star5">★</label>
                <input type="radio" id="star4" name="rating" value="4"><label for="star4">★</label>
                <input type="radio" id="star3" name="rating" value="3"><label for="star3">★</label>
                <input type="radio" id="star2" name="rating" value="2"><label for="star2">★</label>
                <input type="radio" id="star1" name="rating" value="1"><label for="star1">★</label>
            </div>
            
            <label><b>ความคิดเห็นเพิ่มเติม:</b></label>
            <textarea name="comment" placeholder="เขียนความประทับใจหรือข้อเสนอแนะ..."></textarea>
            
            <button type="submit">ส่งรีวิว</button>
        </form>
    </div>
</body>
</html>