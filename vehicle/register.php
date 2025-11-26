<?php
session_start();
include('db_connect.php');

if (isset($_POST['register'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $role = $_POST['role']; // User-chosen role

    // Default role if empty
    if(empty($role)){
        $role = 'client';
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $checkEmail = $stmt->get_result();

    if ($checkEmail->num_rows > 0) {
        $error = "❌ Email already registered.";
    } else {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users(full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $full_name, $email, $phone, $password_hash, $role);
        if ($stmt->execute()) {
            $success = "✅ Registration successful! Role: $role. You can now <a href='login.php'>login</a>.";
        } else {
            $error = "❌ Registration failed: " . $conn->error;
        }
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Car Rental System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin:0;
            height:100vh;
            background: url('prado.jpeg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        /* Dark overlay */
        body::before {
            content: "";
            position: absolute;
            top:0; left:0; right:0; bottom:0;
            background: rgba(0,0,0,0.4);
            z-index: 0;
        }

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
            font-size: 28px;
            color: #0047b3;
            margin-bottom: 20px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.2);
        }

        input, select, button {
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

        .success { color: green; margin-bottom: 10px; }
        .error { color: red; margin-bottom: 10px; }

        a { color: #0047b3; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Jambo Car Rentals</h1>

        <?php 
        if(isset($error)) echo "<p class='error'>$error</p>";
        if(isset($success)) echo "<p class='success'>$success</p>";
        ?>

        <form method="POST">
            <input type="text" name="full_name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="phone" placeholder="Phone">
            <input type="password" name="password" placeholder="Password" required>
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="client">Client</option>
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
            </select>
            <button type="submit" name="register">Register</button>
        </form>

        <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
</body>
</html>