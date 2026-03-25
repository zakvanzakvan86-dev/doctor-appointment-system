<?php
session_start();
require "db.php";
date_default_timezone_set("Asia/Kolkata");

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php"); exit;
}

$user_id = $_SESSION["user_id"];
$role    = $_SESSION['role'];
$name    = $_SESSION['username'];

// Auto-complete past appointments
$today = date("Y-m-d");
$now   = date("H:i:s");
mysqli_query($conn,
    "UPDATE appointments SET status='completed'
     WHERE user_id=$user_id
     AND status='booked'
     AND (appointment_date < '$today'
         OR (appointment_date = '$today' AND appointment_time < '$now'))"
);

// Filter
$f_status = isset($_GET['status']) && $_GET['status'] !== '' ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$where = "WHERE a.user_id = $user_id";
if ($f_status) $where .= " AND a.status='$f_status'";

$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.message,
               a.status, a.cancel_reason,
               d.name as doctor_name, d.specialization, d.photo, d.consultation_fee, d.id as doctor_id
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        $where
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$result = mysqli_query($conn, $sql);

// Stats
$total     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM appointments WHERE user_id=$user_id"))['c'];
$upcoming  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM appointments WHERE user_id=$user_id AND status='booked' AND appointment_date >= CURDATE()"))['c'];
$completed = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM appointments WHERE user_id=$user_id AND status='completed'"))['c'];
$cancelled = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM appointments WHERE user_id=$user_id AND status='cancelled'"))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Appointments – Doctor App</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
    --blue:#1a73e8;--blue-dark:#0d47a1;--blue-light:#e8f0fe;
    --green:#00c853;--orange:#ff9100;--red:#e53935;--purple:#7c4dff;
    --shadow:0 4px 24px rgba(26,115,232,.10);--card-r:16px;--sidebar-w:260px;
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

