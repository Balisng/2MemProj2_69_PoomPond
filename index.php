<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบค้นหาและจองที่จอดรถ</title>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        #map { height: 500px; width: 100%; border-radius: 10px; }
    </style>
</head>
<body>
    <h2>📍 ค้นหาและจองที่จอดรถใกล้เคียง</h2>
    <div id="map"></div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // สร้างแผนที่ (จุดเริ่มต้นเริ่มต้นที่พิกัดกลาง)
        var map = L.map('map').setView([7.0085, 100.4747], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // ดึงตำแหน่งปัจจุบันของผู้ใช้
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var userLat = position.coords.latitude;
                var userLng = position.coords.longitude;
                
                // เลื่อนแผนที่ไปตำแหน่งผู้ใช้ และปักหมุดสีฟ้า/ข้อความ
                map.setView([userLat, userLng], 14);
                L.marker([userLat, userLng]).addTo(map)
                    .bindPopup('<b>คุณอยู่ที่นี่</b>')
                    .openPopup();
            });
        }

        // ดึงข้อมูลจุดจอดรถจาก get_spots.php มาปักหมุด
        fetch('get_spots.php')
            .then(response => response.json())
            .then(data => {
                data.forEach(spot => {
                    var marker = L.marker([spot.latitude, spot.longitude]).addTo(map);
                    
                    var popupContent = `
                        <b>${spot.title}</b><br>
                        ราคา: ${spot.price_per_hour} บาท/ชม.<br>
                        <button onclick="booking(${spot.spot_id})">จองที่จอดนี้</button>
                    `;
                    marker.bindPopup(popupContent);
                });
            });

        function booking(spotId) {
            alert('คุณกดเลือกจองที่จอดรถ ID: ' + spotId);
        }
    </script>
</body>
</html>