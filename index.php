<?php
session_start();
require_once 'db.php';
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
        .search-box { margin-bottom: 15px; display: flex; gap: 10px; }
        .search-box input { padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        #map { height: 520px; width: 100%; border-radius: 10px; }

        .modal-overlay { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 20px; border-radius: 8px; width: 350px; max-height: 80vh; overflow-y: auto; box-shadow: 0 2px 10px rgba(0,0,0,0.3); }
        .modal-group { margin-bottom: 15px; }
        .modal-group label { display: block; font-size: 14px; margin-bottom: 5px; font-weight: bold; }
        .modal-group input { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .btn-submit { width: 100%; background: #28a745; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer; font-size: 15px; }
        .btn-cancel { width: 100%; background: #6c757d; color: white; border: none; padding: 8px; border-radius: 4px; cursor: pointer; margin-top: 5px; }

        .review-item { border-bottom: 1px solid #eee; padding: 8px 0; }
        .review-item:last-child { border-bottom: none; }
        .review-user { font-weight: bold; font-size: 13px; color: #333; }
        .review-stars { color: #f39c12; font-size: 12px; }
        .review-comment { font-size: 13px; color: #555; margin-top: 3px; }
    </style>
</head>
<body>

    <div class="navbar">
        <h3 style="margin:0;">🚗 Parking App</h3>
        <div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <span>ยินดีต้อนรับ, <b><?= htmlspecialchars($_SESSION['full_name']) ?></b></span>
                <a href="my_bookings.php">ประวัติการจอง</a>
                <a href="my_spots.php" style="background:#17a2b8;" class="btn">ที่จอดรถของฉัน</a>
                <a href="logout.php" style="color: #ff6b6b;">ออกจากระบบ</a>
            <?php else: ?>
                <a href="login.php" class="btn">เข้าสู่ระบบ</a>
                <a href="register.php" class="btn btn-success">สมัครสมาชิก</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <h2>📍 ค้นหาและจองที่จอดรถใกล้เคียง</h2>
        
        <div class="search-box">
            <input type="text" id="searchName" placeholder="🔍 ค้นหาตามชื่อสถานที่..." onkeyup="filterSpots()" style="flex: 2;">
            <input type="number" id="maxPrice" placeholder="💰 ราคาไม่เกิน (บาท/ชม.)..." onkeyup="filterSpots()" style="flex: 1;">
        </div>

        <div id="map"></div>
    </div>

    <!-- Modal จองวันเวลา -->
    <div id="bookingModal" class="modal-overlay">
        <div class="modal-content">
            <h3 style="margin-top:0;">🗓️ จองที่จอดรถล่วงหน้า</h3>
            <input type="hidden" id="modalSpotId">
            <input type="hidden" id="modalPrice">
            
            <div class="modal-group">
                <label>เวลาที่เริ่มเข้าจอด:</label>
                <input type="datetime-local" id="modalStartTime">
            </div>
            
            <div class="modal-group">
                <label>จำนวนชั่วโมง:</label>
                <input type="number" id="modalHours" value="1" min="1">
            </div>
            
            <button onclick="confirmBooking()" class="btn-submit">ยืนยันการจอง</button>
            <button onclick="closeModal('bookingModal')" class="btn-cancel">ยกเลิก</button>
        </div>
    </div>

    <!-- Modal ดูรีวิว -->
    <div id="reviewsModal" class="modal-overlay">
        <div class="modal-content">
            <h3 style="margin-top:0;">⭐ รีวิวจากผู้ใช้งาน</h3>
            <div id="reviewsList">กำลังโหลดรีวิว...</div>
            <button onclick="closeModal('reviewsModal')" class="btn-cancel" style="margin-top:15px;">ปิด</button>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var map = L.map('map').setView([7.0085, 100.4747], 14);
        var allSpots = [];
        var markersGroup = L.layerGroup().addTo(map);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var userLat = position.coords.latitude;
                var userLng = position.coords.longitude;
                map.setView([userLat, userLng], 14);
                L.marker([userLat, userLng]).addTo(map).bindPopup('<b>คุณอยู่ที่นี่</b>');
            });
        }

        fetch('get_spots.php')
            .then(response => response.json())
            .then(data => {
                allSpots = data;
                renderMarkers(allSpots);
            });

        function renderMarkers(spots) {
            markersGroup.clearLayers();
            spots.forEach(spot => {
                var ratingBtn = spot.avg_rating > 0 
                    ? `<span onclick="openReviewsModal(${spot.spot_id})" style="color:#f39c12; font-weight:bold; cursor:pointer; text-decoration:underline;">⭐ ${spot.avg_rating} (${spot.total_reviews} รีวิว - คลิกเพื่ออ่าน)</span>` 
                    : '<span style="color:#888;">⭐ ยังไม่มีรีวิว</span>';
                
                var imgHTML = spot.image ? `<img src="uploads/${spot.image}" style="width:100%; height:110px; object-fit:cover; border-radius:6px; margin-bottom:8px;">` : '';
                var descText = spot.description && spot.description.trim() !== '' ? spot.description : 'ไม่มีรายละเอียดเพิ่มเติม';
                var ownerName = spot.owner_name ? spot.owner_name : 'ไม่ระบุผู้ดูแล';
                var ownerPhone = spot.owner_phone ? spot.owner_phone : '-';

                var marker = L.marker([spot.latitude, spot.longitude]);
                var popupContent = `
                    <div style="width:230px; font-family: Arial, sans-serif;">
                        ${imgHTML}
                        <h3 style="margin:0 0 5px 0; font-size:16px; color:#333;">${spot.title}</h3>
                        <p style="margin:0 0 6px 0; font-size:13px;">${ratingBtn}</p>
                        
                        <div style="background:#f8f9fa; padding:8px; border-radius:5px; margin-bottom:8px; font-size:12px; color:#555; line-height:1.4;">
                            <b>📌 จุดสังเกต / รายละเอียด:</b><br>${descText}
                        </div>

                        <p style="margin:2px 0; font-size:12px; color:#444;">📞 <b>ผู้ดูแล:</b> ${ownerName} (${ownerPhone})</p>
                        <p style="margin:2px 0 8px 0; font-size:13px; color:#28a745; font-weight:bold;">💰 ราคา: ${spot.price_per_hour} บาท/ชม.</p>
                        
                        <div style="display:flex; gap:5px;">
                            <a href="https://www.google.com/maps/dir/?api=1&destination=${spot.latitude},${spot.longitude}" target="_blank" style="flex:1; text-align:center; background:#007bff; color:white; padding:6px 0; border-radius:4px; text-decoration:none; font-size:12px; font-weight:bold;">🗺️ นำทาง</a>
                            <button onclick="openBookingModal(${spot.spot_id}, ${spot.price_per_hour})" style="flex:1; background:#28a745; color:white; border:none; padding:6px 0; border-radius:4px; cursor:pointer; font-size:12px; font-weight:bold;">🗓️ จองเลย</button>
                        </div>
                    </div>
                `;
                marker.bindPopup(popupContent);
                markersGroup.addLayer(marker);
            });
        }

        function openReviewsModal(spotId) {
            var listContainer = document.getElementById('reviewsList');
            listContainer.innerHTML = 'กำลังโหลดรีวิว...';
            document.getElementById('reviewsModal').style.display = 'flex';

            fetch(`get_reviews.php?spot_id=${spotId}`)
                .then(res => res.json())
                .then(reviews => {
                    if (reviews.length === 0) {
                        listContainer.innerHTML = '<p style="color:#777; font-size:14px;">ยังไม่มีรีวิวสำหรับสถานที่นี้</p>';
                        return;
                    }
                    var html = '';
                    reviews.forEach(r => {
                        var stars = '⭐'.repeat(r.rating);
                        var user = r.full_name ? r.full_name : 'ผู้ใช้งาน';
                        var comment = r.comment ? r.comment : '<i>ไม่มีข้อความรีวิว</i>';
                        html += `
                            <div class="review-item">
                                <div class="review-user">${user}</div>
                                <div class="review-stars">${stars} (${r.rating}/5)</div>
                                <div class="review-comment">${comment}</div>
                            </div>
                        `;
                    });
                    listContainer.innerHTML = html;
                });
        }

        function filterSpots() {
            var searchName = document.getElementById('searchName').value.toLowerCase();
            var maxPrice = parseFloat(document.getElementById('maxPrice').value);

            var filtered = allSpots.filter(spot => {
                var matchName = spot.title.toLowerCase().includes(searchName) || (spot.description && spot.description.toLowerCase().includes(searchName));
                var matchPrice = isNaN(maxPrice) || parseFloat(spot.price_per_hour) <= maxPrice;
                return matchName && matchPrice;
            });

            renderMarkers(filtered);
        }

        function openBookingModal(spotId, pricePerHour) {
            document.getElementById('modalSpotId').value = spotId;
            document.getElementById('modalPrice').value = pricePerHour;
            
            var now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            document.getElementById('modalStartTime').value = now.toISOString().slice(0, 16);

            document.getElementById('bookingModal').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function confirmBooking() {
            var spotId = document.getElementById('modalSpotId').value;
            var pricePerHour = document.getElementById('modalPrice').value;
            var startTime = document.getElementById('modalStartTime').value;
            var hours = document.getElementById('modalHours').value;

            if (!startTime || hours <= 0) {
                alert('กรุณากรอกวัน เวลา และจำนวนชั่วโมงให้ถูกต้อง');
                return;
            }

            var formData = new FormData();
            formData.append('spot_id', spotId);
            formData.append('start_time', startTime);
            formData.append('hours', hours);
            formData.append('price_per_hour', pricePerHour);

            fetch('save_booking.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                closeModal('bookingModal');
                if (data.status === 'error' && data.message.includes('เข้าสู่ระบบ')) {
                    window.location.href = 'login.php';
                } else if (data.status === 'success') {
                    window.location.href = 'my_bookings.php';
                }
            });
        }
    </script>
</body>
</html>