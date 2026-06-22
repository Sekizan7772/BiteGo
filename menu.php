<?php
session_start();
include('db.php');

$isLoggedIn = isset($_SESSION['UserID']);
$custName = isset($_SESSION['CustName']) ? $_SESSION['CustName'] : '';
$custPoints = 0;
if ($isLoggedIn) {
    $cID = $_SESSION['UserID'];
    $ptsRes = mysqli_query($link, "SELECT Points FROM customer WHERE UserID='$cID'");
    if ($pRow = mysqli_fetch_assoc($ptsRes)) {
        $custPoints = (int)$pRow['Points'];
    }
}
$currentTypeQuery = isset($_GET['type']) ? "?type=" . urlencode($_GET['type']) : "";
$typeParamForLinks = isset($_GET['type']) ? "&type=" . urlencode($_GET['type']) : "";

if (isset($_GET['clear_vendor'])) {
    unset($_SESSION['VendorChoice']);
    unset($_SESSION['bitego_ordertype']);
    header("Location: menu.php" . $currentTypeQuery);
    exit();
}

if (isset($_GET['vendor'])) {
    $_SESSION['VendorChoice'] = $_GET['vendor'];
    header("Location: menu.php" . $currentTypeQuery); 
    exit();
}

$vendorChoice = isset($_SESSION['VendorChoice']) ? $_SESSION['VendorChoice'] : '';
$allVendors = [];

// 3NF FIX: Get vendor names from the 'user' table
$vendorListQuery = mysqli_query($link, "SELECT UserName FROM user WHERE Role = 'Vendor'");
if($vendorListQuery) { 
    while($vRow = mysqli_fetch_assoc($vendorListQuery)) { 
        $allVendors[] = $vRow['UserName']; 
    } 
}

$menu_data = [];
$storeStatus = 'Open';
$totalTables = 5;
$vendorUserID = '';
$occupiedTables = [];

