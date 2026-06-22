<?php
session_start();
include('db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['UserID'])) {
    
    $custID = mysqli_real_escape_string($link, $_SESSION['UserID']);
    $orderID = mysqli_real_escape_string($link, $_POST['orderID']);
    $vendorID = mysqli_real_escape_string($link, $_POST['vendorID']);
    
    // 1. Get the two ratings from the form
    $food_rating = (float)$_POST['rating'];
    $vendor_service_rating = (float)$_POST['vendor_rating'];
    $comment = mysqli_real_escape_string($link, $_POST['comment']);

    // 2. Calculate the average for this specific order
    $overall_order_rating = ($food_rating + $vendor_service_rating) / 2;
    
    // Round it so it fits perfectly into your existing int(11) 'Rating' column
    $rounded_order_rating = round($overall_order_rating);

    // Check if the order was already reviewed to prevent duplicate spam
    $checkQ = "SELECT o.OrderID FROM `order` o 
           LEFT JOIN review r ON o.OrderID = r.OrderID 
           WHERE o.OrderID='$orderID' AND o.UserID='$custID' AND r.ReviewID IS NULL";
    $checkRes = mysqli_query($link, $checkQ);

    // If it returns 1 row, NO review exists yet, so it is SAFE to insert!
    if (mysqli_num_rows($checkRes) > 0) {
        
        // 3. Insert the averaged rating into your original review table
        $sql = "INSERT INTO review (OrderID, Rating, Comment, ReviewDate) 
                VALUES ('$orderID', '$rounded_order_rating', '$comment', NOW())";
        mysqli_query($link, $sql);
        
        // 4. Safely calculate the true average of ALL reviews for this vendor directly from the DB
        $avgQuery = "SELECT AVG(r.Rating) as newAvg 
                     FROM review r 
                     JOIN `order` o ON r.OrderID = o.OrderID 
                     WHERE o.VendorUserID='$vendorID'";
        $avgRes = mysqli_query($link, $avgQuery);
        $avgRow = mysqli_fetch_assoc($avgRes);
        
        // Format to 1 decimal place (e.g., 4.5)
        $newVendorRating = round($avgRow['newAvg'], 1);

        // 5. Update the Vendor Table Attribute!
        mysqli_query($link, "UPDATE vendor SET VendorRating='$newVendorRating' WHERE UserID='$vendorID'");
    }

    // Refresh back to their tracking page
    header("Location: past_orders.php");
    exit();
}
?>