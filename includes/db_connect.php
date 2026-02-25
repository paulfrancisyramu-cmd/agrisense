<?php
$conn = new mysqli("localhost", "root", "", "planting_system");
if ($conn->connect_error) { die("Database Connection Failed: " . $conn->connect_error); }
?>