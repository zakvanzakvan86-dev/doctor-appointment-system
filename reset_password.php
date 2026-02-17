<?php
session_start();
require "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $otp = $_POST['otp'];
    $newpass = $_POST['password'];

    if (!isset($_SESSION['reset_otp']) || !isset($_SESSION['reset_email'])) {
        header("Location: forgot_password.php");
        exit;
    }

    if ($otp != $_SESSION['reset_otp']) {
        $error = "Invalid OTP";
    } else {

        $email = $_SESSION['reset_email'];
        $hash = password_hash($newpass, PASSWORD_DEFAULT);

        mysqli_query($conn, "UPDATE users SET password='$hash' WHERE email='$email'");

        unset($_SESSION['reset_otp']);
        unset($_SESSION['reset_email']);

        header("Location: login.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>

    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", sans-serif;
            background: #ffffff;
        }

        .navbar {
            background: #7b1fa2;
            color: white;
            padding: 18px;
            font-size: 24px;
            text-align: center;
            font-weight: bold;
        }

        .page {
            display: flex;
            align-items: center;
            justify-content: center;
            height: calc(100vh - 70px);
        }

        .card {
            width: 900px;
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .left {
            flex: 1;
            text-align: center;
        }

        .left img {
            width: 100%;
            max-width: 360px;
        }

        .right {
            flex: 1;
        }

        .right h1 {
            font-size: 38px;
            color: #5f8f8b;
            margin-bottom: 10px;
        }

        .right p {
            color: #666;
            margin-bottom: 25px;
        }

        input {
            width: 100%;
            border: none;
            border-bottom: 2px solid #ccc;
            padding: 12px 5px;
            font-size: 16px;
            margin-bottom: 20px;
            outline: none;
        }

        input:focus {
            border-color: #5f8f8b;
        }

        button {
            padding: 12px 35px;
            background: #5f8f8b;
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #4e7f7b;
        }

        .error {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

<div class="navbar">Doctor App</div>

<div class="page">
    <div class="card">

        <!-- LEFT IMAGE -->
        <div class="left">
            <img src="forgot.png" alt="Reset Password">
        </div>

        <!-- RIGHT FORM -->
        <div class="right">
            <h1>Reset Password</h1>
            <p>Enter OTP and set a new password</p>

            <?php if ($error) echo "<div class='error'>$error</div>"; ?>

            <form method="POST">
                <input type="number" name="otp" placeholder="Enter OTP" required>
                <input type="password" name="password" placeholder="New Password" required>
                <button type="submit">Reset Password</button>
            </form>
        </div>

    </div>
</div>

</body>
</html>
