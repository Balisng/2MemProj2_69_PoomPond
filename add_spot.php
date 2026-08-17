<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $contact_phone = trim($_POST['contact_phone']);
    $latitude = trim($_POST['latitude']);
    $longitude = trim($_POST['longitude']);
    $price_per_hour = floatval($_POST['price_per_hour']);
    $user_id = $_SESSION['user_id'];
    $image_name = '';

    if (empty($title) || empty($contact_phone) || empty($latitude) || empty($longitude)) {
        $message = "กรุณากรอกข้อมูลที่มีเครื่องหมาย * ให้ครบถ้วน";
    } else {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = time() . '_' . uniqid() . '.' . $ext;
            if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }
            move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $image_name);
        }

        $stmt = $conn->prepare("INSERT INTO parking_spots (user_id, title, description, contact_phone, latitude, longitude, price_per_hour, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'available')");
        $stmt->bind_param("issssdds", $user_id, $title, $description, $contact_phone, $latitude, $longitude, $price_per_hour, $image_name);

        if ($stmt->execute()) {
            echo "<script>alert('เพิ่มที่จอดรถสำเร็จ!'); window.location.href='my_spots.php';</script>";
            exit;
        } else {
            $message = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มที่จอดรถใหม่</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .form-box { background: white; padding: 25px; border-radius: 8px; max-width: 550px; margin: 0 auto; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .req { color: red; }
        #pickerMap { height: 300px; width: 100%; border-radius: 6px; border: 1px solid #ccc; margin-top: 5px; }
        .btn { background: #28a745; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="form-box">
        <h2>🅿️ เพิ่มที่จอดรถใหม่</h2>
        <?php if ($message): ?><p style="color:red;"><?= $message ?></p><?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>ชื่อสถานที่จอดรถ <span class="req">*</span></label>
                <input type="text" name="title" required placeholder="เช่น ที่จอดรถหน้าบ้าน ล็อก A">
            </div>

            <div class="form-group">
                <label>เบอร์โทรศัพท์ติดต่อ <span class="req">*</span></label>
                <input type="tel" name="contact_phone" required placeholder="เช่น 0812345678">
            </div>

            <div class="form-group">
                <label>จุดสังเกต / รายละเอียดเพิ่มเติม</label>
                <textarea name="description" rows="3" placeholder="เช่น อยู่ใกล้ต้นไม้ใหญ่, ติดร้านน้ำชา, ซอย 5"></textarea>
            </div>

            <div class="form-group">
                <label>📍 เลือกตำแหน่งบนแผนที่ (คลิกที่แผนที่เพื่อวางหมุด) <span class="req">*</span></label>
                <div id="pickerMap"></div>
            </div>

            <div style="display: flex; gap: 10px;">
                <div class="form-group" style="flex: 1;">
                    <label>ละติจูด (Latitude) <span class="req">*</span></label>
                    <input type="text" id="latitude" name="latitude" required readonly style="background: #e9ecef;">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>ลองจิจูด (Longitude) <span class="req">*</span></label>
                    <input type="text" id="longitude" name="longitude" required readonly style="background: #e9ecef;">
                </div>
            </div>

            <div class="form-group">
                <label>ราคาต่อชั่วโมง (บาท) <span class="req">*</span></label>
                <input type="number" step="0.01" name="price_per_hour" required placeholder="50">
            </div>

            <div class="form-group">
                <label>รูปภาพสถานที่</label>
                <input type="file" name="image" accept="image/*">
            </div>

            <button type="submit" class="btn">บันทึกข้อมูล</button>
        </form>
        <br>
        <a href="my_spots.php">← กลับหน้าจัดการที่จอดรถ</a>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var defaultLat = 7.0085;
        var defaultLng = 100.4747;

        var map = L.map('pickerMap').setView([defaultLat, defaultLng], 14);
        var marker;

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // ดึงตำแหน่งปัจจุบันของผู้ใช้เพื่อเลื่อนแผนที่ไปหาอัตโนมัติ
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                map.setView([lat, lng], 15);
            });
        }

        // เมื่อคลิกบนแผนที่ จะใส่หมุดและกรอกพิกัดลงใน Input อัตโนมัติ
        map.on('click', function(e) {
            var lat = e.latlng.lat.toFixed(6);
            var lng = e.latlng.lng.toFixed(6);

            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;

            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng).addTo(map);
            }
        });
    </script>
</body>
</html>