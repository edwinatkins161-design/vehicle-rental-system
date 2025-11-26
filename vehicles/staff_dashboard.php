<?php
session_start();
include('db_connect.php');

// Only allow staff
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'staff'){
    header("Location: login.php");
    exit();
}

// Fetch available vehicles
$available_result = $conn->query("SELECT make, model FROM vehicles WHERE status='available'");
$available_vehicles = [];
while($row = $available_result->fetch_assoc()){
    $available_vehicles[] = $row['make'] . ' ' . $row['model'];
}

// Fetch pending rentals
$pending_result = $conn->query("
    SELECT v.vehicle_id, v.make, v.model, u.full_name, u.email, u.phone
    FROM rentals r
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    JOIN users u ON r.user_id = u.user_id
    WHERE r.status='pending'
");
$pending_vehicles = [];
while($row = $pending_result->fetch_assoc()){
    $pending_vehicles[] = [
        'vehicle_id' => $row['vehicle_id'],
        'name' => $row['make'].' '.$row['model'],
        'renter_name' => $row['full_name'],
        'renter_email' => $row['email'],
        'renter_phone' => $row['phone']
    ];
}

// Fetch rented vehicles
$rented_result = $conn->query("
    SELECT v.vehicle_id, v.make, v.model, u.full_name, u.email, u.phone
    FROM rentals r
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    JOIN users u ON r.user_id = u.user_id
    WHERE r.status='active'
");
$rented_vehicles = [];
while($row = $rented_result->fetch_assoc()){
    $rented_vehicles[] = [
        'vehicle_id' => $row['vehicle_id'],
        'name' => $row['make'].' '.$row['model'],
        'renter_name' => $row['full_name'],
        'renter_email' => $row['email'],
        'renter_phone' => $row['phone']
    ];
}

// Search & Filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sql = "SELECT * FROM vehicles";
$conditions = [];
if(!empty($search)){
    $conditions[] = "(make LIKE '%$search%' OR model LIKE '%$search%' OR registration_number LIKE '%$search%')";
}
if(!empty($status_filter)){
    $conditions[] = "status='$status_filter'";
}
if(!empty($conditions)){
    $sql .= " WHERE ".implode(" AND ", $conditions);
}
$vehicles = $conn->query($sql);

// Handle Add Vehicle
if(isset($_POST['add_vehicle'])){
    $make = $_POST['make'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $registration_number = $_POST['registration_number'];
    $daily_rate = $_POST['daily_rate'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("INSERT INTO vehicles (make, model, year, registration_number, daily_rate, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisis", $make, $model, $year, $registration_number, $daily_rate, $status);
    $stmt->execute();
    echo "<script>
        Swal.fire({icon:'success', title:'Vehicle added successfully!', timer:1500, showConfirmButton:false})
        .then(()=>{window.location='staff_dashboard.php'});
    </script>";
}

// Handle Edit Vehicle
if(isset($_POST['edit_vehicle'])){
    $vehicle_id = $_POST['vehicle_id'];
    $make = $_POST['make'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $registration_number = $_POST['registration_number'];
    $daily_rate = $_POST['daily_rate'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE vehicles SET make=?, model=?, year=?, registration_number=?, daily_rate=?, status=? WHERE vehicle_id=?");
    $stmt->bind_param("ssisisi", $make, $model, $year, $registration_number, $daily_rate, $status, $vehicle_id);
    $stmt->execute();
    echo "<script>
        Swal.fire({icon:'success', title:'Vehicle updated successfully!', timer:1500, showConfirmButton:false})
        .then(()=>{window.location='staff_dashboard.php'});
    </script>";
}

// Handle Delete Vehicle
if(isset($_GET['delete_vehicle'])){
    $vehicle_id = $_GET['delete_vehicle'];
    $stmt = $conn->prepare("DELETE FROM vehicles WHERE vehicle_id=?");
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    echo "<script>
        Swal.fire({icon:'success', title:'Vehicle deleted successfully!', timer:1500, showConfirmButton:false})
        .then(()=>{window.location='staff_dashboard.php'});
    </script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Staff Dashboard - Car Rental System</title>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">

    <style>
        body { 
            font-family: Arial, sans-serif; 
            background-image: url('cars.jpg'); 
            background-size: cover; 
            background-position: center; 
            min-height:100vh; 
            margin:0;
            color:#333;
        }

        h1 { 
            text-align:center; 
            color:#007bff; 
            margin-top:20px; 
            text-shadow: 2px 2px 5px rgba(0,0,0,0.3); 
        }

        /* Dashboard Cards */
        .cards { display:flex; justify-content:center; flex-wrap:wrap; margin-top:20px; }
        .dashboard-card {
            border-radius: 12px;
            padding: 20px;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-align: center;
            margin: 10px;
            width: 250px;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        .green { background:#28a745; }   /* Available - white text */
        .orange { background:#ffc107; color:#000; }  /* Pending - black text */
        .red { background:#dc3545; }     /* Rented - white text */
        .icon { font-size:40px; margin-bottom:10px; }

        /* Buttons */
        .btn { padding:8px 12px; border-radius:5px; border:none; cursor:pointer; margin:2px; }
        .btn-primary { background:#007bff; color:#fff; }
        .btn-danger { background:#dc3545; color:#fff; }
        .btn-warning { background:#ffc107; color:#000; }

        /* Tables */
        table { width:90%; margin:20px auto; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; }
        th, td { padding:8px; border-bottom:1px solid #ddd; text-align:left; }
        th { background:#007bff; color:#fff; }

        /* Forms */
        .form-popup { display:none; width:400px; margin:auto; background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.15); }
        .form-popup input, .form-popup select { width:100%; padding:6px; margin:6px 0; border-radius:5px; border:1px solid #ccc; }

    </style>
</head>
<body>

<h1>Staff Dashboard - Welcome <?php echo htmlspecialchars($_SESSION['full_name']); ?> 👋</h1>

<!-- Summary Cards -->
<div class="cards">
    <div class="dashboard-card green">
        <i class="ri-car-fill icon"></i>
        <h3>Available Vehicles</h3>
        <?php echo !empty($available_vehicles) ? implode("<br>", $available_vehicles) : "None"; ?>
    </div>

    <div class="dashboard-card orange">
        <i class="ri-time-line icon"></i>
        <h3>Pending Rentals</h3>
        <?php
        if(empty($pending_vehicles)){ echo "None"; }
        else{
            foreach($pending_vehicles as $v){
                echo "<p><b>{$v['name']}</b><br>{$v['renter_name']}<br>{$v['renter_email']}<br>{$v['renter_phone']}</p>";
            }
        }
        ?>
    </div>

    <div class="dashboard-card red">
        <i class="ri-steering-2-fill icon"></i>
        <h3>Rented Vehicles</h3>
        <?php
        if(empty($rented_vehicles)){ echo "None"; }
        else{
            foreach($rented_vehicles as $v){
                echo "<p><b>{$v['name']}</b><br>{$v['renter_name']}<br>{$v['renter_email']}<br>{$v['renter_phone']}</p>";
            }
        }
        ?>
    </div>
</div>

<!-- Top Buttons -->
<div class="actions" style="text-align:center; margin:20px 0;">
    <button class="btn btn-primary" onclick="document.getElementById('addVehicleForm').style.display='block'">Add Vehicle</button>
    <button class="btn btn-primary" onclick="window.location.href='maintenance.php'">Maintenance</button>
    <button class="btn btn-primary" onclick="window.location.href='reports.php'">Rental Reports</button>
</div>

<!-- Add Vehicle Form -->
<div id="addVehicleForm" class="form-popup">
    <h3>Add Vehicle</h3>
    <form method="POST">
        <input type="text" name="make" placeholder="Make" required>
        <input type="text" name="model" placeholder="Model" required>
        <input type="number" name="year" placeholder="Year" required>
        <input type="text" name="registration_number" placeholder="Registration Number" required>
        <input type="number" name="daily_rate" placeholder="Daily Rate" required>
        <select name="status" required>
            <option value="available">Available</option>
            <option value="booked">Booked</option>
            <option value="pending">Pending</option>
            <option value="maintenance">Maintenance</option>
        </select>
        <button type="submit" name="add_vehicle" class="btn btn-primary">Add Vehicle</button>
        <button type="button" class="btn btn-danger" onclick="document.getElementById('addVehicleForm').style.display='none'">Cancel</button>
    </form>
</div>

<!-- Vehicles Table -->
<table>
<tr>
    <th>Make</th>
    <th>Model</th>
    <th>Year</th>
    <th>Reg No</th>
    <th>Rate</th>
    <th>Status</th>
    <th>Renter Details</th>
    <th>Actions</th>
</tr>
<?php while($v = $vehicles->fetch_assoc()){ 
    $renter = $conn->query("
        SELECT u.full_name, u.email, u.phone, r.status AS rental_status
        FROM rentals r
        JOIN users u ON r.user_id=u.user_id
        WHERE r.vehicle_id={$v['vehicle_id']} AND r.status IN ('active','pending')
    ")->fetch_assoc();

    $status_display = $v['status'];
    if($renter){
        if($renter['rental_status']=='active') $status_display = 'Rented';
        if($renter['rental_status']=='pending') $status_display = 'Pending';
    }
?>
<tr>
    <td><?php echo $v['make']; ?></td>
    <td><?php echo $v['model']; ?></td>
    <td><?php echo $v['year']; ?></td>
    <td><?php echo $v['registration_number']; ?></td>
    <td>Ksh <?php echo number_format($v['daily_rate']); ?></td>
    <td><?php echo $status_display; ?></td>
    <td>
        <?php 
            if($renter){
                echo $renter['full_name']."<br>".$renter['email']."<br>".$renter['phone'];
            } else echo "-";
        ?>
    </td>
    <td>
        <button class="btn btn-primary"
            onclick="editVehicle(
                <?php echo $v['vehicle_id']; ?>,
                '<?php echo addslashes($v['make']); ?>',
                '<?php echo addslashes($v['model']); ?>',
                <?php echo $v['year']; ?>,
                '<?php echo addslashes($v['registration_number']); ?>',
                <?php echo $v['daily_rate']; ?>,
                '<?php echo $v['status']; ?>'
            )">Edit</button>

        <a href="maintenance.php?vehicle_id=<?php echo $v['vehicle_id']; ?>" class="btn btn-primary" style="margin-left:3px;">Maintenance</a>

        <a href="?delete_vehicle=<?php echo $v['vehicle_id']; ?>" class="btn btn-danger"
           onclick="event.preventDefault(); Swal.fire({
               title:'Delete this vehicle?',
               icon:'warning',
               showCancelButton:true,
               confirmButtonColor:'#dc3545',
               cancelButtonColor:'#007bff'
           }).then((result)=>{
               if(result.isConfirmed){ window.location.href='?delete_vehicle=<?php echo $v['vehicle_id']; ?>'; }
           });">Delete</a>
    </td>
</tr>
<?php } ?>
</table>

<div style="text-align:center; margin:20px;">
    <button class="btn btn-danger" onclick="Swal.fire({
        title:'Logout?',
        icon:'question',
        showCancelButton:true,
        confirmButtonColor:'#dc3545',
        cancelButtonColor:'#007bff'
    }).then((result)=>{ if(result.isConfirmed){ window.location.href='logout.php'; } });">Logout</button>
</div>

<script>
function editVehicle(id, make, model, year, regNo, dailyRate, status){
    // Here you can open a popup or redirect to edit page
    Swal.fire({
        title: 'Edit Vehicle feature can be implemented here!',
        icon: 'info'
    });
}
</script>
</body>
</html>