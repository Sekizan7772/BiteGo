<?php
include("adminSession.php");
include("db.php");
include("controller.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $UserID = mysqli_real_escape_string($link, $_POST["editAdminID"]);
    $newName = mysqli_real_escape_string($link, $_POST["editAdminName"]);
    $newEmail = mysqli_real_escape_string($link, $_POST["editAdminEmail"]);
    $newPassword = $_POST["editPass"];

    $updateName = updateAdmin($UserID, $newName, $newEmail);
    if ($updateName) {

        if ($_SESSION['UserID'] == $UserID) {
            $_SESSION['UserName'] = $newName;
        }

        if (!empty($newPassword)) {

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($link, "UPDATE user SET UserPass = ? WHERE UserID = ?");
            mysqli_stmt_bind_param($stmt, "ss", $hashedPassword, $UserID);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // 4. Redirect only if success
        header("Location: adminpage.php?success=credential_updated#Admin");
        exit();

    } else {
    header("Location: adminpage.php?error=update_failed#Admin");
        exit();
    }
}
?>