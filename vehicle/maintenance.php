<?php
session_start();
include('db_connect.php');

// Allow staff and admin
if(!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['staff', 'admin'])){
    header("Location: login.php");
    exit();
}

// Determine dashboard link for back button
$dashboard_link = ($_SESSION['role'] == 'admin') ? 'admin_dashboard.php' : 'staff_dashboard.php';

// Move vehicle to maintenance if called from dashboard
if(isset($_GET['vehicle_id'])){
    $vehicle_id = $_GET['vehicle_id'];
    $stmt = $conn->prepare("UPDATE vehicles SET status='maintenance' WHERE vehicle_id=?");
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Vehicle moved to maintenance!'); window.location='maintenance.php';</script>";
    exit();
}

// Move back to available
if(isset($_GET['available'])){
    $vehicle_id = $_GET['available'];
    $stmt = $conn->prepare("UPDATE vehicles SET status='available' WHERE vehicle_id=?");
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Vehicle is now available!'); window.location='maintenance.php';</script>";
    exit();
}

// Fetch maintenance vehicles
$maintenance = $conn->query("SELECT * FROM vehicles WHERE status='maintenance'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Car Maintenance - Car Rental System</title>
    <style>
        body { 
            font-family: Arial; 
            margin:0; 
            background: url('mechanic.png') no-repeat center center fixed; 
            background-size: cover;
            color: #fff;
        }
        h1 { 
            text-align:center; 
            color:#ffeb3b; 
            margin-top:20px; 
            text-shadow: 2px 2px 5px rgba(0,0,0,0.7); 
        }
        table { 
            width:90%; 
            margin:auto; 
            border-collapse:collapse; 
            margin-top:20px; 
            background: rgba(0,0,0,0.6); 
            border-radius:10px;
            overflow:hidden;
        }
        th, td { 
            padding:10px; 
            text-align:center; 
        }
        th { 
            background: rgba(0,123,255,0.8); 
            color:#fff; 
        }
        td { 
            color:#fff; 
        }
        .btn { 
            padding:5px 10px; 
            border:none; 
            border-radius:5px; 
            cursor:pointer; 
            color:#fff; 
            text-decoration:none; 
            display:inline-block; 
        }
        .available-btn { background:#28a745; }
        .back-btn { background:#007bff; margin:20px; display:inline-block; }
        p { text-align:center; font-size:16px; margin-top:20px; }
    </style>
</head>
<body>

<h1>Car Maintenance</h1>
<a href="<?php echo $dashboard_link; ?>" class="btn back-btn">← Back to Dashboard</a>

<?php if($maintenance->num_rows > 0){ ?>
<table>
    <tr>
        <th>Make</th>
        <th>Model</th>
        <th>Year</th>
        <th>Reg No</th>
        <th>Daily Rate</th>
        <th>Actions</th>
    </tr>
    <?php while($v = $maintenance->fetch_assoc()){ ?>
    <tr>
        <td><?php echo htmlspecialchars($v['make']); ?></td>
        <td><?php echo htmlspecialchars($v['model']); ?></td>
        <td><?php echo $v['year']; ?></td>
        <td><?php echo htmlspecialchars($v['registration_number']); ?></td>
        <td>Ksh <?php echo number_format($v['daily_rate']); ?></td>
        <td>
            <a href="?available=<?php echo $v['vehicle_id']; ?>" class="btn available-btn" onclick="return confirm('Mark vehicle as available?')">Mark Available</a>
        </td>
    </tr>
    <?php } ?>
</table>
<?php } else { ?>
    <p>No vehicles in maintenance.</p>
<?php } ?>

</body>
</html>