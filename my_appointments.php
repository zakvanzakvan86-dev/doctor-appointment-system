<?php
session_start();
require "db.php";
date_default_timezone_set("Asia/Kolkata");

$today = date("Y-m-d");
$now = date("H:i");

mysqli_query(
    $conn,
    "UPDATE appointments 
     SET status='completed' 
     WHERE appointment_date < '$today'
        OR (appointment_date = '$today' AND appointment_time < '$now')"
);

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

/* 
We keep old query idea,
but ADD:
- id (needed for cancel)
- status
- cancel_reason
- hide completed appointments
*/
$sql = "
SELECT 
    a.id,
    a.appointment_date, 
    a.appointment_time, 
    a.message,
    a.status,
    a.cancel_reason,
    d.name, 
    d.specialization
FROM appointments a
JOIN doctors d ON a.doctor_id = d.id
WHERE a.user_id = '$user_id'
AND a.status != 'completed'
ORDER BY a.appointment_date DESC
";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Appointments</title>
    <style>
        body {
            background: #e3f2fd;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
        }

        .navbar {
            background: #1e88e5;
            color: white;
            padding: 18px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
        }

        .container {
            width: 85%;
            margin: 40px auto;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            margin-bottom: 20px;
        }

        .card h3 {
            margin: 0;
            color: #1e88e5;
        }

        .card p {
            margin: 6px 0;
            color: #555;
        }

        .status {
            font-weight: bold;
        }

        .booked { color: green; }
        .cancelled { color: red; }

        textarea {
            width: 100%;
            padding: 8px;
            border-radius: 8px;
            border: 1px solid #ccc;
            margin-top: 10px;
        }

        .btn {
            margin-top: 10px;
            padding: 10px 16px;
            background: #e53935;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .btn:hover {
            background: #c62828;
        }

        .empty {
            text-align: center;
            margin-top: 80px;
            color: #666;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 30px;
            text-decoration: none;
            color: #1e88e5;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="navbar">My Appointments</div>

<div class="container">

<?php if (mysqli_num_rows($result) > 0) { ?>
    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
        <div class="card">

            <h3><?php echo $row['name']; ?></h3>
            <p><?php echo $row['specialization']; ?></p>

            <p><strong>Date:</strong> <?php echo $row['appointment_date']; ?></p>
            <p><strong>Time:</strong> <?php echo $row['appointment_time']; ?></p>

            <?php if ($row['message']) { ?>
                <p><strong>Message:</strong> <?php echo $row['message']; ?></p>
            <?php } ?>

            <p class="status <?php echo $row['status']; ?>">
                <strong>Status:</strong> <?php echo ucfirst($row['status']); ?>
            </p>

            <?php if ($row['status'] == 'cancelled' && $row['cancel_reason']) { ?>
                <p><strong>Cancel Reason:</strong> <?php echo $row['cancel_reason']; ?></p>
            <?php } ?>

            <!-- CANCEL OPTION ONLY IF BOOKED -->
            <?php if ($row['status'] == 'booked') { ?>
                <form action="cancel_appointment.php" method="POST">
                    <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                    
                    <textarea name="reason" required placeholder="Reason for cancellation"></textarea>

                    <button type="submit" class="btn">Cancel Appointment</button>
                </form>
            <?php } ?>

        </div>
    <?php } ?>
<?php } else { ?>
    <div class="empty">
        <h3>No appointments booked yet</h3>
    </div>
<?php } ?>

<a class="back" href="dashboard.php">← Back to Dashboard</a>

</div>

</body>
</html>
