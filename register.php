<?php
session_start();
require_once 'db.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);

    // ตรวจสอบ Username ซ้ำ
    $check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $message = "Username นี้มีผู้ใช้งานแล้ว";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, phone, role) VALUES (?, ?, ?, ?, 'user')");
        $stmt->bind_param("ssss", $username, $password, $full_name, $phone);
        
        if ($stmt->execute()) {
            echo "<script>alert('สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ'); window.location.href='login.php';</script>";
            exit;
        } else {
            // แสดงข้อผิดพลาดจริงจาก MySQL เพื่อช่วยวิเคราะห์สาเหตุ
            $message = "เกิดข้อผิดพลาดจากฐานข้อมูล: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สมัครสมาชิก - Parking App</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f4f4f4; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); width: 320px; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; }
        .error { color: red; font-size: 14px; margin-bottom: 10px; word-break: break-all; }
    </style>
</head>
<body>
    <div class="card">
        <h2>สมัครสมาชิก</h2>
        <?php if ($message): ?>
            <p class="error"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>ชื่อผู้ใช้ (Username)</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>รหัสผ่าน (Password)</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>ชื่อ-นามสกุล</label>
                <input type="text" name="full_name" required>
            </div>
            <div class="form-group">
                <label>เบอร์โทรศัพท์</label>
                <input type="text" name="phone" required>
            </div>
            <button type="submit">ยืนยันสมัครสมาชิก</button>
        </form>
        <p style="text-align:center; font-size:14px; margin-top:15px;">
            มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a>
        </p>
    </div>
</body>
</html>