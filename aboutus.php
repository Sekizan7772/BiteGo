<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['UserID']);
$custName = $isLoggedIn ? $_SESSION['CustName'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>BiteGo | About Us</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="navbar" id="navbar">
        <a href="frontpage.php" class="nav-logo-link">BiteGo.</a>
       <div class="top-btn-container">
            <a href="frontpage.php" class="top-btn active-nav">HOME</a>
            <a href="frontpage.php#how-it-works" class="top-btn">HOW IT WORKS</a>
            <a href="aboutus.php" class="top-btn">ABOUT US</a>
            <a href="apply_vendor.php" class="top-btn btn-cta" style="color: #ffffff; font-weight: 800;">APPLY FOR VENDOR</a>
            
            <?php if ($isLoggedIn): ?>
                <div class="profile-menu">
                    <div class="profile-icon"><i class="fa-regular fa-user"></i></div>
                    <div class="dropdown-content">
                        <div class="dropdown-header">Hello, <?php echo htmlspecialchars($custName); ?></div>
                        <a href="past_orders.php"><i class="fa-solid fa-receipt" style="margin-right:8px;"></i> Past Orders</a>
                        <a href="process_logout_cust.php" class="logout-text"><i class="fa-solid fa-right-from-bracket" style="margin-right:8px;"></i> Log Out</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="loginforcust.php?source=frontpage" class="top-btn btn-cta" style="color: #ffffff; font-weight: 800;">CUSTOMER LOGIN</a>
            <?php endif; ?>
            <a href="login.php" class="top-btn btn-cta" style="color: #ffffff; font-weight: 800;">VENDOR / ADMIN</a>
            <a href="vendorSelectPage.php" class="top-btn btn-cta">BROWSE VENDOR</a>
        </div>
    </nav>

    <main style="padding-top: 80px; overflow: hidden;">
        <section class="story-section">
            <div class="story-left slide-left">
                <h1 id="typewriter-about" style="min-height: 120px;"></h1>
                
                <p class="long-desc">
                    BiteGo was born out of a simple frustration: why does getting amazing local food have to be so complicated? We noticed the massive disconnect between passionate, hardworking local hawkers and busy, modern customers who hate standing in long queues.<br><br>
                    Our system bridges this gap. By equipping local vendors with seamless digital POS systems and providing customers with an ultra-fast ordering app, we are preserving the rich heritage of Malaysian food culture while modernizing the way it is served. Skip the line, dine online.
                </p>
                <div class="history-badge">
                    <span class="history-year">Founded in 2026</span>
                    <p>Started by a group of computer science students in UiTM Tapah, BiteGo has rapidly evolved from a university project into a premium software solution powering local restaurants across the nation.</p>
                </div>
            </div>
            
            <div class="story-right slide-right">
                <img src="gambar/bitegologo.png" alt="BiteGo Logo" id="largeLogo">
            </div>
        </section>

        <article class="values-section">
            <h2 class="slide-up">Our Core Values</h2>
            <div class="values-grid">
                <div class="value-card slide-up delay-100">
                    <div class="value-icon">⚡</div>
                    <h3>Speed & Efficiency</h3>
                    <p>No more waiting in long lines. Our system is built to ensure your order goes straight to the kitchen the second you tap checkout.</p>
                </div>
                <div class="value-card slide-up delay-200">
                    <div class="value-icon">🤝</div>
                    <h3>Vendor Empowerment</h3>
                    <p>We don't just serve customers; we equip local hawkers and restaurants with powerful digital tools to grow their businesses.</p>
                </div>
                <div class="value-card slide-up delay-300">
                    <div class="value-icon">🔒</div>
                    <h3>Seamless Trust</h3>
                    <p>From encrypted payments to real-time order tracking, we build trust into every single byte of our application.</p>
                </div>
            </div>
        </article>

        <section class="team-section">
            <h2 class="slide-up">Meet Our Team</h2>
            <p class="slide-up">Hover over our cards to learn more about the minds behind the BiteGo System.</p>
            
            <div class="team-grid">
                <div class="team-profile slide-up delay-100">
                    <div class="profile-main">
                        <img src="gambar/adib.png" alt="Team Member" class="team-img" onerror="this.src='gambar/bitegologo.png'">
                        <h3 class="team-name">Naufal Adib, 20</h3>
                        <div class="team-role">Founder & Lead Dev</div>
                    </div>
                    <div class="profile-hover-details">
                        <div class="mini-role">Founder & Lead Dev</div>
                        <p class="bio">The architect behind BiteGo's core code. Naufal focuses on building highly scalable and beautifully minimal systems.</p>
                        <div style="font-size: 28px;">🚀 💻 ☕</div>
                    </div>
                </div>
                
                <div class="team-profile slide-up delay-200">
                    <div class="profile-main">
                        <img src="gambar/airiel.jpg" alt="Team Member" class="team-img" onerror="this.src='gambar/bitegologo.png'">
                        <h3 class="team-name">Airiel, 20</h3>
                        <div class="team-role">System Analyst</div>
                    </div>
                    <div class="profile-hover-details">
                        <div class="mini-role">System Analyst</div>
                        <p class="bio">Airiel is responsible for analyzing system requirements and designing efficient database solutions for BiteGo.</p>
                        <div style="font-size: 28px;">📊 📈 📱</div>
                    </div>
                </div>
                
                <div class="team-profile slide-up delay-300">
                    <div class="profile-main">
                        <img src="gambar/wafi.png" alt="Team Member" class="team-img" onerror="this.src='gambar/bitegologo.png'">
                        <h3 class="team-name">Ahmad Wafi, 20</h3>
                        <div class="team-role">Frontend Developer</div>
                    </div>
                    <div class="profile-hover-details">
                        <div class="mini-role">Frontend Developer</div>
                        <p class="bio">Wafi is our frontend expert, creating intuitive and visually appealing user interfaces for BiteGo.</p>
                        <div style="font-size: 28px;">🤝 🥘 🛵</div>
                    </div>
                </div>
                
                <div class="team-profile slide-up delay-400">
                    <div class="profile-main">
                        <img src="gambar/arman.png" alt="Team Member" class="team-img" onerror="this.src='gambar/bitegologo.png'">
                        <h3 class="team-name">Arman Aqil, 20</h3>
                        <div class="team-role">Backend Developer</div>
                    </div>
                    <div class="profile-hover-details">
                        <div class="mini-role">Backend Developer</div>
                        <p class="bio">Arman is our backend expert, building robust and scalable server-side solutions for BiteGo.</p>
                        <div style="font-size: 28px;">🎨 ✨ 📐</div>
                    </div>
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
                    <a href="https://www.tiktok.com/@nfl.adb?_r=1&_t=ZS-970fz58arJc" target ="_blank" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
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
        // --- JS TYPING EFFECT ---
        document.addEventListener("DOMContentLoaded", function() {
            const aboutTitle = document.getElementById("typewriter-about");
            aboutTitle.classList.add("typing-cursor"); 
            
            const textToType = ['R','e','d','e','f','i','n','i','n','g','<br>','L','o','c','a','l',' ','D','i','n','i','n','g','.'];
            let charIndex = 0;

            function typeChar() {
                if (charIndex < textToType.length) {
                    aboutTitle.innerHTML += textToType[charIndex];
                    charIndex++;
                    setTimeout(typeChar, 80); 
                } else {
                    aboutTitle.classList.remove("typing-cursor"); 
                }
            }
            setTimeout(typeChar, 400); 
        });

        // --- STYLISH SCROLL ANIMATIONS (SLIDING) ---
        const observerOptions = { threshold: 0.1, rootMargin: "0px 0px -50px 0px" };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active-slide');
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.slide-up, .slide-left, .slide-right').forEach(el => observer.observe(el));

        // Navbar Scroll
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) navbar.classList.add('scrolled');
            else navbar.classList.remove('scrolled');
        });
    </script>
</body>
</html>