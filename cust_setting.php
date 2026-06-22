<?php
session_start();
include("db.php");

// 1. Session Lock: Kicks out anyone who isn't logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: loginforcust.php");
    exit();
}

$custID = $_SESSION['UserID'];

// Fetch their current details to pre-fill the form
$query = mysqli_query($link, "SELECT u.UserName,u.UserEmail
FROM user u
WHERE u.UserID='$custID'");
$userData = mysqli_fetch_assoc($query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Account Settings | BiteGo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f8; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .settings-card { background: #fff; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #eee; width: 100%; max-width: 450px; }
        .settings-card h2 { margin-top: 0; font-size: 28px; font-weight: 900; margin-bottom: 5px; }
        .settings-card p { color: #666; font-size: 14px; margin-bottom: 25px; line-height: 1.5; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 8px; color: #000; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; font-family: inherit; font-size: 14px; }
        
        .btn-save { background: #000; color: #fff; width: 100%; padding: 15px; border: none; border-radius: 8px; font-weight: bold; font-size: 16px; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-save:hover { background: #333; }
        .btn-cancel { display: block; text-align: center; color: #888; text-decoration: none; font-weight: bold; font-size: 14px; margin-top: 15px; }
        .btn-cancel:hover { color: #000; }

        .alert-box { padding: 15px; border-radius: 8px; font-size: 13px; font-weight: bold; margin-bottom: 20px; text-align: center; }
        .alert-error { background: #fdf0f0; color: #d9534f; border: 1px solid #f5c6cb; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        
        .security-zone { background: #fafafa; padding: 20px; border-radius: 10px; border: 1px dashed #ccc; margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="settings-card">
        <h2>Account Settings</h2>
        <p>Update your profile details below. You must enter your current password to save any changes.</p>

        <?php if(isset($_SESSION['setting_error'])): ?>
            <div class="alert-box alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $_SESSION['setting_error']; unset($_SESSION['setting_error']); ?></div>
        <?php endif; ?>

        <?php if(isset($_SESSION['setting_success'])): ?>
            <div class="alert-box alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $_SESSION['setting_success']; unset($_SESSION['setting_success']); ?></div>
        <?php endif; ?>

        <form action="process_custsetting.php" method="POST">
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="new_name" value="<?php echo htmlspecialchars($userData['UserName']); ?>" required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="new_email" value="<?php echo htmlspecialchars($userData['UserEmail']); ?>" required>
            </div>

            <div class="form-group">
                <label>New Password <span style="color:#888; font-weight:normal;">(Leave blank to keep current)</span></label>
                <input type="password" name="new_password" placeholder="Enter new password...">
            </div>

            <div class="security-zone">
                <label style="font-size: 13px; font-weight: bold; color: #d9534f; margin-bottom: 8px; display: block;"><i class="fa-solid fa-lock"></i> Verify Current Password to Save</label>
                <input type="password" name="current_password" placeholder="Enter current password" required style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box;">
            </div>

            <button type="submit" class="btn-save">Save Changes</button>
            <a href="frontpage.php" class="btn-cancel">Cancel & Return to Home</a>
        </form>
    </div>

</body>
</html>