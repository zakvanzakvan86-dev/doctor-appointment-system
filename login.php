<?php
session_start();
require "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST["email"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {

        $row = mysqli_fetch_assoc($result);

        if (password_verify($password, $row["password"])) {

            $_SESSION["user"] = $row["email"];
            $_SESSION["username"] = $row["fullname"];
            $_SESSION["user_id"] = $row["id"];
            $_SESSION["role"] = $row["role"];

            header("Location: dashboard.php");
            exit;

        } else {
            $error = "Invalid email or password!";
        }

    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="navbar">Doctor App</div>

<div class="container">

    <div class="left">
        <img src="image.png">
    </div>

    <div class="right">
        <h2>Welcome Back</h2>

        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>

        <?php if ($error) { ?>
            <div class="error"><?php echo $error; ?></div>
        <?php } ?>

        <a href="register.php">Register</a>
    </div>

</div>

</body>
</html>

