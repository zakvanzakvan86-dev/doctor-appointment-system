<?php
session_start();
require "db.php";

/* ADMIN ONLY */
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit;
}

/* HANDLE STATUS UPDATE */
if (isset($_POST['action'], $_POST['appointment_id'])) {
    $id = $_POST['appointment_id'];

    if ($_POST['action'] === 'complete') {
        mysqli_query($conn, "UPDATE appointments SET status='completed' WHERE id='$id'");
    }

    if ($_POST['action'] === 'cancel') {
        mysqli_query($conn, "UPDATE appointments SET status='cancelled' WHERE id='$id'");
    }
}

/* FETCH ONLY ACTIVE BOOKINGS */
$sql = "
SELECT 
    a.id,
    a.appointment_date,
    a.appointment_time,
    a.message,
    a.status,
    u.fullname AS user_name,
    d.name AS doctor_name,
    d.specialization
FROM appointments a
JOIN users u ON a.user_id = u.id
JOIN doctors d ON a.doctor_id = d.id
WHERE a.status = 'booked'
ORDER BY a.appointment_date, a.appointment_time
";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin – Appointments</title>

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

        .box {
            width: 95%;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f5f5f5;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .complete {
            background: #4caf50;
            color: white;
        }

        .cancel {
            background: #e53935;
            color: white;
        }

        .back {
            margin-top: 20px;
            text-align: center;
        }

        .back a {
            text-decoration: none;
            color: #1e88e5;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>

<body>

<div class="navbar">Admin – View Appointments</div>

<div class="box">

<?php if (mysqli_num_rows($result) > 0) { ?>

<table>
    <tr>
        <th>User</th>
        <th>Doctor</th>
        <th>Specialization</th>
        <th>Date</th>
        <th>Time</th>
        <th>Message</th>
        <th>Action</th>
    </tr>

    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
    <tr>
        <td><?= htmlspecialchars($row['user_name']) ?></td>
        <td><?= htmlspecialchars($row['doctor_name']) ?></td>
        <td><?= htmlspecialchars($row['specialization']) ?></td>
        <td><?= $row['appointment_date'] ?></td>
        <td><?= $row['appointment_time'] ?></td>
        <td><?= htmlspecialchars($row['message']) ?></td>

        <td>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                <button class="btn complete" name="action" value="complete">✔ Completed</button>
            </form>

            <form method="POST" style="display:inline;">
                <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                <button class="btn cancel" name="action" value="cancel">❌ Cancel</button>
            </form>
        </td>
    </tr>
    <?php } ?>
</table>

<?php } else { ?>
    <div class="empty">
        <h3>No active appointments</h3>
    </div>
<?php } ?>

<div class="back">
    <a href="dashboard.php">← Back to Dashboard</a>
</div>

</div>

</body>
</html>
