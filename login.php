<?php
session_start();
require_once 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                header("Location: index.php");
                exit;
            } else {
                $message = "รหัสผ่านไม่ถูกต้อง";
            }
        } else {
            $message = "ไม่พบอีเมลนี้ในระบบ";
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
    <title>เข้าสู่ระบบ - Parking App</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background: linear-gradient(135deg, #0f172a, #1e293b); min-height: 100vh; display: flex; align-items: center; }
        .card-auth { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); max-width: 420px; width: 100%; margin: 20px auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card card-auth p-4 p-md-5">
            <div class="text-center mb-4">
                <i class="fa-solid fa-square-parking text-warning display-4 mb-2"></i>
                <h4 class="fw-bold text-dark">เข้าสู่ระบบ</h4>
                <p class="text-muted fs-7">ระบบค้นหาและจองที่จอดรถ</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-danger py-2 rounded-3 text-center mb-3 fs-7"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold">อีเมล (Email)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa-solid fa-envelope text-muted"></i></span>
                        <input type="email" name="email" class="form-control" required placeholder="name@example.com">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">รหัสผ่าน</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa-solid fa-lock text-muted"></i></span>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-3 fw-bold mb-3"><i class="fa-solid fa-right-to-bracket me-1"></i> เข้าสู่ระบบ</button>
            </form>

            <div class="text-center mt-3 fs-7">
                <span class="text-muted">ยังไม่มีบัญชี?</span> <a href="register.php" class="text-primary fw-bold text-decoration-none">สมัครสมาชิก</a>
                <div class="mt-3">
                    <a href="index.php" class="text-secondary text-decoration-none"><i class="fa-solid fa-house me-1"></i> กลับหน้าหลัก</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>