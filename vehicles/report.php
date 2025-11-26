<?php
session_start();
include('db_connect.php');

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Filter dates
$start = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build query
$query = "
    SELECT r.rental_id, u.full_name, v.make, v.model, v.registration_number, v.daily_rate, r.start_date, r.end_date, r.status
    FROM rentals r
    JOIN users u ON r.user_id = u.user_id
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    WHERE 1
";

if ($start && $end) {
    $query .= " AND r.start_date >= '$start' AND r.end_date <= '$end'";
}

$query .= " ORDER BY r.start_date DESC";

$report = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Rental Report - Car Rental System</title>
    <style>
        body { font-family: Arial; background: #f5f6f8; margin: 30px; color:#333; }
        h2 { text-align:center; color:#007bff; margin-bottom:20px; }
        .container { max-width: 1200px; margin: auto; }
        table { width:100%; border-collapse:collapse; margin-top:20px; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
        th, td { padding:10px; border-bottom:1px solid #ddd; text-align:center; }
        th { background:#007bff; color:#fff; }
        tr:hover { background:#f1f1f1; }
        .filter { text-align:center; margin-bottom:20px; }
        .filter input { padding:6px; margin-right:5px; border-radius:5px; border:1px solid #ccc; }
        .filter button { padding:6px 12px; background:#007bff; color:#fff; border:none; border-radius:5px; cursor:pointer; }
        .filter button:hover { opacity:0.9; }
        .actions { text-align:center; margin-top:20px; }
        .actions a, .actions button { padding:8px 12px; border-radius:5px; margin:0 5px; text-decoration:none; color:#fff; }
        .actions a.back-btn { background:#dc3545; }
        .actions a.download-btn { background:#28a745; }
        .actions button:hover, .actions a:hover { opacity:0.9; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Rental Report</h2>

        <!-- Filter -->
        <div class="filter">
            <form method="GET">
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($start); ?>">
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($end); ?>">
                <button type="submit">Filter</button>
            </form>
        </div>

        <!-- Table -->
        <table>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Vehicle</th>
                <th>Reg No.</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Daily Rate</th>
                <th>Total Cost</th>
                <th>Status</th>
            </tr>
            <?php if($report->num_rows > 0): ?>
                <?php while($r = $report->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $r['rental_id']; ?></td>
                    <td><?php echo htmlspecialchars($r['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['make'].' '.$r['model']); ?></td>
                    <td><?php echo htmlspecialchars($r['registration_number']); ?></td>
                    <td><?php echo $r['start_date']; ?></td>
                    <td><?php echo $r['end_date']; ?></td>
                    <td>Ksh <?php echo number_format($r['daily_rate']); ?></td>
                    <td>
                        <?php 
                            $days = (strtotime($r['end_date']) - strtotime($r['start_date'])) / (60*60*24);
                            if($days < 1) $days = 1;
                            echo 'Ksh '.number_format($r['daily_rate'] * $days);
                        ?>
                    </td>
                    <td><?php echo ucfirst($r['status']); ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="9">No records found.</td></tr>
            <?php endif; ?>
        </table>

        <!-- Actions -->
        <div class="actions">
            <a href="dashboard.php" class="back-btn">⬅ Back to Dashboard</a>
            <a href="export_report.php?start_date=<?php echo $start; ?>&end_date=<?php echo $end; ?>" class="download-btn">📥 Download CSV</a>
        </div>
    </div>
</body>
</html>