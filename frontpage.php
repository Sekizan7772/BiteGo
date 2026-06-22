<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('db.php'); 

// --- ROLE-AWARE SESSION LOGIC ---
$isLoggedIn = isset($_SESSION['UserID']);
$userRole = isset($_SESSION['Role']) ? $_SESSION['Role'] : 'Customer'; 

// This determines the name to show based on who is logged in
if ($userRole == 'Customer') {
    $displayName = isset($_SESSION['CustName']) ? $_SESSION['CustName'] : 'Guest';
} else {
    // This catches Vendors and Admins
    $displayName = isset($_SESSION['UserName']) ? $_SESSION['UserName'] : 'Staff';
}

$custPoints = 0;
if ($isLoggedIn) {
    $cID = $_SESSION['UserID'];
    $ptsRes = mysqli_query($link, "SELECT Points FROM customer WHERE UserID='$cID'");
    if ($pRow = mysqli_fetch_assoc($ptsRes)) {
        $custPoints = (int)$pRow['Points'];
    }
}

$vendorSlides = [];
// 3NF FIX: Securely join the vendor and user tables
$vQuery = mysqli_query($link, "SELECT u.UserID, u.UserName, v.VendorImage, v.StoreStatus 
FROM vendor v
JOIN user u 
ON u.UserID = v.UserID 
WHERE u.Role = 'Vendor'");

if($vQuery) {
    while($v = mysqli_fetch_assoc($vQuery)) { $vendorSlides[] = $v; }
}

$vendorCountRes = mysqli_query($link, "SELECT COUNT(*) as cnt FROM vendor");
$vendorCountData = mysqli_fetch_assoc($vendorCountRes);
$totalVendors = $vendorCountData ? $vendorCountData['cnt'] : 0;

// 3NF FIX: Use VendorRating from the vendor table for the rolling average
$ratingRes = mysqli_query($link, "SELECT AVG(VendorRating) as avgRating FROM vendor WHERE VendorRating > 0");
$ratingData = mysqli_fetch_assoc($ratingRes);
$avgRating = ($ratingData && $ratingData['avgRating']) ? round($ratingData['avgRating'], 1) : 4.7;

$dinersRes = mysqli_query($link, "SELECT COUNT(*) as cnt FROM customer");
$dinersData = mysqli_fetch_assoc($dinersRes);
$actualDiners = $dinersData ? $dinersData['cnt'] : 0;
$totalDiners = 1000 + $actualDiners; 

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>BiteGo | All Restaurants, One Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.4.0/model-viewer.min.js"></script>
    
    <style>
        /* =========================================================
           1. CINEMATIC CURTAIN LOADING SCREEN
           ========================================================= */
        #loader-wrapper {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            z-index: 999999; /* Stay on top of absolutely everything */
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            pointer-events: none; /* Let clicks pass through once fading out */
        }
        
        .loader-curtain {
            position: absolute;
            top: 0;
            width: 50%;
            height: 100%;
            background: #050505; /* Deep premium black */
            transition: transform 1.2s cubic-bezier(0.77, 0, 0.175, 1); /* Smooth snap reveal */
            z-index: 1;
        }
        .loader-curtain-left { left: 0; }
        .loader-curtain-right { right: 0; }

        .loader-content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            opacity: 1;
            transition: opacity 0.5s ease;
        }

        .loader-logo {
            width: 160px;
            margin-bottom: 40px;
            /* Using the anti-gravity float for the logo! */
            animation: floatContinuous 3s ease-in-out infinite; 
            filter: drop-shadow(0px 10px 20px rgba(0,0,0,0.5));
        }

        .loader-spinner {
            width: 40px; height: 40px;
            border: 3px solid rgba(255,255,255,0.1);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        .loader-text {
            color: #fff;
            font-family: 'Alexandria', sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 6px;
            text-transform: uppercase;
            animation: pulse 2s infinite;
        }

        @keyframes spin { 100% { transform: rotate(360deg); } }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }

        /* The Javascript will add this class to open the curtains */
        .loaded .loader-curtain-left { transform: translateX(-100%); }
        .loaded .loader-curtain-right { transform: translateX(100%); }
        .loaded .loader-content { opacity: 0; }

        /* =========================================================
           2. ANTI-GRAVITY & ADVANCED HOVER PHYSICS 
           ========================================================= */
        @keyframes floatContinuous {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }

        model-viewer {
            animation: floatContinuous 4s ease-in-out infinite;
        }

        .slide-item {
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            will-change: transform, box-shadow;
        }
        .slide-item:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 25px 40px rgba(0,0,0,0.2);
            border: 1px solid #ddd;
            z-index: 10;
        }

        .step-card {
            transition: all 0.4s ease;
            will-change: transform, box-shadow;
        }
        .step-card:hover {
            animation: floatContinuous 3s ease-in-out infinite;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-color: #ddd;
        }

        .stat {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .stat:hover {
            transform: translateY(-10px) scale(1.1);
            color: #555;
        }
    </style>
</head>
<body>

    <div id="loader-wrapper">
        <div class="loader-curtain loader-curtain-left"></div>
        <div class="loader-curtain loader-curtain-right"></div>
        <div class="loader-content">
            <img src="gambar/logobitego.png" alt="BiteGo" class="loader-logo" onerror="this.src='gambar/bitegologo.png'">
            <div class="loader-spinner"></div>
            <div class="loader-text">Preparing Kitchen</div>
        </div>
    </div>

    <nav class="navbar" id="navbar">
        <a href="frontpage.php" class="nav-logo-link">BiteGo.</a>
        <div class="top-btn-container">
            <a href="frontpage.php" class="top-btn active-nav">HOME</a>
            <a href="#how-it-works" class="top-btn">HOW IT WORKS</a>
            <a href="aboutus.php" class="top-btn">ABOUT US</a>
            <a href="apply_vendor.php" class="top-btn btn-cta" style="color: #ffffff; font-weight: 800;">APPLY FOR VENDOR</a>

            <?php
            // --- ROLE-AWARE IDENTITY LOGIC ---
            $isLoggedIn = isset($_SESSION['UserID']);
            $displayName = 'Guest';

            if ($isLoggedIn) {
                $displayName = isset($_SESSION['CustName']) ? $_SESSION['CustName'] : (isset($_SESSION['UserName']) ? $_SESSION['UserName'] : 'User');
            }
            ?>

            <?php if ($isLoggedIn): ?>
                <div class="profile-menu">
                    <div class="profile-icon"><i class="fa-regular fa-user"></i></div>
                    <div class="dropdown-content">
                        <div class="dropdown-header">Hello, <?php echo htmlspecialchars($displayName); ?></div>

                        <?php if ($userRole == 'Customer'): ?>
                            <div style="padding: 10px 15px; font-size:13px; font-weight:bold; color:#b07d00; background:#fffcf2; border-bottom:1px solid #eee;">
                                <i class="fa-solid fa-star" style="color:gold; margin-right:5px;"></i> <?php echo $custPoints; ?> BiteGo Points
                            </div>
                            <a href="past_orders.php"><i class="fa-solid fa-receipt" style="margin-right:8px;"></i> Past Orders</a>
                            <a href="cust_setting.php"><i class="fa-solid fa-gear" style="margin-right:8px;"></i> Account Settings</a>
                        <?php endif; ?>

                        <?php if ($userRole == 'Vendor'): ?>
                            <a href="vendorpage.php"><i class="fa-solid fa-store" style="margin-right:8px;"></i> My Dashboard</a>
                        <?php endif; ?>

                        <a href="process_logout_cust.php" class="logout-text">
                            <i class="fa-solid fa-right-from-bracket" style="margin-right:8px;"></i> Log Out
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="loginforcust.php?source=frontpage" class="top-btn btn-cta" style="color: #ffffff; font-weight: 800;">CUSTOMER LOGIN</a>
            <?php endif; ?>

            <a href="login.php" class="top-btn btn-cta" style="color: #ffffff; font-weight: 800;">VENDOR / ADMIN</a>
            <a href="vendorSelectPage.php" class="top-btn btn-cta">BROWSE VENDOR</a>
        </div>
    </nav>

    <main>
        <section class="hero" id="home" style="overflow: hidden; position: relative;">
            <video class="hero-video" autoplay muted loop playsinline preload="metadata" style="will-change: transform;">
                <source src="gambar/background.mp4" type="video/mp4">
            </video>
            <div class="hero-overlay"></div>

            <div class="hero-content" style="will-change: transform, opacity;">
                <span class="hero-badge slide-up delay-100"><i class="fa-solid fa-bolt"></i> Fast & Seamless</span>
                
                <h1 class="hero-title" id="typewriter-hero" style="min-height: 120px;"></h1>
                
                <p class="hero-subtitle slide-up delay-200">Discover, explore, and order ahead from your favorite local vendors with zero friction.</p>
                
                <div class="hero-stats slide-up delay-300">
                <div class="stat"><span class="stat-num" id="anim-vendors">0</span><span class="stat-label">Restaurants</span></div>
                <div class="stat-divider"></div>
                <div class="stat"><span class="stat-num" id="anim-diners">0</span><span class="stat-label">Happy Diners</span></div>
                <div class="stat-divider"></div>
                <div class="stat"><span class="stat-num" id="anim-rating">0.0★</span><span class="stat-label">Average Rating</span></div>
                </div>
            </div>
        </section>

        <section class="slider-section slide-up" id="vendorSlider">
            <h2 style="text-align: center;">Explore The Vendors</h2>
            <div class="carousel-wrapper">
                <button class="carousel-btn prev-btn" onclick="moveVSlide(-1)">&#10094;</button>
                <div class="carousel-track" id="vendorTrack">
                    <?php if(!empty($vendorSlides)): ?>
                        <?php foreach($vendorSlides as $vs): ?>
                            <div class="slide-item">
                                <img src="<?php echo htmlspecialchars($vs['VendorImage']); ?>" alt="<?php echo htmlspecialchars($vs['UserName']); ?>" onerror="this.src='gambar/bitegologo.png'" style="<?php echo $vs['StoreStatus'] == 'Closed' ? 'filter: grayscale(100%); opacity: 0.6;' : ''; ?>">
                                <div class="slide-label">
                                    <span><?php echo htmlspecialchars($vs['UserName']); ?></span>
                                    <?php if($vs['StoreStatus'] == 'Closed'): ?>
                                        <span style="color:#d9534f; font-size: 16px; font-weight:bold; letter-spacing: 2px;"><i class="fa-solid fa-lock"></i> CURRENTLY CLOSED</span>
                                    <?php else: ?>
                                        <a href="menu.php?vendor=<?php echo urlencode($vs['UserName']); ?>" class="vendor-choose-btn">Choose this vendor</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button class="carousel-btn next-btn" onclick="moveVSlide(1)">&#10095;</button>
            </div>
        </section>

        <section class="section cta-section" style="background-color: #ffffff; overflow: hidden;">
            <div class="cta-inner">
                <div class="slide-left" style="flex: 1; border-radius: 20px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.1); border: 8px solid #fff; height: 350px;">
                    <video autoplay muted loop playsinline style="width: 100%; height: 100%; object-fit: cover;">
                        <source src="gambar/ad.mp4" type="video/mp4">
                    </video>
                </div>
                <div class="cta-text slide-right" style="padding-left: 40px;">
                    <span class="section-tag">Dine Your Way</span>
                    <h2>Order from your table, hassle-free.</h2>
                    <p>Why wave down a waiter when you can control the entire menu from your phone? Scan the QR code at your table, customize your meal exactly how you like it, and send it straight to the kitchen.</p>
                    <a href="vendorSelectPage.php" class="cta-main-btn">Browse the Full Menu <i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>
        </section>

        <section class="section cta-section" style="background-color: #f4f6f8; overflow: hidden;">
            <div class="cta-inner" style="flex-direction: row-reverse;">
                <div class="slide-right" style="flex: 1; border-radius: 20px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.1); border: 8px solid #fff; height: 350px;">
                    <video autoplay muted loop playsinline style="width: 100%; height: 100%; object-fit: cover;">
                        <source src="gambar/videoad2.mp4" type="video/mp4">
                    </video>
                </div>
                <div class="cta-text slide-left" style="padding-right: 40px;">
                    <span class="section-tag" style="background: #fff;">Partner with BiteGo</span>
                    <h2>Grow your restaurant business with us.</h2>
                    <p>Join hundreds of successful local vendors who have digitized their restaurants with BiteGo. We handle the digital storefront, seamless ordering, and instant payments so you can focus entirely on cooking delicious food.</p>
                    <a href="apply_vendor.php" class="cta-main-btn">Apply For Vendor <i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>
        </section>

        <section class="section how-section" id="how-it-works">
            <div class="container">
                <div class="section-header slide-up">
                    <span class="section-tag">Simple & Fast</span>
                    <h2 class="section-title">How It Works</h2>
                    <p class="section-desc">Experience frictionless dining in just three simple steps.</p>
                </div>

                <div class="steps-grid">
                    <div class="step-card slide-up delay-100">
                        <div class="step-icon"><i class="fa-solid fa-bolt-lightning"></i></div>
                        <h3>1. Why Us?</h3>
                        <p>Prioritize your time. Bypass physical queues and get your meals faster with our priority kitchen queueing system.</p>
                    </div>
                    <div class="step-card slide-up delay-200">
                        <div class="step-icon"><i class="fa-solid fa-mobile-screen-button"></i></div>
                        <h3>2. Browse & Order</h3>
                        <p>Open the app, browse real-time menus from local vendors, customize your meal, and place your order instantly.</p>
                    </div>
                    <div class="step-card slide-up delay-300">
                        <div class="step-icon"><i class="fa-solid fa-flag-checkered"></i></div>
                        <h3>3. Pay & Collect</h3>
                        <p>Pay securely via E-Wallet or Card. Receive a live notification the second your food is ready for collection.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section cta-section">
            <div class="cta-inner">
                <div class="cta-text slide-right">
                    <span class="section-tag">Secure Checkout</span>
                    <h2>Ready for a seamless experience?</h2>
                    <p>Create a free account to securely save your payment details, track your order history, and breeze through your next lunch break.</p>
                    <a href="register.php" class="cta-main-btn">Register Now <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <div class="slide-left" style="flex: 1; display: flex; justify-content: center;">
                    <model-viewer src="food/friedrice3D.glb" auto-rotate camera-controls rotation-per-second="30deg"></model-viewer>
                </div>
            </div>
        </section>

    </main>

    <footer class="footer" id="contact">
        <div class="footer-inner">
            <div class="footer-brand">
                <h2>BiteGo.</h2>
                <p class="footer-tagline">"Skip the line, dine online."</p>
                <div class="footer-socials">
                    <a href="unavailablePage.php" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="unavailablePage.php" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="unavailablePage.php" aria-label="Twitter"><i class="fab fa-x-twitter"></i></a>
                    <a href="https://www.tiktok.com/@nfl.adb?_r=1&_t=ZS-970fz58arJc" target = "_blank" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                </div>
                <div class="secure-payment">
                    <span style="font-size: 13px; font-weight: bold; color: var(--text-muted); margin-right: 10px;">We Accept:</span>
                    <img src="gambar/mastercard.png" alt="Mastercard">
                    <img src="gambar/visa.png" alt="Visa">
                    <img src="gambar/duitnow.png" alt="DuitNow">
                    <img src="gambar/TouchNGo.png" alt="TnGPay">
                </div>
            </div>
            
            <div class="footer-links">
                <div class="footer-col">
                    <h4>Discover</h4>
                    <a href="vendorSelectPage.php">Browse Menus</a>
                    <a href="unavailablePage.php">Promotions</a>
                    <a href="vendorSelectPage.php">Vendor List</a>
                </div>
                <div class="footer-col">
                    <h4>Company</h4>
                    <a href="aboutus.php">About Us</a>
                    <a href="unavailablePage.php">Careers</a>
                    <a href="unavailablePage.php">Press</a>
                </div>
                <div class="footer-col">
                    <h4>Support</h4>
                    <a href="unavailablePage.php">Help Center</a>
                    <a href="mailto:support@bitego.com"><i class="fa-solid fa-envelope" style="margin-right: 6px;"></i>Contact Us</a>
                    <a href="login.php" style="color: var(--primary); font-weight: 800;">Vendor Login</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date("Y"); ?> BiteGo. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // ==========================================
        // 1. CURTAIN LOADER LOGIC
        // ==========================================
        window.addEventListener('load', function() {
            // Fake a minimum load time (1.5 seconds) so users actually see the premium animation
            setTimeout(function() {
                // Add class to slide the curtains open and fade out logo
                document.getElementById('loader-wrapper').classList.add('loaded');
                
                // Completely remove it from the screen after the 1.2s CSS animation finishes
                setTimeout(function() {
                    document.getElementById('loader-wrapper').style.display = 'none';
                }, 1200);
            }, 1500); 
        });

        // ==========================================
        // 2. ORIGINAL PAGE ANIMATIONS
        // ==========================================
        document.addEventListener("DOMContentLoaded", function() {
            const heroTitle = document.getElementById("typewriter-hero");
            heroTitle.classList.add("typing-cursor"); 
            
            const textToType = ['S','k','i','p',' ','t','h','e',' ','L','i','n','e',',','<br>','D','i','n','e',' ','O','n','l','i','n','e','.'];
            let charIndex = 0;

            function typeChar() {
                if (charIndex < textToType.length) {
                    heroTitle.innerHTML += textToType[charIndex];
                    charIndex++;
                    setTimeout(typeChar, 80); 
                } else {
                    heroTitle.classList.remove("typing-cursor"); 
                }
            }
            // Wait slightly longer so the typewriter starts just as the curtains open
            setTimeout(typeChar, 2000); 
        });

        const observerOptions = { threshold: 0.15, rootMargin: "0px 0px -50px 0px" };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active-slide');
                }
            });
        }, observerOptions);
        document.querySelectorAll('.slide-up, .slide-left, .slide-right').forEach(el => observer.observe(el));

        // PARALLAX SCROLL ENGINE
        window.addEventListener('scroll', () => {
            const scrollY = window.scrollY;
            
            const heroContent = document.querySelector('.hero-content');
            const heroVideo = document.querySelector('.hero-video');
            
            if (heroContent && scrollY < 800) {
                heroContent.style.transform = `translateY(${scrollY * 0.4}px)`;
                heroContent.style.opacity = 1 - (scrollY * 0.002);
            }
            if (heroVideo && scrollY < 800) {
                heroVideo.style.transform = `translateY(${scrollY * 0.2}px)`;
            }

            const navbar = document.getElementById('navbar');
            if (scrollY > 50) navbar.classList.add('scrolled');
            else navbar.classList.remove('scrolled');
        });

        let vIndex = 0;
        const vTrack = document.getElementById('vendorTrack');
        function moveVSlide(dir) {
            if(!vTrack) return;
            vIndex += dir;
            const items = vTrack.children.length;
            if(vIndex < 0) vIndex = items - 1;
            if(vIndex >= items) vIndex = 0;
            vTrack.style.transform = `translateX(-${vIndex * 100}%)`;
        }
        if(vTrack) { setInterval(() => { moveVSlide(1); }, 4000); }

        document.addEventListener("DOMContentLoaded", function() {
            function scrambleStat(elementId, finalValue, isFloat, suffix, duration) {
                const el = document.getElementById(elementId);
                if (!el) return;
                
                let startTime = Date.now();
                let interval = setInterval(() => {
                    let elapsed = Date.now() - startTime;
                    
                    if (elapsed >= duration) {
                        clearInterval(interval);
                        el.innerText = finalValue + suffix; 
                    } else {
                        if (isFloat) {
                            el.innerText = (Math.random() * 4 + 1).toFixed(1) + suffix; 
                        } else {
                            el.innerText = Math.floor(Math.random() * 999) + suffix; 
                        }
                    }
                }, 40); 
            }

            // Start the numbers spinning after the curtains open
            setTimeout(() => {
                scrambleStat('anim-vendors', '<?php echo $totalVendors; ?>', false, '', 1500);       
                scrambleStat('anim-diners', '<?php echo $totalDiners; ?>', false, '+', 1500);    
                scrambleStat('anim-rating', '<?php echo number_format($avgRating, 1); ?>', true, '★', 1500);      
            }, 2500); 
        });
        
    </script>
</body>
</html>