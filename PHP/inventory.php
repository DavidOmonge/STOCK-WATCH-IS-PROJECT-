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

// Check connection
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
} else {
    // echo "Connected successfully";
}

// Fetch inventory data
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($connection, $_GET['search']);
    $query = "SELECT * FROM products 
              WHERE Name LIKE '%$search%' OR SKU LIKE '%$search%'";
} else {
    $query = "SELECT * FROM products";
}



$result = $connection->query($query);

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INVENTORY</title>
    <link rel="stylesheet" href="inventory.css">
</head>
<body>
    <header>
        <h1>STOCK-WATCH</h1>

        <div class="header-links">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="#">Inventory</a></li>
                <li><a href="suppliers.php">Suppliers</a></li>
                <li><a href="logout.php"><Button class="logout-button" name="logout">Logout</Button></a></li>
            </ul>
        </div>
    </header>
    <hr>

    

    <div class="content">
        <p>Welcome, <?= $_SESSION['user_name'] ?> ( <?= $_SESSION['user_role'] ?> )</p>
        <br>
    <div class="content-description">
            <h1>Inventory Details</h1>

            
    </div>

        <br><br>

        <form method="GET" action="" style="margin-bottom: 20px;">
            <input style="width: 30%; padding: 10px; font-size: 16px;" type="text" name="search" placeholder="Search product name or SKU..." value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>" >
            <button type="submit" style="padding: 10px 15px; font-size: 16px;">Search</button>
            <a href="inventory.php" style="margin-left: 10px; font-size: 20px;">Clear search</a>
        </form>

        <div class="table-container">
            <table border="0">
            <thead>
                <tr>
                    <th>PRODUCT NAME</th>
                    <th>SKU</th>
                    <th>CURRENT STOCK</th>
                    <th>DATE MODIFIED</th>
                </tr>
                
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['Name']}</td>
                                <td>{$row['SKU']}</td>
                                <td>{$row['Amount']}</td>
                                <td>{$row['Date_modified']}</td>
                              </tr>"; 
                    }
                } else {
                    echo "<tr><td colspan='4'>No products found</td></tr>";
                }
                ?>
                
            </tbody>

        </table>
        </div>

        
    </div>
</body>
</html>