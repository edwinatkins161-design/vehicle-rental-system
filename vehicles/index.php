<?php
session_start();
include('db_connect.php');

if (isset($_POST['login'])) {
    $email = strtolower(trim($_POST['email'])); // normalize email
    $password = $_POST['password'];

    // Prepare statement
    $stmt = $conn->prepare("SELECT user_id, full_name, password_hash, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password_hash'])) {
            // Login successful
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            switch ($user['role']) {
                case 'admin': header("Location: admin_dashboard.php"); break;
                case 'staff': header("Location: staff_dashboard.php"); break;
                default: header("Location: dashboard.php");
            }
            exit();
        } else {
            $error = "❌ Invalid password!";
        }
    } else {
        $error = "❌ No account found with that email.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Car Rental System</title>
    <style>
        /* Full-page background with image */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            height: 100vh;
            background: url('prado.jpeg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        /* Dark overlay to improve readability */
        body::before {
            content: "";
            position: absolute;
            top:0; left:0; right:0; bottom:0;
            background: rgba(0,0,0,0.4);
            z-index: 0;
        }

        /* Container sits on top of overlay */
        .container {
            position: relative;
            z-index: 1;
            background: rgba(255,255,255,0.95);
            padding: 30px;
            border-radius: 12px;
            width: 350px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        h1 {
            text-align: center;
            color: #0047b3;
            margin-bottom: 20px;
            font-size: 28px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.2);
        }

        input, button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #bcd0ff;
            font-size: 14px;
        }

        button {
            background: #0047b3;
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            background: #003b99;
        }

        .error {
            color: red;
            margin-bottom: 10px;
        }

        a {
            color: #0047b3;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Jambo Car Rentals</h1>
        <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>