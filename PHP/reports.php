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

// KPIs
$kpis = [
    'distinct_products' => 0,
    'total_units' => 0,
    'low_stock' => 0,
    'inventory_value' => 0.0,
    'orders_count' => 0,
    'ordered_qty' => 0,
];

if ($res = mysqli_query($connection, "SELECT COUNT(*) AS c FROM products")) {
    $kpis['distinct_products'] = (int)mysqli_fetch_assoc($res)['c'];
}
if ($res = mysqli_query($connection, "SELECT COALESCE(SUM(Amount),0) AS s FROM products")) {
    $kpis['total_units'] = (int)mysqli_fetch_assoc($res)['s'];
}
if ($res = mysqli_query($connection, "SELECT COUNT(*) AS c FROM products WHERE Amount < 10")) {
    $kpis['low_stock'] = (int)mysqli_fetch_assoc($res)['c'];
}
if ($res = mysqli_query($connection, "SELECT COALESCE(SUM(Amount * Price),0) AS v FROM products")) {
    $kpis['inventory_value'] = (float)mysqli_fetch_assoc($res)['v'];
}
if ($res = mysqli_query($connection, "SELECT COUNT(*) AS c FROM orders")) {
    $kpis['orders_count'] = (int)mysqli_fetch_assoc($res)['c'];
}
if ($res = mysqli_query($connection, "SELECT COALESCE(SUM(quantity),0) AS q FROM orders")) {
    $kpis['ordered_qty'] = (int)mysqli_fetch_assoc($res)['q'];
}

// Recent orders (limit 10) - avoid columns that may not exist in older schema
$recent_orders = mysqli_query($connection, "SELECT order_id, SKU, quantity, email, order_date FROM orders ORDER BY order_date DESC LIMIT 10");

// Low stock list (top 20)
$low_stock_list = mysqli_query($connection, "SELECT Name, SKU, Amount, Supplier, Price, Date_modified FROM products WHERE Amount < 10 ORDER BY Amount ASC, Date_modified DESC LIMIT 20");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REPORTS</title>
    <link rel="stylesheet" href="report.css">
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
    <p class="welcome-msg">
        Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?> 
        <span class="role">(<?= htmlspecialchars($_SESSION['user_role']) ?>)</span>
    </p>

    <?php if ($_SESSION['user_role'] !== 'Warehouse Staff'): ?>
    <div class="content-header">
        <h1>Reports</h1>
        <p>Overview of inventory and purchase order activity</p>
        <div class="export-buttons">
            <form method="post" action="export_report.php">
                <button type="submit">Export All</button>
            </form>
            <form method="post" action="export_report.php">
                <input type="hidden" name="which" value="low_stock" />
                <button type="submit">Export Low Stock</button>
            </form>
            <form method="post" action="export_report.php">
                <input type="hidden" name="which" value="recent_orders" />
                <button type="submit">Export Orders</button>
            </form>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-section">
        <div class="kpi-card">
            <h2>Distinct Products</h2>
            <p><?= number_format($kpis['distinct_products']) ?></p>
        </div>
        <div class="kpi-card">
            <h2>Total Units</h2>
            <p><?= number_format($kpis['total_units']) ?></p>
        </div>
        <div class="kpi-card warning">
            <h2>Low Stock Items</h2>
            <p><?= number_format($kpis['low_stock']) ?></p>
        </div>
        <div class="kpi-card">
            <h2>Inventory Value</h2>
            <p>$<?= number_format($kpis['inventory_value'], 2) ?></p>
        </div>
        <div class="kpi-card">
            <h2>Total Orders</h2>
            <p><?= number_format($kpis['orders_count']) ?></p>
        </div>
        <div class="kpi-card">
            <h2>Qty Ordered</h2>
            <p><?= number_format($kpis['ordered_qty']) ?></p>
        </div>
    </div>

    <!-- Low Stock Table -->
    <div class="data-section">
        <h2>Low Stock (Top 20)</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Amount</th>
                        <th>Supplier</th>
                        <th>Price</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($low_stock_list && mysqli_num_rows($low_stock_list) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($low_stock_list)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Name']) ?></td>
                            <td><?= htmlspecialchars($row['SKU']) ?></td>
                            <td><?= (int)$row['Amount'] ?></td>
                            <td><?= htmlspecialchars($row['Supplier']) ?></td>
                            <td><?= htmlspecialchars($row['Price']) ?></td>
                            <td><?= htmlspecialchars($row['Date_modified']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No low stock items.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="data-section">
        <h2>Recent Orders (Last 10)</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>SKU</th>
                        <th>Quantity</th>
                        <th>Email</th>
                        <th>Order Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_orders && mysqli_num_rows($recent_orders) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($recent_orders)): ?>
                        <tr>
                            <td><?= (int)$row['order_id'] ?></td>
                            <td><?= htmlspecialchars($row['SKU']) ?></td>
                            <td><?= (int)$row['quantity'] ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['order_date']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No orders found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
        <h2 class="denied">Access Denied: Only Admins/Managers allowed.</h2>
    <?php endif; ?>
</div>

</body>
</html>
