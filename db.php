<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "food_ordering";

$link = mysqli_connect($host, $username, $password, $database);

if (!$link) {
    die("Connection failed: " . mysqli_connect_error());
}
?>