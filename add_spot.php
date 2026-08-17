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
    <!-- Google Font & FontAwesome & Bootstrap 5 -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; }
        .card-custom { border: none; border-radius: 16px; box-shadow: 0 4px 25px rgba(0,0,0,0.06); max-width: 600px; margin: 30px auto; background: white; }
        #pickerMap { height: 280px; width: 100%; border-radius: 12px; border: 1px solid #cbd5e1; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="container py-3">
        <div class="card card-custom p-4 p-md-5">
            <h3 class="fw-bold text-dark mb-4"><i class="fa-solid fa-square-plus text-success me-2"></i>เพิ่มที่จอดรถใหม่</h3>
            
            <?php if ($message): ?>
                <div class="alert alert-danger py-2 rounded-3 mb-3"><?= $message ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label fw-bold">ชื่อสถานที่จอดรถ <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required placeholder="เช่น ที่จอดรถหน้าบ้าน ล็อก A">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">เบอร์โทรศัพท์ติดต่อ <span class="text-danger">*</span></label>
                    <input type="tel" name="contact_phone" class="form-control" required placeholder="เช่น 0812345678">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">จุดสังเกต / รายละเอียดเพิ่มเติม</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="เช่น อยู่ใกล้ต้นไม้ใหญ่, ติดร้านน้ำชา, ซอย 5"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold"><i class="fa-solid fa-map-location-dot text-primary me-1"></i> คลิกเลือกตำแหน่งบนแผนที่ <span class="text-danger">*</span></label>
                    <div id="pickerMap"></div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold fs-7">ละติจูด (Latitude) <span class="text-danger">*</span></label>
                        <input type="text" id="latitude" name="latitude" class="form-control bg-light" required readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold fs-7">ลองจิจูด (Longitude) <span class="text-danger">*</span></label>
                        <input type="text" id="longitude" name="longitude" class="form-control bg-light" required readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">ราคาต่อชั่วโมง (บาท) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="price_per_hour" class="form-control" required placeholder="50">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">รูปภาพสถานที่</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>

                <button type="submit" class="btn btn-success w-100 py-2.5 rounded-3 fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i> บันทึกข้อมูล</button>
            </form>
            
            <div class="text-center mt-4">
                <a href="my_spots.php" class="text-decoration-none text-secondary"><i class="fa-solid fa-arrow-left me-1"></i> กลับหน้าจัดการที่จอดรถ</a>
            </div>
        </div>
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

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                map.setView([lat, lng], 15);
            });
        }

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