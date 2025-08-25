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

// Add status and received_date columns if they don't exist
$alter_status_sql = "ALTER TABLE orders ADD COLUMN IF NOT EXISTS status ENUM('placed', 'received') DEFAULT 'placed'";
mysqli_query($connection, $alter_status_sql);

$alter_received_date_sql = "ALTER TABLE orders ADD COLUMN IF NOT EXISTS received_date TIMESTAMP NULL";
mysqli_query($connection, $alter_received_date_sql);

// Delete received orders older than 10 minutes
$delete_old_received_sql = "DELETE FROM orders WHERE status = 'received' AND received_date < NOW() - INTERVAL 10 MINUTE";
mysqli_query($connection, $delete_old_received_sql);



// Create orders table using the new schema if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    SKU VARCHAR(50),
    quantity INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";

if (!mysqli_query($connection, $create_table_sql)) {
    die("Error creating table: " . mysqli_error($connection));
}


if (isset($_POST['confirm_received'])) {
    $orderId = (int)$_POST['order_id'];

    // Fetch order details
    $order_query = "SELECT SKU, quantity FROM orders WHERE order_id = ? AND status = 'placed'";
    $stmt_order = mysqli_prepare($connection, $order_query);
    mysqli_stmt_bind_param($stmt_order, "i", $orderId);
    mysqli_stmt_execute($stmt_order);
    $order_res = mysqli_stmt_get_result($stmt_order);

    if ($order_row = mysqli_fetch_assoc($order_res)) {
        $sku = $order_row['SKU'];
        $quantity = $order_row['quantity'];

        // Update product quantity
        $update_product_sql = "UPDATE products SET Amount = Amount + ? WHERE SKU = ?";
        $stmt_update = mysqli_prepare($connection, $update_product_sql);
        mysqli_stmt_bind_param($stmt_update, "is", $quantity, $sku);
        mysqli_stmt_execute($stmt_update);

        // Update order status to received and set received_date
        $update_order_sql = "UPDATE orders SET status = 'received', received_date = NOW() WHERE order_id = ?";
        $stmt_update_order = mysqli_prepare($connection, $update_order_sql);
        mysqli_stmt_bind_param($stmt_update_order, "i", $orderId);
        mysqli_stmt_execute($stmt_update_order);

        echo "<script>alert('Order confirmed as received. Quantity added to inventory.'); window.location.href=window.location.href;</script>";
    } else {
        echo "<script>alert('Invalid order or already received.');</script>";
    }
}

// Handle new order submission
if (isset($_POST['submit_order'])) {
    $sku = mysqli_real_escape_string($connection, $_POST['sku']);
    $quantity = (int)$_POST['quantity'];
    $email = trim($_POST['email']);

    if ($quantity > 0 && !empty($sku) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        mysqli_begin_transaction($connection);

        try {
            // 1. Create the order record
            $insert_order_sql = "INSERT INTO orders (SKU, quantity, email) VALUES (?, ?, ?)";
            $stmt_insert = mysqli_prepare($connection, $insert_order_sql);
            mysqli_stmt_bind_param($stmt_insert, "sis", $sku, $quantity, $email);
            mysqli_stmt_execute($stmt_insert);

            $orderId = mysqli_insert_id($connection);

            // Commit the order before attempting email to avoid losing the order on mail failures
            mysqli_commit($connection);

            // 2. Optionally send purchase order email
            if (isset($_POST['send_email'])) {
                $product_name_query = "SELECT Name FROM products WHERE SKU = ?";
                $stmt_prod = mysqli_prepare($connection, $product_name_query);
                mysqli_stmt_bind_param($stmt_prod, "s", $sku);
                mysqli_stmt_execute($stmt_prod);
                $prod_res = mysqli_stmt_get_result($stmt_prod);
                $product_name = ($prod_row = mysqli_fetch_assoc($prod_res)) ? $prod_row['Name'] : $sku;

                $subject = "New Purchase Order - SKU: " . $sku;
                $message = "Hello,\n\nPlease process the following purchase order:\n\nProduct: " . $product_name . "\nSKU: " . $sku . "\nQuantity: " . $quantity;

                // Append custom message if provided
                $custom_message = isset($_POST['custom_message']) ? trim($_POST['custom_message']) : '';
                if (!empty($custom_message)) {
                    $message .= "\n\nAdditional Notes:\n" . $custom_message;
                }

                $message .= "\n\nThank you,\nStock-Watch System";

                // Build robust headers
                $headers = "From: Stock-Watch <davidomonge8@gmail.com>\r\n";
                $headers .= "Reply-To: davidomonge8@gmail.com\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                // Attempt to send via SMTP
                $smtpSuccess = sendEmailViaSMTP($email, $subject, $message, 'davidomonge8@gmail.com', $headers);

                if ($smtpSuccess) {
                    echo "<script>alert('Order created and custom email sent successfully!'); window.location.href=window.location.href;</script>";
                } else {
                    // Log failure
                    $logDir = __DIR__ . '/logs';
                    if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }
                    $outboxDir = $logDir . '/outbox';
                    if (!is_dir($outboxDir)) { @mkdir($outboxDir, 0775, true); }

                    $ts = date('Y-m-d H:i:s');
                    $logEntry = "[$ts] OrderID: {$orderId}, To: {$email}, Subject: {$subject}\nError: SMTP sending failed\n---\n{$message}\n\n";
                    @file_put_contents($logDir . '/mail_errors.log', $logEntry, FILE_APPEND);

                    $outboxFile = $outboxDir . '/order_' . $orderId . '_' . date('Ymd_His') . '.txt';
                    @file_put_contents($outboxFile, "To: {$email}\nSubject: {$subject}\n\n{$message}");

                    // Fallback to mailto
                    $mailtoLink = 'mailto:' . $email . '?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($message);

                    echo "<script>
                        (function(){
                            var mailto = " . json_encode($mailtoLink) . ";
                            try {
                                var a = document.createElement('a');
                                a.href = mailto;
                                a.style.display = 'none';
                                document.body.appendChild(a);
                                a.click();
                            } catch (e) {
                                window.location.href = mailto;
                            }
                            alert('Order created. SMTP email failed; your email client will open with the custom message pre-filled. Please review and send.');
                            setTimeout(function(){ window.location.href = window.location.href; }, 800);
                        })();
                    </script>";
                }
            } else {
                echo "<script>alert('Order created successfully! (No email sent)'); window.location.href=window.location.href;</script>";
            }
        } catch (Exception $e) {
            mysqli_rollback($connection);
            echo "<script>alert('An error occurred: " . $e->getMessage() . "');</script>";
        }
    } else {
        echo "<script>alert('Please fill in all fields correctly.');</script>";
    }
}


