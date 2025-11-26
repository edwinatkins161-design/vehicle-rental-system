<?php
session_start();
include('db_connect.php');

// Redirect if user not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if vehicle_id is provided
if(isset($_GET['vehicle_id'])){
    $vehicle_id = intval($_GET['vehicle_id']); // sanitize input

    // Prepare and execute delete query
    $stmt = $conn->prepare("DELETE FROM vehicles WHERE vehicle_id=?");
    $stmt->bind_param("i", $vehicle_id);

    if($stmt->execute()){
        $stmt->close();
        header("Location: dashboard.php?msg=deleted");
        exit();
    } else {
        $stmt->close();
        echo "<script>alert('Error deleting vehicle.'); window.location='dashboard.php';</script>";
        exit();
    }
} else {
    // No vehicle_id provided
    header("Location: dashboard.php");
    exit();
}
?>