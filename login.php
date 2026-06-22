<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>BiteGo - Staff Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <style>
        /* BASE & TYPOGRAPHY */
        body { 
            margin: 0; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f4f6f8; /* Clean minimalist background */
            display: flex; justify-content: center; align-items: center; height: 100vh; 
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
            padding: 50px 40px; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.04); 
            width: 100%; max-width: 360px; 
            border: 1px solid #eeeeee;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        main:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.1);
            border-color: #dddddd;
        }

        /* BRANDING */
        .brand-header { text-align: center; margin-bottom: 30px; }
        .brand-title { 
            font-family: 'Alexandria', sans-serif; 
            font-size: 36px; font-weight: 900; color: #000; 
            margin: 0 0 5px 0; letter-spacing: -1px;
            animation: floatContinuous 3s ease-in-out infinite; /* Floating Logo */
        }
        .brand-subtitle { font-size: 14px; font-weight: bold; color: #888; text-transform: uppercase; letter-spacing: 2px; margin: 0;}

        /* FORM ELEMENTS */
        div { margin-bottom: 25px; }
        label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: bold; color: #000000; }
        input { 
            width: 100%; padding: 15px; border: 1px solid #e0e0e0; border-radius: 10px; 
            box-sizing: border-box; font-size: 15px; font-family: inherit;
            background-color: #fafafa; transition: all 0.3s ease; 
        }
        input:focus { 
            outline: none; border-color: #000; background-color: #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        span.field-error { color: #d9534f; font-weight: bold; font-size: 12px; margin-top: 8px; display: none; }
        
        /* BUTTON */
        button { 
            width: 100%; background-color: #000000; color: #ffffff; padding: 15px; 
            border: none; border-radius: 10px; font-size: 16px; font-weight: bold; 
            cursor: pointer; margin-bottom: 20px; transition: all 0.3s ease; 
        }
        button:hover { 
            background-color: #333333; 
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        /* LINKS */
        nav { text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px dashed #eee;}
        nav > a { display: inline-block; color: #666; text-decoration: none; font-size: 13px; font-weight: bold; transition: color 0.3s ease; }
        nav > a:hover { color: #000000; }

        .ui-error-box { background: #fdf0f0; color: #d9534f; border: 1px solid #f5c6cb; padding: 12px; border-radius: 10px; font-size: 13px; font-weight: bold; margin-bottom: 25px; text-align: center; }
    </style>
</head>
<body>

    <main>
        <div class="brand-header">
            <h1 class="brand-title">BiteGo.</h1>
            <p class="brand-subtitle">Staff Portal</p>
        </div>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="ui-error-box">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form id="loginForm" action="process_login_staff.php" method="POST">
            <div>
                <label for="email">Email Address</label>
                <input type="text" id="email" name="email" placeholder="Enter your email">
                <span class="field-error" id="error-email">Please enter a valid email address.</span>
            </div>
            <div>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password">
                <span class="field-error" id="error-password">Please enter your password.</span>
            </div>
            <button type="submit">Login</button>
        </form>

        <nav>
            <a href="frontpage.php"><i class="fa-solid fa-arrow-left" style="margin-right: 5px;"></i> Back to Home Page</a> 
        </nav>
    </main>

    <script>
        // EXACT ORIGINAL LOGIC 
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            let isValid = true;
            document.getElementById('error-email').style.display = 'none';
            document.getElementById('error-password').style.display = 'none';

            const email = document.getElementById('email').value;
            const emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,3}$/;
            if (!email.match(emailPattern)) {
                document.getElementById('error-email').style.display = 'block';
                isValid = false;
            }

            const password = document.getElementById('password').value;
            if (password.trim() === '') {
                document.getElementById('error-password').style.display = 'block';
                isValid = false;
            }

            if (!isValid) {
                event.preventDefault(); // Prevents submission silently
            }
        });
    </script>
</body>
</html>