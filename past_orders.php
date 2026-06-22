<?php
session_start();
include("db.php");

if (!isset($_SESSION['UserID'])) {
    header("Location: loginforcust.php");
    exit();
}

$custID = $_SESSION['UserID'];

$orders = [];
// 3NF FIX: Add the * 1.06 tax multiplier so the Past Orders history matches the Receipts!
$query = "SELECT o.*, v.UserName AS VendorName, (SUM(od.OrderedPrice) * 1.06) AS OrderTotal
          FROM `order` o 
          JOIN `order_detail` od ON o.OrderID = od.OrderID
          JOIN `menu_item` m ON od.ItemID = m.ItemID
          JOIN `user` v ON m.UserID = v.UserID
          WHERE o.UserID = '$custID' 
          GROUP BY o.OrderID
          ORDER BY o.OrderDate DESC";
          
$result = mysqli_query($link, $query);

$hasActiveOrders = false; // NEW: Watcher flag

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $orderID = $row['OrderID'];
        
        // Check if we need auto-refresh
        if (in_array($row['OrderStatus'], ['Pending', 'Cooking', 'Ready'])) {
            $hasActiveOrders = true;
        }

        $items = [];
        $itemQ = "SELECT od.Quantity, IFNULL(m.ItemName, 'Deleted Menu Item') as ItemName FROM order_detail od LEFT JOIN menu_item m ON od.ItemID = m.itemID WHERE od.OrderID='$orderID'";
        $itemRes = mysqli_query($link, $itemQ);
        if ($itemRes) {
            while ($i = mysqli_fetch_assoc($itemRes)) {
                $items[] = floatval($i['Quantity']) . "x " . $i['ItemName'];
            }
        }
        $row['items_string'] = implode(', ', $items);
        
        $revQ = mysqli_query($link, "SELECT * FROM review WHERE OrderID='$orderID'");
        if (mysqli_num_rows($revQ) > 0) {
            $row['is_reviewed'] = true;
            $revData = mysqli_fetch_assoc($revQ);
            $row['stars'] = $revData['Rating'];
        } else {
            $row['is_reviewed'] = false;
        }
        
        $orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Past Orders | BiteGo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f8; color: #000; padding: 40px 20px;}
        .container { max-width: 900px; margin: 0 auto; }
        
        .header-area { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-area h1 { margin: 0; font-size: 32px; font-weight: 900; letter-spacing: -1px; }
        .btn-back { background: #fff; color: #000; padding: 10px 20px; border-radius: 30px; text-decoration: none; font-weight: bold; font-size: 14px; border: 1px solid #ddd; transition: 0.3s; }
        .btn-back:hover { background: #000; color: #fff; border-color: #000; }

        .order-card { background: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); border: 1px solid #eee; margin-bottom: 20px; padding: 25px; transition: transform 0.3s;}
        .order-card:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(0,0,0,0.08); }
        
        .card-top { display: flex; justify-content: space-between; border-bottom: 2px dashed #eee; padding-bottom: 15px; margin-bottom: 15px; }
        .vendor-name { font-size: 20px; font-weight: 900; margin: 0 0 5px 0; }
        .order-date { font-size: 13px; color: #888; font-weight: bold; }
        
        .status-badge { padding: 6px 15px; border-radius: 20px; font-size: 12px; font-weight: 800; text-transform: uppercase; height: fit-content; }
        .status-done { background: #e8f5e9; color: #2e7d32; }
        .status-pending { background: #fff4e5; color: #f57f17; }
        .status-cooking { background: #e3f2fd; color: #1976d2; }
        .status-ready { background: #f3e5f5; color: #558b2f; }
        .status-cancelled { background: #fdf0f0; color: #d9534f; }
        .status-unpaid { background: #eee; color: #666; }

        .card-mid { margin-bottom: 20px; }
        .item-list { font-size: 15px; color: #444; line-height: 1.6; }
        
        .card-bottom { display: flex; justify-content: space-between; align-items: center; background: #fafafa; padding: 15px; border-radius: 10px; }
        .total-price { font-size: 18px; font-weight: 900; }
        
        .btn-review { background: #000; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s;}
        .btn-review:hover { background: #333; }
        .btn-track { background: #e3f2fd; color: #1976d2; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; transition: 0.3s; font-size: 14px;}
        .btn-track:hover { background: #bbdefb; }

        /* Modal */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: #fff; padding: 40px; border-radius: 15px; width: 400px; text-align: center; }
        .modal-content h2 { margin-top: 0; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 8px; }
        .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; font-family: inherit; }
        .btn-submit { background: #000; color: #fff; width: 100%; padding: 15px; border: none; border-radius: 8px; font-weight: bold; font-size: 16px; cursor: pointer; margin-bottom: 10px; }
        .btn-cancel { background: transparent; color: #888; width: 100%; padding: 10px; border: none; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header-area">
            <h1>Your Order History</h1>
            <a href="frontpage.php" class="btn-back">&larr; Back to Home</a>
        </div>

        <?php if(empty($orders)): ?>
            <div style="text-align:center; padding: 80px 20px; background:#fff; border-radius:20px; border:1px solid #eee;">
                <i class="fa-solid fa-receipt" style="font-size: 50px; color:#ddd; margin-bottom:20px;"></i>
                <h2 style="margin:0 0 10px 0;">No past orders found.</h2>
                <p style="color:#888; margin-bottom: 20px;">Looks like you haven't ordered anything yet!</p>
                <a href="menu.php" class="btn-review" style="text-decoration:none;">Browse Menu</a>
            </div>
        <?php else: ?>
            <?php foreach($orders as $order): ?>
                <?php 
                    $badgeClass = 'status-pending';
                    if($order['OrderStatus'] == 'Done') $badgeClass = 'status-done';
                    if($order['OrderStatus'] == 'Cooking') $badgeClass = 'status-cooking';
                    if($order['OrderStatus'] == 'Ready') $badgeClass = 'status-ready';
                    if($order['OrderStatus'] == 'Cancelled') $badgeClass = 'status-cancelled';
                    if($order['OrderStatus'] == 'Unpaid') $badgeClass = 'status-unpaid';
                ?>
                <div class="order-card">
                    <div class="card-top">
                        <div>
                            <p class="vendor-name"><?php echo htmlspecialchars($order['VendorName']); ?></p>
                            <p class="order-date"><?php echo date("d M Y, h:i A", strtotime($order['OrderDate'])); ?> • Order #<?php echo $order['OrderID']; ?></p>
                        </div>
                        <div class="status-badge <?php echo $badgeClass; ?>"><?php echo $order['OrderStatus']; ?></div>
                    </div>
                    
                    <div class="card-mid">
                        <div class="item-list"><?php echo htmlspecialchars($order['items_string']); ?></div>
                    </div>

                    <div class="card-bottom">
                        <div class="total-price">RM <?php echo number_format($order['OrderTotal'], 2); ?></div>
                        <div>
                            <?php if(in_array($order['OrderStatus'], ['Pending', 'Cooking', 'Ready'])): ?>
                                <a href="track_order.php?order=<?php echo $order['OrderID']; ?>" class="btn-track"><i class="fa-solid fa-location-crosshairs"></i> Live Tracking</a>
                            <?php elseif($order['OrderStatus'] == 'Done'): ?>
                                <?php if(isset($order['is_reviewed']) && $order['is_reviewed']): ?>
                                    <div style="color: gold; font-size: 18px; letter-spacing: 2px;">
                                        <?php 
                                            for($i=1; $i<=5; $i++) {
                                                if($i <= $order['stars']) echo '<i class="fa-solid fa-star"></i>';
                                                else echo '<i class="fa-regular fa-star" style="color:#ddd;"></i>';
                                            }
                                        ?>
                                    </div>
                                <?php else: ?>
                                    <button class="btn-review" onclick="openReviewModal('<?php echo $order['OrderID']; ?>', '<?php echo $order['VendorUserID']; ?>', '<?php echo addslashes(htmlspecialchars($order['VendorName'])); ?>')">Leave a Review</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="reviewModal" class="modal-overlay">
        <div class="modal-content">
            <h2>Rate your meal & vendor</h2>
            <p style="color:#666; font-size:14px; margin-bottom:20px;">How was your experience from <strong id="modalVendorName"></strong>?</p>
            
            <form action="process_review.php" method="POST">
                <input type="hidden" name="orderID" id="modalOrderID">
                <input type="hidden" name="vendorID" id="modalVendorID">
                
                <div class="form-group">
                    <label>Food Rating (1 to 5 Stars)</label>
                    <select name="rating" required>
                        <option value="5">⭐⭐⭐⭐⭐ (5 - Excellent!)</option>
                        <option value="4">⭐⭐⭐⭐ (4 - Very Good)</option>
                        <option value="3">⭐⭐⭐ (3 - Average)</option>
                        <option value="2">⭐⭐ (2 - Not Great)</option>
                        <option value="1">⭐ (1 - Terrible)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Vendor Service Rating (1 to 5 Stars)</label>
                    <select name="vendor_rating" required>
                        <option value="5">⭐⭐⭐⭐⭐ (5 - Excellent!)</option>
                        <option value="4">⭐⭐⭐⭐ (4 - Very Good)</option>
                        <option value="3">⭐⭐⭐ (3 - Average)</option>
                        <option value="2">⭐⭐ (2 - Not Great)</option>
                        <option value="1">⭐ (1 - Terrible)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Leave a Comment (Optional)</label>
                    <textarea name="comment" rows="3" placeholder="Tell us what you loved..."></textarea>
                </div>

                <button type="submit" class="btn-submit">Submit Review</button>
                <button type="button" class="btn-cancel" onclick="closeReviewModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function openReviewModal(orderID, vendorID, vendorName) {
            document.getElementById('modalOrderID').value = orderID;
            document.getElementById('modalVendorID').value = vendorID;
            document.getElementById('modalVendorName').innerText = vendorName;
            document.getElementById('reviewModal').style.display = 'flex';
        }
        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
        }
        
        // FIX: SMART AUTO-REFRESH FOR ACTIVE ORDERS
        <?php if($hasActiveOrders): ?>
            setTimeout(() => {
                window.location.reload();
            }, 15000);
        <?php endif; ?>
    </script>
</body>
</html>