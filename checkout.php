<?php
session_start();
include("db.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_real_order'])) {
    $orderID = mysqli_real_escape_string($link, $_POST['order_id']);
    $total = mysqli_real_escape_string($link, $_POST['total']);
    $cartData = json_decode($_POST['cart_data'], true);
    
    $guestName = isset($_POST['guest_name']) ? mysqli_real_escape_string($link, $_POST['guest_name']) : 'Guest Customer';
    $orderType = isset($_POST['order_type']) ? mysqli_real_escape_string($link, $_POST['order_type']) : 'takeout';
    $tableNo = isset($_POST['table_no']) ? mysqli_real_escape_string($link, $_POST['table_no']) : '';
    
    // NEW: DETECT IF E-WALLET WAS USED TO MARK AS UNPAID
    $paymentMethod = isset($_POST['payment_method']) ? mysqli_real_escape_string($link, $_POST['payment_method']) : 'card';
    $initialStatus = ($paymentMethod === 'ewallet') ? 'Unpaid' : 'Pending';
    
    if (isset($_SESSION['UserID'])) {
        $custID = $_SESSION['UserID'];
        // Ensure a customer row exists for this user (may be missing after normalization)
        mysqli_query($link, "INSERT IGNORE INTO customer (UserID, Points) VALUES ('$custID', 0)");
    } else {
        $custID = 'GUEST_' . rand(10000,99999);
        // 3NF FIX: We MUST insert the Guest into the `user` table FIRST, otherwise the DB rejects the order!
        mysqli_query($link, "INSERT IGNORE INTO user (UserID, UserName, UserEmail, UserPass, Role) VALUES ('$custID', '$guestName', '$custID@guest.com', '', 'Customer')");
        mysqli_query($link, "INSERT IGNORE INTO customer (UserID, Points) VALUES ('$custID', 0)");
    }

    $vID = NULL;
    if (!empty($cartData)) {
        $firstItemID = mysqli_real_escape_string($link, $cartData[0]['id']);
        $vRes = mysqli_query($link, "SELECT UserID FROM menu_item WHERE ItemID='$firstItemID' LIMIT 1");
        if ($vRow = mysqli_fetch_assoc($vRes)) { $vID = $vRow['UserID']; }
    }

    // 3NF FIX: If tableNo is empty, use NULL so strict databases don't crash
    $vIDSql = ($vID !== NULL) ? "'$vID'" : "NULL";
    $tableNoSql = empty($tableNo) ? "NULL" : "'$tableNo'";

    // INSERT ORDER
    $orderInsert = mysqli_query($link, "INSERT INTO `order` (OrderID, UserID, VendorUserID, OrderStatus, OrderDate, OrderType, TableNo) 
                         VALUES ('$orderID', '$custID', $vIDSql, '$initialStatus', NOW(), '$orderType', $tableNoSql)");

    if (!$orderInsert) {
        exit("ERROR: " . mysqli_error($link));
    }

    if ($cartData) {
        foreach($cartData as $item) {
            $itemID = mysqli_real_escape_string($link, $item['id']);
            $qty = mysqli_real_escape_string($link, $item['qty']);
            $sub = $item['price'] * $qty;

            $result = mysqli_query($link, "
                INSERT INTO order_detail (OrderID, ItemID, Quantity, OrderedPrice)
                VALUES ('$orderID', '$itemID', '$qty', '$sub')
            ");

           if (!$result) {
                exit("ERROR: " . mysqli_error($link));
            }
        }
    }

    // NEW: INSERT DATA INTO THE PAYMENT TABLE
    $paymentID = 'PAY' . time() . rand(100, 999); // Generates a unique Payment ID
    $paymentInsertQuery = "INSERT INTO payment (PaymentID, OrderID, Amount, PaymentMethod) 
                           VALUES ('$paymentID', '$orderID', '$total', '$paymentMethod')";
                           
    $paymentInsertResult = mysqli_query($link, $paymentInsertQuery);

    if (!$paymentInsertResult) {
        exit("ERROR: " . mysqli_error($link));
    }

    // ADDED: DEDUCT POINTS IF CUSTOMER CHOSE TO REDEEM THEM

    // ADDED: DEDUCT POINTS IF CUSTOMER CHOSE TO REDEEM THEM
    if (isset($_POST['points_redeemed']) && $_POST['points_redeemed'] == '1' && strpos($custID, 'GUEST_') === false) {
        mysqli_query($link, "UPDATE customer SET Points = GREATEST(0, Points - 100) WHERE UserID='$custID'");
    }

    exit("SUCCESS"); 
}

$isLoggedIn = isset($_SESSION['UserID']);

// ADDED: FETCH LIVE POINTS TO DISPLAY IN CHECKOUT
$userPoints = 0;
if ($isLoggedIn) {
    $cID = $_SESSION['UserID'];
    $ptsQ = mysqli_query($link, "SELECT Points FROM customer WHERE UserID='$cID'");
    if ($pRow = mysqli_fetch_assoc($ptsQ)) {
        $userPoints = (int)$pRow['Points'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout | BiteGo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; color: #000; }
        header { background: #fff; padding: 20px 50px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100;}
        header a { font-size: 24px; font-weight: 900; color: #000; text-decoration: none; }
        .back-link { font-size: 14px; color: #666; text-decoration: none; font-weight: bold; }
        .back-link:hover { color: #000; }
        .checkout-layout { display: flex; max-width: 1200px; margin: 40px auto; gap: 40px; padding: 0 20px; }
        .checkout-form-area { flex: 1.5; }
        h2 { font-size: 28px; font-weight: 900; margin-top: 0; margin-bottom: 25px; border-bottom: 2px solid #000; padding-bottom: 10px; display: inline-block;}
        .section-box { background: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); border: 1px solid #eee; margin-bottom: 30px; }
        .section-title { font-size: 18px; font-weight: 800; margin-bottom: 20px; }
        .input-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .input-group { flex: 1; }
        label { display: block; font-size: 12px; font-weight: bold; color: #666; margin-bottom: 5px; text-transform: uppercase;}
        input[type="text"], input[type="email"] { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; font-size: 14px; transition: all 0.3s; }
        input[type="text"]:focus, input[type="email"]:focus { border-color: #000; outline: none; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
        .payment-option { border: 1px solid #ddd; border-radius: 10px; margin-bottom: 10px; overflow: hidden; transition: all 0.3s; }
        .payment-option.active { border-color: #000; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .payment-header { padding: 15px 20px; display: flex; align-items: center; cursor: pointer; background: #fafafa; }
        .payment-header:hover { background: #f0f0f0; }
        .payment-header input[type="radio"] { width: auto; margin-right: 15px; cursor: pointer; transform: scale(1.2); }
        .payment-title { font-weight: bold; font-size: 15px; flex: 1; }
        .payment-logos img { height: 20px; margin-left: 10px; vertical-align: middle; }
        .payment-body { max-height: 0; overflow: hidden; transition: max-height 0.4s cubic-bezier(0, 1, 0, 1); background: #fff; padding: 0 20px; }
        .payment-option.active .payment-body { max-height: 400px; padding: 20px; border-top: 1px solid #eee; }
        .ewallet-grid { display: flex; gap: 15px; margin-top: 10px; }
        .ewallet-card { flex: 1; border: 1px solid #ddd; border-radius: 8px; padding: 15px; text-align: center; cursor: pointer; transition: all 0.2s; }
        .ewallet-card:hover { border-color: #000; background: #fafafa; }
        .ewallet-card img { height: 30px; margin-bottom: 10px; }
        .ewallet-card input[type="radio"] { display: none; }
        .ewallet-card input[type="radio"]:checked + .ewallet-content { font-weight: bold; color: #000; }
        .ewallet-card:has(input[type="radio"]:checked) { border: 2px solid #000; background: #f0f0f0; box-shadow: 0 4px 10px rgba(0,0,0,0.05);}
        .receipt-area { flex: 1; }
        .receipt-card { background: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); position: sticky; top: 100px; border: 1px dashed #ccc;}
        .receipt-title { font-size: 20px; font-weight: 900; text-align: center; margin-bottom: 25px; text-transform: uppercase; letter-spacing: 2px;}
        .receipt-items { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px; max-height: 300px; overflow-y: auto;}
        .receipt-item { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: #444; }
        .item-name { flex: 1; padding-right: 15px;}
        .item-qty { width: 30px; font-weight: bold; }
        .item-price { font-weight: bold; }
        .receipt-math { border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 15px; }
        .math-line { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: #666;}
        .grand-total { display: flex; justify-content: space-between; font-size: 22px; font-weight: 900; }
        .btn-container { display: flex; gap: 15px; margin-top: 25px; }
        .pay-btn { flex: 2; background: #000; color: #fff; border: none; padding: 18px; border-radius: 10px; font-size: 18px; font-weight: bold; cursor: pointer; transition: all 0.3s; box-shadow: 0 5px 15px rgba(0,0,0,0.2);}
        .pay-btn:hover { background: #333; transform: translateY(-2px); }
        .clear-btn { flex: 1; background: #eeeeee; color: #000; border: none; padding: 18px; border-radius: 10px; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.3s; }
        .clear-btn:hover { background: #dddddd; }
        
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); }
        .modal-content { background-color: #fff; padding: 40px; border-radius: 20px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.3); width: 420px; animation: popIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);}
        @keyframes popIn { 0% { transform: scale(0.9) translateY(20px); opacity: 0; } 100% { transform: scale(1) translateY(0); opacity: 1; } }
    </style>
</head>
<body>

    <header>
        <a href="frontpage.php">BiteGo.</a>
        <a href="menu.php" class="back-link">← Back to Menu</a>
    </header>

    <div class="checkout-layout">
        <div class="checkout-form-area">
            <h2>Secure Checkout</h2>

            <?php if(!$isLoggedIn): ?>
            <div class="section-box">
                <div class="section-title">1. Your Details (Guest)</div>
                <div class="input-row">
                    <div class="input-group">
                        <label>First Name</label>
                        <input type="text" id="guestFName" placeholder="John" required>
                    </div>
                    <div class="input-group">
                        <label>Last Name</label>
                        <input type="text" id="guestLName" placeholder="Doe" required>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="section-box" style="background: #e8f5e9; border-color: #c8e6c9;">
                <div class="section-title" style="margin-bottom: 0; color: #2e7d32;">✓ Checking out as logged-in user</div>
            </div>
            <?php endif; ?>

            <div class="section-box">
                <div class="section-title">2. Payment Method</div>

                <div class="payment-option" id="opt-card">
                    <label class="payment-header">
                        <input type="radio" name="payment" value="card" onclick="selectPayment('opt-card')">
                        <span class="payment-title">Credit / Debit Card</span>
                        <span class="payment-logos">
                            <img src="gambar/visa.png" alt="Visa">
                            <img src="gambar/mastercard.png" alt="Mastercard">
                        </span>
                    </label>
                    <div class="payment-body">
                        <div class="input-group" style="margin-bottom: 15px;">
                            <label>Card Number</label>
                            <input type="text" id="ccNumber" placeholder="0000 0000 0000 0000" maxlength="19" oninput="formatCardNumber(this)">
                        </div>
                        <div class="input-row">
                            <div class="input-group">
                                <label>Expiry Date</label>
                                <input type="text" id="ccExpiry" placeholder="MM/YY" maxlength="5" oninput="formatExpiry(this)">
                            </div>
                            <div class="input-group">
                                <label>CVC / CVV</label>
                                <input type="text" id="ccCVC" placeholder="123" maxlength="3" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="payment-option" id="opt-ewallet">
                    <label class="payment-header">
                        <input type="radio" name="payment" value="ewallet" onclick="selectPayment('opt-ewallet')">
                        <span class="payment-title">QR Pay / E-Wallets</span>
                        <span class="payment-logos">
                            <img src="gambar/TouchNGo.png" alt="TnG">
                            <img src="gambar/duitnow.png" alt="DuitNow">
                            <img src="gambar/MaybankQR.png" alt="MaybankQR">
                        </span>
                    </label>
                    <div class="payment-body">
                        <p style="font-size: 14px; color: #666;">Select your preferred app to scan the QR code on the next page.</p>
                        
                        <div class="ewallet-grid">
                            <label class="ewallet-card">
                                <input type="radio" name="ewallet_choice" value="TouchNGo">
                                <div class="ewallet-content">
                                    <img src="gambar/TouchNGo.png" alt="TnG"><br>Touch 'n Go
                                </div>
                            </label>
                            <label class="ewallet-card">
                                <input type="radio" name="ewallet_choice" value="DuitNow">
                                <div class="ewallet-content">
                                    <img src="gambar/duitnow.png" alt="DuitNow"><br>DuitNow
                                </div>
                            </label>
                            <label class="ewallet-card">
                                <input type="radio" name="ewallet_choice" value="MaybankQR">
                                <div class="ewallet-content">
                                    <img src="gambar/MaybankQR.png" alt="MaybankQR"><br>Maybank QR Pay
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="payment-option" id="opt-counter">
                    <label class="payment-header">
                        <input type="radio" name="payment" value="counter" onclick="selectPayment('opt-counter')">
                        <span class="payment-title">Pay at Counter (Cash / Physical Card)</span>
                    </label>
                    <div class="payment-body">
                        <p style="font-size: 14px; color: #666; margin: 0;">Your order will be sent to the kitchen immediately. Please head to the cashier to complete your payment.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="receipt-area">
            <div class="receipt-card">
                <div class="receipt-title">Order Summary</div>
                
                <div class="receipt-items" id="receiptItems">
                    <div style="text-align:center; color:#999; font-size:12px;">Loading cart...</div>
                </div>

                <div class="receipt-math">
                    <div class="math-line">
                        <span>Subtotal</span>
                        <span id="subtotalText">RM 0.00</span>
                    </div>
                    <div class="math-line">
                        <span>Service Tax (6% SST)</span>
                        <span id="taxText">RM 0.00</span>
                    </div>
                    
                    <?php if($isLoggedIn): ?>
                    <div style="background: #fffcf2; border: 1px solid #ffecb3; padding: 15px; border-radius: 10px; margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-size: 13px;">
                            <strong style="color: #b07d00;"><i class="fa-solid fa-star"></i> BiteGo Points</strong><br>
                            Available: <?php echo $userPoints; ?> pts
                        </div>
                        <?php if($userPoints >= 100): ?>
                            <label style="font-size:13px; font-weight:bold; cursor:pointer;">
                                <input type="checkbox" id="redeemPtsBox" onchange="recalculateTotals()"> Redeem 100 pts (-RM 5.00)
                            </label>
                        <?php else: ?>
                            <span style="font-size:12px; color:#888;">Need 100 pts</span>
                        <?php endif; ?>
                    </div>
                    <div class="math-line" id="discountLine" style="display:none; color: #2e7d32; font-weight: bold; margin-top: 10px;">
                        <span>Points Discount</span><span>- RM 5.00</span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="grand-total">
                    <span>Total</span>
                    <span id="grandTotalText">RM 0.00</span>
                </div>

                <div id="checkoutError" style="color: #d9534f; background: #fdf0f0; border: 1px solid #f5c6cb; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: bold; margin-top: 15px; text-align: center; display: none;"></div>

                <div class="btn-container">
                    <button class="clear-btn" onclick="openClearModal()">Clear Form</button>
                    <button class="pay-btn" id="payBtn" onclick="submitOrder()">Place Order</button>
                </div>
            </div>
        </div>
    </div>

    <div id="clearCartModal" class="modal-overlay">
        <div class="modal-content">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 40px; color: #d9534f; margin-bottom: 15px;"></i>
            <h2 style="font-size: 22px; margin-top:0;">Clear Cart?</h2>
            <p style="color: #666; font-size: 14px; margin-bottom: 25px;">Are you sure you want to clear your entire cart? All items will be removed.</p>
            <div style="display: flex; gap: 10px;">
                <button onclick="closeClearModal()" class="clear-btn" style="flex: 1; padding: 12px;">Cancel</button>
                <button onclick="executeClearCart()" class="pay-btn" style="flex: 1; padding: 12px; background: #d9534f; box-shadow: none;">Yes, Clear</button>
            </div>
        </div>
    </div>

    <script>
        const loggedInUserId = "<?php echo isset($_SESSION['UserID']) ? $_SESSION['UserID'] : 'guest'; ?>";

        function selectPayment(selectedId) {
            document.querySelectorAll('.payment-option').forEach(el => el.classList.remove('active'));
            document.getElementById(selectedId).classList.add('active');
        }

        function formatCardNumber(input) {
            let val = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formatted = val.match(/.{1,4}/g);
            input.value = formatted ? formatted.join(' ') : val;
        }

        function formatExpiry(input) {
            let val = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            if (val.length >= 2) {
                input.value = val.substring(0,2) + '/' + val.substring(2,4);
            } else {
                input.value = val;
            }
        }

        let cart = [];
        let rawTotal = 0;
        let finalTotalStr = "RM 0.00";
        window.baseSubtotal = 0; // ADDED: Global subtotal for points math

        window.onload = function() {
            const cartStr = localStorage.getItem('bitego_cart');
            
            if (!cartStr) {
                document.getElementById('receiptItems').innerHTML = "<div style='text-align:center; color:#d9534f; padding: 20px;'>Your cart is empty!</div>";
                document.getElementById('payBtn').disabled = true;
                return;
            }
            
            cart = JSON.parse(cartStr);
            if (cart.length === 0) {
                document.getElementById('receiptItems').innerHTML = "<div style='text-align:center; color:#d9534f; padding: 20px;'>Your cart is empty!</div>";
                document.getElementById('payBtn').disabled = true;
                return;
            }

            let html = "";
            let subtotal = 0;
            
            cart.forEach(item => {
                let itemTotal = item.price * item.qty;
                subtotal += itemTotal;
                html += `
                    <div class="receipt-item">
                        <span class="item-qty">${item.qty}x</span>
                        <span class="item-name">${item.name}</span>
                        <span class="item-price">RM ${itemTotal.toFixed(2)}</span>
                    </div>
                `;
            });

            document.getElementById('receiptItems').innerHTML = html;
            window.baseSubtotal = subtotal; // Save it
            
            recalculateTotals(); // ADDED: Calls the new function to calculate total with potential points
        };

        // ADDED: DYNAMIC POINTS CALCULATION
        function recalculateTotals() {
            let tax = window.baseSubtotal * 0.06;
            let discount = 0;
            
            const redeemBox = document.getElementById('redeemPtsBox');
            const discountLine = document.getElementById('discountLine');

            if (redeemBox && redeemBox.checked) {
                discount = 5.00;
                if(discountLine) discountLine.style.display = 'flex';
            } else {
                if(discountLine) discountLine.style.display = 'none';
            }

            rawTotal = window.baseSubtotal + tax - discount;
            if (rawTotal < 0) rawTotal = 0; 
            
            finalTotalStr = "RM " + rawTotal.toFixed(2);
            
            document.getElementById('subtotalText').innerText = "RM " + window.baseSubtotal.toFixed(2);
            document.getElementById('taxText').innerText = "RM " + tax.toFixed(2);
            document.getElementById('grandTotalText').innerText = finalTotalStr;
        }

        function openClearModal() { document.getElementById('clearCartModal').style.display = 'flex'; }
        function closeClearModal() { document.getElementById('clearCartModal').style.display = 'none'; }
        
        function executeClearCart() {
            localStorage.removeItem('bitego_cart');
            localStorage.removeItem('bitego_ordertype');
            localStorage.removeItem('bitego_table');
            window.location.href = "menu.php";
        }

        function submitOrder() {
            const errEl = document.getElementById('checkoutError');
            errEl.style.display = 'none';

            let guestFullName = "Guest";
            if (loggedInUserId === 'guest') {
                const fName = document.getElementById('guestFName').value.trim();
                const lName = document.getElementById('guestLName').value.trim();
                if (!fName || !lName) {
                    errEl.innerText = "Please fill in your first and last name.";
                    errEl.style.display = 'block';
                    return;
                }
                guestFullName = fName + " " + lName;
            }

            const selectedPayment = document.querySelector('input[name="payment"]:checked');
            if (!selectedPayment) {
                errEl.innerText = "Please select a payment method.";
                errEl.style.display = 'block';
                return;
            }

            let ewalletChoice = "";
            if (selectedPayment.value === 'card') {
                if (document.getElementById('ccNumber').value.length < 16) {
                    errEl.innerText = "Please enter a valid card number.";
                    errEl.style.display = 'block';
                    return;
                }
            } else if (selectedPayment.value === 'ewallet') {
                const eChoice = document.querySelector('input[name="ewallet_choice"]:checked');
                if (!eChoice) {
                    errEl.innerText = "Please select an E-Wallet provider.";
                    errEl.style.display = 'block';
                    return;
                }
                ewalletChoice = eChoice.value;
            }

            const orderType = localStorage.getItem('bitego_ordertype') || 'takeout';
            const tableNo = localStorage.getItem('bitego_table') || '';
            const randomID = 'ORD' + Date.now();
            
            const formData = new URLSearchParams();
            formData.append('place_real_order', '1');
            formData.append('order_id', randomID);
            formData.append('total', rawTotal);
            formData.append('cart_data', localStorage.getItem('bitego_cart'));
            formData.append('guest_name', guestFullName);
            formData.append('order_type', orderType);
            formData.append('table_no', tableNo);
            
            formData.append('payment_method', selectedPayment.value);

            const redeemBox = document.getElementById('redeemPtsBox');
            if (redeemBox && redeemBox.checked) {
                formData.append('points_redeemed', '1');
            }

            fetch('checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // 3NF FIX: We now intercept DB SQL errors instead of blindly redirecting!
                if (data.includes("ERROR") || data.includes("Error") || data.includes("error")) {
                    errEl.innerText = "Database Error: " + data;
                    errEl.style.display = 'block';
                    return;
                }

                localStorage.setItem('bitego_last_order', localStorage.getItem('bitego_cart'));
                localStorage.setItem('bitego_last_total', finalTotalStr);
                localStorage.removeItem('bitego_cart');

                if (selectedPayment.value === 'ewallet') {
                    window.location.href = "qrpay.php?order=" + randomID + "&app=" + ewalletChoice + "&total=" + rawTotal;
                } else {
                    window.location.href = "receipt.php?order=" + randomID + "&total=" + rawTotal;
                }
            })
            .catch(error => {
                errEl.innerText = "Error placing order. Please try again.";
                errEl.style.display = 'block';
            });
        }
    </script>
</body>
</html>