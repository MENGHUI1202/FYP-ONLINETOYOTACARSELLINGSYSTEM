<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - NO1 Car Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
        }

        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, #505D6C);
            padding: 15px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .navbar-brand {
            font-size: 2rem;
            font-weight: 900;
            color: #ffffff !important;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .navbar-brand i {
            font-size: 2.2rem;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-size: 1.05rem;
            font-weight: 700;
            margin: 0 15px;
            padding: 10px 20px !important;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .nav-link:hover, .nav-link.active {
            color: #70c3ff !important;
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Hero */
        .hero {
            background: linear-gradient(135deg, #5c606e 0%, #53485e 100%);
            color: #ffffff;
            padding: 120px 0 80px;
            text-align: center;
        }

        .hero h1 {
            font-size: 4rem;
            font-weight: 900;
            margin-bottom: 20px;
            text-shadow: 3px 3px 10px rgba(0, 0, 0, 0.3);
        }

        .hero p {
            font-size: 1.5rem;
            opacity: 0.95;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Story Section */
        .story-section {
            padding: 100px 0;
            background: #ffffff;
        }

        .section-title {
            font-size: 3rem;
            font-weight: 900;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 50px;
        }

        .story-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .story-text {
            font-size: 1.2rem;
            line-height: 2;
            color: #555;
            margin-bottom: 30px;
            text-align: justify;
        }

        .highlight {
            color: #667eea;
            font-weight: 800;
        }

        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, #747782 0%, #67616c 100%);
            color: #ffffff;
            padding: 80px 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .stat-card {
            text-align: center;
            padding: 30px;
            background: rgba(186, 232, 255, 0.49);
            border-radius: 20px;
            border: 2px solid rgb(0, 0, 0);
            transition: all 0.3s;
        }

        .stat-card:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .stat-number {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.2rem;
            opacity: 0.95;
        }

        /* Values Section */
        .values-section {
            padding: 100px 0;
            background: #f8f9fa;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .value-card {
            background: #ffffff;
            border: 2px solid #000000;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s;
        }

        .value-card:hover {
            border-color: #002fff;
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }

        .value-icon {
            font-size: 4rem;
            color: #94a7ff;
            margin-bottom: 25px;
        }

        .value-title {
            font-size: 1.8rem;
            font-weight: 900;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .value-text {
            font-size: 1.1rem;
            color: #7f8c8d;
            line-height: 1.8;
        }

        /* Team Section */
        .team-section {
            padding: 100px 0;
            background: #ffffff;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 50px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .team-card {
            text-align: center;
            transition: all 0.3s;
        }

        .team-card:hover {
            transform: translateY(-10px);
        }

        .team-photo {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            margin: 0 auto 25px;
            border: 5px solid #000000;
            object-fit: cover;
            transition: all 0.3s;
        }

        .team-card:hover .team-photo {
            transform: scale(1.1);
            box-shadow: 0 10px 30px rgba(0, 47, 255, 0.4);
        }

        .team-name {
            font-size: 1.8rem;
            font-weight: 900;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .team-role {
            font-size: 1.2rem;
            color: #087fff;
            font-weight: 800;
            margin-bottom: 15px;
        }

        .team-desc {
            color: #7f8c8d;
            line-height: 1.8;
            font-size: 1.05rem;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #52545e 0%, #afa7b6 100%);
            color: #ffffff;
            padding: 100px 0;
            text-align: center;
        }

        .cta-section h2 {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 25px;
        }

        .cta-section p {
            font-size: 1.4rem;
            opacity: 0.95;
            margin-bottom: 40px;
        }

        .cta-btn {
            padding: 20px 50px;
            background: #ffffff;
            color: #002fff;
            border: none;
            border-radius: 50px;
            font-size: 1.3rem;
            font-weight: 900;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            display: inline-flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
        }

        .cta-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgb(0, 0, 0);
            color: #000000;
        }

        /* Footer */
        .footer {
            background: #505D6C;
            color: #ffffff;
            padding: 60px 0 30px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 60px;
            max-width: 1400px;
            margin: 0 auto 40px;
            padding: 0 40px;
        }

        .footer h5 {
            font-weight: 900;
            font-size: 1.3rem;
            margin-bottom: 20px;
        }

        .footer p, .footer li {
            color: rgba(255, 255, 255, 0.8);
            line-height: 2;
        }

        .footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }

        .footer a:hover {
            color: #ffffff;
        }

        .footer ul {
            list-style: none;
            padding: 0;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 30px;
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .values-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .team-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .values-grid {
                grid-template-columns: 1fr;
            }
            .team-grid {
                grid-template-columns: 1fr;
            }
            .footer-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="homepage.php">
                <i class="fas fa-car"></i>
                <span>NO 1 Car Rental</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="homepage.php">HOME</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="product_catalogue.php">CATALOGUE</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">ABOUT US</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="aboutus.php#contact">CONTACT</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="my_account.php">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">LOGOUT</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">REGISTER</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">LOGIN</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <h1><i class="fas fa-info-circle me-3"></i>About Us</h1>
        <p>Discover the story behind Malaysia's premier car rental service</p>
    </section>

    <!-- Story Section -->
    <section class="story-section">
        <h2 class="section-title">Our Story</h2>
        <div class="story-content">
            <p class="story-text">
                <span class="highlight">NO 1 Car Rental</span> was founded in 2015 with a vision to revolutionize the car rental industry in Malaysia. 
                What started as a small operation with just 10 vehicles has grown into one of the country's most trusted car rental companies, 
                serving over <span class="highlight">10,000 satisfied customers</span> annually.
            </p>
            <p class="story-text">
                Our founder, <span class="highlight">Koh Hong</span>, recognized the need for a car rental service that prioritizes transparency, 
                customer satisfaction, and flexibility. With a background in automotive engineering and a passion for exceptional service, 
                he set out to create a company that would set new standards in the industry.
            </p>
            <p class="story-text">
                Today, we operate across <span class="highlight">50+ locations</span> in Malaysia, offering a diverse fleet of over 
                <span class="highlight">500 vehicles</span> ranging from budget-friendly economy cars to luxury sedans and SUVs. 
                Our commitment to excellence has earned us a <span class="highlight">4.9-star rating</span> and the trust of thousands of customers.
            </p>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-car"></i></div>
                <div class="stat-number">500+</div>
                <div class="stat-label">Vehicles Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-number">10K+</div>
                <div class="stat-label">Happy Customers</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="stat-number">50+</div>
                <div class="stat-label">Locations</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <div class="stat-number">4.9</div>
                <div class="stat-label">Average Rating</div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="values-section">
        <h2 class="section-title">Our Core Values</h2>
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon"><i class="fas fa-heart"></i></div>
                <div class="value-title">Customer First</div>
                <div class="value-text">Your satisfaction is our top priority. We go above and beyond to ensure every rental experience exceeds expectations.</div>
            </div>

            <div class="value-card">
                <div class="value-icon"><i class="fas fa-handshake"></i></div>
                <div class="value-title">Integrity</div>
                <div class="value-text">Transparent pricing with no hidden fees. What you see is exactly what you pay.</div>
            </div>

            <div class="value-card">
                <div class="value-icon"><i class="fas fa-award"></i></div>
                <div class="value-title">Excellence</div>
                <div class="value-text">From vehicle maintenance to customer service, we maintain the highest standards in everything we do.</div>
            </div>

            <div class="value-card">
                <div class="value-icon"><i class="fas fa-leaf"></i></div>
                <div class="value-title">Sustainability</div>
                <div class="value-text">Committed to reducing our environmental impact with eco-friendly vehicles and green practices.</div>
            </div>

            <div class="value-card">
                <div class="value-icon"><i class="fas fa-lightbulb"></i></div>
                <div class="value-title">Innovation</div>
                <div class="value-text">Continuously improving our services and adopting new technologies to enhance your rental experience.</div>
            </div>

            <div class="value-card">
                <div class="value-icon"><i class="fas fa-shield-alt"></i></div>
                <div class="value-title">Safety</div>
                <div class="value-text">All vehicles undergo rigorous safety inspections and come with comprehensive insurance coverage.</div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
        <h2 class="section-title">Meet Our Team</h2>
        <div class="team-grid">
            <div class="team-card">
                <img src="https://ui-avatars.com/api/?name=Koh+Hong&size=200&background=667eea&color=fff&bold=true" 
                     alt="Koh Hong" class="team-photo">
                <div class="team-name">Koh Hong</div>
                <div class="team-role">Founder & CEO</div>
                <div class="team-desc">Visionary leader with 15+ years of experience in the automotive industry.</div>
            </div>

            <div class="team-card">
                <img src="https://ui-avatars.com/api/?name=Sarah+Lee&size=200&background=667eea&color=fff&bold=true" 
                     alt="Sarah Lee" class="team-photo">
                <div class="team-name">Sarah Lee</div>
                <div class="team-role">Operations Director</div>
                <div class="team-desc">Ensures smooth operations across all our locations nationwide.</div>
            </div>

            <div class="team-card">
                <img src="https://ui-avatars.com/api/?name=Ahmad+Rahman&size=200&background=667eea&color=fff&bold=true" 
                     alt="Ahmad Rahman" class="team-photo">
                <div class="team-name">Ahmad Rahman</div>
                <div class="team-role">Customer Service Manager</div>
                <div class="team-desc">Dedicated to providing exceptional customer support 24/7.</div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <h2>Ready to Experience the Difference?</h2>
        <p>Join thousands of satisfied customers and book your car today</p>
        <a href="product_catalogue.php" class="cta-btn">
            <i class="fas fa-car"></i>
            <span>Browse Our Fleet</span>
        </a>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="footer-grid">
            <div>
                <h5><i class="fas fa-car me-2"></i>NO1 Car Rental</h5>
                <p>Premium rentals with clear pricing, add-ons, and flexible pick-up across Malaysia.</p>
            </div>

            <div>
                <h5>Quick Links</h5>
                <ul>
                    <li><a href="homepage.php">Home</a></li>
                    <li><a href="about.php">About Us</a></li>
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
            &copy; 2026 NO 1 Car Rental. All rights reserved.
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
