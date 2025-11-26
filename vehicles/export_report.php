<?php
include('db_connect.php');

// Get filters
$start = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// File name
$filename = "rental_report_" . date('Y-m-d') . ".csv";

// Headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Open output stream
$output = fopen("php://output", "w");

// CSV Column Headers
fputcsv($output, array(
    'Rental ID',
    'Customer',
    'Vehicle',
    'Reg Number',
    'Start Date',
    'End Date',
    'Daily Rate',
    'Total Cost',
    'Status'
));

// Build main query
$query = "
    SELECT r.rental_id, u.full_name, v.make, v.model, v.registration_number, 
           v.daily_rate, r.start_date, r.end_date, r.status
    FROM rentals r
    JOIN users u ON r.user_id = u.user_id
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    WHERE 1
";

if ($start && $end) {
    $query .= " AND r.start_date >= '$start' AND r.end_date <= '$end'";
}

$query .= " ORDER BY r.start_date DESC";

$result = $conn->query($query);

// Push data into CSV
while ($row = $result->fetch_assoc()) {

    // Calculate total cost
    $days = (strtotime($row['end_date']) - strtotime($row['start_date'])) / (60*60*24);
    if ($days < 1) $days = 1;
    $total_cost = $row['daily_rate'] * $days;

    fputcsv($output, array(
        $row['rental_id'],
        $row['full_name'],
        $row['make'] . ' ' . $row['model'],
        $row['registration_number'],
        $row['start_date'],
        $row['end_date'],
        $row['daily_rate'],
        $total_cost,
        $row['status']
    ));
}

fclose($output);
exit();
?>