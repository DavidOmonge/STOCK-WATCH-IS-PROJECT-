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

// Connect to database
$connection = mysqli_connect($host, $username, $password, $database);

if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Total number of products (rows)
$total_query = "SELECT SUM(Amount) FROM products";
$total_result = mysqli_query($connection, $total_query);
$total_products = 0;
if ($row = mysqli_fetch_assoc($total_result)) {
    $total_products = $row['SUM(Amount)'];
}

$recent_query = "SELECT * FROM products WHERE Date_modified >= NOW() - INTERVAL 3 HOUR ORDER BY Date_modified DESC";
$recent_result = mysqli_query($connection, $recent_query);


//Low stock alert query
$low_stock_query = "SELECT * FROM products WHERE Amount < 10";
$low_stock_result = mysqli_query($connection, $low_stock_query);
$low_stock = 0;
if ($low_stock_result) {
    $low_stock = mysqli_num_rows($low_stock_result);
} else {
    $low_stock = 0; // If the query fails, set low stock to 0
}


//New orders query (assuming new orders are products added in the last 24 hours)
$new_orders_query = "SELECT * FROM products WHERE Date_modified >= NOW() - INTERVAL 3 HOUR";
$new_orders_result = mysqli_query($connection, $new_orders_query);
$new_orders = 0;
if ($new_orders_result) {
    $new_orders = mysqli_num_rows($new_orders_result);
} else {
    $new_orders = 0; // If the query fails, set new orders to 0
}


?>







<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DASHBOARD</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <header>
        <h1>STOCK-WATCH</h1>

        <div class="header-links">
            <ul>
                <li><a href="#">Dashboard</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="inventory.php">Inventory</a></li>
                <li><a href="suppliers.php">Suppliers</a></li>
                <li><a href="logout.php"><Button class="logout-button" name="logout">Logout</Button></a></li>
            </ul>
        </div>
    </header>
    <hr>

    

    <div class="content">
        <p>Welcome, <?= $_SESSION['user_name'] ?> ( <?= $_SESSION['user_role'] ?>)</p>
        <br>
        <div class="content-description">
            <h1>Dashboard</h1>
            <p>Overview of your inventory status and recent activity.</p>
        </div>

        <br><br>

        <div class="content-cards">

            <div class="card">
                <h2>Total Products</h2>
                <br>
                <p><?= $total_products ?></p>
            </div>

            <div class="card">
                <h2>Low Stock Alerts</h2>
                <br>
                <p><?= $low_stock ?> products low on stock</p>
            </div>

            <div class="card">
                <h2>New Orders</h2>
                <br>
                <p><?= $new_orders ?></p>
            </div>

        </div>

        <br><br>

    <div class="low-stock">
    <h2>Low Stock Products (Less than 10 in stock)</h2>
    <br>
    <div class="table-container">
        <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th>SKU</th>
                <th>Stock Amount</th>
                <th>Supplier</th>
                <th>Price</th>
                <th>Date Modified</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (isset($low_stock_result) && mysqli_num_rows($low_stock_result) > 0) {
                while ($row = mysqli_fetch_assoc($low_stock_result)) {
                    echo "<tr>
                            <td>{$row['Name']}</td>
                            <td>{$row['SKU']}</td>
                            <td>{$row['Amount']}</td>
                            <td>{$row['Supplier']}</td>
                            <td>{$row['Price']}</td>
                            <td>{$row['Date_modified']}</td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No low stock items found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
    </div>
</div>

<br><br>

    <div class="recent">
    <h2>Recent Activity ( Last 1 HOUR )</h2>
    <br>
    <div class="table-container">
        <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th>SKU</th>
                <th>Stock Amount</th>
                <th>Supplier</th>
                <th>Price</th>
                <th>Date Modified</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (isset($recent_result) && mysqli_num_rows($recent_result) > 0) {
                while ($row = mysqli_fetch_assoc($recent_result)) {
                    echo "<tr>
                            <td>{$row['Name']}</td>
                            <td>{$row['SKU']}</td>
                            <td>{$row['Amount']}</td>
                            <td>{$row['Supplier']}</td>
                            <td>{$row['Price']}</td>
                            <td>{$row['Date_modified']}</td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No recent activity in the past 1 HOUR.</td></tr>";
            }
            ?>
        </tbody>
    </table>
    </div>
</div>





</div>


</body>
</html>