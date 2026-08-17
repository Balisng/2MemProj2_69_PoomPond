<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$spot_id = intval($_GET['spot_id'] ?? 0);
$booking_id = intval($_GET['booking_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');

    // เช็กว่าเคยรีวิวรายการนี้ไปแล้วหรือยัง
    $check = $conn->prepare("SELECT review_id FROM reviews WHERE booking_id = ?");
    $check->bind_param("i", $booking_id);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        echo "<script>alert('คุณได้รีวิวรายการนี้ไปแล้ว'); window.location.href='my_bookings.php';</script>";
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO reviews (spot_id, user_id, booking_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiis", $spot_id, $user_id, $booking_id, $rating, $comment);
    
    if ($stmt->execute()) {
        echo "<script>alert('ขอบคุณสำหรับรีวิว!'); window.location.href='my_bookings.php';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ให้รีวิวสถานที่</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background: #f4f6f9; }
        .star-rating { display: flex; flex-direction: row-reverse; justify-content: center; font-size: 2.2rem; }
        .star-rating input { display: none; }
        .star-rating label { color: #cbd5e1; cursor: pointer; padding: 0 5px; }
        .star-rating input:checked ~ label, .star-rating label:hover, .star-rating label:hover ~ label { color: #f59e0b; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="card border-0 shadow-sm rounded-4 mx-auto p-4" style="max-width: 420px; background: white;">
            <h4 class="fw-bold text-center mb-3">ให้คะแนนที่จอดรถ</h4>
            <form method="POST">
                <div class="star-rating mb-3">
                    <input type="radio" id="star5" name="rating" value="5" checked><label for="star5" class="fa-solid fa-star"></label>
                    <input type="radio" id="star4" name="rating" value="4"><label for="star4" class="fa-solid fa-star"></label>
                    <input type="radio" id="star3" name="rating" value="3"><label for="star3" class="fa-solid fa-star"></label>
                    <input type="radio" id="star2" name="rating" value="2"><label for="star2" class="fa-solid fa-star"></label>
                    <input type="radio" id="star1" name="rating" value="1"><label for="star1" class="fa-solid fa-star"></label>
                </div>
                <div class="mb-3">
                    <textarea name="comment" class="form-control" rows="3" placeholder="ความพึงพอใจเกี่ยวกับการจอดรถ..."></textarea>
                </div>
                <button type="submit" class="btn btn-warning w-100 fw-bold rounded-pill">ส่งรีวิว</button>
            </form>
        </div>
    </div>
</body>
</html>