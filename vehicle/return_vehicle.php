<?php
session_start();
include('db_connect.php');

// Redirect if user not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle vehicle return
if (isset($_POST['return'])) {
    $rental_id = $_POST['rental_id'];
    $vehicle_id = $_POST['vehicle_id'];

    // Update rental status safely
    $update_rental = $conn->prepare("UPDATE rentals SET status='completed' WHERE rental_id=?");
    $update_rental->bind_param("i", $rental_id);
    $update_rental->execute();

    // Update vehicle status safely
    $update_vehicle = $conn->prepare("UPDATE vehicles SET status='available' WHERE vehicle_id=?");
    $update_vehicle->bind_param("i", $vehicle_id);
    $update_vehicle->execute();

    if ($update_rental->affected_rows > 0 && $update_vehicle->affected_rows > 0) {
        $message = "✅ Vehicle successfully returned!";
    } else {
        $message = "❌ Error updating status: " . $conn->error;
    }

    $update_rental->close();
    $update_vehicle->close();
}

// Fetch user's active rentals
$user_id = $_SESSION['user_id'];
$query = "
    SELECT r.rental_id, r.status AS rental_status, v.vehicle_id, v.make, v.model, 
           v.registration_number, r.start_date, r.end_date
    FROM rentals r
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    WHERE r.user_id=? AND r.status='active'
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rentals = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Return Vehicle - Car Rental System</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 30px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .container {
            width: 90%;
            max-width: 800px;
            margin: 30px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .message {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            color: green;
        }
        .rental {
            border-bottom: 1px solid #ddd;
            padding: 15px 0;
        }
        .rental p {
            margin: 5px 0;
        }
        button {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background: #1e7e34;
        }
        .back {
            display: block;
            text-align: center;
            margin-top: 25px;
            background: #007bff;
            color: white;
            padding: 10px;
            border-radius: 8px;
            text-decoration: none;
        }
        .back:hover {
            background: #0056b3;
        }
        .status {
            color: #555;
            font-size: 0.95em;
        }
    </style>
</head>
<body>

    <h2>My Active Rentals 🚗</h2>

    <div class="container">
        <?php if (isset($message)) echo "<p class='message'>$message</p>"; ?>

        <?php if ($rentals->num_rows > 0) { ?>
            <?php while ($r = $rentals->fetch_assoc()) { ?>
                <div class="rental">
                    <form method="POST">
                        <input type="hidden" name="rental_id" value="<?php echo $r['rental_id']; ?>">
                        <input type="hidden" name="vehicle_id" value="<?php echo $r['vehicle_id']; ?>">
                        <p><strong>Car:</strong> <?php echo htmlspecialchars($r['make'] . ' ' . $r['model']); ?></p>
                        <p><strong>Reg No:</strong> <?php echo htmlspecialchars($r['registration_number']); ?></p>
                        <p><strong>From:</strong> <?php echo htmlspecialchars($r['start_date']); ?> &nbsp; 
                           <strong>To:</strong> <?php echo htmlspecialchars($r['end_date']); ?></p>
                        <p class="status"><strong>Status:</strong> <?php echo ucfirst($r['rental_status']); ?></p>
                        <button type="submit" name="return">Return Vehicle</button>
                    </form>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p style="text-align:center;">No active rentals found.</p>
        <?php } ?>
    </div>

    <a href="dashboard.php" class="back">⬅ Back to Dashboard</a>

</body>
</html>