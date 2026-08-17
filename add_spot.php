<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $price_per_hour = floatval($_POST['price_per_hour']);
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);

    // การจัดการอัปโหลดรูปภาพ
    $image_name = NULL;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = time() . '_' . uniqid() . '.' . $ext;
        if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }
        move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $image_name);
    }

    $stmt = $conn->prepare("INSERT INTO parking_spots (user_id, title, price_per_hour, latitude, longitude, image, status) VALUES (?, ?, ?, ?, ?, ?, 'available')");
    $stmt->bind_param("isddss", $user_id, $title, $price_per_hour, $latitude, $longitude, $image_name);

    if ($stmt->execute()) {
        echo "<script>alert('เพิ่มที่จอดรถสำเร็จ!'); window.location.href='my_spots.php';</script>";
        exit;
    } else {
        $message = "เกิดข้อผิดพลาด: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มที่จอดรถของคุณ</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .form-container { background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 8px; box-sizing: border-box; }
        #map-select { height: 300px; width: 100%; border-radius: 5px; margin-top: 5px; }
        button { background: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; }
        .hint { font-size: 13px; color: #666; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>➕ เพิ่มสถานที่จอดรถ</h2>
        <p><a href="index.php">← กลับหน้าหลัก</a></p>
        
        <?php if ($message): ?><p style="color:red;"><?= $message ?></p><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>ชื่อสถานที่จอดรถ</label>
                <input type="text" name="title" required placeholder="เช่น ที่จอดรถหน้าบ้าน A">
            </div>
            <div class="form-group">
                <label>ราคาต่อชั่วโมง (บาท)</label>
                <input type="number" step="0.01" name="price_per_hour" required placeholder="30.00">
            </div>
            <div class="form-group">
                <label>รูปถ่ายสถานที่ (ถ้ามี)</label>
                <input type="file" name="image" accept="image/*">
            </div>
            <div class="form-group">
                <label>คลิกบนแผนที่เพื่อระบุพิกัด <span class="hint">(พิกัดจะกรอกให้อัตโนมัติ)</span></label>
                <div id="map-select"></div>
            </div>
            <div class="form-group">
                <label>Latitude</label>
                <input type="text" id="latitude" name="latitude" required readonly>
            </div>
            <div class="form-group">
                <label>Longitude</label>
                <input type="text" id="longitude" name="longitude" required readonly>
            </div>
            <button type="submit">บันทึกที่จอดรถ</button>
        </form>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var map = L.map('map-select').setView([7.0085, 100.4747], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        var marker;
        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;

            document.getElementById('latitude').value = lat.toFixed(6);
            document.getElementById('longitude').value = lng.toFixed(6);

            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng).addTo(map);
            }
        });
    </script>
</body>
</html>