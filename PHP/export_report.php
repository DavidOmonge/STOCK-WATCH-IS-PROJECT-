<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Warehouse Staff') {
    http_response_code(403);
    echo 'Access Denied: This export is restricted to Admins and Managers only.';
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
mysqli_set_charset($connection, 'utf8mb4');

// Determine which export to generate
$which = isset($_REQUEST['which']) ? (string)$_REQUEST['which'] : 'all';
$ts = date('Ymd_His');
// If triggered from the dashboard without an explicit selector, export products only
if ($which === 'all') {
    $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    if (stripos($ref, 'dashboard.php') !== false) {
        $which = 'products';
    }
}

switch ($which) {
    case 'low_stock':
        $filename = "stockwatch_low_stock_{$ts}.xls";
        break;
    case 'recent_orders':
        $filename = "stockwatch_recent_orders_{$ts}.xls";
        break;
    case 'products':
        $filename = "stockwatch_products_{$ts}.xls";
        break;
    default:
        $filename = "stockwatch_report_{$ts}.xls";
}

// Output headers for Excel (HTML table format)
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=" . $filename);
header("Pragma: no-cache");
header("Expires: 0");

// Helper for safe output
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Branch by export type
if ($which === 'low_stock') {
    // Low stock list to match reports page (Top 20)
    $low_stock_res = mysqli_query($connection, "SELECT Name, SKU, Amount, Supplier, Price, Date_modified FROM products WHERE Amount < 10 ORDER BY Amount ASC, Date_modified DESC LIMIT 20");
    ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Low Stock Export</title>
</head>
<body>
<h3>Low Stock Items (Top 20, Amount < 10)</h3>
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr style="background-color:#ffe6e6; font-weight:bold;">
            <th>Product</th>
            <th>SKU</th>
            <th>Amount</th>
            <th>Supplier</th>
            <th>Price</th>
            <th>Last Updated</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($low_stock_res && mysqli_num_rows($low_stock_res) > 0): ?>
            <?php while ($r = mysqli_fetch_assoc($low_stock_res)): ?>
            <tr>
                <td><?php echo h($r['Name']); ?></td>
                <td><?php echo h($r['SKU']); ?></td>
                <td><?php echo (int)$r['Amount']; ?></td>
                <td><?php echo h($r['Supplier']); ?></td>
                <td><?php echo h($r['Price']); ?></td>
                <td><?php echo h($r['Date_modified']); ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No low stock items.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</body>
</html>
<?php
    exit();
}

if ($which === 'recent_orders') {
    // Recent orders to match reports page (Last 10)
    $orders_res = mysqli_query($connection, "SELECT order_id, SKU, quantity, email, order_date FROM orders ORDER BY order_date DESC LIMIT 10");
    ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Recent Orders Export</title>
</head>
<body>
<h3>Recent Orders (Last 10)</h3>
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr style="background-color:#e6ffe6; font-weight:bold;">
            <th>Order ID</th>
            <th>SKU</th>
            <th>Quantity</th>
            <th>Supplier Email</th>
            <th>Order Date</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($orders_res && mysqli_num_rows($orders_res) > 0): ?>
            <?php while ($o = mysqli_fetch_assoc($orders_res)): ?>
            <tr>
                <td><?php echo (int)$o['order_id']; ?></td>
                <td><?php echo h($o['SKU']); ?></td>
                <td><?php echo (int)$o['quantity']; ?></td>
                <td><?php echo h($o['email']); ?></td>
                <td><?php echo h($o['order_date']); ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5">No orders found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</body>
</html>
<?php
    exit();
}

if ($which === 'products') {
    // Full products export
    $products_res = mysqli_query($connection, "SELECT Name, SKU, Amount, Supplier, Price, Date_modified FROM products ORDER BY Date_modified DESC");
    ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Products Export</title>
</head>
<body>
<h3>Products (All)</h3>
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr style="background-color:#e6f0ff; font-weight:bold;">
            <th>Product</th>
            <th>SKU</th>
            <th>Amount</th>
            <th>Supplier</th>
            <th>Price</th>
            <th>Last Updated</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($products_res && mysqli_num_rows($products_res) > 0): ?>
            <?php while ($p = mysqli_fetch_assoc($products_res)): ?>
            <tr>
                <td><?php echo h($p['Name']); ?></td>
                <td><?php echo h($p['SKU']); ?></td>
                <td><?php echo (int)$p['Amount']; ?></td>
                <td><?php echo h($p['Supplier']); ?></td>
                <td><?php echo h($p['Price']); ?></td>
                <td><?php echo h($p['Date_modified']); ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No products found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</body>
</html>
<?php
    exit();
}

