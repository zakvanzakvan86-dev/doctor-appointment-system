<?php
session_start();
require "db.php";

$message = "";
$success = false;

if (!isset($_SESSION['otp'])) {
    header("Location: register.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_otp = $_POST['otp'];

    if ($user_otp == $_SESSION['otp']) {

        $name  = $_SESSION['reg_name'];
        $email = $_SESSION['reg_email'];
        $pass  = $_SESSION['reg_pass'];

        $sql = "INSERT INTO users (fullname, email, password)
                VALUES ('$name', '$email', '$pass')";

        if (mysqli_query($conn, $sql)) {
            $success = true;

            // clear session
            unset($_SESSION['otp']);
            unset($_SESSION['reg_name']);
            unset($_SESSION['reg_email']);
            unset($_SESSION['reg_pass']);
        } else {
            $message = "Something went wrong. Please try again.";
        }

    } else {
        $message = "Invalid OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registration Successful</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to bottom right, #e8f5e9, #e3f2fd);
        }

        .navbar {
            background: #7b1fa2;
            color: white;
            padding: 18px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
        }

        .card {
            width: 420px;
            margin: 100px auto;
            background: white;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.2);
            text-align: center;
        }

        .success {
            font-size: 60px;
            color: #4caf50;
        }

        h2 {
            color: #333;
            margin: 15px 0;
        }

        p {
            color: #666;
            font-size: 16px;
            margin-bottom: 25px;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: #7b1fa2;
            color: white;
            border-radius: 10px;
            text-decoration: none;
            font-size: 18px;
            transition: 0.3s;
        }

        .btn:hover {
            background: #6a1b9a;
        }

        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="navbar">Doctor App</div>

<div class="card">

<?php if ($success) { ?>

    <div class="success">✔</div>
    <h2>Registration Successful</h2>
    <p>Your account has been created successfully.<br>
       You can now login and continue.</p>

    <a href="login.php" class="btn">Login Now</a>

<?php } else { ?>

    <h2>OTP Verification Failed</h2>
    <p class="error"><?php echo $message; ?></p>
    <a href="register.php" class="btn">Try Again</a>

<?php } ?>

</div>

</body>
</html>
