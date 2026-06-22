<?php
session_start();
include("db.php");

if (!isset($_SESSION['UserID'])) {
    header("Location: loginforcust.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $custID = $_SESSION['UserID'];
    
    // Sanitize inputs
    $newName = mysqli_real_escape_string($link, trim($_POST['new_name']));
    $newEmail = mysqli_real_escape_string($link, trim($_POST['new_email']));
    $newPassword = trim($_POST['new_password']);
    $currentPassword = $_POST['current_password'];

    // 1. Fetch current password hash from database
    $query = mysqli_query($link, "SELECT UserPass FROM user WHERE UserID='$custID'");
    $userData = mysqli_fetch_assoc($query);

    // 2. SECURITY CHECK: Verify the current password
    if (!password_verify($currentPassword, $userData['UserPass'])) {
        $_SESSION['setting_error'] = "Incorrect current password. Changes were not saved.";
        header("Location: cust_setting.php");
        exit();
    }

    // 3. Check if they are changing their email to one that already exists
    $emailCheck = mysqli_query($link, "SELECT UserID FROM user WHERE UserEmail='$newEmail' AND UserID != '$custID'");
    if (mysqli_num_rows($emailCheck) > 0) {
        $_SESSION['setting_error'] = "That email address is already taken by another account.";
        header("Location: cust_setting.php");
        exit();
    }

    // 4. Update the database!
    $updateSQL = "UPDATE user SET UserName='$newName', UserEmail='$newEmail'";
    
    // If they typed a new password, hash it and add it to the update query
    if (!empty($newPassword)) {
        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateSQL .= ", UserPass='$hashedNewPassword'";
    }

    $updateSQL .= " WHERE UserID='$custID'";

    if (mysqli_query($link, $updateSQL)) {
        // Update their live Session Name so the Navbar changes instantly!
        $_SESSION['CustName'] = $newName;
        
        $_SESSION['setting_success'] = "Account settings updated successfully!";
        header("Location: cust_setting.php");
        exit();
    } else {
        $_SESSION['setting_error'] = "A database error occurred. Please try again.";
        header("Location: cust_setting.php");
        exit();
    }
} else {
    header("Location: cust_setting.php");
    exit();
}
?>