<?php
require "db.php";

$doctor_id = (int)($_GET['doctor_id'] ?? 0);
$date      = mysqli_real_escape_string($conn, $_GET['date'] ?? '');

$booked = [];

if ($doctor_id && $date) {
    $res = mysqli_query($conn,
        "SELECT appointment_time FROM appointments
         WHERE doctor_id=$doctor_id AND appointment_date='$date' AND status='booked'"
    );
    while ($row = mysqli_fetch_assoc($res)) {
        // Normalize to HH:MM format
        $booked[] = substr($row['appointment_time'], 0, 5);
    }
}

header('Content-Type: application/json');
echo json_encode($booked);
