<?php
session_start();
// ★★★ 修复1：确保路径指向 includes 文件夹 ★★★
require_once 'includes/config.php';

// 检查是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. 获取用户信息
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // 如果找不到用户（可能被删除了），强制登出
    session_destroy();
    header('Location: login.php');
    exit();
}
$user = $result->fetch_assoc();
$stmt->close();

// ★★★ 修复2：处理图片路径，去掉可能的 '../' ★★★
$profile_pic = !empty($user['profile_picture']) 
    ? str_replace('../', '', $user['profile_picture']) 
    : '';

// 2. 获取用户的最近预订 (只取前5条)
$stmt = $conn->prepare("
    SELECT b.*, 
           COUNT(bi.id) as item_count,
           MIN(bi.start_datetime) as earliest_pickup
    FROM bookings b
    LEFT JOIN booking_items bi ON b.id = bi.booking_id
    WHERE b.user_id = ?
    GROUP BY b.id
    ORDER BY b.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 3. 获取统计数据 - 总预订数
$stmt = $conn->prepare("SELECT COUNT(*) as total_bookings FROM bookings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$booking_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 4. 获取统计数据 - 总消费 (已支付)
$stmt = $conn->prepare("SELECT SUM(grand_total) as total_spent FROM bookings WHERE user_id = ? AND payment_status = 'paid'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$spending_stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account -NO 1 Car Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #d1e2ff 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        /* 导航栏样式保持不变 */
        .navbar { background: #505d6c; backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding: 15px 0; box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1); }
        .navbar-brand { font-size: 1.8rem; font-weight: 700; color: #ffffff; letter-spacing: 1px; }
        .nav-link { color: #e0e0e0 !important; font-size: 1.1rem; margin: 0 8px; transition: all 0.3s; padding: 10px 20px !important; border-radius: 10px; }
        .nav-link:hover { color: #ffffff !important; background: rgba(255, 255, 255, 0.15); transform: translateY(-2px); }
        .nav-link.active { background: rgba(255, 255, 255, 0.2); color: #ffffff !important; }

        .page-header { background: linear-gradient(135deg, #6cb1ff 0%, #6cb1ff 100%); color: white; text-align: center; padding: 50px 0; margin-bottom: 40px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); }
        .page-title { font-size: 3rem; font-weight: 800; margin-bottom: 10px; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2); }
        .page-subtitle { font-size: 1.2rem; opacity: 0.95; }

        .main-container { max-width: 1400px; margin: 0 auto; padding: 0 30px; }
        .account-layout { display: grid; grid-template-columns: 300px 1fr; gap: 30px; margin-bottom: 50px; }
        
        .sidebar { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); height: fit-content; position: sticky; top: 20px; }
        
        .profile-section { text-align: center; margin-bottom: 30px; padding-bottom: 30px; border-bottom: 2px solid #f0f0f0; }
        .profile-avatar { width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #afbdff 0%, #744ba2 100%); margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: white; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2); cursor: pointer; overflow: hidden; position: relative; }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .profile-avatar .upload-overlay { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0, 0, 0, 0.7); color: white; padding: 5px; font-size: 0.8rem; opacity: 0; transition: opacity 0.3s; }
        .profile-avatar:hover .upload-overlay { opacity: 1; }
        #profile-upload { display: none; }
        
        .profile-name { font-size: 1.8rem; font-weight: 700; color: #2c3e50; margin-bottom: 5px; }
        .profile-email { color: #7f8c8d; font-size: 0.95rem; margin-bottom: 15px; }
        .member-badge { display: inline-block; padding: 6px 15px; background: linear-gradient(135deg, #f093fb 0%, #9d87ee 100%); color: white; border-radius: 20px; font-size: 0.9rem; font-weight: 600; }

        .sidebar-menu { list-style: none; padding: 0; margin-bottom: 20px; }
        .menu-item { margin-bottom: 10px; }
        .menu-link { display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #f8f9fa; border-radius: 12px; color: #2c3e50; text-decoration: none; font-weight: 600; font-size: 1rem; transition: all 0.3s; }
        .menu-link:hover, .menu-link.active { background: linear-gradient(135deg, #667eea 100%); color: white; transform: translateX(5px); }
        .menu-link i { font-size: 1.2rem; width: 24px; }

        .logout-btn { display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff5f5; border-radius: 12px; color: #e74c3c; text-decoration: none; font-weight: 600; font-size: 1rem; transition: all 0.3s; margin-top: 20px; border: 2px solid #ffecec; }
        .logout-btn:hover { background: #e74c3c; color: white; transform: translateX(5px); }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08); transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1); }
        .stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 20px; }
        .stat-card:nth-child(1) .stat-icon { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #667eea; }
        .stat-card:nth-child(2) .stat-icon { background: linear-gradient(135deg, #f093fb 0%, #9fc4ff 100%); color: white; }
        .stat-value { font-size: 2.2rem; font-weight: 800; color: #2c3e50; margin-bottom: 5px; }
        .stat-label { color: #7f8c8d; font-size: 1rem; }

        .section-card { background: white; border-radius: 20px; padding: 35px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); margin-bottom: 30px; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #f0f0f0; }
        .section-title { font-size: 1.8rem; font-weight: 700; color: #2c3e50; display: flex; align-items: center; gap: 12px; }
        .section-title i { color: #8fa3ff; font-size: 1.5rem; }
        .view-all { color: #000000; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 5px; transition: all 0.3s; }
        .view-all:hover { color: #5568d3; gap: 8px; }

        .booking-list { display: grid; gap: 20px; }
        .booking-item { background: #f8f9fa; border-radius: 15px; padding: 25px; border: 2px solid #e0e0e0; transition: all 0.3s; }
        .booking-item:hover { border-color: #667eea; background: #f8f9ff; transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1); }
        .booking-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .booking-ref { font-size: 1.2rem; font-weight: 700; color: #2c3e50; }
        
        .booking-badge { padding: 6px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        /* 修复 CSS 类名匹配问题，使用小写 */
        .badge-confirmed { background: #d4edda; color: #155724; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }
        .badge-completed { background: #d1e7dd; color: #0f5132; }

        .booking-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .booking-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 20px; border-top: 2px solid #e0e0e0; }
        .booking-total { font-size: 1.5rem; font-weight: 700; color: #667eea; }
        
        .action-btn { padding: 8px 20px; border-radius: 10px; font-weight: 600; text-decoration: none; font-size: 0.9rem; transition: all 0.3s; border: none; cursor: pointer; }
        .btn-view { background: linear-gradient(135deg, #4164ff 0%, #6593ff 100%); color: white; box-shadow: 0 4px 15px rgb(0, 0, 0); }
        .btn-view:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4); color: white; }

        .form-group { margin-bottom: 25px; }
        .form-label { font-weight: 700; color: #2c3e50; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .form-label i { color: #667eea; }
        .form-control { width: 100%; padding: 14px 18px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1rem; transition: all 0.3s; }
        .form-control:focus { border-color: #667eea; outline: none; box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1); }
        
        .btn-save { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 15px 40px; border-radius: 10px; font-weight: 700; font-size: 1.1rem; cursor: pointer; transition: all 0.3s; }
        .btn-save:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3); }

        @media (max-width: 992px) { .account-layout { grid-template-columns: 1fr; } .sidebar { position: relative; top: 0; } }
        @media (max-width: 768px) { .main-container { padding: 0 20px; } .page-title { font-size: 2.2rem; } .booking-details { grid-template-columns: 1fr; } .booking-footer { flex-direction: column; gap: 15px; } .booking-actions { width: 100%; justify-content: space-between; } }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="product_catalogue.php"><i class="fas fa-car me-2"></i>NO 1 Car Rental</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="homepage.php"><i class="fas fa-home"></i> Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="product_catalogue.php"><i class="fas fa-th-large"></i> Catalogue</a></li>
                    <li class="nav-item"><a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                    <li class="nav-item"><a class="nav-link active" href="my_account.php"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user['name']); ?></a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1 class="page-title"><i class="fas fa-user-circle me-3"></i>MY ACCOUNT</h1>
            <p class="page-subtitle">Manage your profile, bookings, and settings</p>
        </div>
    </div>

    <div class="main-container">
        <div class="account-layout">
            <div class="sidebar">
                <div class="profile-section">
                    <div class="profile-avatar" onclick="document.getElementById('profile-upload').click()">
                        <?php if (!empty($profile_pic)): ?>
                            <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        <?php endif; ?>
                        <div class="upload-overlay">
                            <i class="fas fa-camera"></i> Change Photo
                        </div>
                    </div>
                    <input type="file" id="profile-upload" accept="image/*" onchange="uploadProfilePicture(this)">
                    <h2 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <span class="member-badge">
                        <i class="fas fa-crown me-2"></i>Member Since <?php echo date('Y', strtotime($user['created_at'])); ?>
                    </span>
                </div>

                <ul class="sidebar-menu">
                    <li class="menu-item">
                        <a href="#dashboard" class="menu-link active" onclick="showSection('dashboard')">
                            <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="#profile" class="menu-link" onclick="showSection('profile')">
                            <i class="fas fa-user-edit"></i> <span>Edit Profile</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="#bookings" class="menu-link" onclick="showSection('bookings')">
                            <i class="fas fa-history"></i> <span>My Bookings</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="#security" class="menu-link" onclick="showSection('security')">
                            <i class="fas fa-lock"></i> <span>Change Password</span>
                        </a>
                    </li>
                </ul>

                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>

            <div class="content-area">
                <div id="dashboard-section" class="section-content">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="stat-value"><?php echo $booking_stats['total_bookings'] ?? 0; ?></div>
                            <div class="stat-label">Total Bookings</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                            <div class="stat-value">RM <?php echo number_format($spending_stats['total_spent'] ?? 0, 2); ?></div>
                            <div class="stat-label">Total Spent</div>
                        </div>
                    </div>

                    <div class="section-card">
                        <div class="section-header">
                            <h3 class="section-title"><i class="fas fa-clock"></i> Recent Bookings</h3>
                            <a href="#bookings" class="view-all" onclick="showSection('bookings')">View All <i class="fas fa-arrow-right"></i></a>
                        </div>

                        <?php if (empty($recent_bookings)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h4>No bookings yet</h4>
                                <a href="product_catalogue.php" class="btn-save mt-3">Start Renting Now</a>
                            </div>
                        <?php else: ?>
                            <div class="booking-list">
                                <?php foreach ($recent_bookings as $booking): 
                                    $display_amount = !empty($booking['grand_total']) ? $booking['grand_total'] : $booking['total_amount'];
                                    // ★★★ 修复：将状态转为小写以匹配CSS类 (.badge-confirmed) ★★★
                                    $status_class = 'badge-' . strtolower($booking['booking_status']);
                                ?>
                                    <div class="booking-item">
                                        <div class="booking-header">
                                            <div class="booking-ref">#<?php echo $booking['booking_reference']; ?></div>
                                            <span class="booking-badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($booking['booking_status']); ?>
                                            </span>
                                        </div>

                                        <div class="booking-details">
                                            <div class="detail-group">
                                                <i class="fas fa-calendar text-primary"></i>
                                                <div>
                                                    <div class="detail-label">Booked On</div>
                                                    <div class="detail-value"><?php echo date('d/m/Y', strtotime($booking['created_at'])); ?></div>
                                                </div>
                                            </div>
                                            <div class="detail-group">
                                                <i class="fas fa-car text-primary"></i>
                                                <div>
                                                    <div class="detail-label">Cars</div>
                                                    <div class="detail-value"><?php echo $booking['item_count']; ?> vehicles</div>
                                                </div>
                                            </div>
                                            <div class="detail-group">
                                                <i class="fas fa-money-bill-wave text-primary"></i>
                                                <div>
                                                    <div class="detail-label">Amount</div>
                                                    <div class="detail-value">RM <?php echo number_format($display_amount, 2); ?></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="booking-footer">
                                            <div class="booking-total">RM <?php echo number_format($display_amount, 2); ?></div>
                                            <div class="booking-actions">
                                                <a href="view_booking.php?id=<?php echo $booking['id']; ?>" class="action-btn btn-view">
                                                    <i class="fas fa-eye me-1"></i> View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="profile-section" class="section-content" style="display: none;">
                    <div class="section-card">
                        <h3 class="section-title mb-4"><i class="fas fa-user-edit"></i> Edit Profile</h3>
                        <form id="profile-form" method="POST" action="update_profile.php">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label"><i class="fas fa-user"></i> Full Name</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label"><i class="fas fa-envelope"></i> Email Address</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label"><i class="fas fa-phone"></i> Phone Number</label>
                                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+60 12-345 6789">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label"><i class="fas fa-id-card"></i> Driver's License</label>
                                        <input type="text" class="form-control" name="license_number" value="<?php echo htmlspecialchars($user['license_number'] ?? ''); ?>" placeholder="D12345678">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-home"></i> Address</label>
                                <textarea class="form-control" name="address" rows="3" placeholder="Enter your full address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit" class="btn-save"><i class="fas fa-save me-2"></i> Save Changes</button>
                        </form>
                    </div>
                </div>

                <div id="bookings-section" class="section-content" style="display: none;">
                    <div class="section-card">
                        <h3 class="section-title mb-4"><i class="fas fa-history"></i> My Bookings</h3>
                        <div id="bookings-content">
                            <div class="text-center py-5">
                                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                <p class="mt-3">Loading bookings...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="security-section" class="section-content" style="display: none;">
                    <div class="section-card">
                        <h3 class="section-title mb-4"><i class="fas fa-lock"></i> Change Password</h3>
                        <form id="password-form" method="POST" action="change_password.php">
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-key"></i> Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-lock"></i> New Password</label>
                                <input type="password" class="form-control" name="new_password" required pattern=".{8,}" title="Password must be at least 8 characters">
                                <small class="text-muted">At least 8 characters</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fas fa-lock"></i> Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn-save"><i class="fas fa-key me-2"></i> Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showSection(sectionId) {
            document.querySelectorAll('.section-content').forEach(section => {
                section.style.display = 'none';
            });
            document.querySelectorAll('.menu-link').forEach(link => {
                link.classList.remove('active');
            });
            
            document.getElementById(`${sectionId}-section`).style.display = 'block';
            event.target.classList.add('active');
            
            if (sectionId === 'bookings') {
                loadAllBookings();
            }
        }

        function loadAllBookings() {
            const container = document.getElementById('bookings-content');
            
            fetch('get_bookings.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.bookings.length > 0) {
                        container.innerHTML = data.bookings.map(booking => {
                            const displayAmount = booking.grand_total ? booking.grand_total : booking.total_amount;
                            const statusClass = 'badge-' + booking.booking_status.toLowerCase();
                            
                            return `
                            <div class="booking-item">
                                <div class="booking-header">
                                    <div class="booking-ref">#${booking.booking_reference}</div>
                                    <span class="booking-badge ${statusClass}">
                                        ${booking.booking_status.charAt(0).toUpperCase() + booking.booking_status.slice(1)}
                                    </span>
                                </div>
                                
                                <div class="booking-details">
                                    <div class="detail-group">
                                        <i class="fas fa-calendar text-primary"></i>
                                        <div>
                                            <div class="detail-label">Booked On</div>
                                            <div class="detail-value">${new Date(booking.created_at).toLocaleDateString()}</div>
                                        </div>
                                    </div>
                                    <div class="detail-group">
                                        <i class="fas fa-car text-primary"></i>
                                        <div>
                                            <div class="detail-label">Cars</div>
                                            <div class="detail-value">${booking.item_count} vehicles</div>
                                        </div>
                                    </div>
                                    <div class="detail-group">
                                        <i class="fas fa-money-bill-wave text-primary"></i>
                                        <div>
                                            <div class="detail-label">Amount</div>
                                            <div class="detail-value">RM ${parseFloat(displayAmount).toFixed(2)}</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="booking-footer">
                                    <div class="booking-total">
                                        RM ${parseFloat(displayAmount).toFixed(2)}
                                    </div>
                                    <div class="booking-actions">
                                        <a href="view_booking.php?id=${booking.id}" class="action-btn btn-view">
                                            <i class="fas fa-eye me-1"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `}).join('');
                    } else {
                        container.innerHTML = `
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h4>No bookings yet</h4>
                                <a href="product_catalogue.php" class="btn-save mt-3">Start Renting Now</a>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    container.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Failed to load bookings. Please try again.
                        </div>
                    `;
                });
        }

        function uploadProfilePicture(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const formData = new FormData();
                formData.append('profile_picture', file);
                
                fetch('upload_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Profile picture updated successfully!');
                        location.reload();
                    } else {
                        alert('Failed to upload: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('An error occurred. Please try again.');
                });
            }
        }

        // 表单提交处理
        document.getElementById('profile-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Profile updated successfully!');
                    location.reload();
                } else {
                    alert('Failed to update profile: ' + data.message);
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
            });
        });

        document.getElementById('password-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            if (formData.get('new_password') !== formData.get('confirm_password')) {
                alert('New passwords do not match!');
                return;
            }
            
            fetch('change_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Password changed successfully!');
                    this.reset();
                } else {
                    alert('Failed to change password: ' + data.message);
                }
            })
            .catch(error => {
                alert('An error occurred. Please try again.');
            });
        });

        // 页面加载时显示仪表板
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash.substring(1);
            if (hash && ['dashboard', 'profile', 'bookings', 'security'].includes(hash)) {
                showSection(hash);
            } else {
                showSection('dashboard');
            }
        });
    </script>
</body>
</html>