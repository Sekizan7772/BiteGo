<?php
session_start();

// NEW: DYNAMIC SOURCE TRACKING
// --- UPDATED: SMART REDIRECT LOGIC ---
// Default source to 'frontpage' if nothing is specified
$source = isset($_GET['source']) ? $_GET['source'] : 'frontpage'; 

if ($source === 'checkout') {
    // Only force checkout.php if the source is specifically 'checkout'
    $_SESSION['redirect_to'] = 'checkout.php';
    $guestLink = 'checkout.php';
    $subText = "Complete your secure checkout to finish your order.";
} elseif ($source === 'menu') {
    $typeParam = isset($_GET['type']) ? '?type=' . urlencode($_GET['type']) : '';
    $_SESSION['redirect_to'] = 'menu.php' . $typeParam;
    $guestLink = 'menu.php' . $typeParam;
    $subText = "Log in to save your order progress and earn points.";
} else {
    // Everything else (like clicking 'Log In' in the Navbar) defaults to Home
    $_SESSION['redirect_to'] = 'frontpage.php';
    $guestLink = 'frontpage.php';
    $subText = "Log in to access your saved orders, earn BiteGo points, track live status, and manage your profile.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Customer Login | BiteGo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            margin: 0; font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f4f6f8 0%, #e0e5ec 100%);
            height: 100vh; display: flex; justify-content: center; align-items: center;
        }
        .gateway-container {
            display: flex; background: #fff; border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1); overflow: hidden;
            width: 900px; height: 500px;
        }
        .split-half {
            flex: 1; padding: 50px; display: flex; flex-direction: column; justify-content: center;
            transition: all 0.3s ease;
        }
        .guest-side { background-color: #f8f9fa; border-left: 1px solid #eee; text-align: center; }
        
        h2 { font-size: 26px; font-weight: 900; margin: 0 0 10px 0; letter-spacing: -1px; }
        p { color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 30px; }
        
        .input-group { margin-bottom: 15px; }
        input[type="email"], input[type="password"] {
            width: 100%; padding: 14px; border: 1px solid #ddd; border-radius: 10px;
            font-size: 14px; font-family: inherit; box-sizing: border-box; transition: 0.3s;
        }
        input:focus { border-color: #000; outline: none; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
        
        .btn {
            display: inline-block; padding: 14px 25px; border-radius: 10px; font-weight: bold;
            font-size: 14px; text-decoration: none; cursor: pointer; transition: 0.3s; text-align: center; width: 100%; box-sizing: border-box;
        }
        .btn-black { background: #000; color: #fff; border: 2px solid #000; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .btn-black:hover { background: #333; transform: translateY(-2px); }
        .btn-outline { background: transparent; color: #000; border: 2px solid #000; }
        .btn-outline:hover { background: #000; color: #fff; transform: translateY(-2px); }
        
        .logo-small { width: 120px; margin-bottom: 30px; }
        .ui-error-box { background: #fdf0f0; color: #d9534f; border: 1px solid #f5c6cb; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: bold; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>

    <div class="gateway-container">
        <div class="split-half">
            <img src="gambar/bitegologo.png" alt="BiteGo" class="logo-small">
            <h2>Welcome Back.</h2>
            <p><?php echo $subText; ?></p>
            
            <?php if(isset($_SESSION['login_error'])): ?>
                <div class="ui-error-box">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?>
                </div>
            <?php endif; ?>

            <form action="process_login_cust.php" method="POST">
                <div class="input-group">
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-black">Log In to BiteGo</button>
            </form>
        </div>

        <div class="split-half guest-side">
            <h2>In a hurry?</h2>
            <p>No problem. You can skip the login and continue as a guest.</p>
            
            <a href="<?php echo $guestLink; ?>" class="btn btn-outline" style="margin-bottom: 15px;">Continue as Guest</a>
            
            <p style="margin: 20px 0 10px 0; font-size: 12px; text-transform: uppercase; font-weight: bold; letter-spacing: 1px;">Or Create an Account</p>
            <a href="register.php" style="color: #000; font-size: 14px; font-weight: bold;">Register for free &rarr;</a>
        </div>
    </div>

</body>
</html>