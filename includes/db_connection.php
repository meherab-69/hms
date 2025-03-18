<?php
// Database configuration
$host = 'localhost';       // Database host (usually 'localhost')
$dbname = 'hms'; // Name of the database
$user = 'hms_user';   // Database username
$pass = 'Opivhai@123'; // Database password

// Create a new MySQLi connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Set charset to UTF-8
$conn->set_charset("utf8");

// Debugging: Uncomment the following line to test the connection
// echo "Connected successfully!";
?>