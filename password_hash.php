<?php
// Password Hashing Example
$password = "medo123My@";
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Verify Password
if (password_verify($password, $hashedPassword)) {
    echo "Password is valid!";
} else {
    echo "Invalid password.";
}
?>