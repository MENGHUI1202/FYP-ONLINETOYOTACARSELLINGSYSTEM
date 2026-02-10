<?php
session_start();
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - NO 1 Car Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        body { background: linear-gradient(135deg, #ffffff 0%, #ffffff 100%); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100vh; padding-bottom: 50px; }
        .navbar { background: #505d6c; backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding: 15px 0; box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1); }
        .navbar-brand { font-size: 1.8rem; font-weight: 700; color: #ffffff; letter-spacing: 1px; margin-right: auto; }
        .navbar-nav { margin-left: auto !important; }
        .nav-link { color: #e0e0e0 !important; font-size: 1.1rem; margin: 0 8px; transition: all 0.3s; padding: 10px 20px !important; border-radius: 10px; }
        .nav-link:hover { color: #9bdeff !important; background: rgba(255, 255, 255, 0.15); transform: translateY(-2px); }
        .nav-link.active { background: rgba(255, 255, 255, 0.2); color: #9bdeff !important; }
        .nav-link i { font-size: 1.4rem; margin-right: 8px; }
        .navbar-toggler { border: 2px solid rgba(255, 255, 255, 0.3); padding: 8px 12px; border-radius: 8px; transition: all 0.3s; }
        .navbar-collapse { transition: all 0.3s ease-in-out; }
        @media (min-width: 992px) { .navbar-collapse { display: flex !important; } .navbar-toggler { display: none; } }
        @media (max-width: 991px) { .navbar-collapse { background: rgba(40, 40, 40, 0.98); margin-top: 15px; padding: 20px; border-radius: 15px; border: 1px solid rgba(255, 255, 255, 0.1); } .nav-link { display: block; } }

        .page-header { color: white; text-align: center; padding: 50px 0 40px 0; }
        .page-title { color: #000000; font-size: 5.5rem; font-weight: 800; margin-bottom: 15px; text-shadow: 3px 3px 6px rgb(0, 102, 255); }
        .page-subtitle { font-size: 1.3rem; opacity: 0.95; color: #000000; }

        .main-container { max-width: 1800px; margin: 0 auto; padding: 0 40px; }
        .cart-content-wrapper { display: flex; align-items: stretch; gap: 40px; }
        .cart-column { flex: 1; }
        .summary-column { width: 450px; flex-shrink: 0; }
        .cart-container { background: white; border-radius: 25px; padding: 60px; box-shadow: 0 15px 50px rgba(0, 0, 0, 0.63); height: 100%; }

        .cart-item { background: #f3f3f3; border-radius: 20px; padding: 40px; padding-top: 70px; margin-bottom: 35px; border: 3px solid #000000da; transition: all 0.3s ease; position: relative; }
        .cart-item:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgb(0, 0, 0); border-color: #7dc2ff; }
        .remove-btn { position: absolute; top: 10px; right: 10px; background: #b81200; color: white; border: none; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; box-shadow: 0 5px 15px rgb(255, 0, 0); z-index: 100; }
        .remove-btn:hover { background: #c0392b; transform: scale(1.15); }

        .car-top-section { display: flex; gap: 30px; margin-bottom: 25px; align-items: stretch; }
        .car-carousel-wrapper { flex: 0 0 60%; height: 380px; }
        .car-carousel { width: 100%; height: 100%; position: relative; background: #f5f5f5; overflow: hidden; border-radius: 15px; box-shadow: 0 5px 20px rgb(0, 0, 0); }
        .car-carousel .carousel-inner, .car-carousel .carousel-item, .car-carousel .carousel-item img { height: 100%; width: 100%; object-fit: cover; }
        
        .car-description-area { flex: 0 0 40%; height: 380px; display: flex; flex-direction: column; padding: 20px; background: white; border-radius: 12px; border: 2px solid #000000; overflow-y: auto; }
        .car-description-text { color: #000000; font-size: 1rem; line-height: 1.8; text-align: justify; }
        .car-name { color: #000000; font-size: 2rem; font-weight: 800; margin-bottom: 20px; }

        .car-specs { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; margin-bottom: 25px; }
        .spec-badge { text-align: center; padding: 12px 8px; font-size: 0.9rem; color: #2c3e50; background: white; border-radius: 10px; border: 2px solid #000000; display: flex; flex-direction: column; align-items: center; gap: 5px; }
        .spec-badge i { color: #3b5ffd; font-size: 1.2rem; }

        .rental-services-row { display: flex; gap: 25px; margin-top: 20px; }
        .rental-column, .services-column { flex: 1; background: white; border-radius: 15px; padding: 25px; border: 2px solid #000000; }
        .section-title { color: #000000; font-size: 1.2rem; font-weight: 700; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 3px solid #002fff; }

        .rental-type-selector { display: flex; gap: 15px; margin-bottom: 20px; }
        .rental-type-btn { flex: 1; padding: 12px; border: 3px solid #e0e0e0; border-radius: 10px; background: white; cursor: pointer; font-weight: 700; font-size: 1rem; transition: 0.3s; }
        .rental-type-btn.active { border-color: #a0b2ff; background: #aabaff; color: white; }

        .datetime-group { margin-bottom: 15px; }
        .datetime-label { font-weight: 700; color: #000000; margin-bottom: 8px; display: block; }
        .datetime-input, .location-select, .info-input, .info-select { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1rem; margin-bottom: 12px; }
        .duration-display { background: linear-gradient(135deg, #5cb8ff 0%, #5cb8ff 100%); color: white; padding: 15px; border-radius: 10px; text-align: center; margin: 15px 0; font-weight: 700; }

        .location-selector { background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 15px 0; border: 2px solid #e0e0e0; }
        .confirm-date-btn { width: 100%; padding: 12px; background: #00ba4d; color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; margin-top: 10px; }
        .confirm-date-btn:disabled { background: #7e7e7e; cursor: not-allowed; }
        .edit-date-btn { width: 100%; padding: 12px; background: #4ddb34; color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; margin-top: 10px; display: none; }
        .edit-date-btn.show { display: block; }

        .service-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; margin-bottom: 10px; background: #f8f9fa; border-radius: 8px; border: 2px solid #000000; transition: 0.3s; }
        .service-item:hover { border-color: #002fff; }
        .service-checkbox { width: 20px; height: 20px; cursor: pointer; }
        .service-price { font-weight: 700; color: #000000; }

        .additional-info-section, .price-section { background: white; border-radius: 15px; padding: 25px; border: 2px solid #000000; margin-top: 25px; }
        .info-warning { color: #ff0000; font-size: 0.9rem; margin-top: 5px; display: none; }
        .info-warning.show { display: block; }

        .price-row { display: flex; justify-content: space-between; margin-bottom: 12px; padding: 8px 0; font-size: 1rem; color: #000000; font-weight: 600; }
        .price-subtotal { border-top: 3px solid #000000; padding-top: 15px; margin-top: 10px; }
        .price-subtotal .price-value { color: #46b800; font-size: 1.5rem; font-weight: 700; }

        .fuel-policy-section { background: #f8f9fa; border-radius: 12px; padding: 20px; margin-top: 20px; border: 2px solid #000000; }
        .fuel-option { display: flex; align-items: flex-start; margin-bottom: 15px; cursor: pointer; padding: 12px; border-radius: 8px; transition: 0.3s; }
        .fuel-option:hover { background: white; }

        .cart-summary { background: linear-gradient(135deg, #669bea 0%, #669bea 100%); border-radius: 25px; padding: 40px; color: white; box-shadow: 0 15px 40px rgba(0, 0, 0, 0.74); height: 100%; display: flex; flex-direction: column; }
        .summary-title { font-size: 2.2rem; font-weight: 800; margin-bottom: 30px; text-align: center; text-shadow: 2px 2px 4px rgb(0, 0, 0); }
        
        .promo-code-section { background: rgba(255, 255, 255, 0.15); border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid rgba(0, 0, 0, 0.2); }
        .promo-input-group { display: flex; gap: 10px; }
        .promo-input { flex: 1; padding: 12px; border: 2px solid rgba(255,255,255,0.3); border-radius: 10px; background: rgba(255,255,255,0.9); text-transform: uppercase; }
        .promo-apply-btn { padding: 12px 25px; background: white; color: #009d05; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; }
        .promo-success { margin-top: 10px; padding: 10px 15px; background: rgba(0, 255, 106, 0.67); border-radius: 8px; display: none; position: relative; }
        .promo-success.show { display: block; }
        .promo-remove-btn { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: white; cursor: pointer; font-size: 1.2rem; }

        .car-summary-item { background: rgba(255, 255, 255, 0); border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid rgb(0, 0, 0); }
        .car-summary-name { font-size: 1.2rem; font-weight: 700; margin-bottom: 15px; border-bottom: 2px solid rgba(255, 255, 255, 0.3); padding-bottom: 10px; }
        .summary-detail { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.95rem; }
        .car-subtotal { display: flex; justify-content: space-between; margin-top: 12px; padding-top: 12px; border-top: 2px solid rgb(0, 0, 0); font-weight: 700; font-size: 1.1rem; color: #00ff37; }
        
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid rgba(0,0,0,0); }
        .summary-total { border-top: 3px solid rgba(0, 0, 0, 0.16); padding-top: 20px; margin-top: 15px; margin-bottom: 20px; }
        .summary-total .summary-value { font-size: 2.2rem; text-shadow: 2px 2px 4px rgb(0, 0, 0); font-weight: 800; }

        .checkout-btn { width: 100%; padding: 20px; background: white; color: #000000; border: none; border-radius: 15px; font-weight: 800; font-size: 1.25rem; cursor: pointer; transition: all 0.3s; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2); margin-bottom: 15px; }
        .checkout-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0, 255, 0, 0.3); }
        .continue-shopping { width: 100%; padding: 15px; background: transparent; color: white; border: 3px solid white; border-radius: 15px; font-weight: 700; font-size: 1.05rem; cursor: pointer; }
        .continue-shopping:hover { background: white; color: #667eea; }

        .empty-cart { text-align: center; padding: 80px 40px; background: white; border-radius: 25px; box-shadow: 0 15px 50px rgba(0, 0, 0, 0.25); max-width: 600px; margin: 0 auto; }
        .empty-cart-icon { font-size: 8rem; background: linear-gradient(135deg, #002fff 0%, #9d00ff 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 30px; animation: float 3s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-20px); } }
        .browse-cars-btn { display: inline-flex; align-items: center; gap: 12px; padding: 18px 40px; background: linear-gradient(135deg, #6896ff 0%, #6896ff 100%); color: white; border-radius: 50px; font-weight: 700; font-size: 1.2rem; text-decoration: none; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4); }
        .browse-cars-btn:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0, 47, 255, 0.6); color: white; }

        .cart-count { background: #ff0000; color: white; font-size: 0.9rem; padding: 3px 10px; border-radius: 15px; margin-left: 5px; }

        .insurance-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 1000; align-items: center; justify-content: center; }
        .insurance-modal.show { display: flex; }
        .insurance-modal-content { background: white; border-radius: 20px; padding: 40px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5); animation: modalSlideIn 0.3s ease; }
        @keyframes modalSlideIn { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .insurance-modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #667eea; }
        .insurance-modal-close { background: #e74c3c; color: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 1.2rem; }
        .insurance-option { border: 3px solid #e0e0e0; border-radius: 15px; padding: 25px; margin-bottom: 20px; cursor: pointer; position: relative; }
        .insurance-option.selected { border-color: #667eea; background: #f8f9ff; }
        .insurance-option input { position: absolute; top: 25px; right: 25px; width: 24px; height: 24px; }
        .insurance-features li { padding: 8px 0; color: #555; display: flex; align-items: center; }
        .insurance-features li:before { content: "✓"; color: #27ae60; font-weight: 700; margin-right: 10px; }
        .insurance-confirm-btn { width: 100%; padding: 18px; background: #667eea; color: white; border: none; border-radius: 12px; font-weight: 700; font-size: 1.2rem; cursor: pointer; margin-top: 20px; }

        @media (max-width: 1200px) { .cart-content-wrapper { flex-direction: column; } .summary-column { width: 100%; } .car-specs { grid-template-columns: repeat(3, 1fr); } .car-top-section { flex-direction: column; } .rental-services-row { flex-direction: column; } }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="homepage.php"><i class="fas fa-car me-2"></i>NO 1 Car Rental</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="homepage.php"><i class="fas fa-home"></i> Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="product_catalogue.php"><i class="fas fa-th-large"></i> Catalogue</a></li>
                    <li class="nav-item"><a class="nav-link active" href="cart.php"><i class="fas fa-shopping-cart fa-lg"></i> Cart <span class="cart-count" id="nav-cart-count">0</span></a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="my_account.php"><i class="fas fa-user-circle"></i> <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span></a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php"><i class="fas fa-user-circle fa-lg"></i> My Account</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1 class="page-title"><i class="fas fa-shopping-cart me-3"></i>YOUR CART</h1>
            <p class="page-subtitle">Review your rental selections and proceed to checkout</p>
        </div>
    </div>

    <div class="main-container">
        <div id="empty-cart-section" class="empty-cart" style="display: none;">
            <div class="empty-cart-icon"><i class="fas fa-shopping-cart"></i></div>
            <h3>Your cart is empty</h3>
            <p class="text-muted">Start adding some amazing cars to your rental journey!</p>
            <a href="product_catalogue.php" class="browse-cars-btn"><i class="fas fa-car"></i><span>Browse Cars</span><i class="fas fa-arrow-right"></i></a>
        </div>

        <div id="cart-content-section" class="cart-content-wrapper" style="display: none;">
            <div class="cart-column"><div class="cart-container"><div id="cart-items-container"></div></div></div>
            <div class="summary-column">
                <div class="cart-summary">
                    <h2 class="summary-title">Order Summary</h2>
                    <div id="detailed-summary"></div>
                    <div class="summary-row summary-total">
                        <span class="summary-label"><i class="fas fa-money-bill-wave me-2"></i>GRAND TOTAL</span>
                        <span class="summary-value" id="grand-total">RM 0.00</span>
                    </div>

                    <div class="promo-code-section">
                        <label class="promo-label"><i class="fas fa-ticket-alt me-2"></i>Have a Promo Code?</label>
                        <div class="promo-input-group">
                            <input type="text" class="promo-input" id="promo-code-input" placeholder="Enter code">
                            <button class="promo-apply-btn" onclick="applyPromoCode()"><i class="fas fa-check me-2"></i>Apply</button>
                        </div>
                        <div class="promo-success" id="promo-success">
                            <i class="fas fa-check-circle me-2"></i><span id="promo-message"></span>
                            <button class="promo-remove-btn" onclick="removePromoCode()"><i class="fas fa-times"></i></button>
                        </div>
                    </div>

                    <button class="checkout-btn" onclick="window.location.href='payment.php'"><i class="fas fa-check-circle me-2"></i>Proceed to Checkout</button>
                    <button class="continue-shopping" onclick="window.location.href='product_catalogue.php'"><i class="fas fa-arrow-left me-2"></i>Continue Shopping</button>
                </div>
            </div>
        </div>
    </div>

    <div class="insurance-modal" id="insurance-modal">
        <div class="insurance-modal-content">
            <div class="insurance-modal-header">
                <h3 class="insurance-modal-title"><i class="fas fa-shield-alt me-3"></i>Choose Insurance Level</h3>
                <button class="insurance-modal-close" onclick="closeInsuranceModal()">×</button>
            </div>
            <div id="insurance-options-container"></div>
            <button class="insurance-confirm-btn" onclick="confirmInsuranceLevel()"><i class="fas fa-check me-2"></i>Confirm Selection</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        let cart = JSON.parse(localStorage.getItem('carRentalCart')) || [];
        let appliedPromo = null; 
        let carsData = {};
        let currentInsuranceCartIndex = null;

        const availableServices = [
            { id: 'gps', name: 'GPS Navigation', icon: 'map-marked-alt', price: 15 },
            { id: 'childSeat', name: 'Child Seat', icon: 'baby', price: 10 },
            { id: 'additionalDriver', name: 'Additional Driver', icon: 'user-plus', price: 20 },
            { id: 'insurance', name: 'Full Insurance Coverage', icon: 'shield-alt', price: 30 },
            { id: 'dashcam', name: 'Dashcam', icon: 'video', price: 12 },
            { id: 'wifi', name: 'Portable WiFi Hotspot', icon: 'wifi', price: 8 },
            { id: 'tollCard', name: 'Touch n Go Card', icon: 'credit-card', price: 5 },
            { id: 'roofRack', name: 'Roof Rack', icon: 'warehouse', price: 25 },
            { id: 'bluetooth', name: 'Bluetooth Speaker', icon: 'bluetooth', price: 6 },
            { id: 'cooler', name: 'Portable Cooler Box', icon: 'snowflake', price: 15 }
        ];

        const insuranceLevels = [
            { id: 'basic', name: 'Basic Coverage', price: 0, features: ['Third-party liability', 'Basic damage coverage', 'RM 3,000 excess'] },
            { id: 'standard', name: 'Standard Coverage', price: 20, features: ['All Basic features', 'Theft protection', 'RM 1,500 excess', '24/7 roadside assistance'] },
            { id: 'premium', name: 'Premium Coverage', price: 45, features: ['All Standard features', 'Zero excess', 'Personal accident cover up to RM 100,000', 'Windscreen & tyre protection', 'Priority support'] }
        ];

        const locationsByState = {
            'Selangor': ['KLIA Airport - Terminal 1', 'KLIA Airport - Terminal 2', 'KL Sentral Station', 'Petaling Jaya Office - SS2', 'Subang Airport - Terminal 3', 'One Utama Shopping Centre', 'Mid Valley Megamall', 'Sunway Pyramid Mall', 'Shah Alam City Centre', 'Klang Sentral Bus Terminal'],
            'Kuala Lumpur': ['KLCC - Suria Mall', 'Bukit Bintang - Pavilion KL', 'KL Sentral - Nu Sentral', 'Bangsar Shopping Centre', 'The Gardens Mall', 'Berjaya Times Square', 'Chow Kit Market Area', 'Titiwangsa LRT Station', 'Jalan Alor Food Street', 'Chinatown - Petaling Street'],
            'Penang': ['Penang International Airport', 'Komtar Tower - Georgetown', 'Gurney Plaza Shopping Mall', 'Queensbay Mall', 'Bayan Lepas Industrial Zone', 'Georgetown UNESCO Heritage Area', 'Bukit Jambul Complex', 'Prangin Mall', 'Penang Sentral Bus Terminal', 'Butterworth Ferry Terminal'],
            'Johor': ['Johor Bahru Sentral', 'Senai International Airport', 'City Square Shopping Centre', 'Johor Premium Outlets', 'Paradigm Mall JB', 'Toppen Shopping Centre', 'Larkin Sentral Bus Terminal', 'AEON Tebrau City', 'Mount Austin Business District', 'Danga Bay Waterfront'],
            'Melaka': ['Melaka Sentral Bus Terminal', 'Dataran Pahlawan Shopping Mall', 'Jonker Street Heritage Area', 'AEON Bandaraya Melaka', 'Hatten Square Shopping Mall', 'Melaka International Airport', 'Mahkota Parade Shopping Centre', 'Ayer Keroh Toll Plaza', 'Melaka Gateway Cruise Terminal', 'Klebang Beach Coastal Road']
        };

        document.addEventListener('DOMContentLoaded', function() { loadCarsData(); });

        async function loadCarsData() {
            if (cart.length === 0) { updateCartDisplay(); return; }
            const carIds = cart.map(item => item.id);
            try {
                const response = await fetch('get_cars_data.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ car_ids: carIds })
                });
                carsData = await response.json();
                updateCartDisplay();
            } catch (error) { console.error('Error:', error); updateCartDisplay(); }
        }

        function updateNavCartCount() { document.getElementById('nav-cart-count').textContent = cart.length; }
        function getLocationOptions(state) { return locationsByState[state] || []; }

        function updateCartDisplay() {
            const container = document.getElementById('cart-items-container');
            const emptySection = document.getElementById('empty-cart-section');
            const contentSection = document.getElementById('cart-content-section');
            updateNavCartCount();

            if (cart.length === 0) {
                emptySection.style.display = 'block';
                contentSection.style.display = 'none';
                return;
            }
            emptySection.style.display = 'none';
            contentSection.style.display = 'flex';

            container.innerHTML = cart.map((item, index) => {
                const carData = carsData[item.id] || {};
                let rawImages = carData.images || [item.image || 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=900'];
                const images = rawImages.map(img => img ? img.replace('../', '') : '');
                const description = carData.description || 'No description available.';
                const isConfirmed = item.dateConfirmed || false;
                const sameLocation = item.sameLocation !== false;
                const pickupState = item.pickupState || 'Selangor';
                const dropoffState = item.dropoffState || 'Selangor';
                const pickupLocations = getLocationOptions(pickupState);
                const dropoffLocations = getLocationOptions(dropoffState);

                return `
                <div class="cart-item" id="cart-item-${index}">
                    <button class="remove-btn" onclick="removeFromCart(${index})"><i class="fas fa-trash-alt"></i></button>
                    <div class="car-top-section">
                        <div class="car-carousel-wrapper">
                            <div id="carCarousel${index}" class="carousel slide car-carousel" data-bs-ride="carousel">
                                ${images.length > 1 ? `<div class="carousel-indicators">${images.map((img, imgIndex) => `<button type="button" data-bs-target="#carCarousel${index}" data-bs-slide-to="${imgIndex}" ${imgIndex === 0 ? 'class="active"' : ''}></button>`).join('')}</div>` : ''}
                                <div class="carousel-inner">${images.map((img, imgIndex) => `<div class="carousel-item ${imgIndex === 0 ? 'active' : ''}"><img src="${img}" class="d-block w-100" alt="${item.name}"></div>`).join('')}</div>
                                ${images.length > 1 ? `<button class="carousel-control-prev" type="button" data-bs-target="#carCarousel${index}" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button><button class="carousel-control-next" type="button" data-bs-target="#carCarousel${index}" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>` : ''}
                            </div>
                        </div>
                        <div class="car-description-area"><div class="car-description-text">${description}</div></div>
                    </div>

                    <div class="car-specs">
                        ${['car', 'cogs', 'users', 'gas-pump', 'tachometer-alt', 'rocket'].map((icon, i) => {
                            const labels = [carData.type || 'SUV', carData.transmission || 'Automatic', (carData.seats || '5') + ' Seats', carData.fuel_type || 'Gasoline', (carData.horsepower || '120') + ' HP', (carData.acceleration || '8.5') + 's'];
                            return `<div class="spec-badge"><i class="fas fa-${icon}"></i><span>${labels[i]}</span></div>`;
                        }).join('')}
                    </div>

                    <h3 class="car-name">${item.name}</h3>

                    <div class="rental-services-row">
                        <div class="rental-column">
                            <div class="section-title"><i class="fas fa-calendar-alt me-2"></i>Rental Details</div>
                            <div class="rental-type-selector">
                                <button class="rental-type-btn ${item.rentalType === 'hourly' ? 'active' : ''}" onclick="changeRentalType(${index}, 'hourly')" ${isConfirmed ? 'disabled' : ''}><i class="fas fa-clock me-2"></i>Hourly</button>
                                <button class="rental-type-btn ${item.rentalType !== 'hourly' ? 'active' : ''}" onclick="changeRentalType(${index}, 'daily')" ${isConfirmed ? 'disabled' : ''}><i class="fas fa-calendar-day me-2"></i>Daily</button>
                            </div>
                            <div class="datetime-group"><label class="datetime-label"><i class="fas fa-calendar-check me-2"></i>Start Date & Time</label><input type="text" class="datetime-input" id="start-datetime-${index}" value="${item.startDateTime || ''}" ${isConfirmed ? 'disabled' : ''}></div>
                            <div class="datetime-group"><label class="datetime-label"><i class="fas fa-calendar-times me-2"></i>End Date & Time</label><input type="text" class="datetime-input" id="end-datetime-${index}" value="${item.endDateTime || ''}" ${isConfirmed ? 'disabled' : ''}></div>
                            <div class="duration-display" id="duration-${index}"><i class="fas fa-hourglass-half me-2"></i>Duration: ${calculateDuration(item)}</div>
                            <div class="location-selector">
                                <label class="location-label"><i class="fas fa-map-marked-alt me-2"></i>Pick-up State</label>
                                <select class="location-select" id="pickup-state-${index}" ${isConfirmed ? 'disabled' : ''} onchange="updateStateSelection(${index}, 'pickup', this.value)">${Object.keys(locationsByState).map(state => `<option value="${state}" ${pickupState === state ? 'selected' : ''}>${state}</option>`).join('')}</select>
                                <label class="location-label"><i class="fas fa-map-marker-alt me-2"></i>Pick-up Location</label>
                                <select class="location-select" id="pickup-location-${index}" ${isConfirmed ? 'disabled' : ''} onchange="updateLocation(${index}, 'pickup', this.value)">${pickupLocations.map(loc => `<option value="${loc}" ${item.pickupLocation === loc ? 'selected' : ''}>${loc}</option>`).join('')}</select>
                                <div class="same-location-checkbox"><input type="checkbox" id="same-location-${index}" ${sameLocation ? 'checked' : ''} ${isConfirmed ? 'disabled' : ''} onchange="toggleSameLocation(${index})"><label for="same-location-${index}">Same state and location for drop-off</label></div>
                                <div id="dropoff-section-${index}" style="${sameLocation ? 'display: none;' : ''}">
                                    <label class="location-label" style="margin-top: 15px;"><i class="fas fa-map-marked-alt me-2"></i>Drop-off State</label>
                                    <select class="location-select" id="dropoff-state-${index}" ${isConfirmed ? 'disabled' : ''} onchange="updateStateSelection(${index}, 'dropoff', this.value)">${Object.keys(locationsByState).map(state => `<option value="${state}" ${dropoffState === state ? 'selected' : ''}>${state}</option>`).join('')}</select>
                                    <label class="location-label"><i class="fas fa-map-marker-alt me-2"></i>Drop-off Location</label>
                                    <select class="location-select" id="dropoff-location-${index}" ${isConfirmed ? 'disabled' : ''} onchange="updateLocation(${index}, 'dropoff', this.value)">${dropoffLocations.map(loc => `<option value="${loc}" ${item.dropoffLocation === loc ? 'selected' : ''}>${loc}</option>`).join('')}</select>
                                </div>
                            </div>
                            <button class="confirm-date-btn" id="confirm-btn-${index}" onclick="confirmDates(${index})" ${isConfirmed ? 'disabled' : ''}><i class="fas fa-check me-2"></i>${isConfirmed ? 'Dates Confirmed ✓' : 'Confirm Dates'}</button>
                            <button class="edit-date-btn ${isConfirmed ? 'show' : ''}" id="edit-btn-${index}" onclick="editDates(${index})"><i class="fas fa-edit me-2"></i>Edit Dates</button>
                        </div>
                        <div class="services-column">
                            <div class="section-title"><i class="fas fa-plus-square me-2"></i>Optional Services</div>
                            ${availableServices.map(service => {
                                const duration = getDurationInDays(item);
                                const totalPrice = service.price * duration;
                                return `<div class="service-item ${isConfirmed ? 'disabled' : ''}"><input type="checkbox" class="service-checkbox" id="${service.id}-${index}" ${item.services?.[service.id] ? 'checked' : ''} ${isConfirmed ? 'disabled' : ''} onchange="toggleService(${index}, '${service.id}', ${service.price})"><div class="service-info"><div class="service-name"><i class="fas fa-${service.icon} me-2"></i>${service.name}</div></div><span class="service-price">RM ${totalPrice.toFixed(2)}</span></div>`;
                            }).join('')}
                        </div>
                    </div>

                    <div class="additional-info-section">
                        <div class="section-title"><i class="fas fa-user-check me-2"></i>Driver Information</div>
                        <div class="info-group">
                            <label class="info-label"><i class="fas fa-id-card me-2"></i>Driver Age Group</label>
                            <select class="info-select" id="driver-age-${index}" onchange="updateDriverAge(${index}, this.value)">
                                <option value="25-69" ${item.driverAge === '25-69' ? 'selected' : ''}>25-69 years</option>
                                <option value="under-25" ${item.driverAge === 'under-25' ? 'selected' : ''}>Under 25 years</option>
                                <option value="over-69" ${item.driverAge === 'over-69' ? 'selected' : ''}>Over 69 years</option>
                            </select>
                            <div class="info-warning ${(item.driverAge === 'under-25' || item.driverAge === 'over-69') ? 'show' : ''}" id="age-warning-${index}"><i class="fas fa-exclamation-triangle me-2"></i>${item.driverAge === 'under-25' ? 'Additional RM 10/day surcharge applies for drivers under 25' : item.driverAge === 'over-69' ? 'Additional RM 15/day surcharge applies for drivers over 69' : ''}</div>
                        </div>
                        <div class="info-group">
                            <label class="info-label"><i class="fas fa-phone me-2"></i>Emergency Contact (Optional)</label>
                            <input type="text" class="info-input" placeholder="Name" value="${item.emergencyContact?.name || ''}" onchange="updateEmergencyContact(${index}, 'name', this.value)">
                            <input type="tel" class="info-input" placeholder="Phone Number" value="${item.emergencyContact?.phone || ''}" onchange="updateEmergencyContact(${index}, 'phone', this.value)">
                        </div>
                    </div>

                    <div class="price-section">
                        <div class="price-row"><span class="price-label">Base Rate (${item.rentalType === 'hourly' ? 'Hourly' : 'Daily'})</span><span class="price-value">RM ${item.rentalType === 'hourly' ? (item.pricePerHour || 0).toFixed(2) : (item.price || 0).toFixed(2)}</span></div>
                        <div class="price-row"><span class="price-label">Duration</span><span class="price-value">${calculateDuration(item)}</span></div>
                        <div class="price-row"><span class="price-label">Services</span><span class="price-value">RM ${calculateServicesTotal(item).toFixed(2)}</span></div>
                        ${getAgeSurcharge(item) > 0 ? `<div class="price-row"><span class="price-label">Age Surcharge</span><span class="price-value">RM ${getAgeSurcharge(item).toFixed(2)}</span></div>` : ''}
                        ${item.insuranceLevel && item.insuranceLevel !== 'basic' ? `<div class="price-row"><span class="price-label">Insurance Upgrade</span><span class="price-value">RM ${getInsuranceCost(item).toFixed(2)}</span></div>` : ''}
                        <div class="price-row price-subtotal"><span class="price-label">Subtotal</span><span class="price-value">RM ${calculateItemTotal(item).toFixed(2)}</span></div>
                    </div>

                    <div class="fuel-policy-section">
                        <div class="section-title"><i class="fas fa-gas-pump me-2"></i>Fuel Policy</div>
                        <div class="fuel-option"><input type="radio" name="fuel-policy-${index}" value="same-to-same" ${item.fuelPolicy !== 'pre-purchase' ? 'checked' : ''} onchange="updateFuelPolicy(${index}, 'same-to-same')"><div class="fuel-option-content"><div class="fuel-option-title">Same-to-Same</div><div class="fuel-option-desc">Pick up with full tank, return with full tank</div></div><div class="fuel-option-price">Free</div></div>
                        <div class="fuel-option"><input type="radio" name="fuel-policy-${index}" value="pre-purchase" ${item.fuelPolicy === 'pre-purchase' ? 'checked' : ''} onchange="updateFuelPolicy(${index}, 'pre-purchase')"><div class="fuel-option-content"><div class="fuel-option-title">Pre-Purchase Fuel</div><div class="fuel-option-desc">Pay now, return empty. Convenient option!</div></div><div class="fuel-option-price">+RM 80.00</div></div>
                    </div>
                </div>`;
            }).join('');

            cart.forEach((item, index) => { if (!item.dateConfirmed) initDateTimePicker(index); });
            updateDetailedSummary();
        }

        // ... [此处保留了你原有的辅助函数，如 updateStateSelection, updateLocation 等，未修改逻辑] ...
        function updateStateSelection(index, type, state) { if (type === 'pickup') { cart[index].pickupState = state; cart[index].pickupLocation = getLocationOptions(state)[0]; if (cart[index].sameLocation !== false) { cart[index].dropoffState = state; cart[index].dropoffLocation = getLocationOptions(state)[0]; } } else { cart[index].dropoffState = state; cart[index].dropoffLocation = getLocationOptions(state)[0]; } localStorage.setItem('carRentalCart', JSON.stringify(cart)); updateCartDisplay(); }
        function updateLocation(index, type, value) { if (type === 'pickup') { cart[index].pickupLocation = value; if (cart[index].sameLocation !== false) { cart[index].dropoffLocation = value; } } else { cart[index].dropoffLocation = value; } localStorage.setItem('carRentalCart', JSON.stringify(cart)); updateDetailedSummary(); }
        function toggleSameLocation(index) { const checkbox = document.getElementById(`same-location-${index}`); const dropoffSection = document.getElementById(`dropoff-section-${index}`); cart[index].sameLocation = checkbox.checked; if (checkbox.checked) { cart[index].dropoffState = cart[index].pickupState || 'Selangor'; cart[index].dropoffLocation = cart[index].pickupLocation || getLocationOptions(cart[index].pickupState)[0]; dropoffSection.style.display = 'none'; } else { dropoffSection.style.display = 'block'; } localStorage.setItem('carRentalCart', JSON.stringify(cart)); updateDetailedSummary(); }
        function initDateTimePicker(index) { flatpickr(`#start-datetime-${index}`, { enableTime: true, dateFormat: "Y-m-d H:i", minDate: "today", onChange: function(selectedDates, dateStr) { cart[index].startDateTime = dateStr; localStorage.setItem('carRentalCart', JSON.stringify(cart)); updateDurationDisplay(index); updateCartDisplay(); } }); flatpickr(`#end-datetime-${index}`, { enableTime: true, dateFormat: "Y-m-d H:i", minDate: "today", onChange: function(selectedDates, dateStr) { cart[index].endDateTime = dateStr; localStorage.setItem('carRentalCart', JSON.stringify(cart)); updateDurationDisplay(index); updateCartDisplay(); } }); }
        function updateDriverAge(index, value) { cart[index].driverAge = value; const warning = document.getElementById(`age-warning-${index}`); if (value === 'under-25') { warning.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Additional RM 10/day surcharge applies for drivers under 25'; warning.classList.add('show'); } else if (value === 'over-69') { warning.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Additional RM 15/day surcharge applies for drivers over 69'; warning.classList.add('show'); } else { warning.classList.remove('show'); } localStorage.setItem('carRentalCart', JSON.stringify(cart)); updateCartDisplay(); }
        function updateEmergencyContact(index, field, value) { if (!cart[index].emergencyContact) { cart[index].emergencyContact = {}; } cart[index].emergencyContact[field] = value; localStorage.setItem('carRentalCart', JSON.stringify(cart)); }
        function updateFuelPolicy(index, policy) { cart[index].fuelPolicy = policy; localStorage.setItem('carRentalCart', JSON.stringify(cart)); updateCartDisplay(); }
        function getAgeSurcharge(item) { if (!item.driverAge) return 0; const duration = getDurationInDays(item); if (item.driverAge === 'under-25') return 10 * duration; if (item.driverAge === 'over-69') return 15 * duration; return 0; }
        function getInsuranceCost(item) { if (!item.insuranceLevel || item.insuranceLevel === 'basic') return 0; const level = insuranceLevels.find(l => l.id === item.insuranceLevel); if (!level) return 0; return level.price * getDurationInDays(item); }
        function confirmDates(index) { if (!cart[index].startDateTime || !cart[index].endDateTime) { showNotification('Please select start and end dates', 'warning'); return; } cart[index].dateConfirmed = true; if (!cart[index].pickupState) { cart[index].pickupState = 'Selangor'; } if (!cart[index].pickupLocation) { cart[index].pickupLocation = getLocationOptions(cart[index].pickupState)[0]; } if (!cart[index].dropoffState) { cart[index].dropoffState = cart[index].pickupState; } if (!cart[index].dropoffLocation) { cart[index].dropoffLocation = cart[index].pickupLocation; } localStorage.setItem('carRentalCart', JSON.stringify(cart)); updateCartDisplay(); showNotification('Dates confirmed!', 'success'); }
        function editDates(index) { cart[index].dateConfirmed = false; localStorage.setItem('carRentalCart', JSON.stringify(cart)); updateCartDisplay(); showNotification('You can now edit dates', 'info'); }
        function changeRentalType(index, type) { cart[index].rentalType = type; localStorage.setItem('carRentalCart', JSON.stringify(cart)); updateCartDisplay(); }
        function toggleService(index, serviceName, price) { if (!cart[index].services) cart[index].services = {}; if (!cart[index].servicePrices) cart[index].servicePrices = {}; const wasChecked = cart[index].services[serviceName]; cart[index].services[serviceName] = !wasChecked; cart[index].servicePrices[serviceName] = price; if (serviceName === 'insurance' && !wasChecked) { currentInsuranceCartIndex = index; showInsuranceModal(index); } else if (serviceName === 'insurance' && wasChecked) { delete cart[index].insuranceLevel; } localStorage.setItem('carRentalCart', JSON.stringify(cart)); updateCartDisplay(); }
        function showInsuranceModal(index) { const modal = document.getElementById('insurance-modal'); const container = document.getElementById('insurance-options-container'); const currentLevel = cart[index].insuranceLevel || 'basic'; container.innerHTML = insuranceLevels.map(level => `<div class="insurance-option ${currentLevel === level.id ? 'selected' : ''}" onclick="selectInsuranceLevel('${level.id}')"><input type="radio" name="insurance-level" value="${level.id}" ${currentLevel === level.id ? 'checked' : ''}><div class="insurance-option-name">${level.name}</div><div class="insurance-option-price">${level.price === 0 ? 'Included' : '+RM ' + level.price + '/day'}</div><ul class="insurance-features">${level.features.map(feature => `<li>${feature}</li>`).join('')}</ul></div>`).join(''); modal.classList.add('show'); }
        function selectInsuranceLevel(levelId) { document.querySelectorAll('.insurance-option').forEach(opt => opt.classList.remove('selected')); event.currentTarget.classList.add('selected'); document.querySelector(`input[value="${levelId}"]`).checked = true; }
        function confirmInsuranceLevel() { if (currentInsuranceCartIndex === null) return; const selectedLevel = document.querySelector('input[name="insurance-level"]:checked').value; cart[currentInsuranceCartIndex].insuranceLevel = selectedLevel; localStorage.setItem('carRentalCart', JSON.stringify(cart)); closeInsuranceModal(); updateCartDisplay(); showNotification('Insurance level selected!', 'success'); }
        function closeInsuranceModal() { document.getElementById('insurance-modal').classList.remove('show'); currentInsuranceCartIndex = null; }

        // ✅ 核心修复：applyPromoCode 使用 JSON 格式与后端通信
        function applyPromoCode() {
            const promoInput = document.getElementById('promo-code-input');
            const promoCode = promoInput.value.trim().toUpperCase();

            if (!promoCode) { showNotification('Please enter a promo code', 'warning'); return; }

            let totalAmount = 0;
            cart.forEach(item => { totalAmount += calculateItemTotal(item); });

            const applyBtn = document.querySelector('.promo-apply-btn');
            const originalBtnText = applyBtn.innerHTML;
            applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            applyBtn.disabled = true;

            fetch('validate_promo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code: promoCode, total: totalAmount })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    appliedPromo = { code: data.promo_code, discountAmount: parseFloat(data.discount_amount) };
                    showNotification(`${data.promo_code} applied! Saved RM ${data.discount_amount}`, 'success');
                    document.getElementById('promo-message').textContent = `${data.promo_code} applied! Saved RM ${data.discount_amount}`;
                    document.getElementById('promo-success').classList.add('show');
                    promoInput.disabled = true;
                    applyBtn.innerHTML = '<i class="fas fa-check"></i> Applied';
                    updateDetailedSummary();
                } else {
                    showNotification(data.message, 'error');
                    applyBtn.innerHTML = originalBtnText;
                    applyBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Failed to validate promo code', 'error');
                applyBtn.innerHTML = originalBtnText;
                applyBtn.disabled = false;
            });
        }

        function removePromoCode() {
            appliedPromo = null;
            document.getElementById('promo-success').classList.remove('show');
            document.getElementById('promo-code-input').value = '';
            document.getElementById('promo-code-input').disabled = false;
            const applyBtn = document.querySelector('.promo-apply-btn');
            applyBtn.innerHTML = '<i class="fas fa-check me-2"></i>Apply';
            applyBtn.disabled = false;
            updateDetailedSummary();
            showNotification('Promo code removed', 'info');
        }

        function calculateDuration(item) { if (!item.startDateTime || !item.endDateTime) return 'Not set'; const start = new Date(item.startDateTime); const end = new Date(item.endDateTime); const diffMs = end - start; if (diffMs <= 0) return 'Invalid dates'; if (item.rentalType === 'hourly') { const hours = Math.ceil(diffMs / (1000 * 60 * 60)); return `${hours} hour${hours > 1 ? 's' : ''}`; } else { const days = Math.ceil(diffMs / (1000 * 60 * 60 * 24)); return `${days} day${days > 1 ? 's' : ''}`; } }
        function updateDurationDisplay(index) { const el = document.getElementById(`duration-${index}`); if (el) { el.innerHTML = `<i class="fas fa-hourglass-half me-2"></i>Duration: ${calculateDuration(cart[index])}`; } }
        function getDurationInDays(item) { if (!item.startDateTime || !item.endDateTime) return 1; const start = new Date(item.startDateTime); const end = new Date(item.endDateTime); const diffMs = end - start; if (diffMs <= 0) return 1; return item.rentalType === 'hourly' ? Math.ceil(diffMs / (1000 * 60 * 60)) : Math.ceil(diffMs / (1000 * 60 * 60 * 24)); }
        function calculateServicesTotal(item) { if (!item.services || !item.servicePrices) return 0; const duration = getDurationInDays(item); let total = 0; for (let service in item.services) { if (item.services[service]) { total += (item.servicePrices[service] || 0) * duration; } } return total; }
        function calculateItemTotal(item) { const duration = getDurationInDays(item); const basePrice = item.rentalType === 'hourly' ? (item.pricePerHour || 0) : (item.price || 0); let subtotal = (basePrice * duration) + calculateServicesTotal(item) + getAgeSurcharge(item) + getInsuranceCost(item); if (item.fuelPolicy === 'pre-purchase') { subtotal += 80; } return Math.max(0, subtotal); }

        function updateDetailedSummary() {
            const summaryContainer = document.getElementById('detailed-summary');
            let grandTotal = 0;
            const summaryHTML = cart.map((item, index) => {
                const duration = getDurationInDays(item);
                const basePrice = item.rentalType === 'hourly' ? (item.pricePerHour || 0) : (item.price || 0);
                const rentalCost = basePrice * duration;
                const servicesCost = calculateServicesTotal(item);
                const ageSurcharge = getAgeSurcharge(item);
                const insuranceCost = getInsuranceCost(item);
                const fuelCost = item.fuelPolicy === 'pre-purchase' ? 80 : 0;
                const subtotal = calculateItemTotal(item);
                grandTotal += subtotal;
                const selectedServices = availableServices.filter(s => item.services?.[s.id]);

                return `<div class="car-summary-item"><div class="car-summary-name">${item.name}</div><div class="summary-detail"><span class="summary-detail-label">Rental Type:</span><span class="summary-detail-value">${item.rentalType === 'hourly' ? 'Hourly' : 'Daily'}</span></div><div class="summary-detail"><span class="summary-detail-label">Duration:</span><span class="summary-detail-value">${calculateDuration(item)}</span></div><div class="summary-detail"><span class="summary-detail-label">Base Rate:</span><span class="summary-detail-value">RM ${rentalCost.toFixed(2)}</span></div>${selectedServices.length > 0 ? `<div class="summary-detail" style="margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(255,255,255,0.2);"><span class="summary-detail-label" style="font-weight: 600;">Services:</span></div>${selectedServices.map(s => `<div class="summary-detail" style="padding-left: 15px; font-size: 0.9rem;"><span class="summary-detail-label">${s.name}:</span><span class="summary-detail-value">RM ${(s.price * duration).toFixed(2)}</span></div>`).join('')}` : ''}${ageSurcharge > 0 ? `<div class="summary-detail"><span class="summary-detail-label">Age Surcharge:</span><span class="summary-detail-value">RM ${ageSurcharge.toFixed(2)}</span></div>` : ''}${insuranceCost > 0 ? `<div class="summary-detail"><span class="summary-detail-label">Insurance Upgrade:</span><span class="summary-detail-value">RM ${insuranceCost.toFixed(2)}</span></div>` : ''}${fuelCost > 0 ? `<div class="summary-detail"><span class="summary-detail-label">Pre-Purchase Fuel:</span><span class="summary-detail-value">RM ${fuelCost.toFixed(2)}</span></div>` : ''}<div class="car-subtotal"><span>Subtotal:</span><span>RM ${subtotal.toFixed(2)}</span></div></div>`;
            }).join('');

            const discountAmount = appliedPromo ? appliedPromo.discountAmount : 0;
            const subtotalAfterDiscount = grandTotal - discountAmount;
            const tax = subtotalAfterDiscount * 0.06;
            const finalTotal = subtotalAfterDiscount + tax;

            let discountHTML = '';
            if (discountAmount > 0) {
                discountHTML = `<div class="summary-row" style="color: #00ff37; font-weight: 700;"><span class="summary-label"><i class="fas fa-tag me-2"></i>Discount (${appliedPromo.code})</span><span class="summary-value">- RM ${discountAmount.toFixed(2)}</span></div>`;
            }

            summaryContainer.innerHTML = summaryHTML + discountHTML + `<div class="summary-row"><span class="summary-label">Tax (6%):</span><span class="summary-value">RM ${tax.toFixed(2)}</span></div>`;
            document.getElementById('grand-total').textContent = `RM ${finalTotal.toFixed(2)}`;
        }

        function removeFromCart(index) { if (confirm('Remove this car from your cart?')) { cart.splice(index, 1); localStorage.setItem('carRentalCart', JSON.stringify(cart)); loadCarsData(); showNotification('Car removed', 'info'); } }
        function showNotification(message, type) { const notification = document.createElement('div'); notification.className = `alert alert-${type} position-fixed`; notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 350px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); border-radius: 15px; animation: slideIn 0.3s ease-out;'; notification.innerHTML = `<div class="d-flex justify-content-between align-items-center"><div><i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : type === 'error' ? 'times-circle' : 'info-circle'} me-2"></i>${message}</div><button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button></div>`; document.body.appendChild(notification); setTimeout(() => notification.remove(), 4000); }
    </script>
</body>
</html>