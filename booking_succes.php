<?php
session_start();
require_once 'config.php';

// 获取 booking_id
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if ($booking_id <= 0) {
    header('Location: homepage.php');
    exit();
}

// 查询主订单信息
$sql = "SELECT b.*, u.name as user_name, u.email as user_email, u.phone as user_phone
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        WHERE b.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: homepage.php');
    exit();
}

$booking = $result->fetch_assoc();

// ★★★ 修改 1: SQL查询增加 gallery_image (获取最新图片) ★★★
$sql_items = "SELECT bi.*, c.brand, c.model, c.image_url as main_image, c.type, c.seats, c.transmission, c.fuel_type,
              (SELECT image_url FROM car_images WHERE car_id = c.id ORDER BY id ASC LIMIT 1) as gallery_image
              FROM booking_items bi
              LEFT JOIN cars c ON bi.car_id = c.id
              WHERE bi.booking_id = ?
              ORDER BY bi.id";

$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $booking_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();

$booking_items = [];
while ($row = $result_items->fetch_assoc()) {
    // 查询该项目的服务
    $sql_services = "SELECT service_name, service_price, quantity, total_price 
                     FROM booking_services 
                     WHERE booking_item_id = ?";
    $stmt_services = $conn->prepare($sql_services);
    $stmt_services->bind_param("i", $row['id']);
    $stmt_services->execute();
    $result_services = $stmt_services->get_result();

    $row['services'] = [];
    while ($service = $result_services->fetch_assoc()) {
        $row['services'][] = $service;
    }

    $booking_items[] = $row;
}

