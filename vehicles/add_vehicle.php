<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('db_connect.php');
session_start();

// Optional: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $make = $_POST['make'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $registration_number = $_POST['registration_number'];
    $daily_rate = $_POST['daily_rate'];
    $status = "Available";

    // Insert into the vehicles table
    $sql = "INSERT INTO vehicles (make, model, year, registration_number, daily_rate, status, created_at)
            VALUES ('$make', '$model', '$year', '$registration_number', '$daily_rate', '$status', NOW())";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Vehicle added successfully'); window.location='view_vehicles.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Vehicle</title>
    <style>
        body { font-family: Arial; background-color: #f4f4f4; }
        form {
            background: white;
            width: 400px;
            margin: 80px auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            width: 100%;
            font-weight: bold;
        }
        button:hover { background-color: #0056b3; cursor: pointer; }
    </style>
</head>
<body>

<form method="POST" action="">
    <h2>Add Vehicle</h2>
    <input type="text" name="make" placeholder="Make (e.g., Toyota)" required>
    <input type="text" name="model" placeholder="Model (e.g., Corolla)" required>
    <input type="number" name="year" placeholder="Year (e.g., 2022)" required>
    <input type="text" name="registration_number" placeholder="Registration Number" required>
    <input type="number" name="daily_rate" placeholder="Daily Rate" step="0.01" required>
    <button type="submit">Add Vehicle</button>
</form>

</body>
</html>