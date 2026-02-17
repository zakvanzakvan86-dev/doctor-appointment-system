<?php
session_start();
require "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// auto select doctor if coming from doctors.php
$selected_doctor = $_GET['doctor_id'] ?? '';

$doctors = mysqli_query($conn, "SELECT * FROM doctors");

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $doctor_id = $_POST["doctor_id"];
    $date = $_POST["appointment_date"];
    $time = $_POST["appointment_time"];
    $message = $_POST["message"];

    // ❌ block past dates
    if ($date < date("Y-m-d")) {
        $error = "Invalid date selected";
    } else {

        // ❌ check if slot already booked
        $check = mysqli_query(
            $conn,
            "SELECT id FROM appointments 
             WHERE doctor_id='$doctor_id'
             AND appointment_date='$date'
             AND appointment_time='$time'"
        );

        if (mysqli_num_rows($check) > 0) {
            $error = "This time slot is already booked";
        } else {

            mysqli_query(
                $conn,
                "INSERT INTO appointments 
                (user_id, doctor_id, appointment_date, appointment_time, message)
                VALUES 
                ('$user_id','$doctor_id','$date','$time','$message')"
            );

            $success = "Appointment booked successfully";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Book Appointment</title>

<style>
body {
    background:#e3f2fd;
    font-family:'Segoe UI', sans-serif;
}
.navbar {
    background:#1e88e5;
    color:white;
    padding:18px;
    font-size:24px;
    text-align:center;
    font-weight:bold;
}
.box {
    width:420px;
    background:white;
    margin:60px auto;
    padding:30px;
    border-radius:16px;
    box-shadow:0 4px 20px rgba(0,0,0,0.15);
}
select,input,textarea {
    width:100%;
    padding:12px;
    margin:12px 0;
    border-radius:8px;
    border:1px solid #ccc;
    font-size:16px;
}
button {
    width:100%;
    padding:14px;
    background:#1e88e5;
    color:white;
    border:none;
    border-radius:10px;
    font-size:18px;
    cursor:pointer;
}
.success {
    background:#e8f5e9;
    color:#2e7d32;
    padding:10px;
    text-align:center;
}
.error {
    background:#ffe5e5;
    color:#c62828;
    padding:10px;
    text-align:center;
}
.back {
    text-align:center;
    margin-top:15px;
}
.back a {
    text-decoration:none;
    color:#1e88e5;
    font-weight:bold;
}
</style>

<script>
function loadSlots() {
    let doctor = document.getElementById("doctor").value;
    let date = document.getElementById("date").value;

    if (doctor && date) {
        fetch("fetch_slots.php?doctor_id=" + doctor + "&date=" + date)
        .then(res => res.text())
        .then(data => {
            document.getElementById("time").innerHTML = data;
        });
    }
}
</script>

</head>
<body>

<div class="navbar">Book Appointment</div>

<div class="box">

<?php if ($success) echo "<div class='success'>$success</div>"; ?>
<?php if ($error) echo "<div class='error'>$error</div>"; ?>

<form method="POST">

<select name="doctor_id" id="doctor" onchange="loadSlots()" required>
<option value="">Select Doctor</option>

<?php while ($d = mysqli_fetch_assoc($doctors)) { ?>
<option value="<?= $d['id']; ?>"
    <?= ($selected_doctor == $d['id']) ? 'selected' : ''; ?>>
    <?= $d['name']; ?> (<?= $d['specialization']; ?>)
</option>
<?php } ?>

</select>

<input type="date"
       name="appointment_date"
       id="date"
       min="<?= date('Y-m-d'); ?>"
       onchange="loadSlots()"
       required>

<select name="appointment_time" id="time" required>
<option value="">Select Time</option>
<?php
for ($h = 9; $h <= 17; $h++) {
    echo "<option value='$h:00'>$h:00</option>";
    echo "<option value='$h:30'>$h:30</option>";
}
?>
</select>

<textarea name="message" placeholder="Reason (optional)"></textarea>

<button type="submit">Confirm Booking</button>

</form>

<div class="back">
<a href="dashboard.php">← Back to Dashboard</a>
</div>

</div>
</body>
</html>
