<?php
session_start();
require_once 'config.php'; 

// --- 1. 获取筛选参数 ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$brand = isset($_GET['brand']) ? $_GET['brand'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$transmission = isset($_GET['transmission']) ? $_GET['transmission'] : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 1000;
$availability = isset($_GET['availability']) ? $_GET['availability'] : '';
$popular = isset($_GET['popular']) ? $_GET['popular'] : '';

// --- 2. 构建主 SQL 查询 ---
$sql = "SELECT * FROM cars WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    // 全方位搜索：品牌、车名、型号、类型、以及组合名称
    $sql .= " AND (
        brand LIKE ? OR 
        car_name LIKE ? OR 
        model LIKE ? OR 
        type LIKE ? OR 
        CONCAT(brand, ' ', car_name) LIKE ? OR 
        CONCAT(brand, ' ', model) LIKE ?
    )";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm; 
    $params[] = $searchTerm; 
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ssssss"; 
}

if (!empty($brand)) {
    $sql .= " AND brand = ?";
    $params[] = $brand;
    $types .= "s";
}

if (!empty($type)) {
    $sql .= " AND type = ?";
    $params[] = $type;
    $types .= "s";
}

if (!empty($transmission)) {
    $sql .= " AND transmission = ?";
    $params[] = $transmission;
    $types .= "s";
}

// 价格筛选
$sql .= " AND price_per_day BETWEEN ? AND ?";
$params[] = $min_price;
$params[] = $max_price;
$types .= "dd";

// 可用性筛选
if ($availability === 'available') {
    $sql .= " AND availability = 1";
} elseif ($availability === 'unavailable') {
    $sql .= " AND availability = 0";
}

// 排序逻辑
$sql .= " ORDER BY availability DESC, price_per_day ASC"; 

