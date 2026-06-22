<?php
include ("db.php");
include("controller.php");
include("adminSession.php");


if(isset($_GET['UserID'])){
    $UserId=$_GET['UserID'];

        if(deleteVendor($UserId)){
        header("Location: adminpage.php?success=vendor_deleted#dashboard");
        exit();
    } else {
        header("Location: adminpage.php?error=delete_failed#dashboard");
        exit();
    }

}

?>