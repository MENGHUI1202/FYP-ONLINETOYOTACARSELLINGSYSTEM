<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: homepage.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        $stmt = $conn->prepare('SELECT id, name, email, password, phone FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $user = $res->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_phone'] = $user['phone'];

                // redirect back if provided
                $redirect = $_GET['redirect'] ?? '';
                if ($redirect !== '' && preg_match('/^[a-zA-Z0-9_\-]+\.php$/', $redirect)) {
                    header('Location: ' + $redirect);
                } else {
                    header('Location: homepage.php');
                }
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - NO 1 Car Rental</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

  <style>
    body{
      background: linear-gradient(135deg, #98cfff 0%, #2cc0ff, #9abaff 100%);
      min-height: 100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      padding: 20px;
    }
    .wrap{
      width: 100%;
      max-width: 460px;
      background: #fff;
      border-radius: 25px;
      overflow:hidden;
      box-shadow: 0 20px 60px rgba(0,0,0,.3);
      animation: slideUp .5s ease;
    }
    @keyframes slideUp{from{opacity:0; transform: translateY(30px)}to{opacity:1; transform: translateY(0)}}
    .head{
      background: linear-gradient(135deg, #667eea 0%);
      color:#fff;
      padding: 38px 40px;
      text-align:center;
    }
    .head h1{margin:0; font-size:2.4rem; font-weight:800;}
    .head p{margin:10px 0 0; opacity:.92;}
    .body{padding: 40px;}
    .form-label{font-weight:700; color:#2c3e50; display:flex; gap:10px; align-items:center;}
    .form-label i{color:#667eea; width:18px; text-align:center;}
    .form-control{padding: 14px 18px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1rem;}
    .form-control:focus{border-color:#667eea; box-shadow: 0 0 0 4px rgba(102,126,234,.12); outline:none;}
    .btn-main{
      width:100%; padding: 15px; border:none; border-radius: 12px;
      font-weight:800; font-size:1.1rem; color:#fff;
      background: linear-gradient(135deg, #00579e 0%, #77cbff, #00579e 100%);
      transition:.25s;
    }
    .btn-main:hover{transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102, 126, 234, 0.86);}
    .helper{text-align:center; margin-top: 18px; color:#7f8c8d;}
    .helper a{color:#667eea; font-weight:800; text-decoration:none;}
    .helper a:hover{text-decoration:underline;}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="head">
      <h1><i class="fas fa-sign-in-alt me-2"></i>Login</h1>
      <p>Access your bookings and continue renting</p>
    </div>
    <div class="body">
      <?php if ($error): ?>
        <div class="alert alert-danger" style="border-radius:12px;">
          <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-envelope"></i>Email</label>
          <input class="form-control" type="email" name="email" placeholder="john@example.com" required>
        </div>
        <div class="mb-3">
          <label class="form-label"><i class="fas fa-lock"></i>Password</label>
          <input class="form-control" type="password" name="password" placeholder="Your password" required>
        </div>
        <button class="btn-main" type="submit"><i class="fas fa-sign-in-alt me-2"></i>Login</button>
      </form>

      <div class="helper">
        No account? <a href="register.php">Register</a><br>
        <a href="homepage.php">Back to Home</a>
      </div>
    </div>
  </div>
</body>
</html>