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
    // echo ("Connected successfully");
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

// Create stock_out table for logging goods leaving inventory
$create_stock_out_sql = "CREATE TABLE IF NOT EXISTS stock_out (
    id INT AUTO_INCREMENT PRIMARY KEY,
    SKU VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    note VARCHAR(255) DEFAULT NULL,
    user_id INT DEFAULT NULL,
    user_name VARCHAR(255) DEFAULT NULL,
    out_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";
if (!mysqli_query($connection, $create_stock_out_sql)) {
    die("Error creating stock_out table: " . mysqli_error($connection));
}

// Handle goods leaving inventory (stock out)
if (isset($_POST['log_goods_out'])) {
    $sku_out = mysqli_real_escape_string($connection, $_POST['sku_out'] ?? '');
    $qty_out = isset($_POST['quantity_out']) ? (int)$_POST['quantity_out'] : 0;
    $note_out = mysqli_real_escape_string($connection, $_POST['note_out'] ?? '');

    if ($qty_out > 0 && $sku_out !== '') {
        mysqli_begin_transaction($connection);

        // Lock the product row to prevent race conditions
        $stmt = mysqli_prepare($connection, "SELECT Amount FROM products WHERE SKU = ? FOR UPDATE");
        mysqli_stmt_bind_param($stmt, "s", $sku_out);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($res)) {
            $current = (int)$row['Amount'];
            if ($current >= $qty_out) {
                // Decrease stock
                $stmtU = mysqli_prepare($connection, "UPDATE products SET Amount = Amount - ?, Date_modified = NOW() WHERE SKU = ?");
                mysqli_stmt_bind_param($stmtU, "is", $qty_out, $sku_out);
                mysqli_stmt_execute($stmtU);

                // Insert stock out log
                $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
                $userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : null;
                $stmtI = mysqli_prepare($connection, "INSERT INTO stock_out (SKU, quantity, note, user_id, user_name) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmtI, "sisis", $sku_out, $qty_out, $note_out, $userId, $userName);
                mysqli_stmt_execute($stmtI);

                mysqli_commit($connection);
                echo "<script>alert('Goods out logged and inventory updated.'); window.location.href=window.location.href;</script>";
            } else {
                mysqli_rollback($connection);
                echo "<script>alert('Insufficient stock for the requested quantity.');</script>";
            }
        } else {
            mysqli_rollback($connection);
            echo "<script>alert('SKU not found.');</script>";
        }
    } else {
        echo "<script>alert('Please provide a valid SKU and quantity.');</script>";
    }
}

// Fetch products for the stock-out dropdown
$products_res_out = mysqli_query($connection, "SELECT Name, SKU, Amount FROM products WHERE Amount > 0 ORDER BY Name ASC");

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INVENTORY</title>
    <link rel="stylesheet" href="inventory.css">
    <style>
      /* Match orders form layout */
      #goods-out-form .form-row {
        display: flex;
        gap: 20px;
        align-items: flex-end;
      }
      #goods-out-form select,
      #goods-out-form input {
        padding: 8px;
        border-radius: 5px;
        border: 1px solid #ccc;
        min-width: 200px;
      }
      #goods-out-form button {
        padding: 10px 20px;
        border-radius: 10px;
        border: none;
        background-color: #007BFF;
        color: white;
        cursor: pointer;
      }
      #goods-out-form button:hover {
        background-color: #0056b3;
      }
    </style>
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
                <li><a href="#">Inventory</a></li>
                <?php if ($_SESSION['user_role'] !== 'Warehouse Staff'): ?>
                    <li><a href="suppliers.php">Suppliers</a></li>
                <?php endif; ?>
                <?php if ($_SESSION['user_role'] === 'Admin'): ?>
                <li><a href="register.php">User registration</a></li>
                <?php endif; ?>
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

        
        <div style="margin: 24px 0;">
            <h2>Log Goods Leaving Inventory</h2>
            <br>
            <p>Record items leaving stock by SKU.</p>
            <form method="POST" action="" id="goods-out-form">
                <div class="form-row">
                    <div>
                        <label for="sku_out" hidden>Product (by SKU):</label><br><br>
                        <select id="sku_out" name="sku_out" required>
                            <option value="" disabled selected>-- Select a Product --</option>
                            <?php if ($products_res_out && mysqli_num_rows($products_res_out) > 0): ?>
                                <?php while($po = mysqli_fetch_assoc($products_res_out)): ?>
                                    <option value="<?= htmlspecialchars($po['SKU']) ?>">
                                        <?= htmlspecialchars($po['Name']) ?> (SKU: <?= htmlspecialchars($po['SKU']) ?>) - Stock: <?= (int)$po['Amount'] ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label for="quantity_out">Quantity:</label><br><br>
                        <input type="number" id="quantity_out" name="quantity_out" min="1" required>
                    </div>
                    <div>
                        <label for="note_out">Note (optional):</label><br><br>
                        <input type="text" id="note_out" name="note_out" placeholder="e.g., customer name or reason">
                    </div>
                </div>
                <br>
                <button type="submit" name="log_goods_out">Log Goods Out</button>
            </form>
        </div>
        

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