<?php
session_start();
require "db.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_POST["user_id"];
$doctor_id = $_POST["doctor_id"];
$date = $_POST["appointment_date"];
$time = $_POST["appointment_time"];
$message = $_POST["message"];

$sql = "INSERT INTO appointments 
        (user_id, doctor_id, appointment_date, appointment_time, message) 
        VALUES 
        ('$user_id', '$doctor_id', '$date', '$time', '$message')";

mysqli_query($conn, $sql);

header("Location: my_appointments.php");
exit;
