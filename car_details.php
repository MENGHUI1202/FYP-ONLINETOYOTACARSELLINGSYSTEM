<?php

session_start();

require_once 'config.php';



// 获取车辆ID

$car_id = isset($_GET['id']) ? intval($_GET['id']) : 0;



if ($car_id <= 0) {

    header('Location: product_catalogue.php');

    exit();

}



// 查询车辆详细信息

$sql = "SELECT * FROM cars WHERE id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $car_id);

$stmt->execute();

$result = $stmt->get_result();



if ($result->num_rows === 0) {

    header('Location: product_catalogue.php');

    exit();

}



$car = $result->fetch_assoc();



// 从 car_images 表查询该车辆的所有图片

$images = [];

$sql_images = "SELECT image_url FROM car_images WHERE car_id = ? ORDER BY id ASC";

$stmt_images = $conn->prepare($sql_images);

$stmt_images->bind_param("i", $car_id);

$stmt_images->execute();

$result_images = $stmt_images->get_result();



while ($row = $result_images->fetch_assoc()) {

    $images[] = str_replace('../', '', $row['image_url']);

}



if (empty($images) && !empty($car['image_url'])) {

    $images[] = str_replace('../', '', $car['image_url']);

}



if (empty($images)) {

    $images[] = 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=900&auto=format&fit=crop';

}



$stmt_images->close();

?>



