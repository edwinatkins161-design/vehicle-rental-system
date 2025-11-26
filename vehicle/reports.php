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

// Filters
$report_client = isset($_GET['client']) ? $_GET['client'] : '';
$report_status = isset($_GET['status']) ? $_GET['status'] : '';

// Fetch rental reports
$reports_query = "
    SELECT r.rental_id, u.full_name AS client, u.email, u.phone,
           v.make, v.model, v.registration_number, r.status
    FROM rentals r
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    JOIN users u ON r.user_id = u.user_id
    WHERE u.full_name LIKE '%$report_client%'
      AND r.status LIKE '%$report_status%'
";
$reports = $conn->query($reports_query);

// Download CSV
if(isset($_POST['download_csv'])){
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="rental_reports.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Rental ID','Client','Email','Phone','Vehicle','Reg No','Status']);
    
    $results = $conn->query($reports_query); // fetch again to avoid previous fetch consuming rows
    while($row = $results->fetch_assoc()){
        $vehicle_name = $row['make'].' '.$row['model'];
        fputcsv($output, [$row['rental_id'], $row['client'], $row['email'], $row['phone'], $vehicle_name, $row['registration_number'], $row['status']]);
    }
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Rental Reports - Car Rental System</title>
    <style>
        body { font-family: Arial; margin:0; background:#f0f4ff; }
        h1 { text-align:center; color:#007bff; margin-top:20px; }
        .search-form { text-align:center; margin-bottom:10px; margin-top:20px; }
        .search-form input, .search-form select { padding:6px; margin-right:5px; border-radius:5px; border:1px solid #ccc; }
        .search-form button { padding:6px 10px; background:#007bff; color:#fff; border:none; border-radius:5px; cursor:pointer; }
        table { width:90%; margin:auto; border-collapse:collapse; margin-top:10px; margin-bottom:20px; }
        th, td { padding:8px; border-bottom:1px solid #ddd; text-align:left; }
        .back-btn { background:#007bff; color:#fff; padding:8px 12px; border-radius:5px; margin:20px; display:inline-block; text-decoration:none; }
        .csv-btn { background:#28a745; color:#fff; padding:6px 12px; border:none; border-radius:5px; cursor:pointer; margin-bottom:10px; }
    </style>
</head>
<body>

<h1>Rental Reports</h1>
<a href="<?php echo $dashboard_link; ?>" class="back-btn">← Back to Dashboard</a>

<!-- Search & Filter Form -->
<div class="search-form">
    <form method="GET">
        <input type="text" name="client" placeholder="Client Name" value="<?php echo htmlspecialchars($report_client); ?>">
        <select name="status">
            <option value="">All Status</option>
            <option value="pending" <?php if($report_status=='pending') echo 'selected'; ?>>Pending</option>
            <option value="active" <?php if($report_status=='active') echo 'selected'; ?>>Active</option>
            <option value="completed" <?php if($report_status=='completed') echo 'selected'; ?>>Completed</option>
        </select>
        <button type="submit">Filter</button>
    </form>
</div>

<!-- CSV Download -->
<form method="POST" style="text-align:center;">
    <button type="submit" name="download_csv" class="csv-btn">Download CSV</button>
</form>

<!-- Reports Table -->
<?php if($reports->num_rows > 0){ ?>
<table>
    <tr>
        <th>Rental ID</th>
        <th>Client</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Vehicle</th>
        <th>Reg No</th>
        <th>Status</th>
    </tr>
    <?php while($r = $reports->fetch_assoc()){ ?>
    <tr>
        <td><?php echo $r['rental_id']; ?></td>
        <td><?php echo htmlspecialchars($r['client']); ?></td>
        <td><?php echo htmlspecialchars($r['email']); ?></td>
        <td><?php echo htmlspecialchars($r['phone']); ?></td>
        <td><?php echo htmlspecialchars($r['make'].' '.$r['model']); ?></td>
        <td><?php echo htmlspecialchars($r['registration_number']); ?></td>
        <td><?php echo htmlspecialchars($r['status']); ?></td>
    </tr>
    <?php } ?>
</table>
<?php } else { echo "<p style='text-align:center;'>No rental reports found.</p>"; } ?>

</body>
</html>