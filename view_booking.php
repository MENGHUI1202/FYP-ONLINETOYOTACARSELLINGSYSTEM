<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$booking_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// 获取预订详情 - 使用grand_total字段
$stmt = $conn->prepare("
    SELECT b.*, 
           COUNT(bi.id) as item_count,
           MIN(bi.start_datetime) as earliest_pickup,
           MAX(bi.end_datetime) as latest_dropoff
    FROM bookings b
    LEFT JOIN booking_items bi ON b.id = bi.booking_id
    WHERE b.id = ? AND b.user_id = ?
    GROUP BY b.id
");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    header('Location: my_account.php');
    exit();
}

// 计算显示的总额（优先使用grand_total）
$display_total = !empty($booking['grand_total']) ? $booking['grand_total'] : $booking['total_amount'];

// 获取预订项目详情
$stmt = $conn->prepare("
    SELECT bi.*
    FROM booking_items bi
    WHERE bi.booking_id = ?
    ORDER BY bi.start_datetime
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 对于每个预订项目，获取其服务
foreach ($booking_items as &$item) {
    $item_id = $item['id'];
    
    // 获取该项目的服务
    $service_stmt = $conn->prepare("
        SELECT service_name, service_price
        FROM booking_services
        WHERE booking_item_id = ?
    ");
    $service_stmt->bind_param("i", $item_id);
    $service_stmt->execute();
    $services_result = $service_stmt->get_result();
    
    $services_list = [];
    $services_total = 0;
    while ($service = $services_result->fetch_assoc()) {
        $services_list[] = [
            'name' => $service['service_name'],
            'price' => $service['service_price']
        ];
        $services_total += $service['service_price'];
    }
    $service_stmt->close();
    
    $item['services_list'] = $services_list;
    $item['services_total'] = $services_total;
    
    // 计算每辆车的总价（包括服务）
    $item['car_total'] = $item['base_price'] + $services_total;
}

// 获取用户信息
$user_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_info = $user_result->fetch_assoc();
$user_name = $user_info['name'] ?? 'Account';
$user_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - NO 1 Car Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .navbar {
            background: rgba(40, 40, 60, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px 0;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 1px;
        }
        .nav-link {
            color: #e0e0e0 !important;
            font-size: 1.1rem;
            margin: 0 8px;
            transition: all 0.3s;
            padding: 10px 20px !important;
            border-radius: 10px;
        }
        .nav-link:hover {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 40px 0;
            margin-bottom: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 30px 50px;
        }

        .overview-card, .items-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .booking-ref {
            font-size: 1.8rem;
            font-weight: 800;
            color: #2c3e50;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .booking-ref-badge {
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: 600;
        }
        .badge-confirmed {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        .badge-cancelled {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }

        .car-item {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }
        .car-item:hover {
            border-color: #667eea;
            background: #f8f9ff;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .car-header {
            display: flex;
            gap: 25px;
            margin-bottom: 25px;
            align-items: center;
        }

        .car-image {
            width: 200px;
            height: 140px;
            border-radius: 12px;
            object-fit: cover;
            border: 3px solid #e9ecef;
        }

        .car-name {
            font-size: 1.6rem;
            font-weight: 800;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .car-badge {
            display: inline-block;
            padding: 6px 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-right: 10px;
        }
        
        /* 总体价格卡片样式 */
        .grand-total-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 15px;
            padding: 30px;
            margin-top: 40px;
            color: white;
            text-align: center;
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
        }
        
        .grand-total-label {
            font-size: 1.3rem;
            opacity: 0.9;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .grand-total-amount {
            font-size: 3.5rem;
            font-weight: 800;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            margin-bottom: 10px;
        }
        
        .grand-total-note {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 15px;
        }
        
        .payment-status-badge {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        /* 美化按钮样式 */
        .action-buttons {
            display: flex;
            gap: 20px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 3px solid #e9ecef;
        }

        .action-btn {
            padding: 16px 40px;
            border-radius: 15px;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }

        .action-btn i {
            font-size: 1.3rem;
        }

        .btn-back {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-back:hover {
            transform: translateY(-5px) translateX(-5px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-print {
            background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%);
            color: white;
        }
        .btn-print:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(54, 209, 220, 0.4);
        }

        /* 响应式设计 */
        @media (max-width: 992px) {
            .action-buttons {
                flex-direction: column;
            }
            .action-btn {
                justify-content: center;
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 0 20px 30px;
            }
            .overview-card, .items-container {
                padding: 25px;
            }
            .page-title {
                font-size: 2rem;
            }
            .car-header {
                flex-direction: column;
                text-align: center;
            }
            .car-image {
                width: 100%;
                height: 200px;
            }
            .grand-total-amount {
                font-size: 2.8rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="product_catalogue.php">
                <i class="fas fa-car me-2"></i>NO 1 Car Rental
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="product_catalogue.php">
                            <i class="fas fa-th-large"></i> Catalogue
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_account.php">
                            <i class="fas fa-user-circle"></i> 
                            <?php echo htmlspecialchars($user_name); ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1 class="page-title"><i class="fas fa-receipt me-3"></i>BOOKING DETAILS</h1>
            <p class="page-subtitle">Reference: <?php echo htmlspecialchars($booking['booking_reference']); ?></p>
        </div>
    </div>

    <div class="main-container">
        <!-- Booking Overview -->
        <div class="overview-card">
            <div class="booking-ref">
                <span>#<?php echo htmlspecialchars($booking['booking_reference']); ?></span>
                <span class="booking-ref-badge badge-<?php echo $booking['booking_status']; ?>">
                    <?php echo strtoupper($booking['booking_status']); ?>
                </span>
            </div>

            <div class="row">
                <div class="col-md-3 col-6 mb-4">
                    <div class="overview-label">Booking Date</div>
                    <div class="overview-value">
                        <?php echo date('d/m/Y', strtotime($booking['created_at'])); ?>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="overview-label">Customer</div>
                    <div class="overview-value">
                        <?php echo htmlspecialchars($booking['customer_name']); ?>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="overview-label">Total Cars</div>
                    <div class="overview-value">
                        <?php echo $booking['item_count']; ?> vehicle<?php echo $booking['item_count'] > 1 ? 's' : ''; ?>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="overview-label">Payment Method</div>
                    <div class="overview-value">
                        <?php echo strtoupper($booking['payment_method']); ?>
                    </div>
                </div>
            </div>

            <?php if ($booking['booking_status'] === 'confirmed' && $booking['payment_status'] === 'paid'): ?>
            <div class="alert alert-success mt-3" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Payment confirmed!</strong> Your booking is active and ready for pickup.
            </div>
            <?php elseif ($booking['booking_status'] === 'cancelled'): ?>
            <div class="alert alert-danger mt-3" role="alert">
                <i class="fas fa-times-circle me-2"></i>
                <strong>Booking cancelled.</strong> This booking is no longer active.
            </div>
            <?php endif; ?>
        </div>

        <!-- Booking Items -->
        <div class="items-container">
            <h3 class="section-title mb-4">
                <i class="fas fa-car"></i>
                Rental Details
            </h3>

            <?php if (empty($booking_items)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No rental items found for this booking.
                </div>
            <?php else: ?>
                <?php foreach ($booking_items as $index => $item): ?>
                    <div class="car-item">
                        <div class="car-header">
                            <img src="<?php echo !empty($item['car_image']) ? htmlspecialchars($item['car_image']) : 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=400'; ?>" 
                                 alt="<?php echo htmlspecialchars($item['car_name'] ?? 'Car Image'); ?>" 
                                 class="car-image">
                            <div class="car-info">
                                <h4 class="car-name"><?php echo htmlspecialchars($item['car_name'] ?? 'Unknown Car'); ?></h4>
                                <div>
                                    <span class="car-badge">
                                        <i class="fas fa-<?php echo ($item['rental_type'] ?? 'daily') === 'hourly' ? 'clock' : 'calendar-day'; ?>"></i>
                                        <?php echo ($item['rental_type'] ?? 'daily') === 'hourly' ? 'Hourly Rental' : 'Daily Rental'; ?>
                                    </span>
                                    <?php if (isset($item['insurance_level']) && $item['insurance_level'] !== 'basic'): ?>
                                    <span class="car-badge" style="background: #e74c3c;">
                                        <i class="fas fa-shield-alt"></i>
                                        <?php echo ucfirst($item['insurance_level']); ?> Insurance
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 col-6 mb-3">
                                <div class="overview-label">Pickup Date & Time</div>
                                <div class="overview-value">
                                    <?php echo isset($item['start_datetime']) ? date('d/m/Y H:i', strtotime($item['start_datetime'])) : 'Not set'; ?>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="overview-label">Dropoff Date & Time</div>
                                <div class="overview-value">
                                    <?php echo isset($item['end_datetime']) ? date('d/m/Y H:i', strtotime($item['end_datetime'])) : 'Not set'; ?>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="overview-label">Pickup Location</div>
                                <div class="overview-value">
                                    <?php echo htmlspecialchars($item['pickup_location'] ?? 'Not set'); ?>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="overview-label">Dropoff Location</div>
                                <div class="overview-value">
                                    <?php echo htmlspecialchars($item['dropoff_location'] ?? 'Not set'); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Services -->
                        <?php if (!empty($item['services_list'])): ?>
                            <div class="mt-4">
                                <div class="overview-label mb-2">Selected Services</div>
                                <div class="row">
                                    <?php foreach ($item['services_list'] as $service): ?>
                                        <div class="col-md-4 col-6 mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-check text-success me-2"></i>
                                                <span><?php echo htmlspecialchars($service['name']); ?> - RM <?php echo number_format($service['price'], 2); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- 总体支付金额显示 - 替换了原来的每辆车价格显示 -->
            <div class="grand-total-card">
                <div class="grand-total-label">Total Amount Paid</div>
                <div class="grand-total-amount">RM <?php echo number_format($display_total, 2); ?></div>
                <div class="grand-total-note">Final amount including all taxes, fees, and services</div>
                <div class="payment-status-badge">
                    Payment Status: <?php echo strtoupper($booking['payment_status']); ?>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button onclick="window.history.back()" class="action-btn btn-back">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Account</span>
            </button>
            
            <button onclick="window.print()" class="action-btn btn-print">
                <i class="fas fa-print"></i>
                <span>Print Booking Details</span>
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 打印优化
        window.addEventListener('beforeprint', () => {
            document.querySelector('.navbar').style.display = 'none';
            document.querySelector('.page-header').style.marginTop = '0';
            document.querySelector('.action-buttons').style.display = 'none';
        });

        window.addEventListener('afterprint', () => {
            document.querySelector('.navbar').style.display = '';
            document.querySelector('.page-header').style.marginTop = '';
            document.querySelector('.action-buttons').style.display = 'flex';
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>