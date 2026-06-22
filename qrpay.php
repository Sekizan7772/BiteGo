<?php
session_start();
include("db.php");

// --- CONFIRM PAYMENT -> SENT TO KITCHEN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_payment_id'])) {
    $pID = mysqli_real_escape_string($link, $_POST['confirm_payment_id']);
    // Turn the order from Unpaid to Pending so the Vendor sees it!
    mysqli_query($link, "UPDATE `order` SET OrderStatus='Pending' WHERE OrderID='$pID'");
    exit('PAID');
}

// --- SILENT CANCELLATION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_order_id'])) {
    $cID = mysqli_real_escape_string($link, $_POST['cancel_order_id']);
    mysqli_query($link, "UPDATE `order` SET OrderStatus='Cancelled', CancelReason='Customer cancelled payment' WHERE OrderID='$cID'");
    exit('CANCELLED');
}

$orderID = isset($_GET['order']) ? htmlspecialchars($_GET['order']) : 'UNKNOWN';
$appName = isset($_GET['app']) ? htmlspecialchars($_GET['app']) : 'E-Wallet';

$appImage = "gambar/TouchNGo.png"; 
if ($appName === "GrabPay") $appImage = "gambar/grabpay.png";
if ($appName === "DuitNow") $appImage = "gambar/duitnow.png";
if ($appName === "MaybankQR") $appImage = "gambar/MaybankQR.png"; 

$qrCodeImage = "gambar/tng_qr.jpg"; 
if ($appName === "TouchNGo") $qrCodeImage = "gambar/tng_qr.jpg";
if ($appName === "DuitNow")  $qrCodeImage = "gambar/qrairiel.jpg";
if ($appName === "MaybankQR") $qrCodeImage = "gambar/qrairiel.jpg";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Scan to Pay | BiteGo</title>
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #f4f6f8 0%, #e0e5ec 100%); height: 100vh; display: flex; justify-content: center; align-items: center; }
        
        .qr-card {
            background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            width: 400px; text-align: center; animation: slideUp 0.6s cubic-bezier(0.25, 1, 0.5, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header-imgs { display: flex; justify-content: center; align-items: center; gap: 15px; margin-bottom: 20px; }
        .header-imgs img { height: 40px; object-fit: contain; }
        
        h2 { font-size: 24px; font-weight: 900; margin: 0 0 10px 0; }
        p { color: #666; font-size: 14px; margin-bottom: 30px; }

        .order-badge { background: #f0f0f0; padding: 8px 15px; border-radius: 30px; font-weight: bold; font-size: 14px; display: inline-block; margin-bottom: 25px; }

        /* QR Code Scanner Box with Laser Animation */
        .qr-box-container {
            position: relative; width: 250px; height: 250px; margin: 0 auto 30px auto;
            background: #fafafa; border: 2px dashed #ccc; border-radius: 15px; overflow: hidden;
            display: flex; justify-content: center; align-items: center;
        }

        .real-qr-img {
            width: 200px; 
            height: 200px; 
            object-fit: cover; 
            border-radius: 10px; 
            z-index: 1; 
        }

        .scan-line {
            position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: #000;
            box-shadow: 0 0 15px #000; animation: scan 2s infinite linear; z-index: 2;
        }

        @keyframes scan {
            0% { top: 0; opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }

        .btn-done {
            background: #000; color: #fff; border: none; width: 100%; padding: 16px; border-radius: 12px; 
            font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.3s; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.2); text-decoration: none; display: block; box-sizing: border-box;
        }
        .btn-done:hover { background: #333; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.3); }

        .cancel-link { 
            display: inline-block; margin-top: 20px; color: #888; font-size: 14px; 
            text-decoration: none; font-weight: bold; transition: color 0.3s; 
            cursor: pointer; border: none; background: none; font-family: inherit;
        }
        .cancel-link:hover { color: #d9534f; text-decoration: underline; }
    </style>
</head>
<body>

    <div class="qr-card">
        <div class="header-imgs">
            <img src="gambar/logobitego.png" alt="BiteGo" style="height: 30px;">
            <span style="font-weight: bold; color: #ccc;">X</span>
            <img src="<?php echo $appImage; ?>" alt="<?php echo $appName; ?>">
        </div>

        <h2>Scan to Pay</h2>
        <p>Open your <strong><?php echo $appName; ?></strong> app and scan the QR code below to complete your payment.</p>

        <div class="order-badge">Order ID: <?php echo $orderID; ?></div>

        <div class="qr-box-container">
            <img src="<?php echo $qrCodeImage; ?>" alt="Scan me!" class="real-qr-img" onerror="this.src='gambar/bitegologo.png'">
            
            <div class="scan-line"></div>
        </div>

        <button class="btn-done" onclick="confirmPayment('<?php echo $orderID; ?>')">I Have Paid</button>
        
        <button class="cancel-link" onclick="cancelPayment('<?php echo $orderID; ?>')">Cancel Payment & Return to Menu</button>
    </div>

    <script>
        // 1. Mark Order as Paid and Send to Kitchen
        function confirmPayment(orderId) {
            const formData = new URLSearchParams();
            formData.append('confirm_payment_id', orderId);
            
            fetch('qrpay.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            }).then(() => {
                window.location.href = 'receipt.php?order=' + orderId;
            });
        }

        // 2. Cancel Order Safely & Restore Cart
        function cancelPayment(orderId) {
            // Restore cart from backup
            const backupCart = localStorage.getItem('bitego_last_order');
            if (backupCart) { 
                localStorage.setItem('bitego_cart', backupCart); 
            }

            const formData = new URLSearchParams();
            formData.append('cancel_order_id', orderId);

            fetch('qrpay.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            }).then(() => {
                window.location.href = 'menu.php';
            }).catch(() => {
                window.location.href = 'menu.php';
            });
        }
    </script>
</body>
</html>