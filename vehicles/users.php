<?php
session_start();
include('db_connect.php');

// Only allow admin
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

// --- Search & Filter ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';

$sql = "SELECT * FROM users";
$conditions = [];

if(!empty($search)){
    $conditions[] = "(full_name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";
}
if(!empty($role_filter)){
    $conditions[] = "role='$role_filter'";
}
if(!empty($conditions)){
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY user_id DESC";

$users_result = $conn->query($sql);

// --- Handle Edit User ---
if(isset($_POST['edit_user'])){
    $user_id = $_POST['user_id'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, role=? WHERE user_id=?");
    $stmt->bind_param("ssssi", $full_name, $email, $phone, $role, $user_id);
    $stmt->execute();
    echo "<script>alert('User updated successfully!'); window.location='users.php';</script>";
}

// --- Handle Delete User ---
if(isset($_GET['delete_user'])){
    $user_id = $_GET['delete_user'];
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    echo "<script>alert('User deleted successfully!'); window.location='users.php';</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users - Admin</title>
    <style>
        body { 
            font-family: Arial; 
            margin:0; 
            background: url('driver.jpg') no-repeat center center fixed; 
            background-size: cover; 
            color:#fff;
        }
        h1 { 
            text-align:center; 
            color:#ffeb3b; 
            margin-top:20px; 
            text-shadow: 2px 2px 5px rgba(0,0,0,0.7);
        }
        .search-filter { text-align:center; margin:20px; }
        .search-filter input, .search-filter select { padding:8px; margin:0 5px; }
        table { 
            width:90%; 
            margin:20px auto; 
            border-collapse:collapse; 
            background: rgba(0,0,0,0.6); 
            border-radius:10px; 
            overflow:hidden;
        }
        th, td { padding:8px; text-align:center; color:#fff; }
        th { background: rgba(0,123,255,0.8); }
        .edit-btn { background:#007bff; color:#fff; padding:5px 10px; border-radius:5px; border:none; cursor:pointer; }
        .delete-btn { background:#dc3545; color:#fff; padding:5px 10px; border-radius:5px; text-decoration:none; }
        .back-btn { background:#28a745; color:#fff; padding:8px 12px; border-radius:5px; margin-top:20px; cursor:pointer; display:block; margin:auto; border:none; }
        input, select { display:block; margin:10px 0; padding:8px; width:100%; }
        #editUserForm { display:none; width:400px; margin:auto; background: rgba(0,0,0,0.7); padding:20px; border-radius:10px; }
        #editUserForm input, #editUserForm select, #editUserForm button { color:#000; }
        #editUserForm h3 { color:#ffeb3b; text-align:center; }
        button, input, select { font-size:14px; }
    </style>
</head>
<body>

<h1>Manage Users</h1>

<!-- Search & Filter Form -->
<div class="search-filter">
    <form method="GET">
        <input type="text" name="search" placeholder="Search by name, email, or phone" value="<?php echo htmlspecialchars($search); ?>">
        <select name="role">
            <option value="">All Roles</option>
            <option value="admin" <?php if($role_filter=='admin') echo 'selected'; ?>>Admin</option>
            <option value="staff" <?php if($role_filter=='staff') echo 'selected'; ?>>Staff</option>
            <option value="client" <?php if($role_filter=='client') echo 'selected'; ?>>Client</option>
        </select>
        <button type="submit" style="padding:8px 12px; background:#007bff; color:#fff; border:none; border-radius:5px;">Search</button>
    </form>
</div>

<!-- Users Table -->
<table>
    <tr>
        <th>ID</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Role</th>
        <th>Actions</th>
    </tr>
    <?php while($user = $users_result->fetch_assoc()){ ?>
    <tr>
        <td><?php echo $user['user_id']; ?></td>
        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
        <td><?php echo htmlspecialchars($user['email']); ?></td>
        <td><?php echo htmlspecialchars($user['phone']); ?></td>
        <td><?php echo htmlspecialchars($user['role']); ?></td>
        <td>
            <button class="edit-btn" onclick="editUser(
                <?php echo $user['user_id']; ?>,
                '<?php echo addslashes($user['full_name']); ?>',
                '<?php echo addslashes($user['email']); ?>',
                '<?php echo addslashes($user['phone']); ?>',
                '<?php echo $user['role']; ?>'
            )">Edit</button>
            <a href="?delete_user=<?php echo $user['user_id']; ?>" class="delete-btn" onclick="return confirm('Delete this user?')">Delete</a>
        </td>
    </tr>
    <?php } ?>
</table>

<!-- Edit User Form -->
<div id="editUserForm">
    <h3>Edit User</h3>
    <form method="POST">
        <input type="hidden" id="edit_user_id" name="user_id">
        <input type="text" id="edit_full_name" name="full_name" placeholder="Full Name" required>
        <input type="email" id="edit_email" name="email" placeholder="Email" required>
        <input type="text" id="edit_phone" name="phone" placeholder="Phone" required>
        <select id="edit_role" name="role" required>
            <option value="admin">Admin</option>
            <option value="staff">Staff</option>
            <option value="client">Client</option>
        </select>
        <button type="submit" name="edit_user" style="background:#007bff; color:#fff;">Update User</button>
        <button type="button" style="background:#dc3545; color:#fff;" onclick="document.getElementById('editUserForm').style.display='none'">Cancel</button>
    </form>
</div>

<!-- Back to Dashboard Button -->
<button class="back-btn" onclick="window.location.href='admin_dashboard.php';">Back to Dashboard</button>

<script>
function editUser(id, fullName, email, phone, role){
    document.getElementById('editUserForm').style.display='block';
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_full_name').value = fullName;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_phone').value = phone;
    document.getElementById('edit_role').value = role;
}
</script>

</body>
</html>