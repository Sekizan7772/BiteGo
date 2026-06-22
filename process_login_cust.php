<?php
session_start();
include 'controller.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Send the data to the controller to check the database
    loginCustomer($email, $password);
}
?>

