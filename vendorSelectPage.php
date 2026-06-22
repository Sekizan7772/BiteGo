<?php
session_start();
include "db.php";
include "controller.php";

// Use your friend's logic to fetch vendors
if(isset($_POST['vendorName']) && !empty($_POST['vendorName'])){
   $v = mysqli_real_escape_string($link, $_POST['vendorName']);
   $vendor = searchVendor($v);
}
else{
  $vendor = vendorOpt();
}

$isLoggedIn = isset($_SESSION['UserID']);
$custName = (isset($_SESSION['CustName'])) ? $_SESSION['CustName'] : 'Customer';

// If it's still not set, fetch it directly from the database to be 100% sure
if ($isLoggedIn && $custName == 'Customer') {
    $cID = $_SESSION['UserID'];
    $nameQ = mysqli_query($link, "SELECT UserName FROM user WHERE UserID='$cID'");
    if ($nRow = mysqli_fetch_assoc($nameQ)) {
        $custName = $nRow['UserName'];
        $_SESSION['CustName'] = $custName; 
    }
}

$custPoints = 0;
if ($isLoggedIn) {
    $cID = $_SESSION['UserID'];
    $ptsRes = mysqli_query($link, "SELECT Points FROM customer WHERE UserID='$cID'");
    if ($pRow = mysqli_fetch_assoc($ptsRes)) {
        $custPoints = (int)$pRow['Points'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Restaurant | BiteGo</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="style.css">
    
    <style>
        /* BASE BOOTSTRAP OVERRIDES FOR PREMIUM FEEL */
        body { background-color: #f4f6f8; font-family: 'Segoe UI', sans-serif; }
        
        /* ADVANCED BOOTSTRAP CARD ANIMATIONS */
        .vendor-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.04);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none !important; /* Force remove underline from links */
            color: inherit;
        }
        
        .vendor-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.15) !important;
        }

        /* IMAGE ZOOM PHYSICS */
        .card-img-wrap {
            height: 240px;
            overflow: hidden;
            position: relative;
        }
        .card-img-top {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }
        .vendor-card:hover .card-img-top {
            transform: scale(1.08);
        }

        /* FLOATING BADGES */
        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 2;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 900;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            letter-spacing: 1px;
        }
        .bg-open { background-color: #e8f5e9; color: #2e7d32; }
        .bg-closed { background-color: #fdf0f0; color: #d9534f; }

        /* RATING PILL */
        .rating-pill {
            background-color: #fffcf2;
            color: #b07d00;
            padding: 5px 12px;
            border-radius: 12px;
            font-weight: bold;
            border: 1px solid #ffecb3;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* CUSTOM BUTTON HOVER */
        .btn-visit {
            background-color: #f8f9fa;
            color: #000;
            border-radius: 12px;
            font-weight: bold;
            transition: 0.3s;
            border: 1px solid #eee;
        }
        .vendor-card:hover .btn-visit {
            background-color: #000;
            color: #fff;
            border-color: #000;
        }

        /* SEARCH HERO SECTION */
        .search-hero {
            background: #000;
            color: #fff;
            padding: 80px 20px;
        }
        .search-input {
            border-radius: 30px 0 0 30px !important;
            padding: 15px 25px;
            border: none;
            font-weight: bold;
        }
        .search-btn {
            border-radius: 0 30px 30px 0 !important;
            padding: 15px 35px;
            font-weight: bold;
            background: #333;
            color: white;
            border: none;
            transition: 0.3s;
        }
        .search-btn:hover { background: #555; color: white;}
    </style>
</head>
<body>

    <nav class="navbar" id="navbar" style="background:rgba(255,255,255,0.98); border-bottom:1px solid #eee; position:sticky; top:0; z-index:100; margin-bottom:0;">
        <a href="frontpage.php" class="nav-logo-link" style="color:#000; text-decoration:none;">BiteGo.</a>
        <div class="top-btn-container">
            <a href="frontpage.php" class="top-btn">HOME</a>
            <?php if ($isLoggedIn): ?>
                <div class="profile-menu">
                    <div class="profile-icon" style="color: #000; border-color: #000;"><i class="fa-regular fa-user"></i></div>
                    <div class="dropdown-content">
                        <div class="dropdown-header">Hello, <?php echo htmlspecialchars($custName); ?></div>
                        <div style="padding: 10px 15px; font-size:13px; font-weight:bold; color:#b07d00; background:#fffcf2; border-bottom:1px solid #eee;">
                            <i class="fa-solid fa-star" style="color:gold; margin-right:5px;"></i> <?php echo $custPoints; ?> BiteGo Points
                        </div>
                        <a href="past_orders.php"><i class="fa-solid fa-receipt" style="margin-right:8px;"></i> Past Orders</a>
                        <a href="cust_setting.php"><i class="fa-solid fa-gear" style="margin-right:8px;"></i> Account Settings</a>
                        <a href="process_logout_cust.php" class="logout-text"><i class="fa-solid fa-right-from-bracket" style="margin-right:8px;"></i> Log Out</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="loginforcust.php?source=menu" class="top-btn">LOG IN</a>
            <?php endif; ?>
        </div>
    </nav>

    <section class="search-hero text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Craving something?</h1>
            <p class="lead text-light mb-4">Explore top-rated local restaurants and order ahead.</p>
            
            <form method="POST" class="mx-auto" style="max-width: 600px;">
                <div class="input-group shadow-lg">
                    <input type="text" name="vendorName" class="form-control search-input" placeholder="Search for a restaurant..." value="<?php echo isset($_POST['vendorName']) ? htmlspecialchars($_POST['vendorName']) : ''; ?>">
                    <button type="submit" class="btn search-btn"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                </div>
            </form>
        </div>
    </section>

    <div class="container py-5 my-4">
        
        <?php if(empty($vendor)): ?>
            <div class="text-center py-5 bg-white rounded-4 border border-dashed">
                <i class="fa-solid fa-store-slash text-muted" style="font-size: 50px; margin-bottom: 20px;"></i>
                <h2 class="fw-bold">No restaurants found.</h2>
                <p class="text-secondary">Try searching for a different name.</p>
                <a href="vendorSelectPage.php" class="text-dark fw-bold text-decoration-none mt-2 d-inline-block">Clear Search</a>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach($vendor as $v): ?>
                    <?php 
                        // Routing logic
                        $cardLink = $v['StoreStatus'] == 'Open' ? "menu.php?vendor=" . urlencode($v['UserName']) : "#";
                        $cardStyle = $v['StoreStatus'] == 'Closed' ? "opacity: 0.7; filter: grayscale(100%); cursor: not-allowed;" : "";
                    ?>
                    
                    <div class="col">
                        <a href="<?php echo $cardLink; ?>" class="card h-100 vendor-card" style="<?php echo $cardStyle; ?>">
                            
                            <div class="card-img-wrap">
                                <span class="status-badge <?php echo $v['StoreStatus'] == 'Open' ? 'bg-open' : 'bg-closed'; ?>">
                                    <?php echo $v['StoreStatus'] == 'Open' ? '<i class="fa-solid fa-door-open"></i> OPEN' : '<i class="fa-solid fa-lock"></i> CLOSED'; ?>
                                </span>
                                <img src="<?php echo htmlspecialchars($v['VendorImage']); ?>" class="card-img-top" alt="Vendor Image" onerror="this.src='gambar/bitegologo.png'">
                            </div>
                            
                            <div class="card-body d-flex flex-column p-4">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h4 class="card-title fw-bold m-0"><?php echo htmlspecialchars($v['UserName']); ?></h4>
                                    <span class="rating-pill">
                                        <i class="fa-solid fa-star"></i> <?php echo number_format($v['VendorRating'], 1); ?>
                                    </span>
                                </div>
                                
                                <p class="card-text text-muted small flex-grow-1 mt-2">
                                    <?php echo htmlspecialchars(empty($v['VendorDescription']) ? 'Delicious meals prepared fresh daily. Click to view our full menu.' : $v['VendorDescription']); ?>
                                </p>
                                
                                <div class="btn-visit w-100 py-2 mt-3">
                                    <?php echo $v['StoreStatus'] == 'Open' ? 'View Full Menu <i class="fa-solid fa-arrow-right ms-2"></i>' : 'Currently Unavailable'; ?>
                                </div>
                            </div>
                            
                        </a>
                    </div>
                    
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>