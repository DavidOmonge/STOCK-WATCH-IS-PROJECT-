<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'stockwatch';

$connection = mysqli_connect($host, $username, $password, $database);
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($connection, $_POST['username']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password_raw = $_POST['password'];
    $role = mysqli_real_escape_string($connection, $_POST['role']);

    

    // Check if email already exists
    $check_query = "SELECT * FROM users WHERE email = '$email'";
    $check_result = mysqli_query($connection, $check_query);
    if (mysqli_num_rows($check_result) > 0) {
        echo "<script>alert('A user with this email already exists.');</script>";
    } else {
        // Insert the user into database
        $insert_query = "INSERT INTO users (name, email, password, role) 
                         VALUES ('$name', '$email', '$password_raw', '$role')";

        if (mysqli_query($connection, $insert_query)) {
            echo "<script>alert('User registered successfully.'); window.location.href='register.php';</script>";
        } else {
            echo "<script>alert('Error registering user: " . mysqli_error($connection) . "');</script>";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USER REGISTRATION</title>
    <link rel="stylesheet" href="register.css">
</head>
<body>

    <header>
        <h1>STOCK-WATCH</h1>

        <div class="header-links">
            <ul>
                <?php if ($_SESSION['user_role'] !== 'Warehouse Staff'): ?>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="reports.php">Reports</a></li>
                <?php endif; ?>
                <li><a href="inventory.php">Inventory</a></li>
                <li><a href="suppliers.php">Suppliers</a></li>
                <?php if ($_SESSION['user_role'] === 'Admin'): ?>
                <li><a href="register.php">User registration</a></li>
                <?php endif; ?>
                <li><a href="logout.php"><Button class="logout-button" name="logout">Logout</Button></a></li>
            </ul>
        </div>
    </header>
    <hr>

    <div class="content">
        <h1>Register New Users</h1>
        <br><br>

        <form action="" method="post">
            <label for="username">Full Name: </label>   
            <input type="text" id="username" name="username" placeholder="Enter full name" required>
            <br><br>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" placeholder="Enter email" required>
            <br><br>

            <label for="password">Password: </label>
            <input type="password" id="password" name="password" placeholder="Enter password" required>
            <br>
            <button type="button" onclick="togglePassword()" style="position: absolute; left: -21.5%; width:13%; ">Click to show password</button>
            <br><br><br>

            <label for="confirm_password">Role: </label>
            <select id="role" name="role" required>
                <option value="" disabled selected>Select role</option>
                <option value="admin">Admin</option>
                <option value="Inventory Manager">Inventory Manager</option>
                <option value="Warehouse staff">Warehouse staff</option>
            </select>
            <br><br>
            <button type="submit">Register</button>
        </form>
    </div>


    
<script>
function togglePassword() {
    const passwordInput = document.getElementById("password");
    const toggleBtn = document.querySelector(".toggle-btn");

    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleBtn.textContent = "Hide Password";
    } else {
        passwordInput.type = "password";
        toggleBtn.textContent = "Show Password";
    }
}
</script>


</body>
</html>