<?php
require "db.php";

date_default_timezone_set("Asia/Kolkata"); // 🔥 IMPORTANT FIX

$doctor_id = $_GET['doctor_id'];
$date = $_GET['date'];

$today = date("Y-m-d");
$currentTime = date("H:i");

// get booked slots (only booked ones)
$booked = [];
$result = mysqli_query(
    $conn,
    "SELECT appointment_time FROM appointments 
     WHERE doctor_id='$doctor_id' 
     AND appointment_date='$date'
     AND status='booked'"
);

while ($row = mysqli_fetch_assoc($result)) {
    $booked[] = $row['appointment_time'];
}

echo "<option value=''>Select Time</option>";

for ($h = 9; $h <= 17; $h++) {
    foreach (["00", "30"] as $m) {

        $time = sprintf("%02d:%s", $h, $m);

        // ❌ block past time if today
        if ($date === $today && $time <= $currentTime) {
            continue;
        }

        // ❌ block booked slot
        if (in_array($time, $booked)) {
            continue;
        }

        echo "<option value='$time'>$time</option>";
    }
}
