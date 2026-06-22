<?php
// Put the password you want to use right here:
$myPassword = "airielhakimi";

// This generates the secure Bcrypt hash
$hashedPassword = password_hash($myPassword, PASSWORD_DEFAULT);

echo "<h3>Your secure hash is:</h3>";
echo "<p style='font-family: monospace; background: #eee; padding: 10px; border: 1px solid #ccc;'>";
echo $hashedPassword;
echo "</p>";
echo "<p>Copy that string and paste it into phpMyAdmin!</p>";
?>