require "db.php";

$now = date("Y-m-d H:i:00");

$sql = "UPDATE appointments 
        SET status='completed'
        WHERE CONCAT(appointment_date,' ',appointment_time) < ?
        AND status='booked'";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $now);
mysqli_stmt_execute($stmt);
