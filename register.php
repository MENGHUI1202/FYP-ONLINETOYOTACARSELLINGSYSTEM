<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';

function post($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = post('name');
    $email = post('email');
    $phone = post('phone');
    $address = post('address');
    $date_of_birth = post('date_of_birth');
    $license_number = post('license_number');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // --- 1. 电话号码深度验证逻辑 (严格版) ---
    // 去掉非数字字符
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
    // 强制规则：必须是 01 开头(10-11位) 或 601 开头(11-12位)
    $is_valid_phone = preg_match('/^(01[0-9]{8,9}|601[0-9]{8,9})$/', $clean_phone);

    // --- 2. 基础验证 ---
    if ($name === '' || $email === '' || $phone === '' || $date_of_birth === '' || $license_number === '' || $password === '' || $confirm_password === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_of_birth)) {
        $error = 'Date of birth format must be YYYY-MM-DD.';
    } elseif (!$is_valid_phone) {
        // ★★★ 电话格式报错 ★★★
        $error = 'Invalid phone number. Format must be strict (e.g. 0123456789).';
    } else {
        // 3. 年龄验证
        if (strtotime($date_of_birth) > strtotime('-17 years')) {
            $error = 'You must be at least 17 years old to register.';
        } else {
            // 4. 重复性检查
            $check = $conn->prepare("SELECT name, email, license_number FROM users WHERE name = ? OR email = ? OR license_number = ?");
            $check->bind_param('sss', $name, $email, $license_number);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    if ($row['email'] === $email) {
                        $error = 'This email is already registered.';
                    } elseif ($row['license_number'] === $license_number) {
                        $error = 'This License Number is already in use.';
                    } elseif ($row['name'] === $name) {
                        $error = 'This Name is already taken.';
                    }
                }
            } else {
                // 5. 插入数据库
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('INSERT INTO users (name, email, password, phone, address, date_of_birth, license_number) VALUES (?, ?, ?, ?, ?, ?, ?)');
                
                // 修复：直接使用 $address，不转换成 null，防止数据库报错
                $stmt->bind_param('sssssss', $name, $email, $hashed, $phone, $address, $date_of_birth, $license_number);

                if ($stmt->execute()) {
                    $success = 'Registration successful! Redirecting to login...';
                    // PHP跳转 (如果输出流未占用)
                    header('refresh:2;url=login.php');
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - NO 1 Car Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

    <style>
        body{
            background: linear-gradient(135deg, #98cfff 0%, #2cc0ff, #9abaff 100%);
            min-height: 100vh;
            display:flex; align-items:center; justify-content:center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .card-wrap{
            width: 100%; max-width: 620px;
            background: #fff; border-radius: 25px; overflow: hidden;
            box-shadow: 0 20px 60px rgb(0, 0, 0);
            animation: slideUp .5s ease;
        }
        @keyframes slideUp{ from{opacity:0; transform: translateY(30px)} to{opacity:1; transform: translateY(0)} }
        .header{
            background: linear-gradient(135deg, #667eea 100%); color:#fff;
            padding: 38px 40px; text-align:center;
        }
        .header h1{ margin:0; font-size: 2.4rem; font-weight: 800; }
        .header p{ margin: 10px 0 0; opacity: .92; }
        .body{ padding: 40px; }
        .form-label{ font-weight: 700; color:#2c3e50; display:flex; gap:10px; align-items:center; }
        .form-label i{color:#667eea; width:18px; text-align:center;}
        .req{color:#e74c3c; margin-left:6px;}
        .form-control{
            padding: 14px 18px; border: 2px solid #000000; border-radius: 10px; font-size: 1rem; transition: .2s;
        }
        .form-control:focus{ border-color:#667eea; box-shadow: 0 0 0 4px rgba(102,126,234,.12); }
        .grid{ display:grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        @media(max-width: 700px){ .grid{grid-template-columns: 1fr;} .body{padding: 28px;} }
        .btn-main{
            width: 100%; padding: 15px; border: none; border-radius: 12px;
            font-weight: 800; font-size: 1.1rem; color:#fff;
            background: linear-gradient(135deg, #00579e 0%, #77cbff, #00579e 100%);
            transition: .25s;
        }
        .btn-main:hover{ transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102,126,234,.4); }
        .helper{ text-align:center; margin-top: 18px; color:#7f8c8d; }
        .helper a{color:#667eea; font-weight:800; text-decoration:none;}
        .helper a:hover{text-decoration:underline;}
        .small-note{color:#7f8c8d; font-size:.9rem; margin-top:6px;}
        .password-wrapper { position: relative; }
        .toggle-icon {
            position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
            color: #7f8c8d; cursor: pointer; z-index: 10;
        }
        .toggle-icon:hover { color: #2c3e50; }
    </style>
</head>
<body>
    <div class="card-wrap">
        <div class="header">
            <h1><i class="fas fa-user-plus me-2"></i>Register</h1>
            <p>Create your account to book cars faster</p>
        </div>

        <div class="body">
            <?php if ($error): ?>
                <div class="alert alert-danger" style="border-radius: 12px;">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" style="border-radius: 12px;">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
                <script>
                    setTimeout(function(){
                        window.location.href = 'login.php';
                    }, 2000);
                </script>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="grid">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-user"></i>Full Name<span class="req">*</span></label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars(post('name')); ?>" placeholder="John Doe" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-envelope"></i>Email<span class="req">*</span></label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars(post('email')); ?>" placeholder="john@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-phone"></i>Phone<span class="req">*</span></label>
                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars(post('phone')); ?>" placeholder="012-3456789" required pattern="(\+?60|0)1[0-9 \-]{8,11}" title="Please enter a valid mobile number (e.g. 012-3456789)">
                        <div class="small-note">Format: 012-3456789</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-id-card"></i>License Number<span class="req">*</span></label>
                        <input type="text" class="form-control" name="license_number" value="<?php echo htmlspecialchars(post('license_number')); ?>" placeholder="D1234567" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-map-marker-alt"></i>Address</label>
                    <textarea class="form-control" name="address" rows="3" placeholder="Your address..." style="resize: none;"><?php echo htmlspecialchars(post('address')); ?></textarea>
                </div>

                <div class="grid">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-calendar"></i>Date of Birth<span class="req">*</span></label>
                        <input type="date" class="form-control" name="date_of_birth" value="<?php echo htmlspecialchars(post('date_of_birth')); ?>" required>
                        <div class="small-note">Must be 17+ years old</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-lock"></i>Password<span class="req">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" class="form-control" name="password" id="password" placeholder="At least 6 characters" required style="padding-right: 40px;">
                            <i class="fas fa-eye toggle-icon" onclick="togglePassword('password', this)"></i>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-lock"></i>Confirm Password<span class="req">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Re-enter password" required style="padding-right: 40px;">
                            <i class="fas fa-eye toggle-icon" onclick="togglePassword('confirm_password', this)"></i>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-main">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>

                <div class="helper">
                    Already have an account? <a href="login.php">Login</a><br>
                    <a href="homepage.php" style="font-weight:700; color:#7f8c8d;">Back to Home</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>