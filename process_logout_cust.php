<?php
session_start();

// 1. Unset all session variables
session_unset(); 

// 2. Destroy the session completely (clears memory on the server)
session_destroy(); 

// 3. Clear session cookie (optional but highly recommended)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header("Location: frontpage.php");
exit();
?>