/* STATS */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;}
@media(max-width:1100px){.stats-row{grid-template-columns:repeat(2,1fr);}}
.stat-card{background:white;border-radius:var(--card-r);padding:16px;box-shadow:var(--shadow);display:flex;align-items:center;gap:12px;position:relative;overflow:hidden;cursor:pointer;transition:.2s;text-decoration:none;color:inherit;}
.stat-card:hover{transform:translateY(-3px);}
.stat-card::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;}
.stat-card.blue::after{background:var(--blue);}
.stat-card.green::after{background:var(--green);}
.stat-card.red::after{background:var(--red);}
.stat-card.purple::after{background:var(--purple);}
.stat-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.stat-card.blue   .stat-icon{background:#e8f0fe;color:var(--blue);}
.stat-card.green  .stat-icon{background:#e8f5e9;color:var(--green);}
.stat-card.red    .stat-icon{background:#fce4ec;color:var(--red);}
.stat-card.purple .stat-icon{background:#ede7f6;color:var(--purple);}
.stat-num{font-size:24px;font-weight:800;line-height:1;}
.stat-lbl{font-size:11.5px;color:#888;font-weight:600;margin-top:2px;}

/* TABS */
.status-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;}
.tab{padding:8px 18px;border-radius:20px;font-size:13px;font-weight:700;text-decoration:none;border:2px solid #e8edf5;background:white;color:#555;transition:.2s;}
.tab:hover{border-color:var(--blue);color:var(--blue);}
.tab.active{background:var(--blue);color:#fff;border-color:var(--blue);}
.tab.t-green.active{background:var(--green);border-color:var(--green);}
.tab.t-red.active{background:var(--red);border-color:var(--red);}
.tab.t-purple.active{background:var(--purple);border-color:var(--purple);}

/* APPOINTMENT CARDS */
.appt-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px;}
.appt-card{background:white;border-radius:var(--card-r);box-shadow:var(--shadow);overflow:hidden;transition:.25s;border:2px solid transparent;}
.appt-card:hover{transform:translateY(-4px);border-color:var(--blue-light);}

.appt-card-header{padding:16px 18px;display:flex;align-items:center;gap:12px;border-bottom:1px solid #f0f4ff;}
.doc-avatar{width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#1a73e8,#42a5f5);display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;flex-shrink:0;overflow:hidden;}
.doc-avatar img{width:100%;height:100%;object-fit:cover;}
.doc-details .dname{font-size:14.5px;font-weight:800;}
.doc-details .dspec{font-size:12px;color:#888;margin-top:2px;}
.appt-badge{margin-left:auto;display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:700;flex-shrink:0;}
.appt-badge::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;}
.appt-badge.booked   {background:#e8f0fe;color:#1a73e8;}
.appt-badge.completed{background:#e8f5e9;color:#2e7d32;}
.appt-badge.cancelled{background:#fce4ec;color:#c62828;}

.appt-card-body{padding:16px 18px;}
.appt-info-row{display:flex;gap:16px;margin-bottom:12px;flex-wrap:wrap;}
.appt-info-item{display:flex;align-items:center;gap:7px;font-size:13px;color:#555;}
.appt-info-item i{color:var(--blue);font-size:13px;width:14px;text-align:center;}
.appt-info-item strong{color:#1a1a2e;font-weight:700;}

.appt-message{background:#f8faff;border-radius:10px;padding:10px 12px;font-size:13px;color:#666;margin-bottom:14px;border-left:3px solid var(--blue-light);line-height:1.5;}
.cancel-reason{background:#fce4ec;border-radius:10px;padding:10px 12px;font-size:13px;color:#c62828;margin-bottom:14px;border-left:3px solid #ef9a9a;}

.fee-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.fee-badge{background:#e8f5e9;color:#2e7d32;padding:5px 12px;border-radius:20px;font-size:12.5px;font-weight:800;}

/* CANCEL FORM */
.cancel-section{border-top:1px solid #f0f4ff;padding-top:14px;}
.cancel-toggle{background:none;border:2px solid #fce4ec;color:#c62828;padding:8px 16px;border-radius:10px;font-family:'Nunito',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:.2s;width:100%;}
.cancel-toggle:hover{background:#fce4ec;}
.cancel-form-wrap{display:none;margin-top:12px;}
.cancel-form-wrap.open{display:block;}
.cancel-textarea{width:100%;padding:10px 12px;border:2px solid #e8edf5;border-radius:10px;font-family:'Nunito',sans-serif;font-size:13.5px;resize:vertical;min-height:70px;outline:none;transition:.2s;}
.cancel-textarea:focus{border-color:var(--red);}
.btn-confirm-cancel{width:100%;margin-top:8px;padding:11px;border-radius:10px;border:none;background:linear-gradient(135deg,#e53935,#c62828);color:#fff;font-family:'Nunito',sans-serif;font-size:13.5px;font-weight:800;cursor:pointer;transition:.2s;}
.btn-confirm-cancel:hover{transform:translateY(-1px);}

.btn-book-new{
    display:inline-flex;align-items:center;gap:8px;
    background:linear-gradient(135deg,#1a73e8,#1565c0);color:#fff;
    padding:11px 20px;border-radius:10px;text-decoration:none;
    font-size:13.5px;font-weight:800;box-shadow:0 4px 12px rgba(26,115,232,.3);
    transition:.2s;margin-top:16px;
}
.btn-book-new:hover{transform:translateY(-2px);}

.empty-state{text-align:center;padding:60px 20px;color:#bbb;grid-column:1/-1;}
.empty-state i{font-size:52px;display:block;margin-bottom:16px;color:#d0d8f0;}
.empty-state h3{font-size:18px;font-weight:800;margin-bottom:8px;color:#aaa;}

@keyframes fadeUp{from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:translateY(0);}}
.main>*{animation:fadeUp .4s ease both;}
.topbar{animation-delay:.04s;}.stats-row{animation-delay:.08s;}.status-tabs{animation-delay:.10s;}.appt-grid{animation-delay:.13s;}
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
    <a href="doctors.php"><i class="fa-solid fa-user-doctor"></i>Find Doctors</a>
    <a href="book.php"><i class="fa-solid fa-calendar-plus"></i>Book Appointment</a>
    <a href="my_appointments.php" class="active"><i class="fa-solid fa-list-check"></i>My Appointments</a>
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
        <h1><i class="fa-solid fa-list-check" style="color:var(--blue);margin-right:8px;"></i>My Appointments</h1>
        <a href="dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    </div>

    <!-- STATS -->
    <div class="stats-row">
        <a href="my_appointments.php" class="stat-card blue">
            <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
            <div><div class="stat-num"><?php echo $total; ?></div><div class="stat-lbl">Total</div></div>
        </a>
        <a href="?status=booked" class="stat-card green">
            <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
            <div><div class="stat-num"><?php echo $upcoming; ?></div><div class="stat-lbl">Upcoming</div></div>
        </a>
        <a href="?status=completed" class="stat-card purple">
            <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
            <div><div class="stat-num"><?php echo $completed; ?></div><div class="stat-lbl">Completed</div></div>
        </a>
        <a href="?status=cancelled" class="stat-card red">
            <div class="stat-icon"><i class="fa-solid fa-circle-xmark"></i></div>
            <div><div class="stat-num"><?php echo $cancelled; ?></div><div class="stat-lbl">Cancelled</div></div>
        </a>
    </div>

    <!-- TABS -->
    <div class="status-tabs">
        <a href="my_appointments.php" class="tab <?php echo !$f_status?'active':''; ?>">All</a>
        <a href="?status=booked"    class="tab <?php echo $f_status==='booked'?'active':''; ?>"><i class="fa-solid fa-clock"></i> Upcoming</a>
        <a href="?status=completed" class="tab t-green <?php echo $f_status==='completed'?'active':''; ?>"><i class="fa-solid fa-circle-check"></i> Completed</a>
        <a href="?status=cancelled" class="tab t-red <?php echo $f_status==='cancelled'?'active':''; ?>"><i class="fa-solid fa-circle-xmark"></i> Cancelled</a>
    </div>

    <!-- APPOINTMENTS GRID -->
    <div class="appt-grid">
    <?php if (mysqli_num_rows($result) > 0):
        $idx = 0;
        while ($row = mysqli_fetch_assoc($result)):
            $idx++;
            $photo = !empty($row['photo']) ? 'doctors/' . $row['photo'] : '';
            $is_past = $row['appointment_date'] < date('Y-m-d');
    ?>
    <div class="appt-card" style="animation:fadeUp .4s ease <?php echo $idx*0.07; ?>s both;">

        <!-- Header -->
        <div class="appt-card-header">
            <div class="doc-avatar">
                <?php if ($photo && file_exists($photo)): ?>
                    <img src="<?php echo htmlspecialchars($photo); ?>" alt="">
                <?php else: ?>
                    <i class="fa-solid fa-user-doctor"></i>
                <?php endif; ?>
            </div>
            <div class="doc-details">
                <div class="dname">Dr. <?php echo htmlspecialchars($row['doctor_name']); ?></div>
                <div class="dspec"><?php echo htmlspecialchars($row['specialization']); ?></div>
            </div>
            <span class="appt-badge <?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span>
        </div>

        <!-- Body -->
        <div class="appt-card-body">
            <div class="appt-info-row">
                <div class="appt-info-item">
                    <i class="fa-regular fa-calendar"></i>
                    <strong><?php echo date('d M Y', strtotime($row['appointment_date'])); ?></strong>
                </div>
                <div class="appt-info-item">
                    <i class="fa-regular fa-clock"></i>
                    <strong><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></strong>
                </div>
            </div>

            <div class="fee-row">
                <span class="appt-info-item" style="font-size:12.5px;"><i class="fa-solid fa-indian-rupee-sign"></i> Consultation Fee</span>
                <span class="fee-badge">₹<?php echo $row['consultation_fee']; ?></span>
            </div>

            <?php if (!empty($row['message'])): ?>
            <div class="appt-message">
                <i class="fa-regular fa-message" style="margin-right:6px;color:var(--blue);"></i>
                <?php echo htmlspecialchars($row['message']); ?>
            </div>
            <?php endif; ?>

            <?php if ($row['status'] === 'cancelled' && !empty($row['cancel_reason'])): ?>
            <div class="cancel-reason">
                <i class="fa-solid fa-circle-xmark" style="margin-right:6px;"></i>
                <strong>Reason:</strong> <?php echo htmlspecialchars($row['cancel_reason']); ?>
            </div>
            <?php endif; ?>

            <!-- Cancel option for booked appointments -->
            <?php if ($row['status'] === 'booked'): ?>
            <div class="cancel-section">
                <button class="cancel-toggle" onclick="toggleCancel(<?php echo $row['id']; ?>)">
                    <i class="fa-solid fa-circle-xmark"></i> Cancel Appointment
                </button>
                <div class="cancel-form-wrap" id="cancel-<?php echo $row['id']; ?>">
                    <form action="cancel_appointment.php" method="POST">
                        <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                        <textarea name="reason" class="cancel-textarea"
                                  placeholder="Please provide a reason for cancellation..." required></textarea>
                        <button type="submit" class="btn-confirm-cancel">
                            <i class="fa-solid fa-check"></i> Confirm Cancellation
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($row['status'] === 'completed'): ?>
            <a href="feedback.php?doctor_id=<?php echo $row['doctor_id']; ?>" style="display:inline-flex;align-items:center;gap:6px;background:#fff8e1;color:#f59e0b;padding:8px 14px;border-radius:10px;font-size:12.5px;font-weight:700;text-decoration:none;border:1.5px solid #ffe082;transition:.2s;" onmouseover="this.style.background='#ffc107';this.style.color='#fff'" onmouseout="this.style.background='#fff8e1';this.style.color='#f59e0b'">
                <i class="fa-solid fa-star"></i> Leave a Review
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endwhile; else: ?>
    <div class="empty-state">
        <i class="fa-regular fa-calendar-xmark"></i>
        <h3>No appointments found</h3>
        <p style="font-size:14px;margin-bottom:20px;">
            <?php echo $f_status ? "No $f_status appointments." : "You haven't booked any appointments yet."; ?>
        </p>
        <a href="book.php" class="btn-book-new">
            <i class="fa-solid fa-calendar-plus"></i> Book Your First Appointment
        </a>
    </div>
    <?php endif; ?>
    </div>

</div>
</div>

<script>
function toggleCancel(id) {
    const wrap = document.getElementById('cancel-' + id);
    wrap.classList.toggle('open');
}
</script>
</body>
</html>