// --- 3. 执行主查询 ---
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("SQL 错误 (prepare failed): " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$cars = [];
while ($car = $result->fetch_assoc()) {
    // 获取车辆图片
    $car_images = [];
    $sql_images = "SELECT image_url FROM car_images WHERE car_id = ? ORDER BY image_order ASC, id ASC";
    $stmt_images = $conn->prepare($sql_images);
    
    if ($stmt_images) {
        $stmt_images->bind_param("i", $car['id']);
        $stmt_images->execute();
        $result_images = $stmt_images->get_result();
        while ($img = $result_images->fetch_assoc()) {
            $car_images[] = $img['image_url'];
        }
        $stmt_images->close();
    }

    // 图片回退逻辑
    if (empty($car_images) && !empty($car['image_url'])) {
        $car_images[] = $car['image_url'];
    }
    if (empty($car_images)) {
        $car_images[] = 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=900&auto=format&fit=crop';
    }
    
    // 修复路径
    foreach($car_images as $k => $v) {
        $car_images[$k] = str_replace('../', '', $v);
    }

    $car['images'] = $car_images;
    $cars[] = $car;
}

// --- 4. 获取品牌列表 ---
$brands_result = $conn->query("SELECT DISTINCT brand FROM cars ORDER BY brand");
$brands = [];
if($brands_result) {
    while ($row = $brands_result->fetch_assoc()) {
        $brands[] = $row['brand'];
    }
}

// --- 5. 获取分类列表 (跟随 Admin 后台) ---
$cat_result = $conn->query("SELECT name FROM vehicle_categories ORDER BY name ASC");
$categories = [];
if($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row['name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NO 1 Car Rental - Product Catalogue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body { background-color: #ffffff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100vh; }
        /* 导航栏样式 */
        .navbar { background: linear-gradient(135deg, #505d6c 0%, #505d6c 100%); padding: 15px 0; box-shadow: 0 2px 20px rgba(0, 0, 0, 0.56); }
        .navbar-brand { font-size: 2rem; font-weight: 900; color: #ffffff !important; letter-spacing: 1px; display: flex; align-items: center; gap: 12px; }
        .navbar-brand i { font-size: 2.2rem; }
        .nav-link { color: rgba(255, 255, 255, 0.9) !important; font-size: 1.05rem; font-weight: 700; margin: 0 8px; padding: 10px 15px !important; border-radius: 10px; transition: all 0.3s; display: inline-flex; align-items: center; white-space: nowrap; }
        .nav-link:hover, .nav-link.active { color: #72c2fbe6 !important; background: rgba(255, 255, 255, 0.2); transform: translateY(-2px); }
        .nav-link i { font-size: 1.1rem; }
        
        .page-header { background: linear-gradient(135deg, #1a89ff 0%, #1a89ff 100%); padding: 60px 0 40px 0; margin-bottom: 40px; border-radius: 0 0 30px 30px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
        .page-title { color: #ffffff; font-weight: 800; font-size: 4rem; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.46); }
        .page-subtitle { color: rgba(255, 255, 255, 0.9); font-size: 1.2rem; line-height: 1.6; max-width: 800px; margin: 0 auto; }
        
        .filter-section { background: #c7e3ff; border-radius: 15px; padding: 25px; margin-bottom: 30px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); border: 1px solid #0569ff; }
        .filter-row { margin-bottom: 20px; }
        .form-control, .form-select { border: 2px solid #ffffff; border-radius: 10px; padding: 12px 15px; transition: all 0.3s; font-size: 1rem; }
        .form-control:focus, .form-select:focus { border-color: #55b6ff; box-shadow: 0 0 0 0.2rem rgba(0, 115, 255, 0); }
        .input-group-text { background: #0095ff; border: 2px solid #7f96ff; color: white; border-radius: 10px 0 0 10px; }
        
        .price-range-container { background: #e4e2e2; border-radius: 15px; padding: 20px; border: 2px dashed #e0e0e0; }
        .price-range-label { color: #000000; font-weight: 600; margin-bottom: 15px; font-size: 1.1rem; }
        .price-inputs { display: flex; align-items: center; gap: 15px; }
        .price-arrow { color: #667eea; font-size: 1.8rem; font-weight: bold; }
        
        .btn-primary { background: linear-gradient(45deg, #6d88ff, #6d88ff); border: none; border-radius: 12px; padding: 12px 35px; font-weight: 600; font-size: 1.1rem; transition: all 0.3s; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgb(119, 160, 255); }
        .reset-btn { background: #929a9b; border: none; color: white; border-radius: 12px; padding: 12px 35px; font-weight: 600; font-size: 1.1rem; transition: all 0.3s; }
        .reset-btn:hover { background: #000000; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3); color: white; }
        
        .results-title { color: #000000; font-size: 2rem; font-weight: 700; margin: 30px 0; padding: 15px 0; border-bottom: 3px solid #000000; }
        
        .car-card { border: none; border-radius: 20px; overflow: hidden; height: 100%; margin-bottom: 30px; transition: all 0.3s ease; box-shadow: 0 5px 15px rgba(32, 162, 255, 0.29); background: white; position: relative; width: 100%; }
        .car-card:hover { transform: translateY(-10px); box-shadow: 0 15px 30px rgb(109, 192, 255); }
        
        .card-carousel { height: 280px; position: relative; background: #f5f5f5; overflow: hidden; }
        .card-carousel .carousel-inner { height: 100%; background: transparent !important; }
        .card-carousel .carousel-item { height: 100%; background: transparent !important; }
        .card-carousel .carousel-item img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; background: transparent !important; border: none !important; box-shadow: none !important; }
        .car-card:hover .card-carousel .carousel-item img { transform: scale(1.05); }
        
        .card-carousel .carousel-control-prev, .card-carousel .carousel-control-next { width: 35px; height: 35px; top: 50%; transform: translateY(-50%); background: rgba(0, 0, 0, 0.5); border-radius: 50%; opacity: 0; transition: all 0.3s ease; }
        .car-card:hover .card-carousel .carousel-control-prev, .car-card:hover .card-carousel .carousel-control-next { opacity: 1; }
        .card-carousel .carousel-control-prev { left: 8px; }
        .card-carousel .carousel-control-next { right: 8px; }
        .card-carousel .carousel-control-prev-icon, .card-carousel .carousel-control-next-icon { width: 15px; height: 15px; }
        .card-carousel .carousel-indicators { margin-bottom: 8px; }
        .card-carousel .carousel-indicators button { width: 8px; height: 8px; border-radius: 50%; background-color: rgba(255, 255, 255, 0.5); border: none; margin: 0 3px; }
        .card-carousel .carousel-indicators button.active { background-color: #0040ff; }
        
        .card-badges { position: absolute; top: 0; left: 0; z-index: 10; display: flex; flex-direction: column; gap: 0; }
        .availability-badge { background: #00c600; color: white; padding: 12px 20px; font-weight: 600; font-size: 0.9rem; box-shadow: 0 4px 10px rgb(0, 0, 0); text-align: center; border-bottom-right-radius: 12px; }
        .availability-badge.unavailable { background: #535353; }
        .popular-badge { background: linear-gradient(45deg, #ff0000, #ff8000); color: white; padding: 8px 20px; font-weight: 600; font-size: 0.8rem; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.51); text-align: center; border-bottom-right-radius: 12px; margin-top: 0; display: flex; align-items: center; gap: 5px; }
        
        .price-tag { position: absolute; top: 0; right: 0; background: rgba(255, 255, 255, 0.95); color: #000000; padding: 12px 15px; font-weight: 700; box-shadow: 0 4px 15px rgb(0, 0, 0); z-index: 10; text-align: center; border-bottom-left-radius: 12px; }
        .price-day { display: block; font-size: 1.3rem; color: #000000; font-weight: 700; margin-bottom: 2px; }
        .price-hour { font-size: 0.75rem; color: #5f6363; font-weight: 500; }
        
        .card-body { padding: 25px 20px; }
        .car-title-container { text-align: left; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0; }
        .car-brand { color: #7f8c8d; font-size: 1rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px; }
        .car-name { color: #000000; font-size: 1.8rem; font-weight: 700; margin-bottom: 5px; line-height: 1.2; }
        
        .specs-container { margin: 25px 0; }
        .specs-title { color: #2c3e50; font-size: 1.1rem; font-weight: 700; margin-bottom: 20px; text-align: left; text-transform: uppercase; letter-spacing: 1px; padding-left: 10px; border-left: 4px solid #a0b1ff; }
        .specs-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .spec-item { text-align: center; padding: 12px 8px; background: #f8f9fa; border-radius: 10px; transition: all 0.3s; border: 1px solid #eaeaea; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 75px; }
        .spec-item:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); background: white; border-color: #8ba0ff; }
        .spec-icon { font-size: 1.5rem; margin-bottom: 6px; color: #4466ff; }
        .spec-label { display: block; color: #7f8c8d; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .spec-value { display: block; color: #2c3e50; font-size: 0.9rem; font-weight: 700; line-height: 1.2; }
        
        .action-buttons { display: flex; gap: 12px; margin-top: 25px; }
        .btn-view-details { flex: 1; background: #2969ff; border: none; color: white; padding: 14px; border-radius: 10px; font-weight: 600; font-size: 1rem; transition: all 0.3s; }
        .btn-view-details:hover { background: linear-gradient(45deg, #2085ff, #91e7ff, #2085ff); transform: translateY(-3px); box-shadow: 0 5px 15px rgba(135, 206, 235, 0.4); color: white; }
        .btn-add-cart { flex: 1; background: #00ec5f; border: none; color: #2c3e50; padding: 14px; border-radius: 10px; font-weight: 600; font-size: 1rem; transition: all 0.3s; }
        .btn-add-cart:hover { background: linear-gradient(45deg, #3fc75b, #85ffb6, #3fc75b); transform: translateY(-3px); box-shadow: 0 5px 15px rgba(175, 238, 238, 0.4); color: #2c3e50; }
        .btn-add-cart:disabled { background: #bdc3c7; cursor: not-allowed; transform: none; box-shadow: none; }
        
        /* 购物车计数 */
        .cart-count { position: absolute; top: 5px; right: -5px; background: #ff1900; color: white; font-size: 0.7rem; font-weight: 700; padding: 2px 6px; border-radius: 10px; min-width: 18px; text-align: center; line-height: 1.2; }
        .position-relative { position: relative; }
        
        .empty-catalog { text-align: center; padding: 60px 20px; background: #f8f9fa; border-radius: 20px; border: 2px dashed #bdc3c7; margin: 30px 0; }
        .empty-catalog i { font-size: 4rem; color: #bdc3c7; margin-bottom: 20px; }
        .empty-catalog h3 { color: #7f8c8d; margin-bottom: 15px; }
        
        .car-card-container { padding: 0 10px; }
        
        /* Footer */
        .footer { background: #505d6c; color: #ffffff; padding: 60px 0 30px; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 60px; max-width: 1400px; margin: 0 auto 50px; padding: 0 80px; }
        .footer h5 { font-weight: 900; font-size: 1.3rem; margin-bottom: 10px; }
        .footer p, .footer li { color: rgba(255, 255, 255, 0.76); line-height: 2; }
        .footer a { color: rgba(255, 255, 255, 0.8); text-decoration: none; transition: all 0.3s; }
        .footer a:hover { color: #8882ff; }
        .footer ul { list-style: none; padding: 0; }
        .footer-bottom { border-top: 1px solid rgb(0, 0, 0); padding-top: 30px; text-align: center; color: rgba(0, 0, 0, 0.7); }
        
        @media (max-width: 1200px) { .specs-grid { grid-template-columns: repeat(3, 1fr); gap: 8px; } .spec-item { min-height: 70px; padding: 10px 6px; } }
        @media (max-width: 992px) { .card-carousel { height: 280px; } .specs-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .card-carousel { height: 240px; } .specs-grid { grid-template-columns: repeat(2, 1fr); } .spec-item { min-height: 65px; } .price-tag { padding: 10px 12px; } .price-day { font-size: 1.2rem; } .footer-grid { grid-template-columns: 1fr; gap: 30px; } }
        @media (max-width: 576px) { .specs-grid { grid-template-columns: repeat(2, 1fr); } .action-buttons { flex-direction: column; } .car-name { font-size: 1.6rem; } }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="homepage.php">
                <i class="fas fa-car me-2"></i>NO 1 Car Rental
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="homepage.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="product_catalogue.php">
                            <i class="fas fa-list me-1"></i>Catalogue
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="aboutus.php">
                            <i class="fas fa-info-circle me-1"></i>About Us
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="product_catalogue.php#contact">
                            <i class="fas fa-phone me-1"></i>Contact Us
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="cart.php">
                            <i class="fas fa-shopping-cart me-1"></i>Cart <span class="cart-count" id="cart-count">0</span>
                        </a>
                    </li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="my_account.php">
                                <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">
                                <i class="fas fa-user-plus me-1"></i>Register
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1 class="page-title text-center">PRODUCT CATALOGUE</h1>
            <p class="page-subtitle text-center">
                Find Your Perfect Ride. Browse our wide selection of vehicles. Filter by your preferences or search for specific models.
            </p>
        </div>
    </div>

    <div class="container py-4">
        <div class="filter-section">
            <form method="GET" action="">
                <div class="row filter-row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" name="search" placeholder="Search by car name, brand, or model..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="brand">
                            <option value="">All Brands</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?php echo htmlspecialchars($b); ?>" <?php echo ($brand == $b) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($b); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="type">
                            <option value="">All Types</option>
                            <?php foreach ($categories as $cat_name): ?>
                                <option value="<?php echo htmlspecialchars($cat_name); ?>" <?php echo ($type == $cat_name) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row filter-row g-3">
                    <div class="col-md-4">
                        <select class="form-select" name="transmission">
                            <option value="">All Transmissions</option>
                            <option value="Automatic" <?php echo ($transmission == 'Automatic') ? 'selected' : ''; ?>>Automatic</option>
                            <option value="Manual" <?php echo ($transmission == 'Manual') ? 'selected' : ''; ?>>Manual</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="popular">
                            <option value="">All Popularity</option>
                            <option value="popular" <?php echo ($popular == 'popular') ? 'selected' : ''; ?>>Popular Only</option>
                            <option value="not_popular" <?php echo ($popular == 'not_popular') ? 'selected' : ''; ?>>Not Popular Only</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="availability">
                            <option value="">All Availability</option>
                            <option value="available" <?php echo ($availability == 'available') ? 'selected' : ''; ?>>Available Only</option>
                            <option value="unavailable" <?php echo ($availability == 'unavailable') ? 'selected' : ''; ?>>Unavailable Only</option>
                        </select>
                    </div>
                </div>

                <div class="row filter-row">
                    <div class="col-12">
                        <div class="price-range-container">
                            <div class="price-range-label">
                                <i class="fas fa-money-bill-wave me-2"></i>Price Range (per day)
                            </div>
                            <div class="price-inputs">
                                <input type="number" class="form-control" name="min_price" placeholder="Low Price (RM)" value="<?php echo htmlspecialchars($min_price); ?>" min="0" max="10000">
                                <div class="price-arrow">
                                    <i class="fas fa-long-arrow-alt-right"></i>
                                </div>
                                <input type="number" class="form-control" name="max_price" placeholder="High Price (RM)" value="<?php echo htmlspecialchars($max_price); ?>" min="0" max="10000">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row filter-row">
                    <div class="col-12 text-center">
                        <button type="submit" class="btn btn-primary px-5">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                        <a href="product_catalogue.php" class="btn reset-btn px-5 ms-3">
                            <i class="fas fa-redo me-2"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <h2 class="results-title">Found Your Dream Car</h2>

        <div class="row">
            <?php if (count($cars) > 0): ?>
                <?php foreach ($cars as $car): ?>
                    <div class="col-lg-4 col-md-6 car-card-container">
                        <div class="card car-card">
                            <div id="carCarousel<?php echo $car['id']; ?>" class="carousel slide card-carousel" data-bs-ride="carousel">
                                <?php if (count($car['images']) > 1): ?>
                                    <div class="carousel-indicators">
                                        <?php foreach ($car['images'] as $index => $image): ?>
                                            <button type="button" data-bs-target="#carCarousel<?php echo $car['id']; ?>" data-bs-slide-to="<?php echo $index; ?>" <?php echo $index === 0 ? 'class="active" aria-current="true"' : ''; ?> aria-label="Slide <?php echo $index + 1; ?>">
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="carousel-inner">
                                    <?php foreach ($car['images'] as $index => $image): ?>
                                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                            <img src="<?php echo htmlspecialchars($image); ?>" class="d-block w-100" alt="<?php echo htmlspecialchars($car['car_name']); ?>" onerror="this.src='https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=900&auto=format&fit=crop'">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <?php if (count($car['images']) > 1): ?>
                                    <button class="carousel-control-prev" type="button" data-bs-target="#carCarousel<?php echo $car['id']; ?>" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Previous</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#carCarousel<?php echo $car['id']; ?>" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Next</span>
                                    </button>
                                <?php endif; ?>

                                <div class="card-badges">
                                    <span class="availability-badge <?php echo $car['availability'] ? '' : 'unavailable'; ?>">
                                        <?php echo $car['availability'] ? 'Available' : 'Unavailable'; ?>
                                    </span>
                                    <?php if (isset($car['is_popular']) && $car['is_popular']): ?>
                                        <span class="popular-badge">
                                            <i class="fas fa-fire"></i> Popular
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="price-tag">
                                    <span class="price-day">RM <?php echo number_format($car['price_per_day'], 2); ?></span>
                                    <span class="price-hour">RM<?php echo number_format($car['price_per_hour'], 2); ?>/hour</span>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="car-title-container">
                                    <div class="car-brand"><?php echo strtoupper(htmlspecialchars($car['brand'])); ?></div>
                                    <h3 class="car-name">
                                        <?php 
                                            $db_brand = trim($car['brand']);
                                            $db_name = trim($car['car_name']);
                                            if (stripos($db_name, $db_brand) === 0) {
                                                echo strtoupper(htmlspecialchars($db_name));
                                            } else {
                                                echo strtoupper(htmlspecialchars($db_brand . ' ' . $db_name));
                                            }
                                        ?>
                                    </h3>
                                </div>

                                <div class="specs-container">
                                    <div class="specs-title">Features</div>
                                    <div class="specs-grid">
                                        <div class="spec-item">
                                            <i class="fas fa-car spec-icon"></i>
                                            <span class="spec-label">Type</span>
                                            <span class="spec-value"><?php echo htmlspecialchars($car['type']); ?></span>
                                        </div>
                                        <div class="spec-item">
                                            <i class="fas fa-cogs spec-icon"></i>
                                            <span class="spec-label">Trans</span>
                                            <span class="spec-value"><?php echo htmlspecialchars($car['transmission']); ?></span>
                                        </div>
                                        <div class="spec-item">
                                            <i class="fas fa-users spec-icon"></i>
                                            <span class="spec-label">Seats</span>
                                            <span class="spec-value"><?php echo htmlspecialchars($car['seats']); ?></span>
                                        </div>
                                        <div class="spec-item">
                                            <i class="fas fa-gas-pump spec-icon"></i>
                                            <span class="spec-label">Fuel</span>
                                            <span class="spec-value"><?php echo htmlspecialchars($car['fuel_type'] ?? 'Petrol'); ?></span>
                                        </div>
                                        
                                        <?php if (!empty($car['horsepower']) && $car['horsepower'] > 0): ?>
                                            <div class="spec-item">
                                                <i class="fas fa-tachometer-alt spec-icon"></i>
                                                <span class="spec-label">Power</span>
                                                <span class="spec-value"><?php echo htmlspecialchars($car['horsepower']); ?> HP</span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($car['acceleration']) && $car['acceleration'] > 0): ?>
                                            <div class="spec-item">
                                                <i class="fas fa-rocket spec-icon"></i>
                                                <span class="spec-label">0-100</span>
                                                <span class="spec-value"><?php echo number_format($car['acceleration'], 1); ?>s</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="action-buttons">
                                    <button class="btn btn-view-details" onclick="viewDetails(<?php echo $car['id']; ?>)">
                                        View Details
                                    </button>
                                    <button class="btn btn-add-cart" onclick="addToCart(<?php echo $car['id']; ?>, '<?php echo addslashes($car['car_name']); ?>', <?php echo $car['price_per_day']; ?>)" <?php echo $car['availability'] ? '' : 'disabled'; ?>>
                                        <?php echo $car['availability'] ? 'Add to Cart' : 'Unavailable'; ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="empty-catalog">
                        <i class="fas fa-car"></i>
                        <h3 class="text-muted">No vehicles found</h3>
                        <p class="text-muted">Try adjusting your filters or search terms</p>
                        <a href="product_catalogue.php" class="btn btn-primary mt-3">
                            <i class="fas fa-redo me-2"></i>Clear All Filters
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer" id="contact">
        <div class="footer-grid">
            <div>
                <h5><i class="fas fa-car me-2"></i>NO 1 Car Rental</h5>
                <p>Premium rentals with clear pricing, add-ons, and flexible pick-up across Malaysia.</p>
            </div>
            <div>
                <h5>Quick Links</h5>
                <ul>
                    <li><a href="homepage.php">Home</a></li>
                    <li><a href="aboutus.php">About Us</a></li>
                    <li><a href="product_catalogue.php">Catalogue</a></li>
                    <li><a href="cart.php">Cart</a></li>
                </ul>
            </div>
            <div>
                <h5>Contact</h5>
                <ul>
                    <li><i class="fas fa-phone me-2"></i>CALL US: </li>
                    <a href="tel:+60123456789">+60 12-345 6789</a></li>

                    <li><i class="fas fa-envelope me-2"></i>EMAIL US:</li>
                    <a href="mailto:hoomenghui@student.mmu.edu.my">hoomenghui@student.mmu.edu.my</a></li>
                    <a href="mailto:pangkanghorng@student.mmu.edu.my">pangkanghorng@student.mmu.edu.my</a></li>
                   <a href="mailto:ngmengxin@student.mmu.edu.my">ngmengxin@student.mmu.edu.my</a></li>

                    <li><i class="fas fa-map-marker-alt me-2"></i>OUR LOCATION: </li>
                    <a href="https://www.google.com/maps/place/MMU+Melaka+Campus/" target="_blank">
                        Multimedia University, Melaka
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            © 2026 NO 1 Car Rental. All rights reserved.
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let cart = JSON.parse(localStorage.getItem('carRentalCart')) || [];
        updateCartCount();

        function addToCart(carId, carName, price) {
            const car = { id: carId, name: carName, price: price, quantity: 1, rentalDays: 1 };
            const existingIndex = cart.findIndex(item => item.id === carId);

            if (existingIndex > -1) {
                showNotification(carName + ' is already in your cart!', 'info');
                return;
            }

            cart.push(car);
            localStorage.setItem('carRentalCart', JSON.stringify(cart));
            updateCartCount();
            showNotification(carName + ' has been added to your cart!', 'success');
        }

        function viewDetails(carId) {
            window.location.href = 'car_details.php?id=' + carId;
        }

        function updateCartCount() {
            document.getElementById('cart-count').textContent = cart.length;
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} position-fixed`;
            notification.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 350px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                border: none;
                border-radius: 15px;
                background: ${type === 'success' ? '#27ae60' : '#3498db'};
                color: white;
            `;
            notification.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
        }

        document.querySelector('form').addEventListener('submit', function(e) {
            const minPrice = parseFloat(this.querySelector('[name="min_price"]').value) || 0;
            const maxPrice = parseFloat(this.querySelector('[name="max_price"]').value) || 1000;

            if (minPrice > maxPrice) {
                e.preventDefault();
                showNotification('Minimum price cannot be greater than maximum price!', 'info');
                return false;
            }
        });
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>