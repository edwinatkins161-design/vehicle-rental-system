<?php
session_start();
include('db_connect.php');

// Only allow admin
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
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

// Fetch total users count
$total_users_result = $conn->query("SELECT COUNT(*) as total FROM users");
$total_users = $total_users_result->fetch_assoc()['total'];

// Search & Filter Vehicles
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

// --- Handle Add Vehicle ---
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
        .then(()=>{window.location='admin_dashboard.php'});
    </script>";
}

// --- Handle Edit Vehicle ---
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
        .then(()=>{window.location='admin_dashboard.php'});
    </script>";
}

// --- Handle Delete Vehicle ---
if(isset($_GET['delete_vehicle'])){
    $vehicle_id = $_GET['delete_vehicle'];
    $stmt = $conn->prepare("DELETE FROM vehicles WHERE vehicle_id=?");
    $stmt->bind_param("i", $vehicle_id);
    $stmt->execute();
    echo "<script>
        Swal.fire({icon:'success', title:'Vehicle deleted successfully!', timer:1500, showConfirmButton:false})
        .then(()=>{window.location='admin_dashboard.php'});
    </script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Car Rental System</title>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { 
            font-family: Arial; 
            background-image: url('cars.jpg'); 
            background-size: cover; 
            background-position: center; 
            min-height:100vh; 
            margin:0; 
            color:#333;
        }
        h1 { text-align:center; color:#007bff; margin-top:20px; text-shadow:1px 1px 3px rgba(0,0,0,0.4);}
        .cards { display:flex; justify-content:center; flex-wrap:wrap; margin-top:20px; }
        .card { border-radius:12px; padding:20px; margin:10px; width:220px; color:#fff; text-align:center;
                box-shadow:0 4px 12px rgba(0,0,0,0.15); transition:0.3s; }
        .card:hover { transform:translateY(-5px); box-shadow:0 8px 20px rgba(0,0,0,0.25);}
        .available { background:#28a745; } /* green */
        .pending { background:#ffc107; color:#000; } /* yellow */
        .rented { background:#dc3545; } /* red */
        .users { background:#17a2b8; } /* blue */
        .actions { text-align:center; margin:20px 0; }
        .actions button { margin:0 5px; padding:8px 12px; border:none; border-radius:5px; cursor:pointer; color:#fff; background:#007bff; }
        table { width:90%; margin:20px auto; border-collapse:collapse; background:rgba(255,255,255,0.95); border-radius:10px; overflow:hidden; }
        th, td { padding:8px; border-bottom:1px solid #ddd; text-align:center; }
        th { background:#007bff; color:#fff; }
        .btn { padding:5px 10px; border-radius:5px; border:none; cursor:pointer; color:#fff; }
        .edit-btn { background:#007bff; }
        .delete-btn { background:#dc3545; }
        .maintenance-btn { background:#17a2b8; margin-left:3px; }
        .form-popup { display:none; width:400px; margin:auto; background:#fff; padding:20px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.2); }
        .form-popup input, .form-popup select { display:block; margin:10px 0; padding:8px; width:100%; border-radius:5px; border:1px solid #ccc; }
        .logout-btn { margin:20px auto; display:block; padding:8px 12px; border-radius:5px; border:none; cursor:pointer; background:#dc3545; color:#fff; }
    </style>
</head>
<body>

<h1>Admin Dashboard - Welcome <?php echo htmlspecialchars($_SESSION['full_name']); ?> 👋</h1>

<!-- Summary Cards -->
<div class="cards">
    <div class="card available">
        <h3>Available Vehicles</h3>
        <?php echo !empty($available_vehicles) ? implode("<br>", $available_vehicles) : "None"; ?>
    </div>
    <div class="card pending">
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
    <div class="card rented">
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
    <div class="card users">
        <h3>Total Users</h3>
        <?php echo $total_users; ?>
    </div>
</div>

<!-- Top Buttons -->
<div class="actions">
    <button onclick="document.getElementById('addVehicleForm').style.display='block'">Add Vehicle</button>
    <button onclick="window.location.href='maintenance.php'">Maintenance</button>
    <button onclick="window.location.href='reports.php'">Rental Reports</button>
    <button onclick="window.location.href='users.php'">Manage Users</button>
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
        <button type="submit" name="add_vehicle" style="background:#007bff;">Add Vehicle</button>
        <button type="button" style="background:#dc3545;" onclick="document.getElementById('addVehicleForm').style.display='none'">Cancel</button>
    </form>
</div>

<!-- Edit Vehicle Form -->
<div id="editVehicleForm" class="form-popup">
    <h3>Edit Vehicle</h3>
    <form method="POST">
        <input type="hidden" id="edit_vehicle_id" name="vehicle_id">
        <input type="text" id="edit_make" name="make" placeholder="Make" required>
        <input type="text" id="edit_model" name="model" placeholder="Model" required>
        <input type="number" id="edit_year" name="year" placeholder="Year" required>
        <input type="text" id="edit_registration_number" name="registration_number" placeholder="Registration Number" required>
        <input type="number" id="edit_daily_rate" name="daily_rate" placeholder="Daily Rate" required>
        <select id="edit_status" name="status" required>
            <option value="available">Available</option>
            <option value="booked">Booked</option>
            <option value="pending">Pending</option>
            <option value="maintenance">Maintenance</option>
        </select>
        <button type="submit" name="edit_vehicle" style="background:#007bff;">Update Vehicle</button>
        <button type="button" style="background:#dc3545;" onclick="document.getElementById('editVehicleForm').style.display='none'">Cancel</button>
    </form>
</div>

<!-- Vehicles Table -->
<h2 style="text-align:center; color:#007bff;">Vehicles</h2>
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
        <button class="btn edit-btn"
            onclick="editVehicle(
                <?php echo $v['vehicle_id']; ?>,
                '<?php echo addslashes($v['make']); ?>',
                '<?php echo addslashes($v['model']); ?>',
                <?php echo $v['year']; ?>,
                '<?php echo addslashes($v['registration_number']); ?>',
                <?php echo $v['daily_rate']; ?>,
                '<?php echo $v['status']; ?>'
            )">Edit</button>

        <a href="maintenance.php?vehicle_id=<?php echo $v['vehicle_id']; ?>" class="btn maintenance-btn">Maintenance</a>

        <a href="?delete_vehicle=<?php echo $v['vehicle_id']; ?>" class="btn delete-btn"
           onclick="return confirm('Delete this vehicle?')">Delete</a>
    </td>
</tr>

<?php } ?>
</table>

<button class="logout-btn" onclick="Swal.fire({
    title:'Logout?',
    icon:'question',
    showCancelButton:true,
    confirmButtonColor:'#dc3545',
    cancelButtonColor:'#007bff'
}).then((result)=>{ if(result.isConfirmed){ window.location.href='logout.php'; } });">Logout</button>

<script>
function editVehicle(id, make, model, year, regNo, dailyRate, status){
    document.getElementById('editVehicleForm').style.display='block';
    document.getElementById('edit_vehicle_id').value=id;
    document.getElementById('edit_make').value=make;
    document.getElementById('edit_model').value=model;
    document.getElementById('edit_year').value=year;
    document.getElementById('edit_registration_number').value=regNo;
    document.getElementById('edit_daily_rate').value=dailyRate;
    document.getElementById('edit_status').value=status;
}
</script>

</body>
</html>