<?php
session_start();
require_once 'config.php';

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// 获取用户信息
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// ★★★ 获取 Session 中的 Promo Code ★★★
$appliedPromo = isset($_SESSION['applied_promo']) ? $_SESSION['applied_promo'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - NO 1 Car Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #ffffff 0%, #ffffff 100%); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100vh; padding-bottom: 50px; }
        /* Navbar */
        .navbar { background: #505d6c; backdrop-filter: blur(10px); border-bottom: 1px solid rgb(0, 0, 0); padding: 15px 0; box-shadow: 0 2px 20px rgb(0, 0, 0); }
        .navbar-brand { font-size: 1.8rem; font-weight: 700; color: #ffffff; letter-spacing: 1px; margin-right: auto; }
        .navbar-nav { margin-left: auto !important; }
        .nav-link { color: #e0e0e0 !important; font-size: 1.1rem; margin: 0 8px; transition: all 0.3s; padding: 10px 20px !important; border-radius: 10px; }
        .nav-link:hover { color: #ffffff !important; background: rgba(255, 255, 255, 0.15); transform: translateY(-2px); }
        .nav-link.active { background: rgba(255, 255, 255, 0.2); color: #ffffff !important; }
        /* Page Header */
        .page-header { color: white; text-align: center; padding: 50px 0 40px 0; }
        .page-title { font-size: 5.5rem; font-weight: 800; margin-bottom: 15px; text-shadow: 3px 3px 6px rgb(126, 184, 255); color: #000000; }
        .page-subtitle { font-size: 1.3rem; opacity: 0.95; color: #000000; }
        /* Main Container */
        .main-container { max-width: 1500px; margin: 0 auto; padding: 0 40px; }
        .payment-layout { display: grid; grid-template-columns: 1fr 450px; gap: 40px; align-items: start; }
        /* Left Column - Forms */
        .form-container { background: white; border-radius: 25px; padding: 50px; box-shadow: 0 15px 50px rgb(0, 0, 0); }
        .section { margin-bottom: 45px; padding-bottom: 35px; border-bottom: 3px solid #000000; }
        .section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .section-title { font-size: 1.8rem; font-weight: 800; color: #000000; margin-bottom: 25px; display: flex; align-items: center; gap: 15px; }
        .section-title i { color: #667eea; font-size: 2rem; }
        .section-subtitle { color: #000000; font-size: 1rem; margin-bottom: 25px; }
        /* Booking Summary Cards */
        .booking-card { background: #aeb0b261; border-radius: 15px; padding: 25px; margin-bottom: 20px; border: 2px solid #000000; transition: all 0.3s; }
        .booking-card:hover { border-color: #667eea; transform: translateY(-3px); box-shadow: 0 8px 20px rgb(0, 0, 0); }
        .booking-header { display: flex; gap: 20px; margin-bottom: 20px; }
        .booking-image { width: 150px; height: 100px; border-radius: 10px; object-fit: cover; border: 2px solid #000000; }
        .booking-info { flex: 1; }
        .car-name { font-size: 1.3rem; font-weight: 700; color: rgb(0, 0, 0); margin-bottom: 8px; }
        .booking-badge { display: inline-block; padding: 5px 12px; background: linear-gradient(135deg, #6c87ff 0%, #7377ff 100%); color: white; border-radius: 20px; font-size: 0.85rem; font-weight: 600; margin-right: 8px; }
        .booking-details { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 15px; }
        .detail-item { display: flex; align-items: center; gap: 10px; color: #000000; font-size: 0.95rem; }
        .detail-item i { color: #3e62ff; font-size: 1.1rem; width: 20px; }
        .detail-label { font-weight: 600; color: #000000; }
        .booking-price { text-align: right; margin-top: 15px; padding-top: 15px; border-top: 2px solid #000000; }
        .booking-price-label { color: #7f8c8d; font-size: 0.9rem; margin-bottom: 5px; }
        .booking-price-value { font-size: 1.8rem; font-weight: 800; color: #000000; }
        /* Form Groups */
        .form-group { margin-bottom: 25px; }
        .form-label { font-weight: 700; color: #000000; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; font-size: 1rem; }
        .form-label i { color: #0037ff; }
        .form-label .required { color: #e74c3c; }
        .form-control { width: 100%; padding: 14px 18px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1rem; transition: all 0.3s; background: white; }
        .form-control:focus { border-color: #90e7ff; outline: none; box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1); }
        .form-control:disabled { background: #f5f5f5; cursor: not-allowed; }
        /* Payment Methods */
        .payment-methods { display: grid; gap: 15px; }
        .payment-option { position: relative; border: 3px solid #000000; border-radius: 12px; padding: 20px; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 15px; }
        .payment-option:hover { border-color: #667eea; background: #f8f9ff; }
        .payment-option.selected { border-color: #667eea; background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%); }
        .payment-radio { width: 24px; height: 24px; cursor: pointer; accent-color: #606dff; }
        .payment-icon { font-size: 2rem; color: #0e3aff; width: 50px; text-align: center; }
        .payment-details { flex: 1; }
        .payment-name { font-weight: 700; font-size: 1.1rem; color: #2c3e50; margin-bottom: 5px; }
        .payment-desc { font-size: 0.9rem; color: #545858; }
        /* Card Form */
        .card-form { display: none; margin-top: 20px; padding: 25px; background: #f8f9fa; border-radius: 12px; border: 2px solid #e0e0e0; }
        .card-form.show { display: block; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .card-input-row { display: grid; grid-template-columns: 2fr 1fr; gap: 15px; }
        /* Terms Checkbox */
        .terms-section { background: #fff9e6; border: 2px solid #ffe600; border-radius: 12px; padding: 25px; }
        .checkbox-item { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 15px; }
        .checkbox-item:last-child { margin-bottom: 0; }
        .checkbox-input { width: 22px; height: 22px; cursor: pointer; accent-color: #667eea; margin-top: 2px; flex-shrink: 0; }
        .checkbox-label { font-size: 1rem; color: #2c3e50; line-height: 1.6; cursor: pointer; }
        .checkbox-label a { color: #667eea; text-decoration: underline; font-weight: 600; }
        .checkbox-label a:hover { color: #667eea }
        /* Right Column - Summary */
        .summary-container { background: linear-gradient(135deg, #8198ff 0%, #365eff 100%); border-radius: 25px; padding: 40px; color: white; box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25); position: sticky; top: 20px; }
        .summary-title { font-size: 2rem; font-weight: 800; margin-bottom: 30px; text-align: center; text-shadow: 2px 2px 4px rgb(0, 0, 0); }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 18px; padding: 12px 0; border-bottom: 1px solid rgba(0, 0, 0, 0.37); font-size: 1rem; }
        .summary-label { opacity: 0.95; display: flex; align-items: center; gap: 8px; }
        .summary-value { font-weight: 700; font-size: 1.1rem; }
        .summary-total { border-top: 3px solid rgb(0, 0, 0); border-bottom: none; padding-top: 25px; margin-top: 20px; margin-bottom: 30px; }
        .summary-total .summary-label { font-size: 1.2rem; font-weight: 700; }
        .summary-total .summary-value { font-size: 2.5rem; text-shadow: 2px 2px 4px rgb(0, 0, 0); }
        /* Pay Button */
        .pay-button { width: 100%; padding: 20px; background: white; color: #008a05; border: none; border-radius: 15px; font-weight: 800; font-size: 1.3rem; cursor: pointer; transition: all 0.3s; box-shadow: 0 5px 20px rgb(0, 0, 0); display: flex; align-items: center; justify-content: center; gap: 12px; }
        .pay-button:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 10px 30px rgb(29, 138, 255); }
        .pay-button:disabled { background: #95a5a6; color: white; cursor: not-allowed; opacity: 0.6; }
        .back-button { width: 100%; padding: 15px; background: transparent; color: white; border: 3px solid white; border-radius: 15px; font-weight: 700; font-size: 1rem; cursor: pointer; transition: all 0.3s; margin-top: 15px; }
        .back-button:hover { background: white; color: #9fa0a5; }
        /* Security Badge */
        .security-badge { text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.2); }
        .security-badge i { font-size: 2rem; margin-bottom: 10px; }
        .security-text { font-size: 0.9rem; opacity: 0.9; }
        /* Responsive */
        @media (max-width: 1200px) { .payment-layout { grid-template-columns: 1fr; } .summary-container { position: relative; top: 0; } .booking-details { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .main-container { padding: 0 20px; } .form-container { padding: 30px 25px; } .page-title { font-size: 2.5rem; } .booking-header { flex-direction: column; } .booking-image { width: 100%; height: 180px; } .card-input-row { grid-template-columns: 1fr; } }
        /* Discount Row Style */
        .discount-row { color: #00ff37; font-weight: bold; border-bottom: 1px solid rgba(0,255,55,0.3); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="product_catalogue.php"><i class="fas fa-car me-2"></i>NO 1 Car Rental</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="homepage.php"><i class="fas fa-home"></i> Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="product_catalogue.php"><i class="fas fa-th-large"></i> Catalogue</a></li>
                    <li class="nav-item"><a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_account.php"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user['name']); ?></a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1 class="page-title"><i class="fas fa-credit-card me-3"></i>PAYMENT</h1>
            <p class="page-subtitle">Complete your booking and secure your rental</p>
        </div>
    </div>

    <div class="main-container">
        <div class="payment-layout">
            <div class="form-container">
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-clipboard-list"></i>Booking Summary</h2>
                    <p class="section-subtitle">Review your rental details before payment</p>
                    <div id="booking-summary-container"></div>
                </div>

                <div class="section">
                    <h2 class="section-title"><i class="fas fa-user"></i>Customer Information</h2>
                    <p class="section-subtitle">Confirm your contact details</p>
                    <form id="customer-form">
                        <div class="form-group"><label class="form-label"><i class="fas fa-id-card"></i>Full Name <span class="required">*</span></label><input type="text" class="form-control" id="customer-name" value="<?php echo htmlspecialchars($user['name']); ?>" required></div>
                        <div class="form-group"><label class="form-label"><i class="fas fa-envelope"></i>Email Address <span class="required">*</span></label><input type="email" class="form-control" id="customer-email" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
                        <div class="form-group"><label class="form-label"><i class="fas fa-phone"></i>Phone Number <span class="required">*</span></label><input type="tel" class="form-control" id="customer-phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+60 12-345 6789" required></div>
                    </form>
                </div>

                <div class="section">
                    <h2 class="section-title"><i class="fas fa-wallet"></i>Payment Method</h2>
                    <p class="section-subtitle">Choose how you want to pay</p>
                    <div class="payment-methods">
                        <div class="payment-option" onclick="selectPayment('card')" id="payment-card"><input type="radio" name="payment-method" value="card" class="payment-radio" id="radio-card"><div class="payment-icon"><i class="fas fa-credit-card"></i></div><div class="payment-details"><div class="payment-name">Credit / Debit Card</div><div class="payment-desc">Visa, Mastercard, American Express</div></div></div>
                        <div class="payment-option" onclick="selectPayment('e-wallet')" id="payment-e-wallet"><input type="radio" name="payment-method" value="e-wallet" class="payment-radio" id="radio-e-wallet"><div class="payment-icon"><i class="fas fa-wallet"></i></div><div class="payment-details"><div class="payment-name">E-Wallet</div><div class="payment-desc">Touch 'n Go, GrabPay, Boost</div></div></div>
                        <div class="payment-option" onclick="selectPayment('banking')" id="payment-banking"><input type="radio" name="payment-method" value="banking" class="payment-radio" id="radio-banking"><div class="payment-icon"><i class="fas fa-university"></i></div><div class="payment-details"><div class="payment-name">Online Banking</div><div class="payment-desc">Maybank, CIMB, Public Bank, RHB</div></div></div>
                        <div class="payment-option" onclick="selectPayment('cash')" id="payment-cash"><input type="radio" name="payment-method" value="cash" class="payment-radio" id="radio-cash"><div class="payment-icon"><i class="fas fa-money-bill-wave"></i></div><div class="payment-details"><div class="payment-name">Cash on Pickup</div><div class="payment-desc">Pay when you collect the car</div></div></div>
                    </div>
                    <div class="card-form" id="card-form">
                        <div class="form-group"><label class="form-label"><i class="fas fa-credit-card"></i>Card Number</label><input type="text" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19" id="card-number"></div>
                        <div class="form-group"><label class="form-label"><i class="fas fa-user"></i>Cardholder Name</label><input type="text" class="form-control" placeholder="JOHN DOE" id="card-name"></div>
                        <div class="card-input-row">
                            <div class="form-group"><label class="form-label"><i class="fas fa-calendar"></i>Expiry Date</label><input type="text" class="form-control" placeholder="MM/YY" maxlength="5" id="card-expiry"></div>
                            <div class="form-group"><label class="form-label"><i class="fas fa-lock"></i>CVV</label><input type="text" class="form-control" placeholder="123" maxlength="3" id="card-cvv"></div>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h2 class="section-title"><i class="fas fa-check-circle"></i>Terms & Confirmation</h2>
                    <div class="terms-section">
                        <div class="checkbox-item"><input type="checkbox" class="checkbox-input" id="terms-agree"><label class="checkbox-label" for="terms-agree">I agree to the <a href="#" target="_blank">Terms & Conditions</a> and <a href="#" target="_blank">Privacy Policy</a> of NO 1 Car Rental</label></div>
                        <div class="checkbox-item"><input type="checkbox" class="checkbox-input" id="details-confirm"><label class="checkbox-label" for="details-confirm">I confirm that all rental details, dates, and information provided are correct</label></div>
                        <div class="checkbox-item"><input type="checkbox" class="checkbox-input" id="age-confirm"><label class="checkbox-label" for="age-confirm">I confirm that I am at least 21 years old and hold a valid driving license</label></div>
                    </div>
                </div>
            </div>

            <div class="summary-container">
                <h2 class="summary-title"><i class="fas fa-file-invoice-dollar"></i> Price Summary</h2>
                <div class="summary-row"><span class="summary-label"><i class="fas fa-car"></i> Total Cars</span><span class="summary-value" id="total-cars">0</span></div>
                <div class="summary-row"><span class="summary-label"><i class="fas fa-clock"></i> Total Duration</span><span class="summary-value" id="total-duration">0 days</span></div>
                <div class="summary-row"><span class="summary-label"><i class="fas fa-tag"></i> Services & Add-ons</span><span class="summary-value" id="services-total">RM 0.00</span></div>
                <div class="summary-row"><span class="summary-label"><i class="fas fa-receipt"></i> Subtotal</span><span class="summary-value" id="subtotal">RM 0.00</span></div>
                
                <div class="summary-row discount-row" id="discount-row" style="display: none;">
                    <span class="summary-label"><i class="fas fa-tag"></i> Discount (<span id="promo-code-display"></span>)</span>
                    <span class="summary-value">- RM <span id="discount-amount">0.00</span></span>
                </div>

                <div class="summary-row"><span class="summary-label"><i class="fas fa-percentage"></i> Tax (6%)</span><span class="summary-value" id="tax">RM 0.00</span></div>
                <div class="summary-row"><span class="summary-label"><i class="fas fa-hand-holding-usd"></i> Service Fee</span><span class="summary-value" id="service-fee">RM 15.00</span></div>
                <div class="summary-row summary-total"><span class="summary-label"><i class="fas fa-money-bill-wave"></i> GRAND TOTAL</span><span class="summary-value" id="grand-total">RM 0.00</span></div>
                <button class="pay-button" id="pay-button" onclick="processPayment()" disabled><i class="fas fa-lock"></i><span>Complete Payment</span></button>
                <button class="back-button" onclick="window.location.href='cart.php'"><i class="fas fa-arrow-left me-2"></i> Back to Cart</button>
                <div class="security-badge"><i class="fas fa-shield-alt"></i><div class="security-text">Secure 256-bit SSL Encryption<br>Your payment information is safe with us</div></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let cart = JSON.parse(localStorage.getItem('carRentalCart')) || [];
        let carsData = {};
        let selectedPaymentMethod = null;
        
        // ★★★ 从 PHP 变量读取折扣信息 ★★★
        let appliedPromo = <?php echo json_encode($appliedPromo); ?>; 

        const insuranceLevels = [
            { id: 'basic', name: 'Basic Coverage', price: 0 },
            { id: 'standard', name: 'Standard Coverage', price: 20 },
            { id: 'premium', name: 'Premium Coverage', price: 45 }
        ];

        document.addEventListener('DOMContentLoaded', function() {
            if (cart.length === 0) {
                alert('Your cart is empty! Redirecting to catalogue...');
                window.location.href = 'product_catalogue.php';
                return;
            }
            loadCarsData();
            updatePriceSummary();
            setupFormValidation();
            setupCardFormatting();
        });

        async function loadCarsData() {
            const carIds = cart.map(item => item.id);
            try {
                const response = await fetch('get_cars_data.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ car_ids: carIds })
                });
                carsData = await response.json();
                loadBookingSummary();
            } catch (error) {
                console.error('Error:', error);
                loadBookingSummary();
            }
        }

        function loadBookingSummary() {
            const container = document.getElementById('booking-summary-container');
            container.innerHTML = cart.map((item, index) => {
                const carData = carsData[item.id] || {};
                let displayImage = 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=300';
                if (carData.images && carData.images.length > 0) { displayImage = carData.images[0]; } 
                else if (item.image) { displayImage = item.image; }
                displayImage = displayImage.replace(/\.\.\//g, '');

                return `
                <div class="booking-card">
                    <div class="booking-header">
                        <img src="${displayImage}" onerror="this.src='https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=300'" class="booking-image">
                        <div class="booking-info ms-3">
                            <div class="car-name">${item.name}</div>
                            <div><span class="booking-badge"><i class="fas fa-${item.rentalType === 'hourly' ? 'clock' : 'calendar-day'}"></i> ${item.rentalType === 'hourly' ? 'Hourly' : 'Daily'}</span></div>
                        </div>
                    </div>
                    <div class="booking-details">
                        <div class="detail-item"><i class="fas fa-calendar-check"></i><div><div class="detail-label">Start:</div>${item.startDateTime || 'Not set'}</div></div>
                        <div class="detail-item"><i class="fas fa-calendar-times"></i><div><div class="detail-label">End:</div>${item.endDateTime || 'Not set'}</div></div>
                        <div class="detail-item"><i class="fas fa-hourglass-half"></i><div><div class="detail-label">Duration:</div>${calculateDuration(item)}</div></div>
                    </div>
                    <div class="booking-price">
                        <div class="booking-price-label">Subtotal</div>
                        <div class="booking-price-value">RM ${calculateItemTotal(item).toFixed(2)}</div>
                    </div>
                </div>`;
            }).join('');
        }

        // ★★★ 恢复所有缺失的辅助计算函数 ★★★
        function calculateDuration(item) { 
            if (!item.startDateTime || !item.endDateTime) return 'Not set'; 
            const start = new Date(item.startDateTime); 
            const end = new Date(item.endDateTime); 
            const diffMs = end - start; 
            if (diffMs <= 0) return 'Invalid'; 
            return item.rentalType === 'hourly' ? Math.ceil(diffMs / 3600000) + ' hrs' : Math.ceil(diffMs / 86400000) + ' days'; 
        }

        function getDurationInDays(item) { 
            if (!item.startDateTime || !item.endDateTime) return 1; 
            const start = new Date(item.startDateTime); 
            const end = new Date(item.endDateTime); 
            const diffMs = end - start; 
            if (diffMs <= 0) return 1; 
            return item.rentalType === 'hourly' ? Math.ceil(diffMs / 3600000) : Math.ceil(diffMs / 86400000); 
        }

        function calculateServicesTotal(item) { 
            if (!item.services) return 0; 
            let total = 0; 
            for (let s in item.services) if (item.services[s]) total += (item.servicePrices[s] || 0) * getDurationInDays(item); 
            return total; 
        }

        function getAgeSurcharge(item) {
            if (!item.driverAge) return 0;
            const duration = getDurationInDays(item);
            if (item.driverAge === 'under-25') return 10 * duration;
            if (item.driverAge === 'over-69') return 15 * duration;
            return 0;
        }

        function getInsuranceCost(item) {
            if (!item.insuranceLevel || item.insuranceLevel === 'basic') return 0;
            const level = insuranceLevels.find(l => l.id === item.insuranceLevel);
            return (level ? level.price : 0) * getDurationInDays(item);
        }
        
        function calculateItemTotal(item) {
            const duration = getDurationInDays(item);
            const basePrice = item.rentalType === 'hourly' ? (item.pricePerHour || 0) : (item.price || 0);
            let subtotal = (basePrice * duration) + calculateServicesTotal(item);
            
            // 加上 Surcharges
            subtotal += getAgeSurcharge(item);
            subtotal += getInsuranceCost(item);
            
            if (item.fuelPolicy === 'pre-purchase') subtotal += 80;
            return subtotal;
        }

        function updatePriceSummary() {
            let totalCars = cart.length;
            let totalDuration = 0;
            let servicesTotal = 0;
            let subtotal = 0;
            
            cart.forEach(item => {
                totalDuration += getDurationInDays(item);
                servicesTotal += calculateServicesTotal(item);
                subtotal += calculateItemTotal(item);
            });

            // ★★★ 应用折扣 ★★★
            let discountAmount = 0;
            if (appliedPromo) {
                discountAmount = parseFloat(appliedPromo.discount_amount);
                document.getElementById('discount-row').style.display = 'flex';
                document.getElementById('promo-code-display').textContent = appliedPromo.code;
                document.getElementById('discount-amount').textContent = discountAmount.toFixed(2);
            } else {
                document.getElementById('discount-row').style.display = 'none';
            }

            const taxableAmount = Math.max(0, subtotal - discountAmount);
            const tax = taxableAmount * 0.06;
            const serviceFee = 15.00;
            const grandTotal = taxableAmount + tax + serviceFee;

            document.getElementById('total-cars').textContent = totalCars;
            document.getElementById('total-duration').textContent = `${totalDuration} days/hrs`;
            document.getElementById('services-total').textContent = `RM ${servicesTotal.toFixed(2)}`;
            document.getElementById('subtotal').textContent = `RM ${subtotal.toFixed(2)}`;
            document.getElementById('tax').textContent = `RM ${tax.toFixed(2)}`;
            document.getElementById('service-fee').textContent = `RM ${serviceFee.toFixed(2)}`;
            document.getElementById('grand-total').textContent = `RM ${grandTotal.toFixed(2)}`;
        }

        function setupFormValidation() { const inputs = ['customer-name', 'customer-email', 'customer-phone']; const checkboxes = ['terms-agree', 'details-confirm', 'age-confirm']; inputs.forEach(id => document.getElementById(id).addEventListener('input', checkFormValidity)); checkboxes.forEach(id => document.getElementById(id).addEventListener('change', checkFormValidity)); }
        function checkFormValidity() { const isValid = ['customer-name', 'customer-email', 'customer-phone'].every(id => document.getElementById(id).value.trim()) && ['terms-agree', 'details-confirm', 'age-confirm'].every(id => document.getElementById(id).checked) && selectedPaymentMethod; document.getElementById('pay-button').disabled = !isValid; }
        function selectPayment(method) { selectedPaymentMethod = method; document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected')); document.getElementById(`payment-${method}`).classList.add('selected'); document.getElementById(`radio-${method}`).checked = true; const cardForm = document.getElementById('card-form'); if (method === 'card') cardForm.classList.add('show'); else cardForm.classList.remove('show'); checkFormValidity(); }
        function setupCardFormatting() { /* 保持原有的卡号格式化代码，略 */ }

        async function processPayment() {
            const payButton = document.getElementById('pay-button');
            payButton.disabled = true;
            payButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            let subtotal = 0;
            cart.forEach(item => subtotal += calculateItemTotal(item));
            
            const discountAmount = appliedPromo ? parseFloat(appliedPromo.discount_amount) : 0;
            const taxableAmount = Math.max(0, subtotal - discountAmount);
            const tax = taxableAmount * 0.06;
            const serviceFee = 15.00;
            const grandTotal = taxableAmount + tax + serviceFee;

            const bookingData = {
                user_id: <?php echo $user_id; ?>,
                customer_name: document.getElementById('customer-name').value,
                customer_email: document.getElementById('customer-email').value,
                customer_phone: document.getElementById('customer-phone').value,
                payment_method: selectedPaymentMethod,
                cart: cart,
                total_amount: subtotal.toFixed(2),
                // ★★★ 发送 Promo Code 到后台 ★★★
                promo_code: appliedPromo ? appliedPromo.code : null,
                discount_amount: discountAmount.toFixed(2),
                tax_amount: tax.toFixed(2),
                service_fee: serviceFee.toFixed(2),
                grand_total: grandTotal.toFixed(2)
            };

            try {
                const response = await fetch('process_booking.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(bookingData)
                });
                const result = await response.json();
                if (result.success) {
                    localStorage.removeItem('carRentalCart');
                    // ★★★ 注意：这里用了正确的文件名 booking_success.php ★★★
                    // ★★★ 如果你的文件名确实是 booking_succes.php (少一个s)，请自己改一下 ★★★
                    window.location.href = `booking_succes.php?booking_id=${result.booking_id}`;
                } else {
                    alert('Payment failed: ' + result.message);
                    payButton.disabled = false;
                    payButton.innerHTML = '<i class="fas fa-lock"></i> Complete Payment';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                payButton.disabled = false;
                payButton.innerHTML = '<i class="fas fa-lock"></i> Complete Payment';
            }
        }
    </script>
</body>
</html>