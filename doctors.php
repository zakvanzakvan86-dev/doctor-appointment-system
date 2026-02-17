<?php
session_start();
require "db.php";

/* ✅ Correct session check */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$doctors = mysqli_query($conn, "SELECT * FROM doctors");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Find Doctors</title>

<style>
body {
    background: #e3f2fd;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
}

/* HEADER */
.navbar {
    background: #1e88e5;
    padding: 20px 0;
    text-align: center;
    color: white;
    font-size: 26px;
    font-weight: bold;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
}
.nav-container {
    width: 85%;
    margin: auto;
    position: relative;
}
.nav-back-btn {
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    background: white;
    color: #1e88e5;
    padding: 8px 16px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
}
.nav-back-btn:hover {
    background: #f0f0f0;
}

/* DOCTOR GRID */
.doctor-list {
    width: 90%;
    max-width: 1200px;
    margin: 40px auto;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
}

.doctor-card {
    background: white;
    border-radius: 20px;
    padding: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    transition: 0.3s;
    text-align: center;
}
.doctor-card:hover {
    transform: translateY(-6px);
}

.doctor-card img {
    width: 100%;
    height: 250px;
    object-fit: cover;
    border-radius: 15px;
}

.doctor-name {
    font-size: 20px;
    font-weight: 700;
    margin-top: 10px;
}
.specialty {
    font-size: 14px;
    color: #666;
    margin-bottom: 15px;
}

.book-btn {
    display: block;
    background: #1e88e5;
    color: white;
    padding: 12px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
}
.book-btn:hover {
    background: #1565c0;
}
</style>
</head>

<body>

<div class="navbar">
    <div class="nav-container">
        Find Your Doctor
        <a href="dashboard.php" class="nav-back-btn">← Back</a>
    </div>
</div>

<div class="doctor-list">
<?php while ($row = mysqli_fetch_assoc($doctors)) { ?>
    <div class="doctor-card">
        <img src="doctors/<?php echo htmlspecialchars($row['photo']); ?>">
        <div class="doctor-name"><?php echo htmlspecialchars($row['name']); ?></div>
        <div class="specialty"><?php echo htmlspecialchars($row['specialization']); ?></div>

        <!-- ✅ QUICK BOOK -->
        <a href="book.php?doctor_id=<?php echo $row['id']; ?>" class="book-btn">
            Book Appointment
        </a>
    </div>
<?php } ?>
</div>

</body>
</html>
