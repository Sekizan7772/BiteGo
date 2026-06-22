<?php
session_start();
include("db.php");

// SILENT API FOR JAVASCRIPT
if (isset($_GET['api']) && $_GET['api'] == 'status') {
    $oID = mysqli_real_escape_string($link, $_GET['order']);
    $res = mysqli_query($link, "SELECT OrderStatus FROM `order` WHERE OrderID='$oID'");
    $row = mysqli_fetch_assoc($res);
    echo json_encode(['status' => $row['OrderStatus']]);
    exit();
}

$orderID = isset($_GET['order']) ? mysqli_real_escape_string($link, $_GET['order']) : '';

if (empty($orderID)) {
    die("<h2 style='text-align:center; padding: 50px; font-family:sans-serif;'>Invalid Order Link.</h2>");
}

// 3NF FIX: Dynamically calculate the missing OrderTotal (Subtotal + 6% Tax) on the fly!
$query = "SELECT o.*, 
             u_vendor.UserName AS VendorName, 
             IFNULL(u_cust.UserName, 'Guest Diner') AS CustName,
             (SELECT SUM(OrderedPrice) * 1.06 FROM order_detail WHERE OrderID = o.OrderID) AS OrderTotal
          FROM `order` o 
          JOIN user u_vendor ON o.VendorUserID = u_vendor.UserID 
          LEFT JOIN user u_cust ON o.UserID = u_cust.UserID
          WHERE o.OrderID = '$orderID' LIMIT 1";

$result = mysqli_query($link, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die("<h2 style='text-align:center; padding: 50px; font-family:sans-serif;'>Order not found!</h2>");
}

$order = mysqli_fetch_assoc($result);

