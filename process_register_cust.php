<?php
include "controller.php";
session_start();
include("db.php");  
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $userID = uniqid("CUS_", true);
    $fullName = $_POST["fullname"];
    $email = $_POST["email"];
    $password = $_POST["password"];
   registerUser($userID, $fullName, $email, $password);
}
