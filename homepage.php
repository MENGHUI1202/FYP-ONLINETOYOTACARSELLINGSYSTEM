<?php
session_start();
require_once 'config.php';

// 获取 6 个热门车辆
$popular_cars = [];

$query = "SELECT c.id, c.car_name, c.brand, c.model, c.type, c.transmission, c.seats, 
                 c.price_per_day, c.image_url
          FROM cars c
          WHERE c.availability = 1 
          ORDER BY c.id ASC 
          LIMIT 6";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // ★★★ 核心修改：获取这辆车的前 3 张图片 ★★★
        $car_id = $row['id'];
        $img_query = "SELECT image_url FROM car_images WHERE car_id = $car_id ORDER BY id ASC LIMIT 3";
        $img_res = $conn->query($img_query);
        
        $images = [];
        if ($img_res && $img_res->num_rows > 0) {
            while ($img_row = $img_res->fetch_assoc()) {
                // 修复路径
                $img_url = str_replace('../', '', $img_row['image_url']);
                $images[] = $img_url;
            }
        }
        
        // 如果没有 gallery 图片，就用 cars 表的主图
        if (empty($images)) {
            $main_img = str_replace('../', '', $row['image_url']);
            if (empty($main_img)) {
                $main_img = 'https://images.unsplash.com/photo-1542362567-b07e54358753?w=600';
            }
            $images[] = $main_img;
        }
        
        $row['gallery_images'] = $images;
        $popular_cars[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NO 1 Car Rental - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #ffffff; }

        /* Navbar */
        .navbar { background: linear-gradient(135deg, #505d6c 0%, #505d6c 100%); padding: 15px 0; box-shadow: 0 2px 20px rgba(0, 0, 0, 0.56); }
        .navbar-brand { font-size: 2rem; font-weight: 900; color: #ffffff !important; letter-spacing: 1px; display: flex; align-items: center; gap: 12px; }
        .navbar-brand i { font-size: 2.2rem; }
        .nav-link { color: rgba(255, 255, 255, 0.9) !important; font-size: 1.05rem; font-weight: 700; margin: 0 15px; padding: 10px 20px !important; border-radius: 10px; transition: all 0.3s; }
        .nav-link:hover, .nav-link.active { color: #ffffff !important; background: rgba(255, 255, 255, 0.2); transform: translateY(-2px); }

        /* Hero Carousel */
        #heroCarousel .carousel-item { height: 600px; position: relative; }
        #heroCarousel .carousel-item img { width: 100%; height: 100%; object-fit: cover; }
        .carousel-overlay { position: absolute; inset: 0; background: linear-gradient(to right, rgba(0, 0, 0, 0.54), rgba(0, 0, 0, 0.46)); display: flex; align-items: center; padding: 0 80px; }
        .carousel-content h1 { font-size: 4rem; font-weight: 900; color: #ffffff; text-shadow: 3px 3px 10px rgba(0, 0, 0, 0.5); margin-bottom: 20px; }
        .carousel-content p { font-size: 1.0rem; color: #ffffff; text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.5); }
        .browse-btn { position: absolute; bottom: 40px; right: 40px; padding: 18px 40px; background: linear-gradient(135deg, #5b89ff 0%, #6183ff 100%); color: #000000; font-weight: 900; font-size: 1.2rem; border: none; border-radius: 50px; cursor: pointer; transition: all 0.3s; box-shadow: 0 8px 25px rgb(120, 208, 255); display: flex; align-items: center; gap: 12px; z-index: 10; }
        .browse-btn:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgb(140, 255, 151); }

        /* Brand Icons */
        .brands-section { background: #f8f9fa; padding: 50px 0; }
        .brands-container { display: grid; grid-template-columns: repeat(8, 1fr); gap: 30px; max-width: 1400px; margin: 0 auto; padding: 0 40px; }
        .brand-item { background: #e4f5ff; border: 3px solid #e0e0e0; border-radius: 20px; padding: 30px 20px; text-align: center; cursor: pointer; transition: all 0.3s; display: flex; flex-direction: column; align-items: center; gap: 15px; }
        .brand-item:hover { border-color: #667eea; transform: translateY(-8px); box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15); }
        .brand-item img { width: 80px; height: 80px; object-fit: contain; }
        .brand-item span { font-weight: 800; color: #2c3e50; font-size: 1rem; }

        /* Popular Cars */
        .popular-section { padding: 80px 0; background: #ffffff; }
        .section-title { font-size: 3rem; font-weight: 900; color: #000000; text-align: center; margin-bottom: 50px; }
        .cars-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; max-width: 1400px; margin: 0 auto; padding: 0 40px; }
        
        .car-card { background: #ffffff; border: 2px solid #e0e0e0; border-radius: 20px; overflow: hidden; transition: all 0.3s; cursor: pointer; }
        .car-card:hover { border-color: #90beff; transform: translateY(-10px); box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15); }
        
        /* ★★★ 迷你轮播图样式 ★★★ */
        .mini-carousel { width: 100%; height: 240px; position: relative; }
        .mini-carousel .carousel-item { height: 240px; }
        .mini-carousel img { width: 100%; height: 100%; object-fit: cover; }
        /* 只有鼠标悬停时才显示箭头，避免太乱 */
        .mini-carousel .carousel-control-prev, .mini-carousel .carousel-control-next { opacity: 0; transition: opacity 0.3s; width: 40px; }
        .car-card:hover .carousel-control-prev, .car-card:hover .carousel-control-next { opacity: 0.8; }
        .mini-carousel .carousel-indicators { margin-bottom: 0.5rem; }
        .mini-carousel .carousel-indicators button { width: 8px; height: 8px; border-radius: 50%; }

        .car-body { padding: 25px; }
        .car-name { font-size: 1.5rem; font-weight: 900; color: #000000; margin-bottom: 15px; }
        .car-details { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
        .car-badge { background: #f0f3ff; color: #8ba0ff; padding: 8px 14px; border-radius: 20px; font-weight: 800; font-size: 0.9rem; }
        .car-price { display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 2px solid #e0e0e0; }
        .price-text { font-size: 2rem; font-weight: 900; color: #0073ff; }
        .price-label { color: #000000; font-weight: 700; }
        .btn-view { padding: 10px 20px; background: linear-gradient(135deg, #3567ff 0%, #398cff 100%); color: #ffffff; border: none; border-radius: 10px; font-weight: 800; cursor: pointer; transition: all 0.3s; }
        .btn-view:hover { transform: scale(1.05); }

        /* Why Section */
        .why-section { background: #ffffff; padding: 10px 0; }
        .why-subtitle { text-align: center; color: #000000; font-size: 1.2rem; margin-bottom: 20px; }
        .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; max-width: 1400px; margin: 0 auto; padding: 0 40px; }
        .feature-card { background: #ffffff; border: 2px solid #e0e0e0; border-radius: 20px; padding: 40px; text-align: center; transition: all 0.3s; }
        .feature-card:hover { border-color: #82ff9b; transform: translateY(-8px); box-shadow: 0 12px 35px rgba(0, 0, 0, 0.1); }
        .feature-icon { font-size: 4rem; color: #1f8fff; margin-bottom: 20px; }
        .feature-title { font-size: 1.5rem; font-weight: 900; color: #000000; margin-bottom: 15px; }
        .feature-text { color: #7f8c8d; line-height: 1.8; font-size: 1.05rem; }

        /* Footer */
        .footer { background: #505d6c; color: #ffffff; padding: 60px 0 30px; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 60px; max-width: 1400px; margin: 0 auto 50px; padding: 0 80px; }
        .footer h5 { font-weight: 900; font-size: 1.3rem; margin-bottom: 10px; }
        .footer p, .footer li { color: rgba(255, 255, 255, 0.8); line-height: 2; }
        .footer a { color: rgba(255, 255, 255, 0.8); text-decoration: none; transition: all 0.3s; }
        .footer a:hover { color: #8882ff; }
        .footer ul { list-style: none; padding: 0; }
        .footer-bottom { border-top: 1px solid #000000; padding-top: 30px; text-align: center; color: rgba(0, 0, 0, 0.7); }

        @media (max-width: 1200px) { .brands-container { grid-template-columns: repeat(4, 1fr); } .cars-grid { grid-template-columns: repeat(2, 1fr); } .features-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .carousel-content h1 { font-size: 2.5rem; } .brands-container { grid-template-columns: repeat(2, 1fr); } .cars-grid { grid-template-columns: 1fr; } .features-grid { grid-template-columns: 1fr; } .footer-grid { grid-template-columns: 1fr; } .browse-btn { bottom: 20px; right: 20px; padding: 14px 28px; font-size: 1rem; } }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="homepage.php"><i class="fas fa-car"></i><span>NO 1 Car Rental</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="homepage.php">HOME</a></li>
                    <li class="nav-item"><a class="nav-link" href="product_catalogue.php">CATALOGUE</a></li>
                    <li class="nav-item"><a class="nav-link" href="aboutus.php">ABOUT US</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">CONTACT US</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="my_account.php"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?></a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">LOGOUT</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="register.php">REGISTER</a></li>
                        <li class="nav-item"><a class="nav-link" href="login.php">LOGIN</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=1920" alt="Luxury Car">
                <div class="carousel-overlay">
                    <div class="carousel-content"><h1>Drive Your Dreams</h1><p>Premium car rental with transparent pricing</p></div>
                </div>
            </div>
            <div class="carousel-item">
                <img src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=1920" alt="SUV">
                <div class="carousel-overlay">
                    <div class="carousel-content"><h1>Adventure Awaits</h1><p>Wide selection of SUVs for family trips</p></div>
                </div>
            </div>
            <div class="carousel-item">
                <img src="https://images.unsplash.com/photo-1542362567-b07e54358753?w=1920" alt="Sedan">
                <div class="carousel-overlay">
                    <div class="carousel-content"><h1>Business Class Travel</h1><p>Comfortable sedans for your business needs</p></div>
                </div>
            </div>
        </div>
        <button class="browse-btn" onclick="window.location.href='product_catalogue.php'"><i class="fas fa-car"></i><span>Browse Cars</span></button>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
    </div>

    <section class="brands-section">
        <div class="brands-container">
            <div class="brand-item" onclick="filterBrand('Toyota')"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Toyota_EU.svg/200px-Toyota_EU.svg.png" alt="Toyota"><span>TOYOTA</span></div>
            <div class="brand-item" onclick="filterBrand('Mazda')"><img src="https://logos-world.net/wp-content/uploads/2020/05/Mazda-Logo-2018-present.png" alt="Mazda"><span>MAZDA</span></div>
            <div class="brand-item" onclick="filterBrand('Honda')"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/3/38/Honda.svg/200px-Honda.svg.png" alt="Honda"><span>HONDA</span></div>
            <div class="brand-item" onclick="filterBrand('Nissan')"><img src="https://cdn.freebiesupply.com/logos/large/2x/nissan-1-logo-black-and-white.png" alt="Nissan"><span>NISSAN</span></div>
            <div class="brand-item" onclick="filterBrand('Mercedes')"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/90/Mercedes-Logo.svg/200px-Mercedes-Logo.svg.png" alt="Mercedes"><span>MERCEDES</span></div>
            <div class="brand-item" onclick="filterBrand('BMW')"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/44/BMW.svg/200px-BMW.svg.png" alt="BMW"><span>BMW</span></div>
            <div class="brand-item" onclick="filterBrand('Perodua')"><img src="https://vectorseek.com/wp-content/uploads/2023/08/Perodua-Logo-Vector.svg-.png" alt="Perodua"><span>PERODUA</span></div>
            <div class="brand-item" onclick="filterBrand('Proton')"><img src="https://logos-world.net/wp-content/uploads/2022/12/Proton-Logo.png" alt="Proton"><span>PROTON</span></div>
        </div>
    </section>

    <section class="popular-section">
        <h2 class="section-title">Popular Cars</h2>
        <div class="cars-grid">
            <?php if (count($popular_cars) > 0): ?>
                <?php foreach ($popular_cars as $car): ?>
                    <div class="car-card">
                        <?php $carouselId = 'carousel-' . $car['id']; ?>
                        <div id="<?php echo $carouselId; ?>" class="carousel slide mini-carousel" data-bs-interval="false">
                            <div class="carousel-indicators">
                                <?php foreach ($car['gallery_images'] as $index => $img): ?>
                                    <button type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide-to="<?php echo $index; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>"></button>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="carousel-inner" onclick="window.location.href='product_catalogue.php'">
                                <?php foreach ($car['gallery_images'] as $index => $img): ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <img src="<?php echo htmlspecialchars($img); ?>" 
                                             alt="<?php echo htmlspecialchars($car['car_name']); ?>"
                                             onerror="this.src='https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=600'">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>
                        <div class="car-body" onclick="window.location.href='product_catalogue.php'">
                            <div class="car-name"><?php echo htmlspecialchars($car['car_name']); ?></div>
                            <div class="car-details">
                                <span class="car-badge"><?php echo htmlspecialchars($car['transmission'] ?: 'Automatic'); ?></span>
                                <span class="car-badge"><?php echo htmlspecialchars($car['seats'] ?: '5'); ?> Seats</span>
                                <span class="car-badge"><?php echo htmlspecialchars($car['type'] ?: 'Sedan'); ?></span>
                            </div>
                            <div class="car-price">
                                <div>
                                    <div class="price-text">RM <?php echo number_format($car['price_per_day'], 0); ?></div>
                                    <div class="price-label">per day</div>
                                </div>
                                <button class="btn-view">View</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; color: #7f8c8d; padding: 60px;">
                    <i class="fas fa-car" style="font-size: 4rem; margin-bottom: 20px;"></i>
                    <p style="font-size: 1.2rem;">No cars available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="why-section">
        <h2 class="section-title">Why NO 1 Car Rental?</h2>
        <p class="why-subtitle">Everything you need for a smooth, safe, and fast booking experience.</p>
        <div class="features-grid">
            <div class="feature-card"><div class="feature-icon"><i class="fas fa-tags"></i></div><div class="feature-title">Transparent pricing</div><div class="feature-text">Clear breakdown for rental, add-ons, insurance, fuel policy, and tax.</div></div>
            <div class="feature-card"><div class="feature-icon"><i class="fas fa-clock"></i></div><div class="feature-title">Hourly or daily</div><div class="feature-text">Choose hourly rentals for quick trips or daily rentals for longer journeys.</div></div>
            <div class="feature-card"><div class="feature-icon"><i class="fas fa-shield-alt"></i></div><div class="feature-title">Insurance upgrades</div><div class="feature-text">Select Basic, Standard, or Premium coverage during booking.</div></div>
            <div class="feature-card"><div class="feature-icon"><i class="fas fa-map-marked-alt"></i></div><div class="feature-title">Flexible locations</div><div class="feature-text">Pick-up and drop-off points across major cities and airports.</div></div>
            <div class="feature-card"><div class="feature-icon"><i class="fas fa-bolt"></i></div><div class="feature-title">Fast checkout</div><div class="feature-text">Confirm details, choose payment method, and finish in minutes.</div></div>
            <div class="feature-card"><div class="feature-icon"><i class="fas fa-headset"></i></div><div class="feature-title">24/7 support</div><div class="feature-text">Need help? We're always available for emergencies and assistance.</div></div>
        </div>
    </section>

    <footer class="footer" id="contact">
        <div class="footer-grid">
            <div>
                <h5><i class="fas fa-car me-2"></i>NO 1 Car Rental</h5>
                <p>Premium rentals with: </p><p>clear pricing</p><p>add-ons </p><p> flexible pick-up across Malaysia.</p>
            </div>
            <div>
                <h5>Quick Links</h5>
                <ul><li><a href="homepage.php">Home</a></li><li><a href="aboutus.php">About Us</a></li><li><a href="product_catalogue.php">Catalogue</a></li><li><a href="cart.php">Cart</a></li></ul>
            </div>
            <div>
                <h5>Contact</h5>
                <ul>
                    <li><i class="fas fa-phone me-2"></i>CALL US: </li><a href="tel:+60123456789">+60 12-345 6789</a></li>
                    <li><i class="fas fa-envelope me-2"></i>EMAIL US:</li>
                    <a href="mailto:hoomenghui@student.mmu.edu.my">hoomenghui@student.mmu.edu.my</a></li>
                    <a href="mailto:pangkanghorng@student.mmu.edu.my">pangkanghorng@student.mmu.edu.my</a></li>
                    <a href="mailto:ngmengxin@student.mmu.edu.my">ngmengxin@student.mmu.edu.my</a></li>
                    <li><i class="fas fa-map-marker-alt me-2"></i>OUR LOCATION: </li>
                    <a href="https://www.google.com/maps/place/MMU+Melaka+Campus/" target="_blank">Multimedia University, Melaka
                </ul>
            </div>
        </div>
        <div class="footer-bottom">© 2026 NO 1 Car Rental. All rights reserved.</div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterBrand(brand) {
            window.location.href = 'product_catalogue.php?brand=' + encodeURIComponent(brand);
        }
    </script>
</body>
</html>