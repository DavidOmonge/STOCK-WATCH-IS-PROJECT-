<?php
session_start();

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'stockwatch';

$connection = mysqli_connect($host, $username, $password, $database);

if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_POST['reset'])) {
    $email = $_POST['email'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        // Update password in database (use password_hash in production)
        $query = "UPDATE users SET password='$new_password' WHERE email='$email'";
        $result = mysqli_query($connection, $query);

        if ($result && mysqli_affected_rows($connection) > 0) {
            echo "<script>alert('Password updated successfully. You can now login.'); window.location.href='login.php';</script>";
        } else {
            echo "<script>alert('Email not found or update failed.');</script>";
        }
    } else {
        echo "<script>alert('Passwords do not match.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <header>
        <h1>STOCK-WATCH</h1>
    </header>

    <hr>
    

    <div class="content">
        <h1>Reset Password</h1>
        <div class="form">
            <form method="post" action="">
                <label for="email">Enter your Email</label><br><br>
                <input type="email" id="email" name="email" placeholder="Enter your registered email" required>
                <br><br><br>

                <label for="new_password">New Password</label><br><br>
                <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                <button type="button" onclick="togglePassword('new_password')"
                    style="background-color: transparent; color: grey; border: none; margin-top:5px;">
                    Show Password
                </button>
                <br><br><br>

                <label for="confirm_password">Confirm Password</label><br><br>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                <button type="button" onclick="togglePassword('confirm_password')"
                    style="background-color: transparent; color: grey; border: none; margin-top:5px;">
                    Show Password
                </button>
                <br><br><br>

                <button type="submit" name="reset">Reset Password</button>
            </form>
        </div>
    </div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    if (field.type === "password") {
        field.type = "text";
    } else {
        field.type = "password";
    }
}
</script>

</body>
</html>
