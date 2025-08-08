<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect if not logged in
    exit();

}

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'stockwatch';

$connection = mysqli_connect($host, $username, $password, $database);

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
                <?php endif; ?>
                <li><a href="inventory.php">Inventory</a></li>
                <li><a href="suppliers.php">Suppliers</a></li>
                <?php if ($_SESSION['user_role'] !== 'Warehouse Staff'): ?>
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
            <br><br>

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


    
</body>
</html>