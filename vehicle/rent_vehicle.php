<?php
session_start();
include('db_connect.php');

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle booking form submission
if (isset($_POST['rent'])) {
    $vehicle_id = $_POST['vehicle_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $user_id = $_SESSION['user_id'];

    $today = date('Y-m-d');

    if ($start_date < $today || $end_date < $today) {
        $message = "❌ You cannot book for past dates.";
    } elseif ($start_date > $end_date) {
        $message = "❌ End date must be after start date.";
    } else {
        $conflict_query = $conn->query("
            SELECT * FROM rentals
            WHERE vehicle_id='$vehicle_id'
            AND status IN ('active', 'pending')
            AND NOT (end_date < '$start_date' OR start_date > '$end_date')
        ");

        if ($conflict_query->num_rows > 0) {
            $message = "❌ This vehicle is already booked during your selected dates.";
        } else {
            $vehicle_query = $conn->query("SELECT daily_rate FROM vehicles WHERE vehicle_id='$vehicle_id'");
            $vehicle = $vehicle_query->fetch_assoc();
            $daily_rate = $vehicle['daily_rate'];

            $days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
            if ($days < 1) $days = 1;

            $total_cost = $daily_rate * $days;

            $insert = $conn->query("INSERT INTO rentals (user_id, vehicle_id, start_date, end_date, status, created_at)
                                    VALUES ('$user_id', '$vehicle_id', '$start_date', '$end_date', 'pending', NOW())");

            if ($insert) {
                $message = "✅ Car successfully booked! Total cost: Ksh $total_cost";
            } else {
                $message = "❌ Error: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Rent Vehicle - Car Rental System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            color: #333;
            background-image: url('cars.jpg'); /* same dashboard background */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(245, 246, 248, 0.95);
            z-index: -1;
        }

        h2 {
            text-align: center;
            color: #007bff; /* blue like dashboard */
            margin-top: 30px;
            margin-bottom: 20px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        }

        .container {
            max-width: 500px;
            background: #fff;
            margin: 0 auto 30px auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        select, input[type="date"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        button {
            background: #007bff;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            width: 100%;
        }

        button:hover {
            background: #0056b3;
        }

        .message {
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
            color: green;
        }

        .error {
            color: red;
        }

        .back {
            display: block;
            text-align: center;
            margin: 15px auto;
            background: #28a745;
            color: #fff;
            padding: 10px 20px;
            width: 180px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }

        .back:hover {
            background: #1e7e34;
        }
    </style>
</head>
<body>

<h2>Rent a Vehicle 🚗</h2>

<div class="container">
    <?php if(isset($message)) echo "<p class='message'>" . htmlspecialchars($message) . "</p>"; ?>

    <form method="POST">
        <label>Select Vehicle:</label>
        <select name="vehicle_id" required>
            <option value="">-- Choose Vehicle --</option>
            <?php
            $vehicles = $conn->query("SELECT * FROM vehicles WHERE status='available'");
            while ($v = $vehicles->fetch_assoc()) {
                echo "<option value='{$v['vehicle_id']}'>{$v['make']} {$v['model']} ({$v['registration_number']}) - Ksh {$v['daily_rate']}/day</option>";
            }
            ?>
        </select>

        <label>Start Date:</label>
        <input type="date" name="start_date" required min="<?php echo date('Y-m-d'); ?>">

        <label>End Date:</label>
        <input type="date" name="end_date" required min="<?php echo date('Y-m-d'); ?>">

        <button type="submit" name="rent">Confirm Booking</button>
    </form>
</div>

<a href="dashboard.php" class="back">⬅ Back to Dashboard</a>

</body>
</html>