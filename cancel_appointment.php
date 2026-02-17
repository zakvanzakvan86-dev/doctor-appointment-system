<?php
session_start();
require "db.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$appointment_id = $_POST['appointment_id'];
$reason = $_POST['reason'];

$sql = "UPDATE appointments 
        SET status='cancelled', cancel_reason=? 
        WHERE id=?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "si", $reason, $appointment_id);
mysqli_stmt_execute($stmt);

header("Location: my_appointments.php");
exit;
