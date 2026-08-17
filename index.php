<?php
session_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบค้นหาและจองที่จอดรถ</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .navbar { background: #333; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin-left: 15px; }
        .navbar a.btn { background: #007bff; padding: 6px 12px; border-radius: 4px; }
        .navbar a.btn-success { background: #28a745; }
        .container { padding: 20px; }
        #map { height: 500px; width: 100%; border-radius: 10px; }
    </style>
</head>
<body>

    <!-- แถบเมนูด้านบน (Navbar) -->
    <div class="navbar">
        <h3 style="margin:0;">🚗 Parking App</h3>
        <div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <span>ยินดีต้อนรับ, <b><?= htmlspecialchars($_SESSION['full_name']) ?></b></span>
                <a href="my_bookings.php">ประวัติการจอง</a>
                <a href="logout.php" style="color: #ff6b6b;">ออกจากระบบ</a>
            <?php else: ?>
                <a href="login.php" class="btn">เข้าสู่ระบบ</a>
                <a href="register.php" class="btn btn-success">สมัครสมาชิก</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <h2>📍 ค้นหาและจองที่จอดรถใกล้เคียง</h2>
        <div id="map"></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var map = L.map('map').setView([7.0085, 100.4747], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var userLat = position.coords.latitude;
                var userLng = position.coords.longitude;
                map.setView([userLat, userLng], 14);
                L.marker([userLat, userLng]).addTo(map).bindPopup('<b>คุณอยู่ที่นี่</b>').openPopup();
            });
        }

        fetch('get_spots.php')
            .then(response => response.json())
            .then(data => {
                data.forEach(spot => {
                    var marker = L.marker([spot.latitude, spot.longitude]).addTo(map);
                    var popupContent = `
                        <b>${spot.title}</b><br>
                        ราคา: ${spot.price_per_hour} บาท/ชม.<br>
                        <button onclick="booking(${spot.spot_id}, ${spot.price_per_hour})">จองที่จอดนี้</button>
                    `;
                    marker.bindPopup(popupContent);
                });
            });

        function booking(spotId, pricePerHour) {
            var hours = prompt("ต้องการจองกี่ชั่วโมง?", "1");
            if (hours != null && hours > 0) {
                var formData = new FormData();
                formData.append('spot_id', spotId);
                formData.append('hours', hours);
                formData.append('price_per_hour', pricePerHour);

                fetch('save_booking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    // ถ้ายังไม่ได้ล็อกอิน ให้เด้งไปหน้า login.php อัตโนมัติ
                    if (data.status === 'error' && data.message.includes('เข้าสู่ระบบ')) {
                        window.location.href = 'login.php';
                    }
                });
            }
        }
    </script>
</body>
</html>