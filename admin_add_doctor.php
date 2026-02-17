<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require "db.php";

/* ADMIN ONLY */
if (!isset($_SESSION["user"]) || $_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit;
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST["name"];
    $spec = $_POST["specialization"];

    $photoName = "";
    if (!empty($_FILES["photo"]["name"])) {
        $photoName = time() . "_" . $_FILES["photo"]["name"];
        move_uploaded_file($_FILES["photo"]["tmp_name"], "doctors/" . $photoName);
    }

    $sql = "INSERT INTO doctors (name, specialization, photo)
            VALUES ('$name', '$spec', '$photoName')";

    if (mysqli_query($conn, $sql)) {
        $msg = "Doctor added successfully!";
    } else {
        $msg = "Something went wrong!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Doctor</title>

    <style>
        body {
            background: #e3f2fd;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
        }

        .navbar {
            background: #1e88e5;
            padding: 18px;
            text-align: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }

        .box {
            width: 420px;
            margin: 60px auto;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        input, button {
            width: 100%;
            padding: 12px;
            margin-top: 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 16px;
        }

        button {
            background: #1e88e5;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #1565c0;
        }

        .msg {
            margin-top: 15px;
            padding: 10px;
            background: #d4edda;
            color: #155724;
            border-radius: 6px;
            text-align: center;
        }

        .back {
            text-align: center;
            margin-top: 15px;
        }

        .back a {
            text-decoration: none;
            color: #1e88e5;
            font-weight: 600;
        }
    </style>
</head>

<body>

<div class="navbar">Admin – Add Doctor</div>

<div class="box">

    <h2>Add Doctor</h2>

    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Doctor Name" required>
        <input type="text" name="specialization" placeholder="Specialization" required>
        <input type="file" name="photo" accept="image/*">
        <button type="submit">Add Doctor</button>
    </form>

    <?php if ($msg) { ?>
        <div class="msg"><?php echo $msg; ?></div>
    <?php } ?>

    <div class="back">
        <a href="dashboard.php">← Back to Dashboard</a>
    </div>

</div>

</body>
</html>
