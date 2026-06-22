<?php
session_start();
include("db.php");

$orderID = isset($_GET['order']) ? mysqli_real_escape_string($link, $_GET['order']) : 'UNKNOWN';
$isLoggedIn = isset($_SESSION['UserID']);

// FIXED: Only select OrderDate since OrderTotal doesn't exist in the DB schema
$orderQuery = mysqli_query($link, "SELECT OrderDate FROM `order` WHERE OrderID = '$orderID' LIMIT 1");
$orderData = mysqli_fetch_assoc($orderQuery);
$orderDate = $orderData ? $orderData['OrderDate'] : date('Y-m-d H:i:s'); 

// Fetch passed total from URL if available
$passedTotal = isset($_GET['total']) ? floatval($_GET['total']) : null;

// FETCH ITEMS FROM DATABASE
$items = [];
$subtotal = 0;

$itemQ = "SELECT od.Quantity, od.OrderedPrice, m.ItemName 
          FROM order_detail od 
          LEFT JOIN menu_item m 
          ON od.ItemID = m.ItemID 
          WHERE od.OrderID='$orderID'";

$itemRes = mysqli_query($link, $itemQ);

if ($itemRes) {
    while ($row = mysqli_fetch_assoc($itemRes)) {
        $items[] = $row;
        $subtotal += $row['OrderedPrice'];
    }
}
$tax = $subtotal * 0.06;

// CALCULATE MATH AND DISCOUNT
$expectedTotal = $subtotal + $tax;
$finalTotal = ($passedTotal !== null) ? $passedTotal : $expectedTotal;
$discountAmount = $expectedTotal - $finalTotal;
$hasDiscount = ($discountAmount >= 0.01); // If there's a difference, points were used

// Calculate how many points they will EARN (RM 1 = 1 pt)
$pointsToEarn = floor($finalTotal);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Digital Receipt | BiteGo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 40px 0; }
        .receipt-container { background: #fff; width: 450px; border-radius: 15px; position: relative; box-shadow: 0 20px 40px rgba(0,0,0,0.08); padding: 40px; box-sizing: border-box; animation: dropIn 0.6s cubic-bezier(0.25, 1, 0.5, 1); }
        @keyframes dropIn { from { opacity: 0; transform: translateY(-40px); } to { opacity: 1; transform: translateY(0); } }
        .receipt-header { text-align: center; border-bottom: 2px dashed #eee; padding-bottom: 25px; margin-bottom: 25px; }
        .receipt-header img { height: 40px; margin-bottom: 15px; }
        .receipt-header h1 { margin: 0 0 5px 0; font-size: 24px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; }
        .receipt-header p { margin: 0; color: #888; font-size: 14px; font-family: monospace; }
        .receipt-body { margin-bottom: 30px; }
        .item-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 15px; color: #444; }
        .item-qty { font-weight: bold; width: 30px; }
        .item-name { flex: 1; padding-right: 15px; }
        .item-price { font-weight: bold; color: #000; }
        .receipt-math { border-top: 2px solid #000; padding-top: 15px; margin-top: 15px; }
        .math-line { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: #666; }
        .grand-total { display: flex; justify-content: space-between; font-size: 24px; font-weight: 900; margin-top: 15px; border-top: 2px dashed #eee; padding-top: 15px; color: #000; }
        .btn-home { display: block; text-align: center; background: #000; color: #fff; text-decoration: none; padding: 15px; border-radius: 10px; font-weight: bold; font-size: 16px; transition: 0.3s; margin-top: 20px;}
        .btn-home:hover { background: #333; transform: translateY(-2px); }
        .btn-track { display: block; text-align: center; background: #e3f2fd; color: #1976d2; text-decoration: none; padding: 15px; border-radius: 10px; font-weight: bold; font-size: 16px; transition: 0.3s; margin-top: 10px;}
        .btn-track:hover { background: #bbdefb; transform: translateY(-2px); }
    </style>
</head>
<body>

    <div class="receipt-container">
        <div class="receipt-header">
            <img src="gambar/bitegologo.png" alt="BiteGo" onerror="this.style.display='none'">
            <h1>Payment Success</h1>
            <p>Order #<?php echo $orderID; ?></p>
            <p style="margin-top: 5px; font-size: 13px; font-family: 'Segoe UI', sans-serif;"><?php echo date("d M Y, h:i A", strtotime($orderDate)); ?></p>
        </div>

        <div class="receipt-body" id="receiptItems">
            <?php if(empty($items)): ?>
                <div style='text-align:center; color:#d9534f;'>Error: Could not retrieve order details from database.</div>
            <?php else: ?>
                <?php foreach($items as $item): ?>
                    <div class="item-row">
                        <span class="item-qty"><?php echo floatval($item['Quantity']); ?>x</span>
                        <span class="item-name"><?php echo htmlspecialchars($item['ItemName']); ?></span>
                        <span class="item-price">RM <?php echo number_format($item['OrderedPrice'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="receipt-math">
            <div class="math-line"><span>Subtotal</span><span id="subTotal">RM <?php echo number_format($subtotal, 2); ?></span></div>
            <div class="math-line"><span>Service Tax (6% SST)</span><span id="taxTotal">RM <?php echo number_format($tax, 2); ?></span></div>
            
            <?php if($hasDiscount): ?>
            <div class="math-line" style="color: #2e7d32; font-weight: bold;">
                <span>BiteGo Points Discount</span>
                <span>- RM <?php echo number_format($discountAmount, 2); ?></span>
            </div>
            <?php endif; ?>

            <div class="grand-total"><span>Total Paid</span><span id="finalTotal">RM <?php echo number_format($finalTotal, 2); ?></span></div>
        </div>

        <?php if($isLoggedIn && $pointsToEarn > 0): ?>
        <div style="background: #fffcf2; border: 1px solid #ffecb3; padding: 15px; border-radius: 10px; margin-top: 20px; text-align: center; font-weight: bold; color: #b07d00; font-size: 13px;">
            <i class="fa-solid fa-star" style="color: gold; margin-right: 5px;"></i> You will earn <?php echo $pointsToEarn; ?> pts when this order is completed!
        </div>
        <?php endif; ?>

        <a href="track_order.php?order=<?php echo $orderID; ?>" class="btn-track"><i class="fa-solid fa-location-crosshairs"></i> Track Live Kitchen Status</a>
        <a href="frontpage.php" class="btn-home">Return to Home</a>
    </div>

</body>
</html>