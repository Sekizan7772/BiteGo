<?php session_start(); 
unset($_SESSION['redirect_to']);?>
<!DOCTYPE html>
<html>
<head>
    <title>BiteGo - Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <style>
        /* BASE & TYPOGRAPHY */
        body { 
            margin: 0; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f4f6f8; /* Clean minimalist background */
            display: flex; justify-content: center; align-items: center; min-height: 100vh; 
            padding: 40px 20px; box-sizing: border-box;
        }
        
        /* ANTI-GRAVITY ENGINE */
        @keyframes floatContinuous {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
            100% { transform: translateY(0px); }
        }

        /* CARD UI */
        main { 
            background-color: #ffffff; 
            padding: 40px; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.04); 
            width: 100%; max-width: 380px; 
            border: 1px solid #eeeeee;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        main:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.1);
            border-color: #dddddd;
        }

        /* BRANDING */
        .brand-header { text-align: center; margin-bottom: 30px; }
        .brand-title { 
            font-family: 'Alexandria', sans-serif; 
            font-size: 32px; font-weight: 900; color: #000; 
            margin: 0 0 5px 0; letter-spacing: -1px;
            animation: floatContinuous 3s ease-in-out infinite; /* Floating Logo */
        }
        .brand-subtitle { font-size: 13px; font-weight: bold; color: #888; text-transform: uppercase; letter-spacing: 1px; margin: 0;}

        /* FORM ELEMENTS */
        div { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: bold; color: #000000; }
        input { 
            width: 100%; padding: 12px 15px; border: 1px solid #e0e0e0; border-radius: 10px; 
            box-sizing: border-box; font-size: 14px; font-family: inherit;
            background-color: #fafafa; transition: all 0.3s ease; 
        }
        input:focus { 
            outline: none; border-color: #000; background-color: #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        span.field-error { color: #d9534f; font-weight: bold; font-size: 12px; margin-top: 6px; display: none; }
        
        .ui-error-box { background: #fdf0f0; color: #d9534f; border: 1px solid #f5c6cb; padding: 12px; border-radius: 10px; font-size: 13px; font-weight: bold; margin-bottom: 20px; text-align: center; }

        /* BUTTON */
        button { 
            width: 100%; background-color: #000000; color: #ffffff; padding: 15px; 
            border: none; border-radius: 10px; font-size: 16px; font-weight: bold; 
            cursor: pointer; margin-top: 10px; transition: all 0.3s ease; 
        }
        button:hover { 
            background-color: #333333; 
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        /* LINKS */
        a.back-link { display: block; text-align: center; margin-top: 25px; color: #666; text-decoration: none; transition: color 0.3s ease; font-size: 13px; font-weight: bold; border-top: 1px dashed #eee; padding-top: 20px;}
        a.back-link:hover { color: #000000; }
    </style>
</head>
<body>
    <main>
        <div class="brand-header">
            <h1 class="brand-title">BiteGo.</h1>
            <p class="brand-subtitle">Create Account</p>
        </div>
        
        <?php if(isset($_SESSION['register_error'])): ?>
            <div class="ui-error-box">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $_SESSION['register_error']; ?>
            </div>
            <?php unset($_SESSION['register_error']); ?>
        <?php endif; ?>

        <form id="registerForm" action="process_register_cust.php" method="POST">
            <div>
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname" placeholder="Enter your name">
                <span class="field-error" id="error-name">Please enter your full name.</span>
            </div>
            <div>
                <label for="email">Email Address</label>
                <input type="text" id="email" name="email" placeholder="Enter your email">
                <span class="field-error" id="error-email">Please enter a valid email address.</span>
            </div>
            <div>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Create a password">
                <span class="field-error" id="error-password">Password must be at least 6 characters.</span>
            </div>
            <div>
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password">
                <span class="field-error" id="error-match">Passwords do not match.</span>
            </div>
            <button type="submit">Register</button>
        </form>
        <a href="frontpage.php" class="back-link"><i class="fa-solid fa-arrow-left" style="margin-right: 5px;"></i> Back to Home Page</a>
    </main>

    <script>
        // EXACT ORIGINAL LOGIC
        document.getElementById('registerForm').addEventListener('submit', function(event) {
            let isValid = true;
            
            // Hide all field errors first
            document.getElementById('error-name').style.display = 'none';
            document.getElementById('error-email').style.display = 'none';
            document.getElementById('error-password').style.display = 'none';
            document.getElementById('error-match').style.display = 'none';

            const name = document.getElementById('fullname').value;
            if (name.trim() === '') { document.getElementById('error-name').style.display = 'block'; isValid = false; }

            const email = document.getElementById('email').value;
            const emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,3}$/;
            if (!email.match(emailPattern)) { document.getElementById('error-email').style.display = 'block'; isValid = false; }

            const password = document.getElementById('password').value;
            if (password.length < 6) { document.getElementById('error-password').style.display = 'block'; isValid = false; }

            const confirmPassword = document.getElementById('confirm_password').value;
            if (password !== confirmPassword || confirmPassword === '') { document.getElementById('error-match').style.display = 'block'; isValid = false; }

            if (!isValid) { 
                event.preventDefault(); 
            }
        });
    </script>
</body>
</html>