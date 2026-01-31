<?php
// db.php - Database connection file

$host = "localhost";   // Database host
$user = "root";        // MySQL username (default: root for XAMPP/WAMP)
$pass = "";            // MySQL password (keep empty for default XAMPP/WAMP)
$dbname = "b2c";       // Database name

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: set UTF-8 encoding
$conn->set_charset("utf8");
?>
