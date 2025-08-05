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

if (isset($_POST['submit'])) {
    // Get form data
    $name = $_POST['product_name'];
    $sku = $_POST['sku'];
    $amount = $_POST['amount'];
    $supplier = $_POST['supplier'];
    $price = $_POST['price'];

    // Insert into database
    $insert_query = "INSERT INTO products (Name, SKU, Amount, Supplier, Price, Date_modified)
                     VALUES ('$name', '$sku', '$amount', '$supplier', '$price', NOW())";

    if ($connection->query($insert_query) === TRUE) {
        echo "<script>alert('Product added successfully'); window.location.href=window.location.href;</script>";
    } else {
        echo "Error: " . $connection->error;
    }
}

// Fetch product data
$query = "SELECT * FROM products";
$result = $connection->query($query);

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRODUCTS</title>
    <link rel="stylesheet" href="products.css">
</head>
<body>
    <header>
        <h1>STOCK-WATCH</h1>

        <div class="header-links">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="#">Products</a></li>
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
            <h1>Product Details</h1>
            <p>View and manage detailed information about your products.</p>
        </div>

        <br><br>

        <h2>Add new product</h2>
        <br>

        <form action="" method="POST" id="add-product-form">

            <div style="display: flex;">
                <div>
                    <label for="">Product Name:</label>
                    <br><br>
                    <input type="text" id="" name="product_name" required>
                </div>

                <div>
                    <label for="">SKU:</label>
                    <br><br>
                    <input type="text" id="" name="sku" required>
                </div>

                <div>
                    <label for="">Stock Amount:</label>
                    <br><br>
                    <input type="number" id="" name="amount" required>
                </div>

                <div>
                    <label for="">Supplier:</label>
                    <br><br>
                    <input type="text" id="" name="supplier" required>
                </div>

                <div>
                    <label for="">Price:</label>
                    <br><br>
                    <input type="number" id="" name="price" required>
                </div>

            
            </div>

            <br>

            <button type="submit" id="add-product-button" name="submit">Add Product</button>

        </form>

        <br><br>

        <h2>Product List</h2>

        <br><br>

        <table>
            <thead>
                <tr>
                    <th>PRODUCT NAME</th>
                    <th>SKU</th>
                    <th>STOCK AMOUNT</th>
                    <th>SUPPLIER</th>
                    <th>PRICE</th>
                    <th>DATE MODIFIED</th>
                </tr>
            </thead>

            <tbody>
                <?php
                if($result->num_rows > 0){
                    while($row = $result->fetch_assoc()){
                        echo "<tr>
                                <td>{$row['Name']}</td>
                                <td>{$row['SKU']}</td>
                                <td>{$row['Amount']}</td>
                                <td>{$row['Supplier']}</td>
                                <td>{$row['Price']}</td>
                                <td>{$row['Date_modified']}</td>
                              </tr>";
                    }
                }
                
                
                ?>
            </tbody>
        </table>
    </div>


</body>
</html>