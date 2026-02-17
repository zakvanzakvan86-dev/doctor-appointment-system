<?php
session_start();
require "db.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>

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
            max-width: 380px;
        }

        .right {
            flex: 1;
        }

        .right h1 {
            font-size: 42px;
            color: #5f8f8b;
            margin-bottom: 10px;
        }

        .right p {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .right input {
            width: 100%;
            border: none;
            border-bottom: 2px solid #ccc;
            padding: 12px 5px;
            font-size: 16px;
            outline: none;
            margin-bottom: 25px;
        }

        .right input:focus {
            border-color: #5f8f8b;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .actions a {
            color: #5f8f8b;
            font-size: 14px;
            text-decoration: none;
        }

        .actions button {
            padding: 12px 30px;
            background: #5f8f8b;
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
        }

        .actions button:hover {
            background: #4e7f7b;
        }
    </style>
</head>

<body>

<div class="navbar">Doctor App</div>

<div class="page">
    <div class="card">

        <!-- LEFT IMAGE -->
        <div class="left">
            <img src="forgot.png" alt="Forgot Password">
        </div>

        <!-- RIGHT CONTENT -->
        <div class="right">
            <h1>Forgot Password?</h1>
            <p>
                Enter the email address associated with your account.
            </p>

            <form action="send_reset_otp.php" method="POST">
                <input type="email" name="email" placeholder="Enter Email Address" required>

                <div class="actions">
                    <a href="login.php">Try another way</a>
                    <button type="submit">Next</button>
                </div>
            </form>
        </div>

    </div>
</div>

</body>
</html>
