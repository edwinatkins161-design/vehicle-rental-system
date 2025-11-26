<?php
$host = "localhost";
$user = "root";       // default username in XAMPP
$pass = "";            // leave blank by default
$dbname = "vehicle_rental_db";  // your database name

// Create a new MySQL connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
}
?>