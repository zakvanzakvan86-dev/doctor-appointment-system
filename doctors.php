<?php
session_start();
require "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$role    = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Filter by specialization
$filter = isset($_GET['spec']) ? mysqli_real_escape_string($conn, $_GET['spec']) : '';
$where  = $filter ? "WHERE specialization='$filter'" : '';
$doctors = mysqli_query($conn, "SELECT * FROM doctors $where ORDER BY name");

// All specializations for filter
$specs_res = mysqli_query($conn, "SELECT DISTINCT specialization FROM doctors ORDER BY specialization");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Find Doctors – Doctor App</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
    --blue:#1a73e8;--blue-dark:#0d47a1;--blue-light:#e8f0fe;
    --green:#00c853;--orange:#ff9100;--shadow:0 4px 24px rgba(26,115,232,.10);
    --card-r:16px;--sidebar-w:260px;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Nunito',sans-serif;background:#f0f4ff;color:#1a1a2e;}
.wrapper{display:flex;min-height:100vh;}

/* SIDEBAR */
.sidebar{width:var(--sidebar-w);background:linear-gradient(175deg,#1e88e5,#0d47a1);color:#fff;padding:28px 20px;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto;}
.sidebar-logo{display:flex;align-items:center;gap:10px;margin-bottom:36px;padding:0 6px;}
.sidebar-logo .logo-icon{width:40px;height:40px;background:rgba(255,255,255,.25);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;}
.sidebar-logo span{font-family:'Poppins',sans-serif;font-weight:700;font-size:18px;}
.nav-label{font-size:10px;text-transform:uppercase;letter-spacing:1.5px;opacity:.55;padding:0 14px;margin:18px 0 8px;}
.sidebar a{display:flex;align-items:center;gap:13px;color:rgba(255,255,255,.82);text-decoration:none;padding:12px 14px;border-radius:12px;margin-bottom:4px;font-size:14.5px;font-weight:600;transition:.25s;position:relative;}
.sidebar a i{width:20px;text-align:center;font-size:15px;}
.sidebar a:hover,.sidebar a.active{background:rgba(255,255,255,.18);color:#fff;}
.sidebar a.active::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:4px;height:60%;background:#fff;border-radius:0 4px 4px 0;}
.sidebar-spacer{flex:1;}
.logout-link{background:rgba(255,82,82,.18);color:#ffcdd2 !important;}
.logout-link:hover{background:rgba(255,82,82,.35) !important;}

/* MAIN */
.main{flex:1;padding:28px 32px;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;}
.topbar h1{font-size:24px;font-weight:800;}
.back-btn{background:white;color:var(--blue);border:2px solid var(--blue-light);padding:9px 18px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:700;transition:.2s;}
.back-btn:hover{background:var(--blue-light);}

/* FILTER BAR */
.filter-bar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px;align-items:center;}
.filter-bar a{
    padding:8px 18px;border-radius:20px;font-size:13px;font-weight:700;
    text-decoration:none;transition:.2s;border:2px solid #e8edf5;
    background:white;color:#555;
}
.filter-bar a:hover{border-color:var(--blue);color:var(--blue);}
.filter-bar a.active{background:var(--blue);color:#fff;border-color:var(--blue);}
.filter-bar .count{font-size:12px;color:#aaa;font-weight:600;margin-left:auto;}

/* DOCTOR GRID */
.doctor-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:22px;}

.doc-card{
    background:white;border-radius:20px;overflow:hidden;
    box-shadow:var(--shadow);transition:.3s;
    border:2px solid transparent;
}
.doc-card:hover{transform:translateY(-6px);border-color:var(--blue-light);box-shadow:0 12px 36px rgba(26,115,232,.15);}

.doc-img-wrap{position:relative;height:200px;overflow:hidden;background:#e8f0fe;}
.doc-img-wrap img{width:100%;height:100%;object-fit:cover;transition:.4s;}
.doc-card:hover .doc-img-wrap img{transform:scale(1.05);}
.doc-img-wrap .no-photo{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:64px;background:linear-gradient(135deg,#e8f0fe,#c5d8f8);}
.spec-badge{
    position:absolute;bottom:10px;left:10px;
    background:rgba(26,115,232,.9);color:#fff;
    padding:4px 12px;border-radius:20px;font-size:11.5px;font-weight:700;
    backdrop-filter:blur(4px);
}

.doc-body{padding:18px;}
.doc-name{font-size:16px;font-weight:800;margin-bottom:4px;}
.doc-spec{font-size:12.5px;color:#888;margin-bottom:12px;}
.doc-fee{
    display:inline-flex;align-items:center;gap:6px;
    background:#e8f5e9;color:#2e7d32;
    padding:5px 12px;border-radius:20px;
    font-size:12.5px;font-weight:800;margin-bottom:14px;
}
.doc-actions{display:flex;gap:10px;}
.btn-profile{
    flex:1;padding:10px;border-radius:10px;text-align:center;
    text-decoration:none;font-size:13px;font-weight:700;transition:.2s;
    background:var(--blue-light);color:var(--blue);border:2px solid transparent;
}
.btn-profile:hover{background:var(--blue);color:#fff;}
.btn-book{
    flex:1;padding:10px;border-radius:10px;text-align:center;
    text-decoration:none;font-size:13px;font-weight:700;transition:.2s;
    background:linear-gradient(135deg,#1a73e8,#1565c0);color:#fff;
    box-shadow:0 4px 12px rgba(26,115,232,.3);
}
.btn-book:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(26,115,232,.4);}

.empty-state{text-align:center;padding:60px;color:#bbb;grid-column:1/-1;}
.empty-state i{font-size:48px;display:block;margin-bottom:12px;color:#d0d8f0;}



@keyframes fadeUp{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}
.doctor-grid .doc-card{animation:fadeUp .4s ease both;}
</style>
</head>
<body>
<div class="wrapper">

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="fa-solid fa-stethoscope"></i></div>
        <span>Doctor App</span>
    </div>
    <div class="nav-label">Menu</div>
    <a href="dashboard.php"><i class="fa-solid fa-house"></i>Dashboard</a>
    <a href="doctors.php" class="active"><i class="fa-solid fa-user-doctor"></i>Find Doctors</a>
    <a href="book.php"><i class="fa-solid fa-calendar-plus"></i>Book Appointment</a>
    <a href="my_appointments.php"><i class="fa-solid fa-list-check"></i>My Appointments</a>
    <div class="nav-label">More</div>
    <a href="feedback.php"><i class="fa-solid fa-star"></i>Feedback</a>
    <a href="chat.php"><i class="fa-solid fa-robot"></i>AI Assistant</a>
    <a href="profile.php"><i class="fa-solid fa-circle-user"></i>Profile</a>
    <div class="sidebar-spacer"></div>
    <a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i>Logout</a>
</div>

<!-- MAIN -->
<div class="main">
    <div class="topbar">
        <h1><i class="fa-solid fa-user-doctor" style="color:var(--blue);margin-right:8px;"></i>Find Doctors</h1>
        <a href="dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    </div>

    <!-- FILTER -->
    <div class="filter-bar">
        <a href="doctors.php" class="<?php echo !$filter ? 'active' : ''; ?>">
            <i class="fa-solid fa-list"></i> All
        </a>
        <?php mysqli_data_seek($specs_res, 0); while ($sp = mysqli_fetch_assoc($specs_res)): ?>
        <a href="?spec=<?php echo urlencode($sp['specialization']); ?>"
           class="<?php echo $filter === $sp['specialization'] ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($sp['specialization']); ?>
        </a>
        <?php endwhile; ?>
        <span class="count"><?php echo mysqli_num_rows($doctors); ?> doctor<?php echo mysqli_num_rows($doctors)!=1?'s':''; ?> found</span>
    </div>

    <!-- GRID -->
    <div class="doctor-grid">
        <?php if (mysqli_num_rows($doctors) > 0):
            $i = 0;
            while ($row = mysqli_fetch_assoc($doctors)):
                $i++;
                $photo = !empty($row['photo']) ? 'doctors/' . $row['photo'] : '';
        ?>
        <div class="doc-card" style="animation-delay:<?php echo $i * 0.06; ?>s;">
            <div class="doc-img-wrap">
                <?php if ($photo && file_exists($photo)): ?>
                    <img src="<?php echo htmlspecialchars($photo); ?>" alt="Dr. <?php echo htmlspecialchars($row['name']); ?>">
                <?php else: ?>
                    <div class="no-photo">👨‍⚕️</div>
                <?php endif; ?>
                <span class="spec-badge"><?php echo htmlspecialchars($row['specialization']); ?></span>
            </div>
            <div class="doc-body">
                <div class="doc-name">Dr. <?php echo htmlspecialchars($row['name']); ?></div>
                <div class="doc-spec"><i class="fa-solid fa-stethoscope" style="margin-right:5px;color:var(--blue);"></i><?php echo htmlspecialchars($row['specialization']); ?></div>
                <div class="doc-fee"><i class="fa-solid fa-indian-rupee-sign"></i><?php echo $row['consultation_fee']; ?> Consultation</div>
                <div class="doc-actions">
                    <a href="doctor_profile.php?id=<?php echo $row['id']; ?>" class="btn-profile">
                        <i class="fa-solid fa-circle-info"></i> Profile
                    </a>
                    <a href="book.php?doctor_id=<?php echo $row['id']; ?>" class="btn-book">
                        <i class="fa-solid fa-calendar-plus"></i> Book
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; else: ?>
        <div class="empty-state">
            <i class="fa-solid fa-user-doctor"></i>
            No doctors found<?php echo $filter ? " for \"$filter\"" : ''; ?>.
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

<script>
(function(){
    if(localStorage.getItem('darkMode')==='1') document.body.classList.add('dark');
})();
</script>
</body>
</html>
