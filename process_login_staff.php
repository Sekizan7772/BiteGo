<?php
include("db.php");
include("controller.php");
session_start();


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $result=loginStaff($email, $password);
    if($result == false){
        header("Location: login.php");
        exit();
    }

     
}
?>