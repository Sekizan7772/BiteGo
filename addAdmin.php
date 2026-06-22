<?php
include ("db.php");
include ("adminSession.php");
include ("controller.php");

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $UserID = uniqid("Admin");
    $UserName = mysqli_real_escape_string($link, $_POST["name"]);
    $UserEmail =  mysqli_real_escape_string($link, $_POST["email"]);
    $pass =  mysqli_real_escape_string($link, $_POST["pass"]);
    
}
$UserPass = password_hash($pass, PASSWORD_BCRYPT);
$check = mysqli_query($link,"SELECT UserEmail FROM user WHERE UserEmail = '$UserEmail'");
if(mysqli_num_rows($check) > 0){
    header("Location: adminpage.php?error=email_exists&showModal=addAdmin#Admin");
    exit();
}
    else{
        if (addAdmin($UserID,$UserName,$UserEmail,$UserPass)){   
            header("Location: adminpage.php?success=email_registered#Admin");
        }
            
    
    
}


?>