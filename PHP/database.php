<?php
// JSON endpoint for dashboard and other pages to fetch aggregated data
// Uses existing session to ensure the request is authenticated

session_start();

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'stockwatch';

$connection = mysqli_connect($host, $username, $password, $database);
if (!$connection) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}
mysqli_set_charset($connection, 'utf8mb4');

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Require authentication for API actions
if ($action !== '' && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if ($action === 'supplier_quantities') {
    header('Content-Type: application/json; charset=utf-8');

    $data = [];
    $sql = "SELECT COALESCE(Supplier, 'Unknown') AS Supplier, COALESCE(SUM(Amount), 0) AS total_qty
            FROM products
            GROUP BY Supplier
            ORDER BY total_qty DESC, Supplier ASC";

    if ($res = mysqli_query($connection, $sql)) {
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = [
                'supplier' => $row['Supplier'],
                'total_qty' => (int)$row['total_qty']
            ];
        }
    }

    echo json_encode(['data' => $data]);
    exit();
}

// Default: Unknown/unsupported action
if ($action !== '') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode(['error' => 'Unsupported action']);
    exit();
}

// If visited directly without action, display a minimal message
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data API</title>
</head>
<body>
  <p>Data API is running. Use the "action" query parameter. Example: <code>?action=supplier_quantities</code></p>
</body>
</html>
