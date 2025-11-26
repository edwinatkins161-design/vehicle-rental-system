<?php
session_start();
include('db_connect.php');

// Only allow clients
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'client'){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
if(!empty($search)){
    $available = $conn->query("
        SELECT * FROM vehicles 
        WHERE make LIKE '%$search%' 
        OR model LIKE '%$search%' 
        OR registration_number LIKE '%$search%'
    ");
} else {
    $available = $conn->query("SELECT * FROM vehicles");
}

// Fetch Pending & Rented Vehicles
$pending = $conn->query("
    SELECT r.rental_id, v.make, v.model
    FROM rentals r
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    WHERE r.user_id = '$user_id' AND r.status='pending'
");

$rented = $conn->query("
    SELECT r.rental_id, v.make, v.model, v.registration_number, v.daily_rate
    FROM vehicles v
    JOIN rentals r ON v.vehicle_id = r.vehicle_id
    WHERE r.user_id = '$user_id' AND r.status='active'
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Client Dashboard - Car Rental System</title>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">

    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin:0; 
            color:#333; 
            background-image: url('cars.jpg'); 
            background-size: cover; 
            background-position: center; 
            min-height:100vh; 
        }

        h1 { 
            text-align:center; 
            color:#fff; 
            margin-top:30px; 
            text-shadow: 2px 2px 5px rgba(0,0,0,0.7); 
        }
        h2 { 
            text-align:center; 
            color:#fff; 
            margin-bottom:20px; 
            text-shadow: 1px 1px 4px rgba(0,0,0,0.6); 
        }

        .container { 
            display:flex; 
            flex-wrap: wrap; 
            justify-content: space-around; 
            margin: 20px;
        }

        /* Dashboard Cards */
        .dashboard-card {
            border-radius: 12px;
            padding: 20px;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-align: center;
            margin-bottom: 20px;
            width:360px;
            backdrop-filter: blur(5px);
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
        }
        .green { background: rgba(76, 217, 100, 0.85); }
        .orange { background: rgba(255, 149, 0, 0.85); }
        .red { background: rgba(255, 59, 48, 0.85); }

        .dashboard-card h3 {
            color: #fff;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
        }

        .dashboard-card table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            color: #fff;
        }
        .dashboard-card table th,
        .dashboard-card table td {
            padding: 6px 8px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.3);
        }
        .dashboard-card table th {
            background: rgba(255,255,255,0.2);
            color: #fff;
        }
        .dashboard-card table td {
            background: rgba(0,0,0,0.1);
            color: #fff;
        }

        .icon {
            font-size: 45px;
            margin-bottom: 10px;
        }

        .search-form { text-align:center; margin-bottom:10px; }
        .search-form input { width:70%; padding:6px; border-radius:5px; border:1px solid #ccc; }
        .search-form button { padding:6px 12px; background:#007bff; color:#fff; border:none; border-radius:5px; cursor:pointer; }

        .actions { text-align:center; margin-top:20px; }
        .actions a, .actions button { padding:8px 12px; border-radius:5px; margin:0 5px; text-decoration:none; color:#fff; font-weight:bold; }
        .actions a { background:#007bff; }
        .actions button.logout { background:#dc3545; border:none; cursor:pointer; }

        .pay-btn, .return-btn { padding:6px 10px; border-radius:5px; text-decoration:none; color:#000; font-weight:bold; font-size:13px; display:inline-block; }
        .pay-btn { background:#28a745; }
        .return-btn { background:#ffc107; }
        .pay-btn:hover, .return-btn:hover { opacity:0.9; }

    </style>
</head>
<body>
<h1>Jambo Car Rentals</h1>
<h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> 👋</h2>

<div class="container">
    <!-- Available Vehicles -->
    <div class="dashboard-card green">
        <i class="ri-car-fill icon"></i>
        <h3>Available Vehicles</h3>
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search by Make, Model, or Reg No." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit">Search</button>
        </form>
        <?php if ($available->num_rows > 0) { ?>
        <table>
            <tr>
                <th>Make</th>
                <th>Model</th>
                <th>Rate</th>
                <th>Status</th>
            </tr>
            <?php while($v = $available->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($v['make']); ?></td>
                <td><?php echo htmlspecialchars($v['model']); ?></td>
                <td>Ksh <?php echo number_format($v['daily_rate']); ?>/day</td>
                <td>
                    <?php 
                        if ($v['status'] == 'available') echo "Available";
                        else if ($v['status'] == 'maintenance') echo "<span style='color:#ffebeb;'>Maintenance</span>";
                        else echo htmlspecialchars($v['status']);
                    ?>
                </td>
            </tr>
            <?php } ?>
        </table>
        <?php } else { echo "<p>No available vehicles.</p>"; } ?>
    </div>

    <!-- Pending Requests -->
    <div class="dashboard-card orange">
        <i class="ri-time-line icon"></i>
        <h3>Pending Requests</h3>
        <?php if ($pending->num_rows > 0) { ?>
        <table>
            <tr>
                <th>Rental ID</th>
                <th>Vehicle</th>
                <th>Action</th>
            </tr>
            <?php while($p = $pending->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($p['rental_id']); ?></td>
                <td><?php echo htmlspecialchars($p['make'].' '.$p['model']); ?></td>
                <td>
                    <a href="process_payment.php?rental_id=<?php echo $p['rental_id']; ?>" class="pay-btn" onclick="Swal.fire({icon:'info', title:'Redirecting to payment', timer:1000, showConfirmButton:false})">Pay Now</a>
                </td>
            </tr>
            <?php } ?>
        </table>
        <?php } else { echo "<p>No pending requests.</p>"; } ?>
    </div>

    <!-- Rented Vehicles -->
    <div class="dashboard-card red">
        <i class="ri-steering-2-fill icon"></i>
        <h3>Rented Vehicles</h3>
        <?php if ($rented->num_rows > 0) { ?>
        <table>
            <tr>
                <th>Make</th>
                <th>Model</th>
                <th>Reg No.</th>
                <th>Action</th>
            </tr>
            <?php while($r = $rented->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($r['make']); ?></td>
                <td><?php echo htmlspecialchars($r['model']); ?></td>
                <td><?php echo htmlspecialchars($r['registration_number']); ?></td>
                <td>
                    <a href="return_vehicle.php?rental_id=<?php echo $r['rental_id']; ?>" class="return-btn" 
                       onclick="event.preventDefault(); Swal.fire({
                           title:'Return Vehicle?',
                           icon:'warning',
                           showCancelButton:true,
                           confirmButtonColor:'#28a745',
                           cancelButtonColor:'#d33'
                       }).then((result)=>{
                           if(result.isConfirmed){
                               window.location.href='return_vehicle.php?rental_id=<?php echo $r['rental_id']; ?>';
                           }
                       });">
                       Return
                    </a>
                </td>
            </tr>
            <?php } ?>
        </table>
        <?php } else { echo "<p>No rented vehicles.</p>"; } ?>
    </div>
</div>

<div class="actions">
    <a href="rent_vehicle.php">Rent Vehicle</a>
    <a href="report.php">Generate Report</a>
    <button class="logout" onclick="Swal.fire({
        title:'Logout?',
        icon:'question',
        showCancelButton:true,
        confirmButtonColor:'#dc3545',
        cancelButtonColor:'#007bff'
    }).then((result)=>{
        if(result.isConfirmed){ window.location.href='logout.php'; }
    });">Logout</button>
</div>
</body>
</html>