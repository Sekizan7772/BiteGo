<?php

$password = "asdfghjkl";

$hash = password_hash($password, PASSWORD_BCRYPT);

echo $hash;

?>