if ($vendorChoice !== '') {
    $vSafeSearch = mysqli_real_escape_string($link, $vendorChoice);
    
    // 3NF FIX: Join vendor and user tables to get Store Status
    $vInfoQ = mysqli_query($link, "SELECT v.UserID, v.StoreStatus, v.TotalTables 
                                   FROM vendor v 
                                   JOIN user u ON v.UserID = u.UserID 
                                   WHERE u.UserName LIKE '%$vSafeSearch%' LIMIT 1");
    if($vInfo = mysqli_fetch_assoc($vInfoQ)) { 
        $storeStatus = $vInfo['StoreStatus']; 
        $totalTables = $vInfo['TotalTables']; 
        $vendorUserID = $vInfo['UserID'];
    }

    if (!empty($vendorUserID)) {
        $occQ = mysqli_query($link, "SELECT TableNo, UserID FROM `order` WHERE VendorUserID='$vendorUserID' AND OrderStatus IN ('Unpaid', 'Pending', 'Cooking', 'Ready') AND OrderType='dinein' AND TableNo != ''");
        if ($occQ) {
            while ($occRow = mysqli_fetch_assoc($occQ)) {
                $occupiedTables[$occRow['TableNo']] = $occRow['UserID'];
            }
        }
    }

    // 3NF FIX: Join menu_item, category, and user tables to get the food and category names
    $query = "SELECT m.*, c.CategoryName AS Category 
              FROM menu_item m 
              JOIN category c ON m.CategoryID = c.CategoryID 
              JOIN user u ON m.UserID = u.UserID 
              WHERE u.UserName LIKE '%$vSafeSearch%'";
              
    $result = mysqli_query($link, $query);
    if($result) {
        while($row = mysqli_fetch_assoc($result)) {
            $cat = $row['Category']; // Now correctly pulled from the category table!
            if(!isset($menu_data[$cat])) { $menu_data[$cat] = []; }
            $menu_data[$cat][] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>BiteGo | Menu</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        html { scroll-behavior: smooth; }
        body { background-color: #f4f6f8; font-family: 'Alexandria', sans-serif; color: #111; margin: 0; }
        #orderTypeBanner { background-color: #000; color: #fff; text-align: center; padding: 12px; font-size: 14px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; display: none; position: sticky; top: 60px; z-index: 90; box-shadow: 0 4px 15px rgba(0,0,0,0.1);}
        .vendor-choice-text { color: #aaa; font-size: 11px; font-weight: 600; letter-spacing: 1px; display: block; margin-top: 4px;}
        .change-vendor-link { color: #fff; text-decoration: underline; margin-left: 8px; cursor: pointer; transition: 0.3s; }
        .change-vendor-link:hover { color: #aaa; }
        .page-layout { display: flex; max-width: 1400px; margin: 0 auto; align-items: flex-start; gap: 40px; padding: 30px 40px; }
        .menu-area { flex: 1; }
        .menu-header { font-size: 42px; font-weight: 900; margin-bottom: 5px; margin-top: 0; letter-spacing: -1px; color: #000; }
        .menu-subtitle { color: #666; font-size: 15px; margin-bottom: 30px; font-weight: 500; }
        .category-nav { display: flex; gap: 15px; overflow-x: auto; padding: 15px 0; margin-bottom: 40px; scrollbar-width: none; position: sticky; top: 120px; background: rgba(244, 246, 248, 0.95); backdrop-filter: blur(10px); z-index: 80; border-bottom: 1px solid #e0e0e0; }
        .category-nav::-webkit-scrollbar { display: none; }
        .category-nav a { background: #fff; border: 1px solid #ddd; padding: 10px 24px; border-radius: 50px; text-decoration: none; color: #555; font-weight: 700; font-size: 14px; white-space: nowrap; transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1); box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        .category-nav a:hover, .category-nav a:active { background: #000; color: #fff; border-color: #000; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.1); }
        section { scroll-margin-top: 200px; }
        .category-title { font-size: 24px; font-weight: 900; margin-bottom: 25px; color: #000; display: inline-block; }
        .food-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 25px; margin-bottom: 60px; }
        .food-card { background: #fff; border-radius: 20px; overflow: hidden; border: 1px solid #eee; transition: all 0.3s; display: flex; flex-direction: row; height: 170px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .food-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.08); border-color: #ddd; }
        .food-card.sold-out { opacity: 0.6; filter: grayscale(80%); pointer-events: none; }
        .img-wrapper { width: 160px; height: 100%; overflow: hidden; flex-shrink: 0; background-color: #f9f9f9; position: relative; }
        .img-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .food-card:hover .img-wrapper img { transform: scale(1.08); }
        .food-info { padding: 15px 20px; display: flex; flex-direction: column; flex: 1; justify-content: space-between; }
        .food-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
        .food-badge { background-color: #e8f5e9; color: #2e7d32; font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 50px; text-transform: uppercase; letter-spacing: 1px; }
        .badge-soldout { background-color: #fdf0f0; color: #d9534f; border: 1px solid #f5c6cb; }
        .food-eta { font-size: 12px; font-weight: bold; color: #888; }
        .food-title { font-size: 17px; font-weight: 800; color: #000; margin: 0 0 5px 0; line-height: 1.2; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;}
        .food-desc { font-size: 12px; color: #666; margin: 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .food-footer { display: flex; justify-content: space-between; align-items: center; margin-top: auto;}
        .food-price { font-size: 18px; font-weight: 900; color: #000; }
        .add-btn { background-color: #f4f6f8; color: #000; border: 1px solid #ddd; width: 40px; height: 40px; border-radius: 12px; font-size: 20px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; display: flex; justify-content: center; align-items: center; }
        .food-card:hover .add-btn { background-color: #000; color: #fff; border-color: #000; }
        .add-btn:hover { transform: scale(1.1); }
        .cart-sidebar { width: 360px; background-color: #fff; height: calc(100vh - 120px); position: sticky; top: 120px; border-radius: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.06); border: 1px solid #eee; display: flex; flex-direction: column; overflow: hidden; }
        .cart-header { padding: 25px 25px 15px; font-size: 22px; font-weight: 900; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f0f0f0; }
        .cart-count-badge { background: #000; color: #fff; padding: 4px 12px; border-radius: 50px; font-size: 14px; font-weight: 800; }
        .cart-items { flex: 1; overflow-y: auto; padding: 20px 25px; scrollbar-width: thin; }
        .cart-item { display: flex; align-items: center; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px dashed #eee; }
        .cart-item-img { width: 55px; height: 55px; border-radius: 12px; object-fit: cover; margin-right: 15px; border: 1px solid #eee; }
        .cart-item-details { flex: 1; }
        .cart-item-name { font-size: 14px; font-weight: 700; margin: 0 0 5px 0; color: #000; line-height: 1.3;}
        .cart-item-price { font-size: 14px; font-weight: 900; color: #555; margin: 0;}
        .qty-controls { display: flex; align-items: center; background-color: #f4f6f8; border-radius: 50px; padding: 4px; border: 1px solid #eee;}
        .qty-btn { background: #fff; border: 1px solid #ddd; border-radius: 50%; width: 26px; height: 26px; font-size: 14px; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #000; transition: 0.2s;}
        .qty-btn:hover { background: #000; color: #fff; border-color: #000;}
        .qty-text { font-size: 13px; font-weight: 800; width: 24px; text-align: center; }
        .empty-cart-msg { text-align: center; color: #aaa; margin-top: 60px; font-weight: 600; font-size: 15px; display: flex; flex-direction: column; align-items: center; gap: 15px;}
        .empty-cart-msg i { font-size: 40px; color: #ddd; }
        .cart-footer { padding: 25px; border-top: 1px solid #eee; background-color: #fff; }
        .cart-total { display: flex; justify-content: space-between; align-items: flex-end; font-size: 16px; font-weight: 600; color: #555; margin-bottom: 20px; }
        .cart-total span:last-child { font-size: 26px; font-weight: 900; color: #000; }
        .table-group { margin-bottom: 10px; }
        .table-group select { width: 100%; padding: 14px; border-radius: 12px; border: 1px solid #ddd; font-size: 14px; font-weight: 600; font-family: inherit; background: #f9f9f9; color: #333; outline: none; transition: 0.3s;}
        .table-group select:focus { border-color: #000; background: #fff; }
        select option[disabled] { color: #d9534f; font-style: italic; background: #fdf0f0; }
        
        .checkout-btn { width: 100%; background-color: #000; color: #fff; border: none; padding: 18px; border-radius: 14px; font-size: 16px; font-weight: 800; cursor: pointer; transition: all 0.3s; text-transform: uppercase; letter-spacing: 1px; display: flex; justify-content: center; align-items: center; gap: 10px;}
        .checkout-btn:hover { background-color: #222; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.15); }
        .checkout-btn:disabled { background-color: #ccc; color: #fff; cursor: not-allowed; transform: none; box-shadow: none; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); z-index: 9999; justify-content: center; align-items: center; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); }
        .modal-content { background-color: #fff; padding: 50px 40px; border-radius: 24px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.3); width: 420px; border: 1px solid rgba(255,255,255,0.2); animation: popIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); max-height: 80vh; overflow-y: auto;}
        @keyframes popIn { 0% { transform: scale(0.9) translateY(20px); opacity: 0; } 100% { transform: scale(1) translateY(0); opacity: 1; } }
        .modal-content h2 { margin-top: 0; font-size: 30px; font-weight: 900; color: #000; letter-spacing: -1px; margin-bottom: 10px;}
        .modal-content p { color: #666; margin-bottom: 35px; font-size: 15px; line-height: 1.5; font-weight: 500;}
        .vendor-btn { background-color: #f8f9fa; color: #000; border: 1px solid #e0e0e0; padding: 18px; border-radius: 16px; text-decoration: none; font-weight: 800; font-size: 16px; transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.02);}
        .vendor-btn:hover { border-color: #000; background-color: #000; color: #fff; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .modal-buttons { display: flex; gap: 15px; justify-content: center; }
        .modal-btn { flex: 1; padding: 16px; font-size: 15px; font-weight: 800; border-radius: 14px; cursor: pointer; text-decoration: none; transition: all 0.3s; text-transform: uppercase; letter-spacing: 1px;}
        .btn-black { background-color: #000; color: #fff; border: 2px solid #000; }
        .btn-black:hover { background-color: transparent; color: #000; transform: translateY(-2px); }
        .btn-outline { background-color: transparent; color: #000; border: 2px solid #000; }
        .btn-outline:hover { background-color: #000; color: #fff; transform: translateY(-2px); }
        
        .ui-error-box { display: none; background: #fdf0f0; color: #d9534f; border: 1px solid #f5c6cb; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: bold; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>

    <nav class="navbar" id="navbar" style="background:rgba(255,255,255,0.98); border-bottom:1px solid #eee; position:sticky; top:0; z-index:100;">
    <a href="frontpage.php" class="nav-logo-link" style="color:#000;">BiteGo.</a>
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
            <a href="loginforcust.php?source=menu<?php echo isset($_GET['type']) ? '&type=' . urlencode($_GET['type']) : ''; ?>" class="top-btn">LOG IN</a>
        <?php endif; ?>
    </div>
</nav>

    <?php if ($vendorChoice == ''): ?>
        
        <div class="modal-overlay" style="display: flex;">
            <div class="modal-content">
                <i class="fa-solid fa-store" style="font-size: 40px; color: #000; margin-bottom: 20px;"></i>
                <h2>Choose a Vendor</h2>
                <p>You must select a restaurant before viewing the menu.</p>
                <div style="display: flex; flex-direction: column;">
                    <?php if(empty($allVendors)): ?>
                        <p style="color:#d9534f; font-weight:bold;">No vendors found in the database.</p>
                    <?php else: ?>
                        <?php foreach($allVendors as $vName): ?>
                            <a href="?vendor=<?php echo urlencode($vName); ?><?php echo $typeParamForLinks; ?>" class="vendor-btn">
                                <?php echo htmlspecialchars($vName); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <a href="frontpage.php" style="display: inline-block; margin-top: 20px; color: #888; text-decoration: underline; font-size: 14px; font-weight: 600; transition: 0.2s;">&larr; Back to Home</a>
            </div>
        </div>
        <style>body { overflow: hidden; }</style>

    <?php else: ?>

        <div id="orderTypeBanner">
            ORDER TYPE: <span id="displayType" style="color: #ff6b2b;">Loading...</span>
            <span class="vendor-choice-text">
                RESTAURANT: <?php echo htmlspecialchars($vendorChoice); ?> 
                <a href="vendorSelectPage.php" class="change-vendor-link"><i class="fa-solid fa-pen-to-square"></i> Change</a>
            </span>
        </div>

        <div class="page-layout">
            <main class="menu-area">
                <h1 class="menu-header"><?php echo htmlspecialchars($vendorChoice); ?></h1>
                <p class="menu-subtitle">Explore our delicious offerings and build your perfect meal.</p>

                <?php if($storeStatus === 'Closed'): ?>
                    <div style="background: #fdf0f0; color: #c9302c; padding: 25px; border-radius: 15px; text-align: center; border: 1px solid #ebccd1; margin-bottom: 30px; box-shadow: 0 5px 15px rgba(200,0,0,0.05);">
                        <i class="fa-solid fa-lock" style="font-size:32px; margin-bottom:10px; display:block;"></i>
                        <h3 style="margin: 0 0 5px 0; font-weight: 900;">RESTAURANT CLOSED</h3>
                        <p style="margin:0; font-size: 14px; font-weight: 600; color: #a94442;">You can view the menu, but online ordering is currently disabled.</p>
                    </div>
                <?php endif; ?>

                <nav class="category-nav">
                    <?php foreach($menu_data as $categoryName => $items): ?>
                        <a href="#<?php echo strtolower(str_replace(' ', '', $categoryName)); ?>"><?php echo htmlspecialchars($categoryName); ?></a>
                    <?php endforeach; ?>
                </nav>

                <?php if(empty($menu_data)): ?>
                    <div style="text-align: center; padding: 100px 0; color: #888;">
                        <i class="fa-solid fa-plate-wheat" style="font-size: 60px; color: #ddd; margin-bottom: 20px;"></i>
                        <h2 style="color: #000; font-weight: 900;">Menu Not Available</h2>
                        <p>This vendor hasn't added any items yet.</p>
                        <a href="vendorSelectPage.php" class="vendor-btn" style="width: 250px; margin: 30px auto;">Choose Another Vendor</a>
                    </div>
                <?php endif; ?>

                <?php foreach($menu_data as $categoryName => $items): ?>
                    <section id="<?php echo strtolower(str_replace(' ', '', $categoryName)); ?>">
                        <h2 class="category-title"><?php echo htmlspecialchars($categoryName); ?></h2>
                        <div class="food-list">
                            <?php foreach($items as $item): ?>
                                <div class="food-card <?php echo $item['Availability'] == 'Sold Out' ? 'sold-out' : ''; ?>">
                                    <div class="img-wrapper">
                                        <img src="<?php echo htmlspecialchars($item['ItemImage']); ?>" alt="<?php echo htmlspecialchars($item['ItemName']); ?>" onerror="this.src='gambar/bitegologo.png'">
                                    </div>
                                    <div class="food-info">
                                        <div>
                                            <div class="food-meta">
                                                <?php if($item['Availability'] == 'Sold Out'): ?>
                                                    <span class="food-badge badge-soldout">Sold Out</span>
                                                <?php else: ?>
                                                    <span class="food-badge">Available</span>
                                                <?php endif; ?>
                                                <span class="food-eta"><i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($item['ETAMinutes']); ?> Min</span>
                                            </div>
                                            <h3 class="food-title"><?php echo htmlspecialchars($item['ItemName']); ?></h3>
                                            <p class="food-desc"><?php echo htmlspecialchars($item['ItemDesc']); ?></p>
                                        </div>
                                        <div class="food-footer">
                                            <span class="food-price">RM <?php echo number_format($item['ItemPrice'], 2); ?></span>
                                            <button class="add-btn" 
                                                <?php if($storeStatus == 'Closed' || $item['Availability'] == 'Sold Out') echo 'disabled style="cursor:not-allowed; opacity:0.5;"'; ?>
                                                onclick="addToCart('<?php echo $item['ItemID']; ?>', '<?php echo addslashes(htmlspecialchars($item['ItemName'])); ?>', <?php echo $item['ItemPrice']; ?>, '<?php echo htmlspecialchars($item['ItemImage']); ?>')">
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </main>

            <aside class="cart-sidebar">
                <div class="cart-header">
                    My Order
                    <span id="cartCount" class="cart-count-badge">0</span>
                </div>
                <div class="cart-items" id="cartItemsContainer">
                    <div class="empty-cart-msg">
                        <i class="fa-solid fa-basket-shopping"></i>
                        <span>Your cart is empty.<br>Add some delicious items!</span>
                    </div>
                </div>
                <div class="cart-footer">
                    <div class="cart-total">
                        <span>Total</span>
                        <span id="cartGrandTotal">RM 0.00</span>
                    </div>
                    
                    <div class="table-group" id="tableSelectionDiv">
                        <select id="tableNum" name="tableNum">
                            <option value="">-- Choose Your Table --</option>
                            
                            <?php 
                            $currentCustID = isset($_SESSION['UserID']) ? $_SESSION['UserID'] : 'GUEST_UNAVAILABLE';
                            
                            for($i = 1; $i <= $totalTables; $i++): 
                                $isOccupied = array_key_exists((string)$i, $occupiedTables);
                                $occupierID = $isOccupied ? $occupiedTables[(string)$i] : '';
                                
                                if ($isOccupied) {
                                    if ($occupierID === $currentCustID) {
                                        echo '<option value="' . $i . '">Table ' . $i . ' (Your Table)</option>';
                                    } else {
                                        echo '<option value="' . $i . '" disabled>Table ' . $i . ' (Occupied)</option>';
                                    }
                                } else {
                                    echo '<option value="' . $i . '">Table ' . $i . '</option>';
                                }
                            endfor; 
                            ?>
                        </select>
                    </div>

                    <div id="menuError" class="ui-error-box"></div>

                    <button class="checkout-btn" id="checkoutBtn" disabled onclick="proceedToCheckout()">
                        Checkout <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </aside>
        </div>

        <div id="choiceModal" class="modal-overlay">
            <div class="modal-content">
                <i class="fa-solid fa-bell-concierge" style="font-size: 40px; color: #000; margin-bottom: 20px;"></i>
                <h2>Dining Option</h2>
                <p>How would you like your order prepared today?</p>
                <div class="modal-buttons">
                    <a href="?type=dinein" class="modal-btn btn-black">Dine In</a>
                    <a href="?type=takeout" class="modal-btn btn-outline">Take Out</a>
                </div>
            </div>
        </div>

        <script>
            // SMART CART MANAGER
            const currentVendorName = "<?php echo addslashes($vendorChoice); ?>";
            const savedVendorName = localStorage.getItem('bitego_current_vendor');

            if (savedVendorName && savedVendorName !== currentVendorName) {
                localStorage.removeItem('bitego_cart');
            }
            localStorage.setItem('bitego_current_vendor', currentVendorName);

            const urlParams = new URLSearchParams(window.location.search);
            const orderType = urlParams.get('type');
            const tableDiv = document.getElementById('tableSelectionDiv');
            const isStoreClosed = <?php echo $storeStatus === 'Closed' ? 'true' : 'false'; ?>;

            if (orderType === 'dinein') {
                document.getElementById('displayType').innerText = "Dine In";
                document.getElementById('orderTypeBanner').style.display = "block"; 
            } 
            else if (orderType === 'takeout') {
                document.getElementById('displayType').innerText = "Take Out";
                document.getElementById('orderTypeBanner').style.display = "block"; 
                tableDiv.style.display = "none"; 
            } 
            else {
                document.getElementById('choiceModal').style.display = "flex"; 
                document.body.style.overflow = "hidden"; 
            }

            let cart = JSON.parse(localStorage.getItem('bitego_cart')) || [];
            updateCartUI(); // Load existing cart on page refresh

            function addToCart(id, name, price, img) {
                if(isStoreClosed) return; 
                let existingItem = cart.find(item => item.id === id);
                if(existingItem) { existingItem.qty += 1; } else { cart.push({ id: id, name: name, price: price, img: img, qty: 1 }); }
                updateCartUI();
            }

            function changeQty(id, amount) {
                let item = cart.find(item => item.id === id);
                if(item) {
                    item.qty += amount;
                    if(item.qty <= 0) { cart = cart.filter(cartItem => cartItem.id !== id); }
                }
                updateCartUI();
            }

            function updateCartUI() {
                const container = document.getElementById('cartItemsContainer');
                const totalEl = document.getElementById('cartGrandTotal');
                const countEl = document.getElementById('cartCount');
                const checkoutBtn = document.getElementById('checkoutBtn');
                
                container.innerHTML = '';
                document.getElementById('menuError').style.display = 'none';

                if (cart.length === 0) {
                    container.innerHTML = '<div class="empty-cart-msg"><i class="fa-solid fa-basket-shopping"></i><span>Your cart is empty.<br>Add some delicious items!</span></div>';
                    totalEl.innerText = 'RM 0.00';
                    countEl.innerText = '0';
                    checkoutBtn.disabled = true;
                    localStorage.removeItem('bitego_cart');
                    return;
                }

                let grandTotal = 0;
                let totalItems = 0;

                cart.forEach(item => {
                    let itemTotal = item.price * item.qty;
                    grandTotal += itemTotal;
                    totalItems += item.qty;

                    container.innerHTML += `
                        <div class="cart-item">
                            <img src="${item.img}" class="cart-item-img" onerror="this.src='gambar/bitegologo.png'">
                            <div class="cart-item-details">
                                <p class="cart-item-name">${item.name}</p>
                                <p class="cart-item-price">RM ${itemTotal.toFixed(2)}</p>
                            </div>
                            <div class="qty-controls">
                                <button class="qty-btn" onclick="changeQty('${item.id}', -1)"><i class="fa-solid fa-minus" style="font-size:10px;"></i></button>
                                <span class="qty-text">${item.qty}</span>
                                <button class="qty-btn" onclick="changeQty('${item.id}', 1)"><i class="fa-solid fa-plus" style="font-size:10px;"></i></button>
                            </div>
                        </div>
                    `;
                });

                totalEl.innerText = 'RM ' + grandTotal.toFixed(2);
                countEl.innerText = totalItems;
                checkoutBtn.disabled = isStoreClosed ? true : false;
                localStorage.setItem('bitego_cart', JSON.stringify(cart));
            }

            function proceedToCheckout() {
                const errEl = document.getElementById('menuError');
                errEl.style.display = 'none';

                if(isStoreClosed) {
                    errEl.innerText = "The restaurant is closed.";
                    errEl.style.display = 'block';
                    return;
                }

                if(orderType === 'dinein') {
                    const tableNum = document.getElementById('tableNum').value;
                    if(!tableNum) {
                        errEl.innerText = "Please select a table number!";
                        errEl.style.display = 'block';
                        return;
                    }
                    localStorage.setItem('bitego_table', tableNum);
                }
                
                localStorage.setItem('bitego_ordertype', orderType);

                const isUserLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

                if (isUserLoggedIn) {
                    window.location.href = "checkout.php"; 
                } else {
                    window.location.href = "loginforcust.php?source=checkout"; 
                }
            }
        </script>
    <?php endif; ?>
</body>
</html>