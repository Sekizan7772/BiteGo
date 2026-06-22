<?php
include("adminSession.php");
include("db.php");

// Catch massive files that crash XAMPP
if (empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
    die("<script>alert('ERROR: File size exceeds server limit.'); window.history.back();</script>");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = mysqli_real_escape_string($link, $_POST["edit_userid"]);
    $newName = mysqli_real_escape_string($link, $_POST["edit_name"]);
    $newEmail = mysqli_real_escape_string($link, $_POST["edit_email"]);
    $newPassword = $_POST["edit_password"];

    // 1. Update text details directly
    $sql1 = "UPDATE user SET UserName='$newName', UserEmail='$newEmail' WHERE UserID='$userId'";
    mysqli_query($link, $sql1);

    if (!empty($newPassword)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        mysqli_query($link, "UPDATE user SET UserPass='$hashedPassword' WHERE UserID='$userId'");
    }


    // 2. Handle Image Upload
    if (!empty($_FILES['edit_vendorImage']['name'])) {
        if ($_FILES['edit_vendorImage']['error'] == 0) {
            $targetDir = "gambar/";
            if (!is_dir($targetDir)) { mkdir($targetDir, 0777, true); }
            
            $safeFileName = preg_replace("/[^a-zA-Z0-9\.]/", "_", basename($_FILES["edit_vendorImage"]["name"]));
            $fileName = time() . "_" . $safeFileName;
            $targetFilePath = $targetDir . $fileName;
            
            if (move_uploaded_file($_FILES["edit_vendorImage"]["tmp_name"], $targetFilePath)) {
                mysqli_query($link, "UPDATE vendor SET VendorImage='$targetFilePath' WHERE UserID='$userId'");
                header("Location: adminpage.php?success=credential_updated#Admin");
                exit();
            } else {
                header("Location: adminpage.php?success=credential_updated#Admin");
                exit();
            }
        } else {
            $err = $_FILES['edit_vendorImage']['error'];
            header("Location: adminpage.php?error=credential_updatedPicError#dashboard");;
            exit();
        }
    } else {
        // Form submitted, but no picture was chosen
        header("Location: adminpage.php?success=credential_updatedNoPic#dashboard");;
        exit();
    }
}
?>