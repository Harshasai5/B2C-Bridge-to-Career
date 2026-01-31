<?php
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to login page (update this if your login file has a different name)
header("Location: PC-login.php");  // change to your actual login page filename
exit();
?>
