<?php
session_start();

if (!isset($_SESSION['Role']) || $_SESSION['Role'] != 'Admin') {
    header("Location: login.php");
    exit();
}
?>