<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($car['car_name']); ?> - NO 1 Car Rental</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>

        body {

            background-color: #ffffff;

            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;

            min-height: 100vh;

        }



        /* 导航栏修复 */

        .navbar {

            background: linear-gradient(135deg, #505d6c 0%, #505d6c 100%);

            padding: 15px 0;

            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.57);

        }



        .navbar-brand {

            font-size: 1.8rem;

            font-weight: 800;

            color: #ffffff !important;

            letter-spacing: 1px;

            display: flex;

            align-items: center;

            gap: 12px;

        }



        .navbar-brand i { font-size: 2rem; }



        .nav-link {

            color: rgba(255, 255, 255, 0.9) !important;

            font-size: 1rem;

            font-weight: 600;

            margin: 0 10px;

            padding: 8px 15px !important;

            border-radius: 8px;

            transition: all 0.3s;

        }



        .nav-link:hover, .nav-link.active {

            color: #ffffff !important;

            background: rgba(255, 255, 255, 0.2);

            transform: translateY(-2px);

        }



        .cart-count {

            position: absolute;

            top: 2px;

            right: -8px;

            background: #ff4757;

            color: white;

            font-size: 0.7rem;

            font-weight: 700;

            padding: 2px 6px;

            border-radius: 10px;

            min-width: 18px;

            text-align: center;

        }



        .container { max-width: 1400px; }



        .back-btn {

            background: #505d6c;

            border: none;

            color: white;

            padding: 12px 25px;

            border-radius: 8px;

            font-weight: 600;

            transition: all 0.3s;

            text-decoration: none;

            display: inline-block;

            margin: 20px 0;

        }

        .back-btn:hover {

            transform: translateY(-2px);

            background: #374151;

            color: white;

        }



        .details-container {

            background: white;

            border-radius: 20px;

            padding: 40px;

            box-shadow: 0 10px 40px rgba(0,0,0,0.08);

            margin: 20px 0;

            border: 1px solid #eee;

        }



        .car-title-section {

            margin-bottom: 30px;

            padding-bottom: 20px;

            border-bottom: 2px solid #f1f5f9;

        }

        .car-brand-title {

            color: #64748b;

            font-size: 1rem;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 2px;

            margin-bottom: 5px;

        }

        .car-name-title {

            color: #1e293b;

            font-size: 3.5rem;

            font-weight: 800;

            margin: 0;

            line-height: 1.2;

        }



        .content-row {

            display: flex;

            gap: 50px;

            margin-bottom: 40px;

            align-items: flex-start;

        }



        .left-column { flex: 1; display: flex; flex-direction: column; gap: 20px; }



        .car-image-carousel {

            position: relative;

            width: 100%;

            border-radius: 15px;

            overflow: hidden;

            background: #f8fafc;

            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);

        }



        .carousel-inner { height: 500px; }

        .carousel-item { height: 100%; }

        .carousel-item img { width: 100%; height: 100%; object-fit: cover; }



        .carousel-control-prev, .carousel-control-next {

            width: 45px; height: 45px; top: 50%; transform: translateY(-50%);

            background: rgba(0, 0, 0, 0.4); border-radius: 50%; margin: 0 10px; opacity: 0.7;

        }

        .carousel-control-prev:hover, .carousel-control-next:hover { opacity: 1; }



        .detail-badges {

            position: absolute; top: 20px; left: 20px; display: flex; flex-direction: column; gap: 10px; z-index: 10;

        }

        .detail-badge {

            padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 0.85rem;

            box-shadow: 0 4px 10px rgba(0,0,0,0.15); display: inline-flex; align-items: center; gap: 6px;

        }

        .badge-available { background: #10b981; color: white; }

        .badge-unavailable { background: #64748b; color: white; }

        .badge-popular { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }



        .price-display {

            background: #f8fafc;

            border: 1px solid #e2e8f0;

            border-radius: 15px;

            padding: 25px;

            display: flex;

            justify-content: space-around;

            align-items: center;

        }

        .price-item-label { color: #64748b; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; }

        .price-item-amount { color: #1e293b; font-size: 2rem; font-weight: 800; }

        .price-divider { width: 1px; height: 50px; background: #cbd5e1; }



        .specs-section { flex: 1; display: flex; flex-direction: column; gap: 20px; }

        .section-title {

            color: #1e293b; font-size: 2rem; font-weight: 800; margin-bottom: 20px;

            padding-left: 15px; border-left: 5px solid #3b82f6;

        }



        .specs-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }

        .spec-card {

            background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;

            transition: 0.2s; text-align: center; display: flex; flex-direction: column; justify-content: center; min-height: 110px;

        }

        .spec-card:hover { transform: translateY(-3px); border-color: #3b82f6; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.1); }

        .spec-icon { font-size: 1.8rem; color: #3b82f6; margin-bottom: 8px; }

        .spec-label { color: #64748b; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }

        .spec-value { color: #1e293b; font-size: 1.1rem; font-weight: 700; }



        .action-buttons-below { display: flex; gap: 15px; margin-top: 15px; }

        .btn-action {

            flex: 1; padding: 15px; border-radius: 10px; font-weight: 700; font-size: 1rem;

            transition: 0.2s; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;

        }

        .btn-add-to-cart { background: #10b981; color: white; }

        .btn-add-to-cart:hover { background: #059669; transform: translateY(-2px); color: white; }

        .btn-browse { background: white; border: 2px solid #1e293b; color: #1e293b; }

        .btn-browse:hover { background: #1e293b; color: white; transform: translateY(-2px); }



        .details-row { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px; }

        .detail-column { background: #fff; border-radius: 15px; border: 1px solid #e2e8f0; overflow: hidden; }

        .detail-header { background: #f1f5f9; padding: 15px 25px; border-bottom: 1px solid #e2e8f0; }

        .detail-title { font-size: 1.2rem; font-weight: 800; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 10px; }

        .detail-content { padding: 25px; color: #475569; line-height: 1.6; }



        .footer { background: #1e293b; color: white; padding: 60px 0 20px; margin-top: 60px; }

        .footer h5 { font-weight: 800; margin-bottom: 20px; color: white; }

        .footer a { color: #cbd5e1; text-decoration: none; transition: 0.2s; }

        .footer a:hover { color: #3b82f6; }

        .footer-bottom { border-top: 1px solid #334155; padding-top: 20px; margin-top: 40px; text-align: center; color: #94a3b8; font-size: 0.9rem; }

    </style>

</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark">

        <div class="container">

            <a class="navbar-brand" href="homepage.php">

                <i class="fas fa-car"></i>

                <span>NO 1 CAR RENTAL</span>

            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">

                <span class="navbar-toggler-icon"></span>

            </button>

            <div class="collapse navbar-collapse" id="navbarNav">

                <ul class="navbar-nav ms-auto align-items-center">

                    <li class="nav-item"><a class="nav-link" href="homepage.php">Home</a></li>

                    <li class="nav-item"><a class="nav-link" href="product_catalogue.php">Catalogue</a></li>

                    <li class="nav-item"><a class="nav-link" href="aboutus.php">About Us</a></li>

                    <li class="nav-item">

                        <a class="nav-link position-relative" href="cart.php">

                            <i class="fas fa-shopping-cart"></i> Cart

                            <span class="cart-count" id="cart-count">0</span>

                        </a>

                    </li>

                    <?php if(isset($_SESSION['user_id'])): ?>

                        <li class="nav-item"><a class="nav-link" href="my_account.php"><i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?></a></li>

                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>

                    <?php else: ?>

                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>

                        <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>

                    <?php endif; ?>

                </ul>

            </div>

        </div>

    </nav>



    <div class="container">

        <a href="product_catalogue.php" class="back-btn"><i class="fas fa-arrow-left me-2"></i>Back to Catalogue</a>



        <div class="details-container">

            <div class="car-title-section">

                <div class="car-brand-title"><?php echo strtoupper(htmlspecialchars($car['brand'])); ?></div>

                <h1 class="car-name-title"><?php echo strtoupper(htmlspecialchars($car['brand'] . ' ' . $car['model'])); ?></h1>

            </div>



            <div class="content-row">

                <div class="left-column">

                    <div id="carImageCarousel" class="carousel slide car-image-carousel" data-bs-ride="carousel">

                        <?php if (count($images) > 1): ?>

                        <div class="carousel-indicators">

                            <?php foreach ($images as $index => $image): ?>

                                <button type="button" data-bs-target="#carImageCarousel" data-bs-slide-to="<?php echo $index; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>"></button>

                            <?php endforeach; ?>

                        </div>

                        <?php endif; ?>



                        <div class="carousel-inner">

                            <?php foreach ($images as $index => $image): ?>

                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">

                                    <img src="<?php echo htmlspecialchars($image); ?>" class="d-block w-100" alt="Car Image" onerror="this.src='https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=900'">

                                </div>

                            <?php endforeach; ?>

                        </div>



                        <?php if (count($images) > 1): ?>

                        <button class="carousel-control-prev" type="button" data-bs-target="#carImageCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>

                        <button class="carousel-control-next" type="button" data-bs-target="#carImageCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>

                        <?php endif; ?>



                        <div class="detail-badges">

                            <?php if ($car['availability']): ?>

                                <span class="detail-badge badge-available"><i class="fas fa-check-circle"></i> Available</span>

                            <?php else: ?>

                                <span class="detail-badge badge-unavailable"><i class="fas fa-times-circle"></i> Booked</span>

                            <?php endif; ?>

                            <?php if ($car['is_popular']): ?>

                                <span class="detail-badge badge-popular"><i class="fas fa-fire"></i> Popular</span>

                            <?php endif; ?>

                        </div>

                    </div>



                    <div class="price-display">

                        <div class="price-item">

                            <div class="price-item-label">Daily Rate</div>

                            <div class="price-item-amount">RM <?php echo number_format($car['price_per_day'], 2); ?></div>

                        </div>

                        <div class="price-divider"></div>

                        <div class="price-item">

                            <div class="price-item-label">Hourly Rate</div>

                            <div class="price-item-amount">RM <?php echo number_format($car['price_per_hour'], 2); ?></div>

                        </div>

                    </div>

                </div>



                <div class="specs-section">

                    <h2 class="section-title">Specifications</h2>

                    <div class="specs-grid">

                        <div class="spec-card"><i class="fas fa-car spec-icon"></i><div class="spec-label">Type</div><div class="spec-value"><?php echo htmlspecialchars($car['type']); ?></div></div>

                        <div class="spec-card"><i class="fas fa-cogs spec-icon"></i><div class="spec-label">Trans.</div><div class="spec-value"><?php echo htmlspecialchars($car['transmission']); ?></div></div>

                        <div class="spec-card"><i class="fas fa-users spec-icon"></i><div class="spec-label">Seats</div><div class="spec-value"><?php echo htmlspecialchars($car['seats']); ?></div></div>

                        <div class="spec-card"><i class="fas fa-gas-pump spec-icon"></i><div class="spec-label">Fuel</div><div class="spec-value"><?php echo htmlspecialchars($car['fuel_type']); ?></div></div>

                        <div class="spec-card"><i class="fas fa-tachometer-alt spec-icon"></i><div class="spec-label">HP</div><div class="spec-value"><?php echo htmlspecialchars($car['horsepower']); ?></div></div>

                        <div class="spec-card"><i class="fas fa-rocket spec-icon"></i><div class="spec-label">0-100</div><div class="spec-value"><?php echo number_format($car['acceleration'], 1); ?>s</div></div>

                    </div>



                    <div class="action-buttons-below">

                        <button class="btn-action btn-add-to-cart" onclick="addToCart(<?php echo $car['id']; ?>, '<?php echo addslashes($car['car_name']); ?>', <?php echo $car['price_per_day']; ?>)" <?php echo $car['availability'] ? '' : 'disabled'; ?>>

                            <i class="fas fa-shopping-cart"></i> Add to Cart

                        </button>

                        <a href="product_catalogue.php" class="btn-action btn-browse">

                            <i class="fas fa-th-large"></i> Browse Cars

                        </a>

                    </div>

                </div>

            </div>



            <div class="details-row">

                <div class="detail-column">

                    <div class="detail-header"><h3 class="detail-title"><i class="fas fa-file-alt text-primary"></i> Description</h3></div>

                    <div class="detail-content"><?php echo nl2br(htmlspecialchars($car['description'])); ?></div>

                </div>

                <div class="detail-column">

                    <div class="detail-header"><h3 class="detail-title"><i class="fas fa-list-ul text-primary"></i> Detailed Specs</h3></div>

                    <div class="detail-content"><?php echo nl2br(htmlspecialchars($car['specification'])); ?></div>

                </div>

            </div>

        </div>

    </div>



    <footer class="footer">

        <div class="container">

            <div class="footer-grid">

                <div>

                    <h5>NO 1 Car Rental</h5>

                    <p class="text-secondary">Premium car rental services in Malaysia. Reliable, affordable, and convenient.</p>

                </div>

                <div>

                    <h5>Quick Links</h5>

                    <ul class="list-unstyled">

                        <li><a href="homepage.php">Home</a></li>

                        <li><a href="product_catalogue.php">Catalogue</a></li>

                        <li><a href="aboutus.php">About Us</a></li>

                    </ul>

                </div>

                <div>

                    <h5>Contact</h5>

                    <ul class="list-unstyled">

                        <li><i class="fas fa-phone me-2"></i> +60 12-345 6789</li>

                        <li><i class="fas fa-envelope me-2"></i> support@khrental.com</li>

                    </ul>

                </div>

            </div>

            <div class="footer-bottom">

                &copy; <?php echo date('Y'); ?> NO 1 Car Rental. All rights reserved.

            </div>

        </div>

    </footer>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>

        let cart = JSON.parse(localStorage.getItem('carRentalCart')) || [];

        document.getElementById('cart-count').textContent = cart.length;



        function addToCart(id, name, price) {

            if(cart.some(item => item.id === id)) { alert(name + ' is already in your cart!'); return; }

            cart.push({id: id, name: name, price: price, rentalType: 'daily'});

            localStorage.setItem('carRentalCart', JSON.stringify(cart));

            document.getElementById('cart-count').textContent = cart.length;

            window.location.href = 'cart.php';

        }

    </script>

</body>

</html>

<?php

$stmt->close();

$conn->close();

?>