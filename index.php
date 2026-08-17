<?php
session_start();
require_once 'db.php';

// เช็กสิทธิ์การเป็นผู้ให้เช่า
$is_owner = false;
if (isset($_SESSION['user_id'])) {
    $role = strtolower(trim($_SESSION['role'] ?? ''));
    if (in_array($role, ['owner', 'admin', '1', 'seller', 'lessor', '']) || !isset($_SESSION['role'])) {
        $is_owner = true;
    }
}

// ดึงรายการสถานที่พร้อมคำนวณคะแนนดาวเฉลี่ย และจำนวนรีวิว
$sql = "SELECT p.*, u.full_name AS owner_name, u.phone AS owner_phone,
               COALESCE(AVG(r.rating), 0) AS avg_rating, 
               COUNT(r.review_id) AS total_reviews 
        FROM parking_spots p 
        LEFT JOIN users u ON p.user_id = u.user_id 
        LEFT JOIN reviews r ON p.spot_id = r.spot_id 
        GROUP BY p.spot_id 
        ORDER BY p.spot_id DESC";

$result = $conn->query($sql);
$spots = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $spots[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจองที่จอดรถ - ค้นหาที่จอดรถใกล้คุณ</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        * { font-family: 'Prompt', sans-serif; }
        body, html { height: 100%; margin: 0; padding: 0; overflow-x: hidden; background-color: #f8fafc; }
        .navbar-custom { background-color: #0f172a; z-index: 1030; }
        .main-wrapper { height: calc(100vh - 62px); display: flex; flex-direction: row; }
        .sidebar-panel { width: 380px; height: 100%; overflow-y: auto; background: #ffffff; border-right: 1px solid #e2e8f0; z-index: 1000; display: flex; flex-direction: column; }
        .sidebar-header { padding: 15px; background: #fff; position: sticky; top: 0; z-index: 10; border-bottom: 1px solid #f1f5f9; }
        .spot-card { border: 1px solid #e2e8f0; border-radius: 12px; transition: all 0.2s ease-in-out; cursor: pointer; background: #fff; }
        .spot-card:hover { border-color: #0d6efd; box-shadow: 0 4px 12px rgba(13, 110, 253, 0.12); transform: translateY(-2px); }
        #map { flex: 1; height: 100%; width: 100%; z-index: 1; }
        
        /* สไตล์ Leaflet Popup */
        .leaflet-popup-content-wrapper { border-radius: 16px; padding: 4px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
        .leaflet-popup-content { width: 260px !important; margin: 12px 14px !important; }
        .popup-box { width: 100%; }
        .detail-box { background-color: #f1f5f9; border-radius: 10px; padding: 10px; font-size: 0.85rem; color: #475569; }
        .my-location-btn { position: absolute; bottom: 30px; right: 20px; z-index: 999; background: white; border: none; border-radius: 50%; width: 48px; height: 48px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; align-items: center; justify-content: center; cursor: pointer; color: #0d6efd; font-size: 1.2rem; }
        .my-location-btn:hover { background: #0d6efd; color: white; }
        @media (max-width: 768px) { .main-wrapper { flex-direction: column-reverse; } .sidebar-panel { width: 100%; height: 50vh; } #map { height: 50vh; } }
    </style>
</head>
<body>

    <!-- Navbar Header -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom px-3">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold fs-5" href="index.php">
                <i class="fa-solid fa-square-parking text-warning me-2 fs-4"></i>Parking App
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarText">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarText">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0"></ul>
                <div class="d-flex align-items-center gap-2">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="text-white-50 me-2 fs-7 d-none d-md-inline">
                            <i class="fa-solid fa-user-circle me-1 text-light"></i><?= htmlspecialchars($_SESSION['full_name'] ?? 'ผู้ใช้งาน') ?>
                        </span>
                        
                        <a href="my_bookings.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> ประวัติการจอง
                        </a>

                        <?php if ($is_owner): ?>
                            <a href="add_spot.php" class="btn btn-success btn-sm rounded-pill px-3 fw-bold">
                                <i class="fa-solid fa-circle-plus me-1"></i> เพิ่มที่จอดรถ
                            </a>
                            <a href="my_spots.php" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold">
                                <i class="fa-solid fa-bars-progress me-1"></i> จัดการที่จอดรถ
                            </a>
                        <?php endif; ?>

                        <a href="logout.php" class="btn btn-danger btn-sm rounded-pill px-3">
                            <i class="fa-solid fa-right-from-bracket me-1"></i> ออกจากระบบ
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-warning btn-sm fw-bold rounded-pill px-3">
                            <i class="fa-solid fa-right-to-bracket me-1"></i> เข้าสู่ระบบ
                        </a>
                        <a href="register.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                            สมัครสมาชิก
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="main-wrapper">
        <div class="sidebar-panel">
            <div class="sidebar-header shadow-sm">
                <h6 class="fw-bold mb-2 text-dark"><i class="fa-solid fa-magnifying-glass me-1 text-primary"></i> ค้นหาที่จอดรถ</h6>
                <div class="input-group mb-2">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                    <input type="text" id="searchInput" class="form-control bg-light border-start-0" placeholder="พิมพ์ชื่อสถานที่..." onkeyup="filterSpots()">
                </div>

                <?php if ($is_owner): ?>
                    <a href="add_spot.php" class="btn btn-success btn-sm w-100 rounded-3 fw-bold shadow-sm">
                        <i class="fa-solid fa-plus-circle me-1"></i> + เพิ่มที่จอดรถใหม่
                    </a>
                <?php endif; ?>
            </div>

            <div class="p-3" id="spotList">
                <?php if (empty($spots)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fa-solid fa-map-location-dot display-4 mb-3 text-secondary"></i>
                        <p class="m-0">ยังไม่มีข้อมูลที่จอดรถในระบบ</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($spots as $spot): ?>
                        <div class="spot-card p-3 mb-3 spot-item" 
                             data-title="<?= htmlspecialchars($spot['title'] ?? '') ?>" 
                             data-desc="<?= htmlspecialchars($spot['description'] ?? '') ?>"
                             onclick="focusMap(<?= $spot['latitude'] ?>, <?= $spot['longitude'] ?>, <?= $spot['spot_id'] ?>)">
                            
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="fw-bold text-dark m-0"><?= htmlspecialchars($spot['title'] ?? 'ที่จอดรถ') ?></h6>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                                    <?= number_format($spot['price_per_hour'] ?? 0, 0) ?> ฿/ชม.
                                </span>
                            </div>

                            <div class="mb-2 fs-7 d-flex align-items-center justify-content-between">
                                <div>
                                    <?php if (($spot['total_reviews'] ?? 0) > 0): ?>
                                        <span class="text-warning fw-bold"><i class="fa-solid fa-star me-1"></i><?= number_format($spot['avg_rating'], 1) ?></span>
                                        <span class="text-muted"> (<?= $spot['total_reviews'] ?> รีวิว)</span>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="fa-regular fa-star me-1"></i>ยังไม่มีรีวิว</span>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 fw-bold" 
                                        onclick="event.stopPropagation(); loadReviews(<?= $spot['spot_id'] ?>, '<?= htmlspecialchars($spot['title'] ?? '', ENT_QUOTES) ?>')">
                                    <i class="fa-solid fa-comments me-1"></i>อ่านรีวิว
                                </button>
                            </div>

                            <div class="detail-box mb-2">
                                <i class="fa-solid fa-thumbtack text-danger me-1"></i>
                                <?= htmlspecialchars(mb_strimwidth($spot['description'] ?? 'ไม่มีรายละเอียด', 0, 60, '...')) ?>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="fs-7 text-muted">
                                    <i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($spot['contact_phone'] ?? $spot['owner_phone'] ?? '-') ?>
                                </span>
                                <button type="button" class="btn btn-sm btn-success text-white rounded-pill px-3 fw-bold" 
                                        onclick="event.stopPropagation(); openBookingModal(<?= $spot['spot_id'] ?>, '<?= htmlspecialchars($spot['title'] ?? '', ENT_QUOTES) ?>', <?= $spot['price_per_hour'] ?? 0 ?>)">
                                    <i class="fa-solid fa-calendar-check me-1"></i> จองเลย
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div style="position: relative; flex: 1;">
            <div id="map"></div>
            <button class="my-location-btn" onclick="locateUser()" title="ระบุตำแหน่งของฉัน">
                <i class="fa-solid fa-crosshairs"></i>
            </button>
        </div>
    </div>

    <!-- Modal อ่านรีวิว -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="reviewModalTitle"><i class="fa-solid fa-star text-warning me-2"></i>รีวิวสถานที่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="reviewModalBody">
                    <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal จองที่จอดรถ -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-square-parking text-warning me-2"></i>จองที่จอดรถ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="save_booking.php" method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="spot_id" id="modal_spot_id">
                        
                        <h5 class="fw-bold text-primary mb-1" id="modal_spot_title"></h5>
                        <p class="text-muted fs-7 mb-3">อัตราบริการ: <span class="fw-bold text-success" id="modal_spot_price"></span> บาท/ชม.</p>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fa-regular fa-calendar-days text-primary me-1"></i> วันและเวลาที่เริ่มจอด</label>
                            <input type="datetime-local" name="start_time" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fa-regular fa-clock text-primary me-1"></i> จำนวนระยะเวลา (ชั่วโมง)</label>
                            <select name="hours" id="modal_hours" class="form-select" onchange="updateBookingTotal()">
                                <?php for($i=1; $i<=24; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?> ชั่วโมง</option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="p-3 bg-light border rounded-3 text-end">
                            <small class="text-muted d-block">ราคารวมโดยประมาณ</small>
                            <span class="fs-4 fw-bold text-success" id="modal_total_price">0.00</span> บาท
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold"><i class="fa-solid fa-circle-check me-1"></i> ยืนยันจอง</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        var map = L.map('map').setView([7.0087, 100.4747], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);

        var spots = <?= json_encode($spots, JSON_UNESCAPED_UNICODE) ?>;
        var markers = {};
        var currentPricePerHour = 0;

        spots.forEach(function(spot) {
            if (spot.latitude && spot.longitude) {
                var lat = parseFloat(spot.latitude);
                var lng = parseFloat(spot.longitude);

                // ดักจับชื่อคอลัมน์รูปภาพทั้งหมดที่อาจเป็นไปได้ในฐานข้อมูล
                var rawImg = spot.image || spot.image_url || spot.image_path || spot.spot_image || spot.photo || spot.picture || spot.img || '';
                var spotImg = rawImg.trim();

                // ปรับแต่ง Path ของรูปภาพให้ถูกต้องอัตโนมัติ
                if (spotImg !== '') {
                    if (!spotImg.startsWith('http') && !spotImg.startsWith('uploads/') && !spotImg.startsWith('/') && !spotImg.startsWith('./')) {
                        spotImg = 'uploads/' + spotImg;
                    }
                }

                // สร้าง HTML สำหรับแสดงรูปภาพด้านบนสุด
                var imageHTML = (spotImg !== '')
                    ? `<div class="mb-2" style="margin: -4px -4px 10px -4px; overflow: hidden; border-radius: 12px 12px 0 0;">
                        <img src="${spotImg}" class="img-fluid w-100" style="height: 140px; object-fit: cover;" alt="รูปสถานที่" onerror="this.onerror=null; this.src='uploads/'+'${rawImg}';">
                       </div>`
                    : '';

                var ratingHTML = (spot.total_reviews > 0)
                    ? `<span class="text-warning fw-bold"><i class="fa-solid fa-star me-1"></i>${parseFloat(spot.avg_rating).toFixed(1)}</span> <small class="text-muted">(${spot.total_reviews} รีวิว)</small>`
                    : `<small class="text-muted"><i class="fa-regular fa-star me-1"></i>ยังไม่มีรีวิว</small>`;

                var popupContent = `
                    <div class="popup-box">
                        <!-- 1. รูปภาพสถานที่ด้านบนสุด -->
                        ${imageHTML}

                        <!-- 2. ชื่อสถานที่ + คะแนนรีวิว + ปุ่มนำทางอัตโนมัติ -->
                        <div class="d-flex justify-content-between align-items-start mb-2 pe-1">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">${spot.title || 'ที่จอดรถ'}</h6>
                                <div class="mt-1">${ratingHTML}</div>
                            </div>
                            <button type="button" onclick="navigateSpot(${lat}, ${lng})" class="btn btn-primary btn-sm rounded-pill px-2 py-1 text-nowrap fw-bold shadow-sm ms-2 text-white" style="font-size: 0.75rem;">
                                <i class="fa-solid fa-diamond-turn-right me-1 text-white"></i>นำทาง
                            </button>
                        </div>

                        <!-- 3. รายละเอียด / จุดสังเกต -->
                        <div class="detail-box mb-2">
                            <i class="fa-solid fa-thumbtack text-danger me-1"></i> ${spot.description || 'ไม่มีรายละเอียด'}
                        </div>

                        <!-- 4. เบอร์โทร และ ราคา -->
                        <div class="mb-1 fs-7"><i class="fa-solid fa-phone text-primary me-1"></i> ${spot.contact_phone || spot.owner_phone || spot.phone || '-'}</div>
                        <div class="mb-3 text-success fw-bold">${parseFloat(spot.price_per_hour || 0).toFixed(0)} บาท/ชม.</div>

                        <!-- 5. ปุ่ม อ่านรีวิว และ จองเลย ด้านล่าง -->
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-3 flex-fill fw-bold" onclick="loadReviews(${spot.spot_id}, '${(spot.title || '').replace(/'/g, "\\'")}')">
                                <i class="fa-solid fa-comments me-1"></i> รีวิว
                            </button>
                            <button type="button" class="btn btn-success btn-sm rounded-3 flex-fill text-white fw-bold" onclick="openBookingModal(${spot.spot_id}, '${(spot.title || '').replace(/'/g, "\\'")}', ${spot.price_per_hour || 0})">
                                <i class="fa-solid fa-calendar-check me-1"></i> จองเลย
                            </button>
                        </div>
                    </div>
                `;

                var marker = L.marker([lat, lng]).addTo(map).bindPopup(popupContent);
                markers[spot.spot_id] = marker;
            }
        });

        // ฟังก์ชันนำทางอัตโนมัติไปยังจุดหมาย
        function navigateSpot(destLat, destLng) {
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        var originLat = pos.coords.latitude;
                        var originLng = pos.coords.longitude;
                        var url = `https://www.google.com/maps/dir/?api=1&origin=${originLat},${originLng}&destination=${destLat},${destLng}&travelmode=driving`;
                        window.open(url, '_blank');
                    },
                    function(error) {
                        // ถ้าดึงตำแหน่งปัจจุบันไม่สำเร็จ ให้ส่งไปโหมดนำทางโดยใช้ตำแหน่งอุปกรณ์อัตโนมัติ
                        var url = `https://www.google.com/maps/dir/?api=1&destination=${destLat},${destLng}&travelmode=driving`;
                        window.open(url, '_blank');
                    },
                    { enableHighAccuracy: true, timeout: 5000 }
                );
            } else {
                var url = `https://www.google.com/maps/dir/?api=1&destination=${destLat},${destLng}&travelmode=driving`;
                window.open(url, '_blank');
            }
        }

        function focusMap(lat, lng, spotId) {
            map.flyTo([lat, lng], 16, { duration: 1.2 });
            if (markers[spotId]) setTimeout(function() { markers[spotId].openPopup(); }, 500);
        }

        function filterSpots() {
            var input = document.getElementById('searchInput').value.toLowerCase();
            Array.from(document.getElementsByClassName('spot-item')).forEach(function(item) {
                var title = (item.getAttribute('data-title') || '').toLowerCase();
                var desc = (item.getAttribute('data-desc') || '').toLowerCase();
                item.style.display = (title.includes(input) || desc.includes(input)) ? "block" : "none";
            });
        }

        function locateUser() {
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(function(pos) {
                    map.flyTo([pos.coords.latitude, pos.coords.longitude], 15);
                    L.popup().setLatLng([pos.coords.latitude, pos.coords.longitude]).setContent('<b class="text-primary"><i class="fa-solid fa-location-crosshairs me-1"></i> คุณอยู่ที่นี่</b>').openOn(map);
                });
            }
        }

        function loadReviews(spotId, title) {
            document.getElementById('reviewModalTitle').innerText = 'รีวิว ' + title;
            var body = document.getElementById('reviewModalBody');
            body.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
            
            var modal = new bootstrap.Modal(document.getElementById('reviewModal'));
            modal.show();

            fetch('get_reviews.php?spot_id=' + spotId)
                .then(res => res.json())
                .then(data => {
                    if (!data || data.length === 0) {
                        body.innerHTML = '<p class="text-center text-muted my-4"><i class="fa-regular fa-comment-dots display-4 mb-2 d-block"></i>ยังไม่มีผู้รีวิวสถานที่นี้</p>';
                        return;
                    }
                    var html = '';
                    data.forEach(function(r) {
                        var stars = '';
                        for (var i = 1; i <= 5; i++) {
                            stars += i <= r.rating ? '<i class="fa-solid fa-star text-warning"></i>' : '<i class="fa-regular fa-star text-muted"></i>';
                        }
                        html += `
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-dark">${r.full_name}</strong>
                                    <div>${stars}</div>
                                </div>
                                <p class="mb-1 text-secondary fs-7">${r.comment || 'ไม่ได้ระบุความเห็น'}</p>
                                <small class="text-muted fs-8">${r.created_at}</small>
                            </div>
                        `;
                    });
                    body.innerHTML = html;
                })
                .catch(err => {
                    body.innerHTML = '<p class="text-center text-danger my-4">เกิดข้อผิดพลาดในการดึงข้อมูลรีวิว</p>';
                });
        }

        function openBookingModal(spotId, title, price) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                window.location.href = 'login.php';
                return;
            <?php endif; ?>

            document.getElementById('modal_spot_id').value = spotId;
            document.getElementById('modal_spot_title').innerText = title;
            document.getElementById('modal_spot_price').innerText = price;
            currentPricePerHour = parseFloat(price);
            
            document.getElementById('modal_hours').value = 1;
            updateBookingTotal();

            var modal = new bootstrap.Modal(document.getElementById('bookingModal'));
            modal.show();
        }

        function updateBookingTotal() {
            var h = parseInt(document.getElementById('modal_hours').value) || 1;
            document.getElementById('modal_total_price').innerText = (h * currentPricePerHour).toFixed(2);
        }
    </script>
</body>
</html>