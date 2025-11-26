<?php
include('db_connect.php');
session_start();

// Optional: only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$sql = "SELECT * FROM vehicles";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Vehicles</title>
    <style>
        body { font-family: Arial; background-color: #f4f4f4; }
        table {
            width: 90%;
            margin: 40px auto;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px #ccc;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:nth-child(even) { background-color: #f9f9f9; }
        a.button {
            text-decoration: none;
            background-color: #28a745;
            color: white;
            padding: 6px 10px;
            border-radius: 5px;
        }
        a.button:hover { background-color: #218838; }
    </style>
</head>
<body>

<h2 style="text-align:center;">Vehicle List</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Make</th>
        <th>Model</th>
        <th>Year</th>
        <th>Registration</th>
        <th>Daily Rate</th>
        <th>Status</th>
        <th>Action</th>
    </tr>

    <?php
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>
                <td>{$row['vehicle_id']}</td>
                <td>{$row['make']}</td>
                <td>{$row['model']}</td>
                <td>{$row['year']}</td>
                <td>{$row['registration_number']}</td>
                <td>{$row['daily_rate']}</td>
                <td>{$row['status']}</td>
                <td>
                    <a href='rent_vehicle.php?id={$row['vehicle_id']}' class='button'>Rent</a>
                </td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='8'>No vehicles available.</td></tr>";
    }
    ?>
</table>

</body>
</html>