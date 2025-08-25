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

if (isset($_POST['submit'])) {
    $name = mysqli_real_escape_string($connection, $_POST['product_name']);
    $sku = mysqli_real_escape_string($connection, $_POST['sku']);
    $amount = (int)$_POST['amount'];
    $supplier = mysqli_real_escape_string($connection, $_POST['supplier']);
    $price = (float)$_POST['price'];

    $check_sql = "SELECT * FROM products WHERE SKU = '$sku'";
    $check_res = mysqli_query($connection, $check_sql);

    if ($check_res && mysqli_num_rows($check_res) > 0) {
        $existing_row = mysqli_fetch_assoc($check_res);
        $new_amount = $existing_row['Amount'] + $amount;

        $update_sql = "UPDATE products
                       SET Amount = $new_amount,
                           Name = '$name',
                           Supplier = '$supplier',
                           Price = $price,
                           Date_modified = NOW()
                       WHERE SKU = '$sku'";
        mysqli_query($connection, $update_sql);
        echo "<script>alert('Stock updated successfully for SKU: $sku'); window.location.href=window.location.href;</script>";
    } else {
        $insert_sql = "INSERT INTO products (Name, SKU, Amount, Supplier, Price, Date_modified)
                       VALUES ('$name', '$sku', $amount, '$supplier', $price, NOW())";
        mysqli_query($connection, $insert_sql);
        echo "<script>alert('Product added successfully'); window.location.href=window.location.href;</script>";
    }
}

$query = "SELECT * FROM products";
$result = $connection->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | Stock-Watch</title>
    <link rel="stylesheet" href="products.css">
</head>
<body>
    <header>
        <h1 class="logo">STOCK-WATCH</h1>
        <div class="header-links">
            <ul>
                <?php if ($_SESSION['user_role'] !== 'Warehouse Staff'): ?>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="products.php" class="active">Products</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="reports.php">Reports</a></li>
                <?php endif; ?>
                <li><a href="inventory.php">Inventory</a></li>
                <li><a href="suppliers.php">Suppliers</a></li>
                <?php if ($_SESSION['user_role'] === 'Admin'): ?>
                <li><a href="register.php">User Registration</a></li>
                <?php endif; ?>
                <li><a href="logout.php"><button class="logout-button">Logout</button></a></li>
            </ul>
        </div>
    </header>

    <main class="content">
        <h2>Welcome, <?= $_SESSION['user_name'] ?> (<?= $_SESSION['user_role'] ?>)</h2>

        <?php if ($_SESSION['user_role'] !== 'Warehouse Staff'): ?>
        <section class="card">
            <h2>Add / Update Product</h2>
            <form action="" method="POST" id="add-product-form">
                <div class="form-grid">
                    <div>
                        <label>Product Name</label>
                        <input type="text" name="product_name" required>
                    </div>
                    <div>
                        <label>SKU</label>
                        <input type="text" name="sku" required>
                    </div>
                    <div>
                        <label>Stock Amount</label>
                        <input type="number" name="amount" required>
                    </div>
                    <div>
                        <label>Supplier</label>
                        <input type="text" name="supplier" required>
                    </div>
                    <div>
                        <label>Price</label>
                        <input type="number" name="price" step="0.01" required>
                    </div>
                </div>
                <button type="submit" id="add-product-button" name="submit">Save Product</button>
            </form>
        </section>

        <section class="card">
            <h2>Product List</h2>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>PRODUCT NAME</th>
                        <th>SKU</th>
                        <th>STOCK</th>
                        <th>SUPPLIER</th>
                        <th>PRICE</th>
                        <th>LAST UPDATED</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['Name'] ?></td>
                                <td><?= $row['SKU'] ?></td>
                                <td><?= $row['Amount'] ?></td>
                                <td><?= $row['Supplier'] ?></td>
                                <td>$<?= number_format($row['Price'], 2) ?></td>
                                <td><?= $row['Date_modified'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">No products found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
        <?php else: ?>
            <h2 style="color: red; text-align: center; margin-top: 50px;">
                Access Denied: This page is restricted to Admins and Managers only.
            </h2>
        <?php endif; ?>
    </main>
</body>
</html>