function sendEmailViaSMTP($to, $subject, $message, $from, $headers) {
    // SMTP configuration - adjust these as needed
    // Example for Gmail: (use app password if 2FA enabled)
    $smtpHost = 'smtp.gmail.com';
    $smtpPort = 587;
    $smtpUsername = 'davidomonge8@gmail.com'; // Replace with your Gmail address
    $smtpPassword = 'fzvccylnwkbepgcz'; // Replace with your Gmail app password
    $useAuth = true;
    $secure = 'tls';

    // Example for local server without auth:
    // $smtpHost = 'localhost';  // e.g., 'smtp.example.com' or 'localhost' for local server
    // $smtpPort = 25;           // Common ports: 25 (none), 465 (ssl), 587 (tls)
    // $smtpUsername = '';       // SMTP username if authentication is required
    // $smtpPassword = '';       // SMTP password if authentication is required
    // $useAuth = false;         // Set to true if your SMTP requires authentication
    // $secure = 'none';         // 'none', 'ssl', or 'tls' (STARTTLS)

    // Prefix for SSL
    $hostPrefix = ($secure === 'ssl') ? 'ssl://' : '';
    $connectHost = $hostPrefix . $smtpHost;

    // Connect to SMTP server
    $socket = @fsockopen($connectHost, $smtpPort, $errno, $errstr, 15);
    if (!$socket) {
        return false;
    }

    // Read initial response
    readSmtpResponse($socket);

    // Send EHLO
    fputs($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
    readSmtpResponse($socket);

    // Handle STARTTLS if required
    if ($secure === 'tls') {
        fputs($socket, "STARTTLS\r\n");
        readSmtpResponse($socket);
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return false;
        }
        // Re-send EHLO after STARTTLS
        fputs($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        readSmtpResponse($socket);
    }

    // Authenticate if required
    if ($useAuth) {
        fputs($socket, "AUTH LOGIN\r\n");
        readSmtpResponse($socket);
        fputs($socket, base64_encode($smtpUsername) . "\r\n");
        readSmtpResponse($socket);
        fputs($socket, base64_encode($smtpPassword) . "\r\n");
        readSmtpResponse($socket);
    }

    // Send MAIL FROM
    fputs($socket, "MAIL FROM: <" . $from . ">\r\n");
    readSmtpResponse($socket);

    // Send RCPT TO
    fputs($socket, "RCPT TO: <" . $to . ">\r\n");
    readSmtpResponse($socket);

    // Send DATA
    fputs($socket, "DATA\r\n");
    readSmtpResponse($socket);

    // Send headers and message
    fputs($socket, $headers . "\r\n\r\n" . $message . "\r\n.\r\n");
    readSmtpResponse($socket);

    // Quit
    fputs($socket, "QUIT\r\n");
    fclose($socket);

    return true;
}

function readSmtpResponse($socket) {
    $response = '';
    while ($str = fgets($socket, 515)) {
        $response .= $str;
        if (substr($str, 3, 1) == ' ') { break; }
    }
    if (substr($response, 0, 3) != '250' && substr($response, 0, 3) != '354' && substr($response, 0, 3) != '220') {
        // Error handling - you can log the response if needed
        return false;
    }
    return $response;
}

// Fetch products for the order form dropdown
$products_res = mysqli_query($connection, "SELECT Name, SKU, Amount FROM products WHERE Amount > 0 ORDER BY Name ASC");

$orders_res = mysqli_query($connection, 
    "SELECT o.order_id, p.Name, o.SKU, o.quantity, o.email, o.order_date, o.status, o.received_date
     FROM orders o
     LEFT JOIN products p ON o.SKU = p.SKU
     ORDER BY o.order_date DESC"
);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORDERS</title>
    <link rel="stylesheet" href="orders.css">
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
                <li><a href="logout.php"><button class="logout-button" name="logout">Logout</button></a></li>
            </ul>
        </div>
    </header>

    <hr>

    <div class="content">
        <p>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?> (<?= htmlspecialchars($_SESSION['user_role']) ?>)</p>
        <br>

    <?php if ($_SESSION['user_role'] !== 'Warehouse Staff'): ?>
        <div class="content-description">
            <h1>Create New Purchase Order</h1>
            <p>Select a product, specify the quantity, and enter the supplier's email to send a purchase order.</p>
        </div>

        <br><br>

        <form action="" method="POST" id="create-order-form">
            <div class="form-row">
                <div>
                    <label for="sku">Product:</label><br><br>
                    <select id="sku" name="sku" required>
                        <option value="" disabled selected>-- Select a Product --</option>
                        <?php while($p = mysqli_fetch_assoc($products_res)): ?>
                            <option value="<?= htmlspecialchars($p['SKU']) ?>">
                                <?= htmlspecialchars($p['Name']) ?> (SKU: <?= htmlspecialchars($p['SKU']) ?>) - Stock: <?= $p['Amount'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label for="quantity">Quantity:</label><br><br>
                    <input type="number" id="quantity" name="quantity" min="1" required>
                </div>

                <div>
                    <label for="email">Supplier Email:</label><br><br>
                    <input type="email" id="email" name="email" required>
                </div>
            </div>
            <br>
            <div>
                <input type="checkbox" id="send_email" name="send_email" checked>
                <label for="send_email">Send Email Notification</label>
            </div>
            <br>
            
            <br>
            <button type="submit" name="submit_order">Create Order</button>
        </form>

        <br><br>

        <br><br><hr><br><br>

        <h2>Order History</h2>
        <br>
        <div class="table-container">
            <table>
                <thead>
                <tr>
                <th>ORDER ID</th>
                <th>PRODUCT</th>
                <th>SKU</th>
                <th>QUANTITY</th>
                <th>SUPPLIER EMAIL</th>
                <th>STATUS</th>
                <th>ACTION</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($orders_res && mysqli_num_rows($orders_res) > 0): ?>
                <?php while($o = mysqli_fetch_assoc($orders_res)): ?>
                <?php
                $status = $o['status'];
                $displayStatus = $status;
                $statusColor = '';
                if ($status === 'placed') {
                    $orderTs = strtotime($o['order_date']);
                    if (time() - $orderTs > 600) { // 10 minutes
                        $displayStatus = 'processing';
                        $statusColor = 'orange';
                    } else {
                        $displayStatus = 'placed';
                        $statusColor = 'blue';
                    }
                } elseif ($status === 'received') {
                    $receivedTs = strtotime($o['received_date']);
                    if (time() - $receivedTs > 600) {
                        continue; // Skip if older than 10 minutes
                    }
                    $displayStatus = 'received';
                    $statusColor = 'green';
                }
                ?>
                <tr>
                <td><?= $o['order_id'] ?></td>
                <td><?= htmlspecialchars($o['Name'] ?: 'N/A') ?></td>
                <td><?= htmlspecialchars($o['SKU']) ?></td>
                <td><?= $o['quantity'] ?></td>
                <td><?= htmlspecialchars($o['email']) ?></td>
                <td style="color: <?= $statusColor ?>;"><?= ucfirst($displayStatus) ?></td>
                <td>
                <?php if ($status === 'placed'): ?>
                <form method="POST" style="display:inline;">
                <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                <button type="submit" name="confirm_received" class="confirm-button">Confirm Received</button>
                </form>
                <?php endif; ?>
                </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr><td colspan="7" style="text-align:center; opacity:0.7;">No orders found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <h2 style="color: red; text-align: center; margin-top: 50px;">Access Denied: This page is restricted to Admins and Managers only.</h2>
    <?php endif; ?>
    </div>

    

</body>
</html>
