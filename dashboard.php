<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$name = $_SESSION['username'];   // USER / ADMIN NAME
$role = $_SESSION['role'];       // admin / user
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>

<!-- Google Font -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<!-- Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family:'Poppins',sans-serif;
    background:#f4f7fb;
}

.wrapper {
    display:flex;
    min-height:100vh;
}

/* SIDEBAR */
.sidebar {
    width:260px;
    background:linear-gradient(180deg,#1e88e5,#0d47a1);
    color:#fff;
    padding:25px;
}

.sidebar h2 {
    text-align:center;
    margin-bottom:40px;
}

.sidebar a {
    display:flex;
    align-items:center;
    gap:14px;
    color:white;
    text-decoration:none;
    padding:14px;
    margin-bottom:12px;
    border-radius:12px;
    transition:0.3s;
}

.sidebar a:hover {
    background:rgba(255,255,255,0.15);
}

/* MAIN */
.main {
    flex:1;
    padding:30px;
    animation:fadeIn 0.5s ease;
}

.topbar {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:30px;
}

.topbar h1 {
    font-size:26px;
}

.logout {
    background:#e53935;
    color:white;
    padding:10px 18px;
    border-radius:10px;
    text-decoration:none;
}

/* CARDS */
.cards {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:25px;
}

.card {
    background:white;
    padding:25px;
    border-radius:18px;
    box-shadow:0 10px 25px rgba(0,0,0,0.1);
    transition:0.3s;
}

.card:hover {
    transform:translateY(-6px);
}

.card i {
    font-size:32px;
    color:#1e88e5;
    margin-bottom:12px;
}

.card h3 {
    font-size:20px;
}

.card p {
    color:#555;
}

a.card-link {
    text-decoration:none;
    color:inherit;
}

@keyframes fadeIn {
    from { opacity:0; transform:translateY(15px); }
    to { opacity:1; transform:translateY(0); }
}
</style>
</head>

<body>

<div class="wrapper">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h2>Doctor App</h2>

        <a href="dashboard.php"><i class="fa-solid fa-house"></i>Dashboard</a>

        <!-- USER SIDEBAR -->
        <?php if ($role === 'user') { ?>
            <a href="doctors.php"><i class="fa-solid fa-user-doctor"></i>Find Doctors</a>
            <a href="book.php"><i class="fa-solid fa-calendar-plus"></i>Book Appointment</a>
            <a href="my_appointments.php"><i class="fa-solid fa-list"></i>My Appointments</a>
        <?php } ?>

        <!-- ADMIN SIDEBAR -->
        <?php if ($role === 'admin') { ?>
            <a href="admin_add_doctor.php"><i class="fa-solid fa-user-plus"></i>Add Doctor</a>
            <a href="admin_bookings.php"><i class="fa-solid fa-table"></i>View Appointments</a>
        <?php } ?>

        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i>Logout</a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">

        <div class="topbar">
            <h1>Welcome, <?php echo htmlspecialchars($name); ?> 👋</h1>
            <a class="logout" href="logout.php">Logout</a>
        </div>

        <div class="cards">

            <!-- USER CARDS -->
            <?php if ($role === 'user') { ?>

            <a href="doctors.php" class="card-link">
                <div class="card">
                    <i class="fa-solid fa-user-doctor"></i>
                    <h3>Find Doctors</h3>
                    <p>View available doctors</p>
                </div>
            </a>

            <a href="book.php" class="card-link">
                <div class="card">
                    <i class="fa-solid fa-calendar-check"></i>
                    <h3>Book Appointment</h3>
                    <p>Select date & time</p>
                </div>
            </a>

            <a href="my_appointments.php" class="card-link">
                <div class="card">
                    <i class="fa-solid fa-notes-medical"></i>
                    <h3>My Appointments</h3>
                    <p>View & cancel bookings</p>
                </div>
            </a>

            <?php } ?>

            <!-- ADMIN CARDS -->
            <?php if ($role === 'admin') { ?>

            <a href="admin_add_doctor.php" class="card-link">
                <div class="card">
                    <i class="fa-solid fa-user-plus"></i>
                    <h3>Add Doctor</h3>
                    <p>Manage doctor details</p>
                </div>
            </a>

            <a href="admin_bookings.php" class="card-link">
                <div class="card">
                    <i class="fa-solid fa-chart-line"></i>
                    <h3>View Appointments</h3>
                    <p>Monitor all bookings</p>
                </div>
            </a>

            <?php } ?>

        </div>

    </div>

</div>

</body>
</html>
