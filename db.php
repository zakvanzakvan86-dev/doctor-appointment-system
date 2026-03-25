<?php
$host = "127.0.0.1";
$user = "root";
$pass = "zakvan"; // the one you set during MySQL install
$db   = "doctor_app";
$port = 3306; // MySQL 8.4 port

$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
