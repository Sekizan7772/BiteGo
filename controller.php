<?php
include ("db.php");

function registerUser($userID, $fullName, $email, $password) {
    global $link;
    
    $checkEmail = mysqli_real_escape_string($link, $email);
    $checkQuery = mysqli_query($link, "SELECT UserEmail 
    FROM user 
    WHERE UserEmail = '$checkEmail'");
    
    if (mysqli_num_rows($checkQuery) > 0) {
        $_SESSION['register_error'] = "This email is already registered. Please log in or use a different email.";
        header("Location: register.php");
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $fullNameSafe = mysqli_real_escape_string($link, $fullName);
    
    $sql = "INSERT INTO user (UserID, UserName, UserEmail, UserPass, Role )
            VALUES ('$userID', '$fullNameSafe', '$checkEmail', '$hashedPassword', 'Customer' )";
    
    $result = mysqli_query($link, $sql);
    
    if($result){
         $sql = "INSERT INTO customer (UserID,Points  )
            VALUES ('$userID', 0)";
        mysqli_query($link, $sql);

        $_SESSION['UserID'] = $userID;
        $_SESSION['CustName'] = $fullName;
        
        // FIX: SMART REDIRECT AFTER REGISTRATION
        $redirect = isset($_SESSION['redirect_to']) ? $_SESSION['redirect_to'] : 'frontpage.php';
        unset($_SESSION['redirect_to']); 
        
        header("Location: " . $redirect);
        exit();
    }
    else{
        $_SESSION['register_error'] = "A database error occurred. Please try again.";
        header("Location: register.php");
        exit();
    }
}

function loginStaff($email, $password) {   
    global $link; 
    $email = mysqli_real_escape_string($link, $email); 
    $sql = "SELECT UserID, UserPass,Role,UserName FROM user WHERE UserEmail = '$email'" ;
    $result = mysqli_query($link, $sql);
    if(mysqli_num_rows($result)==1){
        $row = mysqli_fetch_assoc($result);
        if(password_verify($password,$row['UserPass'])){ 
            $role = $row['Role'];
            $name = $row['UserName'];
            $userID = $row['UserID'];

            $_SESSION['Role'] = $role;
            $_SESSION['UserName'] = $name;
            $_SESSION['UserID'] = $userID;

            if ($role == 'Admin') {
                header("Location: adminpage.php");
                exit(); 
            }
            else if ($role == 'Vendor') {
                header("Location: vendorpage.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid email or password.";
            return false;
        }
    } else {
        $_SESSION['error'] = "User not found.";
        return false;
    }
}

function getTotalVendors(){
    global $link;
    $sql = "SELECT COUNT(*) AS total FROM user WHERE role = 'Vendor'";
    $result = mysqli_query($link, $sql);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

function get_vendorList(){
    global $link;
    $sql = "SELECT u.UserID, u.UserName, u.UserEmail, v.StoreStatus FROM user u LEFT JOIN vendor v ON u.UserID = v.UserID WHERE u.Role ='Vendor'";
    $result = mysqli_query($link,$sql);
    $vendor = [];
    if($result){
        while($row= mysqli_fetch_assoc($result)){ $vendor[]=$row; }
        return $vendor;
    }
}
    
function deleteVendor($UserId) {
    global $link;
    $UserId = mysqli_real_escape_string($link, $UserId);

    // Must delete child rows first — MySQL blocks deleting from user
    // if a matching row still exists in vendor (foreign key constraint)
    mysqli_query($link, "DELETE FROM vendor WHERE UserID = '$UserId'");

    $result = mysqli_query($link, "DELETE FROM user WHERE UserID = '$UserId'");
    return $result ? true : false;
}

function loginCustomer($email, $password) {   
    global $link; 
    $email = mysqli_real_escape_string($link, $email); 
    $sql = "SELECT UserID, UserName, UserPass FROM user WHERE UserEmail = '$email'";
    $result = mysqli_query($link, $sql);
    
    if(mysqli_num_rows($result) == 1){
        $row = mysqli_fetch_assoc($result);
        if(password_verify($password, $row['UserPass'])){
            $_SESSION['UserID'] = $row['UserID'];
            $_SESSION['CustName'] = $row['UserName'];
            
            $redirect = isset($_SESSION['redirect_to']) ? $_SESSION['redirect_to'] : 'checkout.php';
            unset($_SESSION['redirect_to']); 
            
            header("Location: " . $redirect);
            exit(); 
        } else {
            $_SESSION['login_error'] = "Invalid password! Please try again.";
            header("Location: loginforcust.php");
            exit();
        }
    } else {
        $_SESSION['login_error'] = "Account not found! Please check your email.";
        header("Location: loginforcust.php");
        exit();
    }
}

function vendorOpt(){
    global $link;
    $sql = "SELECT u.UserID, v.VendorDescription, u.UserName, v.VendorImage, v.VendorRating, v.StoreStatus 
    FROM user u 
    JOIN vendor v
    ON u.userID = v.UserID";
    $result = mysqli_query($link, $sql);
    $vendor = [];
    if($result){
        while($row = mysqli_fetch_assoc($result)){
            $vendor[] = $row;
        }
    }  
    return $vendor;
}

function searchVendor($vendorName){
    global $link;
    $safeSearch = mysqli_real_escape_string($link, $vendorName);
    
    $sql ="SELECT u.UserID, v.VendorDescription, u.UserName, v.VendorRating, v.VendorImage, v.StoreStatus 
    FROM user u
    JOIN vendor v
    ON u.UserID = v.UserID 
    WHERE u.UserName LIKE '%$safeSearch%'";
    $result = mysqli_query($link, $sql);
    $vendor = [];
    if($result){
        while($row = mysqli_fetch_assoc($result)){
            $vendor[] = $row;
        }
    }
    return $vendor;
}

function getAdminList(){
    global $link;
    $sql = "SELECT UserID,UserName,UserEmail FROM user WHERE Role = 'Admin'";
    $result = mysqli_query($link,$sql);
    $admin = [];
    if($result){
        while($row = mysqli_fetch_assoc($result)){
            $admin[] = $row;
        }
    }
    return $admin;

}
function deleteAdmin($UserID){
    global $link;
    $sql = "DELETE FROM user WHERE UserID = '$UserID'";
    $result = mysqli_query($link,$sql);
    if($result){
        return true;
    }
    return false;
}
function addAdmin($UserID,$UserName,$UserEmail,$UserPass){
    global $link;
    $sql = "INSERT INTO user (UserID, UserName, UserEmail , UserPass ,Role)
    VAlUES ( '$UserID', '$UserName', '$UserEmail', '$UserPass', 'Admin')";
    $result = mysqli_query($link,$sql);
    if($result){
        return true;
    }
    return false;

}

function updateAdmin($UserID, $UserName, $UserEmail){
    global $link;

    $sql = "UPDATE user SET UserName = ?, UserEmail = ? WHERE UserID = ?";
    $stmt = mysqli_prepare($link, $sql);

    if (!$stmt) return false;

    mysqli_stmt_bind_param($stmt, "sss", $UserName, $UserEmail, $UserID);

    $result = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);

    return $result;
}

?>