$items = [];
$itemQ = "SELECT od.Quantity, IFNULL(m.ItemName, 'Deleted Menu Item') as ItemName FROM order_detail od LEFT JOIN menu_item m ON od.ItemID = m.itemID WHERE od.OrderID='$orderID'";
$itemRes = mysqli_query($link, $itemQ);
if ($itemRes) {
    while ($i = mysqli_fetch_assoc($itemRes)) {
        $items[] = floatval($i['Quantity']) . "x " . $i['ItemName'];
    }
}
$order['items_string'] = implode(', ', $items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Live Tracking | BiteGo</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f4f6f8; font-family: 'Alexandria', sans-serif; color: #111; margin: 0; display: flex; flex-direction: column; min-height: 100vh;}
        
        .tracker-wrapper { flex: 1; display: flex; justify-content: center; align-items: center; padding: 40px 20px; }
        .tracker-card { background: #fff; width: 100%; max-width: 600px; border-radius: 20px; padding: 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.06); border: 1px solid #eee; text-align: center;}
        
        .tracker-header h1 { margin: 0 0 5px 0; font-size: 28px; font-weight: 900; }
        .tracker-header p { margin: 0 0 30px 0; color: #888; font-size: 15px; }

        .status-box { padding: 30px; border-radius: 15px; margin-bottom: 30px; border: 2px solid transparent; }
        .status-box i { font-size: 50px; margin-bottom: 15px; }
        .status-box h2 { margin: 0; font-size: 24px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;}
        
        /* ALL STATUS BADGES */
        .box-Unpaid { background: #eeeeee; color: #666; border-color: #ddd; }
        .box-Pending { background: #fff4e5; color: #b07d00; border-color: #ffe3bc; }
        .box-Cooking { background: #e3f2fd; color: #1976d2; border-color: #bbdefb; }
        .box-Ready { background: #e8f5e9; color: #2e7d32; border-color: #c8e6c9; }
        .box-Done { background: #f0f0f0; color: #555; border-color: #ddd; }
        .box-Cancelled { background: #fdf0f0; color: #d9534f; border-color: #f5c6cb; }

        .order-details { text-align: left; background: #fafafa; padding: 25px; border-radius: 15px; border: 1px solid #eee; }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; }
        .detail-row:last-child { margin-bottom: 0; border-top: 1px dashed #ccc; padding-top: 12px; margin-top: 5px;}
        .lbl { color: #888; font-weight: bold; }
        .val { color: #000; font-weight: 900; text-align: right; max-width: 60%; line-height: 1.4;}

        .auto-refresh-msg { font-size: 12px; color: #aaa; margin-top: 25px; font-weight: bold; }
        .auto-refresh-msg i { animation: spin 2s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        .btn-home { display: inline-block; margin-top: 30px; color: #000; font-weight: bold; text-decoration: none; border-bottom: 2px solid #000; padding-bottom: 2px; transition: 0.3s;}
        .btn-home:hover { color: #666; border-color: #666; }
        
        .btn-resume { display: inline-block; background: #1976d2; color: #fff; padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: bold; margin-top: 20px; transition: 0.3s; }
        .btn-resume:hover { background: #115293; transform: translateY(-2px); }
    </style>
</head>
<body>

    <header class="navbar" style="position: sticky; border-bottom: 1px solid #eee; background:#fff;">
        <a href="frontpage.php" class="nav-logo-link" style="color: #000;">BiteGo.</a>
    </header>

    <div class="tracker-wrapper">
        <div class="tracker-card">
            
            <div class="tracker-header">
                <h1>Live Kitchen Status</h1>
                <p>Order ID: #<?php echo $orderID; ?></p>
            </div>

            <div class="status-box box-<?php echo $order['OrderStatus']; ?>">
                
                <?php if($order['OrderStatus'] == 'Unpaid'): ?>
                    <i class="fa-solid fa-wallet"></i>
                    <h2>Waiting for Payment</h2>
                    <p style="margin:10px 0 0 0; font-size:14px; color:inherit; opacity:0.8;">Your order is saved, but you must complete payment before the kitchen starts cooking.</p>
                    <a href="qrpay.php?order=<?php echo $orderID; ?>&app=TouchNGo" class="btn-resume">Resume Payment Process</a>

                <?php elseif($order['OrderStatus'] == 'Pending'): ?>
                    <i class="fa-solid fa-clock"></i>
                    <h2>Order Received</h2>
                    <p style="margin:10px 0 0 0; font-size:14px; color:inherit; opacity:0.8;">The kitchen has received your order and will start soon.</p>
                
                <?php elseif($order['OrderStatus'] == 'Cooking'): ?>
                    <i class="fa-solid fa-fire-burner"></i>
                    <h2>Preparing Food</h2>
                    <p style="margin:10px 0 0 0; font-size:14px; color:inherit; opacity:0.8;">Your food is actively being prepared. Hang tight!</p>
                
                <?php elseif($order['OrderStatus'] == 'Ready'): ?>
                    <i class="fa-solid fa-bell-concierge"></i>
                    <h2>Food is Ready!</h2>
                    <p style="margin:10px 0 0 0; font-size:14px; color:inherit; opacity:0.8;">Please head to the counter to collect your meal.</p>
                
                <?php elseif($order['OrderStatus'] == 'Done'): ?>
                    <i class="fa-solid fa-check-double"></i>
                    <h2>Order Completed</h2>
                    <p style="margin:10px 0 0 0; font-size:14px; color:inherit; opacity:0.8;">Thank you for dining with BiteGo!</p>

                <?php elseif($order['OrderStatus'] == 'Cancelled'): ?>
                    <i class="fa-solid fa-ban"></i>
                    <h2>Order Cancelled</h2>
                    <p style="margin:10px 0 0 0; font-size:14px; color:inherit; font-weight:bold;">Reason: <?php echo htmlspecialchars($order['CancelReason']); ?></p>
                <?php endif; ?>
            </div>

            <div class="order-details">
                <div class="detail-row">
                    <span class="lbl">Restaurant</span>
                    <span class="val"><?php echo htmlspecialchars($order['VendorName']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Customer Name</span>
                    <span class="val"><?php echo htmlspecialchars($order['CustName']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Items</span>
                    <span class="val"><?php echo htmlspecialchars($order['items_string']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Total Paid</span>
                    <span class="val" style="<?php echo $order['OrderStatus'] == 'Cancelled' ? 'text-decoration: line-through; color: #888;' : ''; ?>">RM <?php echo number_format($order['OrderTotal'], 2); ?></span>
                </div>
            </div>

            <?php if($order['OrderStatus'] != 'Done' && $order['OrderStatus'] != 'Cancelled'): ?>
                <div class="auto-refresh-msg">
                    <i class="fa-solid fa-rotate"></i> Auto-refreshing every 15 seconds to fetch live updates...
                </div>
            <?php endif; ?>

            <a href="frontpage.php" class="btn-home" style="margin-right: 15px;">Return to Home</a>
            <?php if(isset($_SESSION['UserID'])): ?>
                <a href="past_orders.php" class="btn-home">View All Orders</a>
            <?php endif; ?>

        </div>
    </div>

    <script>
     let currentStatus = "<?php echo $order['OrderStatus']; ?>";

     // Silently ping the server every 5 seconds to check if status changed
     if (currentStatus !== 'Done' && currentStatus !== 'Cancelled') {
         setInterval(() => {
             fetch(`track_order.php?api=status&order=<?php echo $orderID; ?>`)
             .then(res => res.json())
             .then(data => {
                 // If the kitchen changed the status, reload the page ONCE to show the new UI
                 if(data.status && data.status !== currentStatus) {
                     window.location.reload(); 
                 }
             });
         }, 5000); 
     }
 </script>

</body>
</html>