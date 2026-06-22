<?php
include("adminSession.php");
include("db.php");
include("sweet.html");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $vendorName = mysqli_real_escape_string($link, $_POST["RestaurantName"]);
    $vendorEmail = mysqli_real_escape_string($link, $_POST["VendorEmail"]);
    $vendorPassword = $_POST["VendorPassword"];
    $UserId = uniqid("ved");

    // 1. Check if email is already taken
    $checkQ = mysqli_query($link, "SELECT UserEmail FROM user WHERE UserEmail='$vendorEmail'");


    if (mysqli_num_rows($checkQ) > 0) {
    // Email exists! Display SweetAlert and STOP the rest of the script.
        echo "
        <script>
         setTimeout(function() {
            Swal.fire({
                title: 'Registration Failed!',
                text: 'Registration failed! Email is already been registered.',
                icon: 'error', // Changed to 'error' for a better look
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            }).then(function() {
                window.location.href = 'adminpage.php';
            });
        }, 100);
    </script>";
    
    // CRITICAL: Stop PHP from running anything below this line!
    exit(); 
}

    $hashedPassword = password_hash($vendorPassword, PASSWORD_DEFAULT);
    
    // 2. Process the Image Upload FIRST
    $imagePath = 'gambar/bitegologo.png'; // Default
    
    if (isset($_FILES['vendorImage']) && $_FILES['vendorImage']['error'] == 0) {
        $targetDir = "gambar/";
        if (!is_dir($targetDir)) { mkdir($targetDir, 0777, true); }
        
        $safeFileName = preg_replace("/[^a-zA-Z0-9\.]/", "_", basename($_FILES["vendorImage"]["name"]));
        $fileName = time() . "_" . $safeFileName;
        $targetFilePath = $targetDir . $fileName;
        
        if (move_uploaded_file($_FILES["vendorImage"]["tmp_name"], $targetFilePath)) {
            $imagePath = $targetFilePath; // Set the real image path!
        }
    }

    // 3. Save EVERYTHING to the database at once
    mysqli_begin_transaction($link);
    try {
        // Create User account
        $sql1 = "INSERT INTO user (UserID, UserName, UserEmail, UserPass, Role) VALUES ('$UserId', '$vendorName', '$vendorEmail', '$hashedPassword', 'Vendor')";
        mysqli_query($link, $sql1);

        // Create Vendor profile with the Image Path instantly included!
        $sql2 = "INSERT INTO vendor (UserID, StoreStatus, TotalTables, VendorSales, VendorImage) VALUES ('$UserId', 'Open', 5, 0, '$imagePath')";
        mysqli_query($link, $sql2);

        mysqli_commit($link);
        header("Location: adminpage.php?success=vendor_registered#dashboard");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($link);
        die("Database Error: " . $e->getMessage());
    }
}
?>