// Default combined report (existing behavior)

// KPIs
$kpis = [
    'Distinct Products' => 0,
    'Total Units in Stock' => 0,
    'Low Stock Items (<10)' => 0,
    'Inventory Value' => 0.0,
    'Total Orders' => 0,
    'Quantity Ordered (All Time)' => 0,
];

if ($res = mysqli_query($connection, "SELECT COUNT(*) AS c FROM products")) { $kpis['Distinct Products'] = (int)mysqli_fetch_assoc($res)['c']; }
if ($res = mysqli_query($connection, "SELECT COALESCE(SUM(Amount),0) AS s FROM products")) { $kpis['Total Units in Stock'] = (int)mysqli_fetch_assoc($res)['s']; }
if ($res = mysqli_query($connection, "SELECT COUNT(*) AS c FROM products WHERE Amount < 10")) { $kpis['Low Stock Items (<10)'] = (int)mysqli_fetch_assoc($res)['c']; }
if ($res = mysqli_query($connection, "SELECT COALESCE(SUM(Amount * Price),0) AS v FROM products")) { $kpis['Inventory Value'] = (float)mysqli_fetch_assoc($res)['v']; }
if ($res = mysqli_query($connection, "SELECT COUNT(*) AS c FROM orders")) { $kpis['Total Orders'] = (int)mysqli_fetch_assoc($res)['c']; }
if ($res = mysqli_query($connection, "SELECT COALESCE(SUM(quantity),0) AS q FROM orders")) { $kpis['Quantity Ordered (All Time)'] = (int)mysqli_fetch_assoc($res)['q']; }

// Low stock full list
$low_stock_res = mysqli_query($connection, "SELECT Name, SKU, Amount, Supplier, Price, Date_modified FROM products WHERE Amount < 10 ORDER BY Amount ASC, Date_modified DESC");

// All orders
$orders_res = mysqli_query($connection, "SELECT order_id, SKU, quantity, email, order_date FROM orders ORDER BY order_date DESC");
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Stock-Watch Report</title>
</head>
<body>
<h2>Stock-Watch Report - Generated at <?php echo date('Y-m-d H:i:s'); ?></h2>

<!-- KPI Summary -->
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr style="background-color:#e6f0ff; font-weight:bold;">
            <th>Metric</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($kpis as $label => $value): ?>
        <tr>
            <td><?php echo h($label); ?></td>
            <td><?php echo is_numeric($value) ? h($value) : h($value); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<br/>

<!-- Low Stock Items -->
<h3>Low Stock Items (Amount < 10)</h3>
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr style="background-color:#ffe6e6; font-weight:bold;">
            <th>Product</th>
            <th>SKU</th>
            <th>Amount</th>
            <th>Supplier</th>
            <th>Price</th>
            <th>Last Updated</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($low_stock_res && mysqli_num_rows($low_stock_res) > 0): ?>
            <?php while ($r = mysqli_fetch_assoc($low_stock_res)): ?>
            <tr>
                <td><?php echo h($r['Name']); ?></td>
                <td><?php echo h($r['SKU']); ?></td>
                <td><?php echo (int)$r['Amount']; ?></td>
                <td><?php echo h($r['Supplier']); ?></td>
                <td><?php echo h($r['Price']); ?></td>
                <td><?php echo h($r['Date_modified']); ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No low stock items.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<br/>

<!-- Orders -->
<h3>Orders (All)</h3>
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr style="background-color:#e6ffe6; font-weight:bold;">
            <th>Order ID</th>
            <th>SKU</th>
            <th>Quantity</th>
            <th>Supplier Email</th>
            <th>Order Date</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($orders_res && mysqli_num_rows($orders_res) > 0): ?>
            <?php while ($o = mysqli_fetch_assoc($orders_res)): ?>
            <tr>
                <td><?php echo (int)$o['order_id']; ?></td>
                <td><?php echo h($o['SKU']); ?></td>
                <td><?php echo (int)$o['quantity']; ?></td>
                <td><?php echo h($o['email']); ?></td>
                <td><?php echo h($o['order_date']); ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5">No orders found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</body>
</html>
