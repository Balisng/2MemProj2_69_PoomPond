<?php
session_start();
require_once 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);

    if (!empty($full_name) && !empty($email) && !empty($phone) && !empty($password)) {
        
        // เช็กอีเมลซ้ำ
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        if (!$check) {
            die("เกิดข้อผิดพลาดในการเชื่อมต่อตาราง users: " . $conn->error);
        }
        
        $check->bind_param("s", $email);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $message = "อีเมลนี้ถูกใช้งานแล้ว";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
            
            if (!$stmt) {
                die("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $conn->error);
            }

            $stmt->bind_param("ssss", $full_name, $email, $phone, $hashed_password);

            if ($stmt->execute()) {
                echo "<script>alert('สมัครสมาชิกสำเร็จ!'); window.location.href='login.php';</script>";
                exit;
            } else {
                $message = "เกิดข้อผิดพลาด: " . $stmt->error;
            }
        }
    } else {
        $message = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - Parking App</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background: linear-gradient(135deg, #0f172a, #1e293b); min-height: 100vh; display: flex; align-items: center; }
        .card-auth { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); max-width: 460px; width: 100%; margin: 20px auto; }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="card card-auth p-4 p-md-5">
            <div class="text-center mb-4">
                <i class="fa-solid fa-user-plus text-success display-5 mb-2"></i>
                <h4 class="fw-bold text-dark">สมัครสมาชิกใหม่</h4>
                <p class="text-muted fs-7">กรอกข้อมูลเพื่อเริ่มต้นใช้งาน</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-danger py-2 rounded-3 text-center mb-3 fs-7"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold">ชื่อ-นามสกุล / ชื่อเล่น</label>
                    <input type="text" name="full_name" class="form-control" required placeholder="สมชาย ใจดี">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">อีเมล (Email)</label>
                    <input type="email" name="email" class="form-control" required placeholder="name@example.com">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">เบอร์โทรศัพท์</label>
                    <input type="tel" name="phone" class="form-control" required placeholder="0812345678">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">รหัสผ่าน</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>

                <button type="submit" class="btn btn-success w-100 py-2.5 rounded-3 fw-bold mb-3"><i class="fa-solid fa-user-check me-1"></i> ลงทะเบียน</button>
            </form>

            <div class="text-center mt-2 fs-7">
                <span class="text-muted">มีบัญชีอยู่แล้ว?</span> <a href="login.php" class="text-success fw-bold text-decoration-none">เข้าสู่ระบบ</a>
                <div class="mt-3">
                    <a href="index.php" class="text-secondary text-decoration-none"><i class="fa-solid fa-house me-1"></i> กลับหน้าหลัก</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>