$booking_date = date('d M Y, h:i A', strtotime($booking['created_at']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - NO 1 Car Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #707070 0%, #757575 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .success-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* Success Header */
        .success-header {
            background: white;
            border-radius: 25px 25px 0 0;
            padding: 60px 40px 40px;
            text-align: center;
            box-shadow: 0 -5px 30px rgba(0, 0, 0, 0.1);
        }

        .success-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #00964e 0%, #00964e 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease-out;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.4);
        }

        .success-icon i {
            font-size: 4rem;
            color: white;
            animation: checkmark 0.8s ease-in-out 0.3s both;
        }

        @keyframes scaleIn {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes checkmark {
            0% { transform: scale(0) rotate(-45deg); opacity: 0; }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }

        .success-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .success-subtitle {
            font-size: 1.2rem;
            color: #7f8c8d;
            margin-bottom: 25px;
        }

        .booking-number {
            display: inline-block;
            background: linear-gradient(135deg, #224bff 0%, #416dff 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }

        /* Receipt Container */
        .receipt-container {
            background: white;
            border-radius: 0 0 25px 25px;
            padding: 0;
            box-shadow: 0 10px 40px rgb(0, 0, 0);
        }

        .receipt-header {
            background: #f8f9fa;
            padding: 30px 40px;
            border-bottom: 3px dashed #e0e0e0;
        }

        .receipt-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #000000;
            margin-bottom: 5px;
        }

        .receipt-date {
            color: #000000;
            font-size: 1rem;
        }

        .receipt-body {
            padding: 40px;
        }

        /* Info Sections */
        .info-section {
            margin-bottom: 35px;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #000000;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000000;
        }

        .info-row {
            display: flex;
            margin-bottom: 12px;
            padding: 10px 0;
        }

        .info-label {
            flex: 0 0 180px;
            font-weight: 600;
            color: #7f8c8d;
        }

        .info-value {
            flex: 1;
            color: #2c3e50;
            font-weight: 600;
        }

        /* Car Cards */
        .car-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border: 2px solid #e0e0e0;
        }

        .car-details {
            display: flex;
            gap: 25px;
            align-items: flex-start;
        }

        .car-image {
            flex: 0 0 200px;
            height: 150px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .car-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .car-info {
            flex: 1;
        }

        .car-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: #000000;
            margin-bottom: 15px;
        }

        .car-specs {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .spec-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        .spec-item i {
            color: #667eea;
        }

        /* Rental Info */
        .rental-info {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            border: 1px solid #e0e0e0;
        }

        .rental-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 0.9rem;
        }

        .rental-label {
            color: #7f8c8d;
        }

        .rental-value {
            font-weight: 600;
            color: #2c3e50;
        }

        /* Services List */
        .services-list {
            margin-top: 10px;
        }

        .service-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 12px;
            background: white;
            border-radius: 8px;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }

        .service-name {
            color: #2c3e50;
            font-weight: 600;
        }

        .service-price {
            color: #000000;
            font-weight: 700;
        }

        /* Price Breakdown */
        .price-breakdown {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 1.05rem;
        }

        .price-label {
            color: #7f8c8d;
            font-weight: 600;
        }

        .price-value {
            color: #2c3e50;
            font-weight: 700;
        }

        .price-total {
            border-top: 3px solid #000000;
            margin-top: 15px;
            padding-top: 20px;
        }

        .price-total .price-label {
            font-size: 1.3rem;
            color: #2c3e50;
        }

        .price-total .price-value {
            font-size: 1.8rem;
            color: #000000;
        }

        /* Important Notes */
        .important-notes {
            background: #fff3cd;
            border-left: 5px solid #ffc107;
            border-radius: 10px;
            padding: 20px 25px;
            margin-bottom: 30px;
        }

        .important-notes h4 {
            color: #856404;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .important-notes ul {
            margin: 0;
            padding-left: 20px;
            color: #856404;
        }

        .important-notes li {
            margin-bottom: 8px;
            line-height: 1.6;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            padding: 30px 40px;
            background: #f8f9fa;
            border-radius: 0 0 25px 25px;
        }

        .btn-action {
            flex: 1;
            padding: 18px 30px;
            border-radius: 15px;
            font-weight: 700;
            font-size: 1.1rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-download {
            background: white;
            color: #667eea;
            border: 3px solid #667eea;
        }

        .btn-download:hover {
            background: #8f8d8d;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-home {
            background: linear-gradient(135deg, #3358ff 0%, #284cff 100%);
            color: white;
            box-shadow: 0 5px 20px rgb(0, 0, 0);
        }

        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(100, 129, 255, 0.82);
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .success-header {
                padding: 40px 20px 30px;
            }

            .success-title {
                font-size: 1.8rem;
            }

            .receipt-body {
                padding: 25px 20px;
            }

            .car-details {
                flex-direction: column;
            }

            .car-image {
                width: 100%;
            }

            .action-buttons {
                flex-direction: column;
                padding: 20px;
            }

            .info-row {
                flex-direction: column;
            }

            .info-label {
                margin-bottom: 5px;
            }
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .action-buttons {
                display: none;
            }

            .success-icon {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="success-title">Booking Confirmed!</h1>
            <p class="success-subtitle">Thank you for choosing NO 1 Car Rental</p>
            <div class="booking-number">
                <i class="fas fa-ticket-alt me-2"></i><?php echo htmlspecialchars($booking['booking_reference']); ?>
            </div>
            <p style="color: #7f8c8d; font-size: 0.95rem;">
                <i class="fas fa-envelope me-2"></i>Confirmation email sent to 
                <strong><?php echo htmlspecialchars($booking['customer_email']); ?></strong>
            </p>
        </div>

        <div class="receipt-container">
            <div class="receipt-header">
                <h2 class="receipt-title"><i class="fas fa-receipt me-2"></i>Booking Receipt</h2>
                <p class="receipt-date">Booked on <?php echo $booking_date; ?></p>
            </div>

            <div class="receipt-body">
                <div class="info-section">
                    <h3 class="section-title"><i class="fas fa-user me-2"></i>Customer Information</h3>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-id-card me-2"></i>Full Name:</div>
                        <div class="info-value"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-envelope me-2"></i>Email:</div>
                        <div class="info-value"><?php echo htmlspecialchars($booking['customer_email']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-phone me-2"></i>Phone:</div>
                        <div class="info-value"><?php echo htmlspecialchars($booking['customer_phone']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-credit-card me-2"></i>Payment Method:</div>
                        <div class="info-value"><?php echo strtoupper(htmlspecialchars($booking['payment_method'])); ?></div>
                    </div>
                </div>

                <div class="info-section">
                    <h3 class="section-title"><i class="fas fa-car me-2"></i>Vehicles Booked (<?php echo count($booking_items); ?>)</h3>

                    <?php foreach ($booking_items as $index => $item): ?>
                    <?php 
                        // ★★★ 修改 2: 图片选择与路径修复 ★★★
                        $img_src = !empty($item['gallery_image']) ? $item['gallery_image'] : $item['main_image'];
                        $img_src = str_replace('../', '', $img_src); // 移除 ../ 因为这里是前台页面
                        if(empty($img_src)) $img_src = 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=400'; // 默认备用图
                    ?>
                    <div class="car-card">
                        <div class="car-details">
                            <div class="car-image">
                                <img src="<?php echo htmlspecialchars($img_src); ?>" 
                                     alt="<?php echo htmlspecialchars($item['car_name']); ?>"
                                     onerror="this.src='https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=400'">
                            </div>
                            <div class="car-info">
                                <div class="car-name"><?php echo htmlspecialchars($item['car_name']); ?></div>
                                <div class="car-specs">
                                    <?php if ($item['type']): ?>
                                    <div class="spec-item">
                                        <i class="fas fa-car"></i>
                                        <span><?php echo htmlspecialchars($item['type']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($item['seats']): ?>
                                    <div class="spec-item">
                                        <i class="fas fa-users"></i>
                                        <span><?php echo htmlspecialchars($item['seats']); ?> Seats</span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($item['transmission']): ?>
                                    <div class="spec-item">
                                        <i class="fas fa-cogs"></i>
                                        <span><?php echo htmlspecialchars($item['transmission']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="rental-info">
                                    <div class="rental-row">
                                        <span class="rental-label"><i class="fas fa-calendar-check me-1"></i>Pick-up:</span>
                                        <span class="rental-value"><?php echo date('d M Y, h:i A', strtotime($item['start_datetime'])); ?></span>
                                    </div>
                                    <div class="rental-row">
                                        <span class="rental-label"><i class="fas fa-calendar-times me-1"></i>Return:</span>
                                        <span class="rental-value"><?php echo date('d M Y, h:i A', strtotime($item['end_datetime'])); ?></span>
                                    </div>
                                    <div class="rental-row">
                                        <span class="rental-label"><i class="fas fa-clock me-1"></i>Duration:</span>
                                        <span class="rental-value"><?php echo $item['duration']; ?> <?php echo $item['rental_type'] === 'hourly' ? 'Hours' : 'Days'; ?></span>
                                    </div>
                                    <div class="rental-row">
                                        <span class="rental-label"><i class="fas fa-map-marker-alt me-1"></i>Pick-up:</span>
                                        <span class="rental-value"><?php echo htmlspecialchars($item['pickup_location']); ?></span>
                                    </div>
                                    <div class="rental-row">
                                        <span class="rental-label"><i class="fas fa-map-marker-alt me-1"></i>Drop-off:</span>
                                        <span class="rental-value"><?php echo htmlspecialchars($item['dropoff_location']); ?></span>
                                    </div>
                                </div>

                                <?php if (!empty($item['services'])): ?>
                                <div class="services-list">
                                    <strong style="font-size: 0.9rem; color: #7f8c8d; display: block; margin-bottom: 8px;">Additional Services:</strong>
                                    <?php foreach ($item['services'] as $service): ?>
                                        <div class="service-item">
                                            <span class="service-name">
                                                <i class="fas fa-check-circle me-1" style="color: #27ae60;"></i>
                                                <?php echo htmlspecialchars($service['service_name']); ?>
                                            </span>
                                            <span class="service-price">RM <?php echo number_format($service['total_price'], 2); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="info-section">
                    <h3 class="section-title"><i class="fas fa-money-bill-wave me-2"></i>Payment Summary</h3>
                    <div class="price-breakdown">
                        <div class="price-row">
                            <div class="price-label">Subtotal:</div>
                            <div class="price-value">RM <?php echo number_format($booking['total_amount'], 2); ?></div>
                        </div>
                        <?php if ($booking['discount_amount'] > 0): ?>
                        <div class="price-row">
                            <div class="price-label">Promo Code (<?php echo htmlspecialchars($booking['promo_code']); ?>):</div>
                            <div class="price-value" style="color: #27ae60;">- RM <?php echo number_format($booking['discount_amount'], 2); ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="price-row">
                            <div class="price-label">Tax (6%):</div>
                            <div class="price-value">RM <?php echo number_format($booking['tax_amount'], 2); ?></div>
                        </div>
                        <div class="price-row">
                            <div class="price-label">Service Fee:</div>
                            <div class="price-value">RM <?php echo number_format($booking['service_fee'], 2); ?></div>
                        </div>
                        <div class="price-row price-total">
                            <div class="price-label">Grand Total Paid:</div>
                            <div class="price-value">RM <?php echo number_format($booking['grand_total'], 2); ?></div>
                        </div>
                    </div>
                </div>

                <div class="important-notes">
                    <h4><i class="fas fa-exclamation-triangle"></i>Important Information</h4>
                    <ul>
                        <li>Please arrive <strong>15 minutes before</strong> your scheduled pick-up time</li>
                        <li>Bring your <strong>valid driver's license</strong> and <strong>ID card</strong></li>
                        <li>Late returns may incur additional charges</li>
                        <li>For changes or cancellations, contact us at <strong>+60 12-345 6789</strong></li>
                    </ul>
                </div>
            </div>

            <div class="action-buttons">
                <button class="btn-action btn-download" onclick="window.print()">
                    <i class="fas fa-download"></i>
                    <span>Download Receipt</span>
                </button>
                <a href="homepage.php" class="btn-action btn-home">
                    <i class="fas fa-home"></i>
                    <span>Return to Homepage</span>
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmt->close();
$stmt_items->close();
$conn->close();
?>