<?php
// Railway uses specific environment variables. We prioritize those.
$host = getenv('MYSQLHOST');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');
$db   = getenv('MYSQLDATABASE');
$port = getenv('MYSQLPORT');

// If the Railway variables are missing, we assume you are working locally on XAMPP
if (!$host) {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db   = 'planting_system'; // Your local DB name
    $port = 3306;
}

// Create the connection
$conn = new mysqli($host, $user, $pass, $db, $port);

// Check for connection errors
if ($conn->connect_error) {
    // On the web, we don't want to show the full error for security, 
    // but for now, this helps us troubleshoot.
    die("Database Connection Failed: " . $conn->connect_error);
}

// Optional: Set charset to utf8mb4 for better compatibility
$conn->set_charset("utf8mb4");
?>