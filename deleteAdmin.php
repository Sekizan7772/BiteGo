<?php
include("db.php");
include("adminSession.php");
include("controller.php");

if(isset($_GET['UserID'])){

    $UserID = $_GET['UserID'];

    if(deleteAdmin($UserID)){
        header("Location: adminpage.php?success=admin_deleted#Admin");
        exit();
    } else {
        header("Location: adminpage.php?error=delete_failed#Admin");
        exit();
    }
}
?>