<?php

session_start();

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'stockwatch';

$connection = mysqli_connect($host, $username, $password, $database);

if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
} else {
    // echo "Connected successfully";
}

// Handle login
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password_input = $_POST['password'];

    // Look for the user in the database
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        // Compare passwords (in real apps, use password_hash() and password_verify())
        if ($user['password'] === $password_input) {
    if ($user['role'] === 'Inventory Manager' || $user['role'] === 'Admin') {
        // Store session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        echo "<script> window.location.href = 'dashboard.php';</script>";
    } else {
        echo "<script>alert('Access denied. Only Admin and Managers are allowed to log into this page.');</script>";
    }
} else {
    echo "<script>alert('Incorrect password. Please try again.');</script>";
}

    }


    //
}

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOGIN</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <header>
        <h1>STOCK-WATCH</h1>
    </header>

    <hr>

    <br><br>

    <div class="content">
        <h1>Welcome back</h1>

        <div class="form">
            <form action="" method="post">
                <label for="email">Email</label>
                <br><br>
                <input type="text" id="email" name="email" placeholder="Enter your email" required>
                <br><br><br>
                <label for="password">Password</label>
                <br><br>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <br><br>
                <button type="button" onclick="togglePassword()" style="background-color: transparent; color: grey; border-bottom:solid black 1px; padding: 0; text-align:center; width:30%; border-radius:0;">Click to Show Password</button>



                <br><br><br>
                <button type="submit" name="login">Login</button>

            </form>

            <br>
            
            <p><a href="#">forgot password?</a></p>
        </div>
    </div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById("password");
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
    } else {
        passwordInput.type = "password";
    }
}
</script>

</body>
</html>