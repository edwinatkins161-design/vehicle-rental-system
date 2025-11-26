<?php
session_start();
include('db_connect.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if rental_id is passed
if (!isset($_GET['rental_id'])) {
    die("Rental ID missing.");
}

$rental_id = $_GET['rental_id'];

// Fetch rental details with vehicle info
$rental = $conn->query("
    SELECT r.*, v.make, v.model, v.daily_rate, v.vehicle_id
    FROM rentals r
    JOIN vehicles v ON r.vehicle_id = v.vehicle_id
    WHERE r.rental_id = '$rental_id'
")->fetch_assoc();

if (!$rental) {
    die("Rental not found.");
}

// Calculate total cost
$start = new DateTime($rental['start_date']);
$end = new DateTime($rental['end_date']);
$days = $start->diff($end)->days;
if ($days == 0) $days = 1; // Minimum 1 day

$total_amount = $rental['daily_rate'] * $days;

// Handle form submission
if (isset($_POST['pay'])) {
    $method = $_POST['method'];

    // Record payment
    $insert = $conn->query("
        INSERT INTO payments (rental_id, amount, method, status)
        VALUES ('$rental_id', '$total_amount', '$method', 'paid')
    ");

    if ($insert) {
        // ✅ Update rental to 'active' after payment
        $conn->query("UPDATE rentals SET status='active' WHERE rental_id='$rental_id'");

        // ✅ Update vehicle to 'rented'
        $conn->query("
            UPDATE vehicles 
            SET status='rented' 
            WHERE vehicle_id='{$rental['vehicle_id']}'
        ");

        $message = "✅ Payment successful! Vehicle is now marked as rented.";
    } else {
        $message = "❌ Payment failed: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Process Payment - Car Rental System</title>
    <style>
        body {
            font-family: Arial;
            background: #f5f6f8;
            margin: 40px;
        }
        .container {
            width: 90%;
            max-width: 600px;
            background: white;
            padding: 25px;
            margin: 0 auto;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        p {
            font-size: 15px;
        }
        select, button {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        button {
            background: #28a745;
            color: white;
            border: none;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background: #218838;
        }
        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
            background: #007bff;
            color: white;
            padding: 10px;
            border-radius: 6px;
            text-decoration: none;
        }
        .back:hover {
            background: #0056b3;
        }
        .message {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            color: green;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Process Payment 💳</h2>

    <?php if (isset($message)) echo "<p class='message'>$message</p>"; ?>

    <p><strong>Car:</strong> <?php echo htmlspecialchars($rental['make'].' '.$rental['model']); ?></p>
    <p><strong>Rental Period:</strong> <?php echo htmlspecialchars($rental['start_date']); ?> → <?php echo htmlspecialchars($rental['end_date']); ?></p>
    <p><strong>Days:</strong> <?php echo $days; ?></p>
    <p><strong>Total Amount:</strong> Ksh <?php echo number_format($total_amount, 2); ?></p>

    <?php if (!isset($message)) { ?>
    <form method="POST">
        <label><strong>Payment Method:</strong></label>
        <select name="method" required>
            <option value="">--Select Method--</option>
            <option value="mpesa">MPESA</option>
            <option value="cash">Cash</option>
            <option value="card">Card</option>
        </select>
        <button type="submit" name="pay">Confirm Payment</button>
    </form>
    <?php } ?>

    <a href="dashboard.php" class="back">⬅ Back to Dashboard</a>
</div>

</body>
</html>