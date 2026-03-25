<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$name = $_SESSION['username'];
$role = $_SESSION['role'];

// DB connection
require_once 'db.php';

// Fetch stats for user
$user_id = $_SESSION['user_id'];
$total_appointments = 0;
$upcoming_appointments = 0;
$total_doctors = 0;
$total_feedback = 0;

if ($role === 'user') {
    $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE user_id = $user_id");
    if ($res) $total_appointments = mysqli_fetch_assoc($res)['cnt'];

    $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE user_id = $user_id AND appointment_date >= CURDATE() AND status = 'booked'");
    if ($res) $upcoming_appointments = mysqli_fetch_assoc($res)['cnt'];

    $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM doctors");
    if ($res) $total_doctors = mysqli_fetch_assoc($res)['cnt'];

    $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM feedback WHERE user_id = $user_id");
    if ($res) $total_feedback = mysqli_fetch_assoc($res)['cnt'];
}

if ($role === 'admin') {
    $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments");
    if ($res) $total_appointments = mysqli_fetch_assoc($res)['cnt'];

    $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE appointment_date >= CURDATE() AND status = 'booked'");
    if ($res) $upcoming_appointments = mysqli_fetch_assoc($res)['cnt'];

    $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM doctors");
    if ($res) $total_doctors = mysqli_fetch_assoc($res)['cnt'];

    $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM feedback");
    if ($res) $total_feedback = mysqli_fetch_assoc($res)['cnt'];
}

// Monthly appointments for chart (last 6 months)
$monthly_data = [];
$monthly_labels = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $label = date('M', strtotime("-$i months"));
    $monthly_labels[] = $label;
    if ($role === 'user') {
        $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE user_id = $user_id AND DATE_FORMAT(appointment_date, '%Y-%m') = '$month'");
    } else {
        $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE DATE_FORMAT(appointment_date, '%Y-%m') = '$month'");
    }
    $monthly_data[] = $res ? (int)mysqli_fetch_assoc($res)['cnt'] : 0;
}


// ── ADMIN EXTRA ANALYTICS DATA ──
if ($role === 'admin') {
    // Status breakdown
    $status_data = [];
    foreach (['booked','completed','cancelled'] as $st) {
        $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM appointments WHERE status='$st'"));
        $status_data[$st] = (int)$r['c'];
    }

    // Top 5 busiest doctors
    $top_docs_res = mysqli_query($conn,
        "SELECT d.name, COUNT(a.id) cnt FROM appointments a
         JOIN doctors d ON a.doctor_id = d.id
         GROUP BY a.doctor_id ORDER BY cnt DESC LIMIT 5");
    $top_doc_labels = []; $top_doc_data = [];
    while ($r = mysqli_fetch_assoc($top_docs_res)) {
        $top_doc_labels[] = 'Dr. ' . $r['name'];
        $top_doc_data[]   = (int)$r['cnt'];
    }

    // Daily bookings last 14 days
    $daily_labels = []; $daily_data = [];
    for ($i = 13; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        $lbl = date('d M',   strtotime("-$i days"));
        $daily_labels[] = $lbl;
        $r = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(*) c FROM appointments WHERE appointment_date='$day'"));
        $daily_data[] = (int)$r['c'];
    }

    // Avg rating per doctor (top 6)
    $rating_res = mysqli_query($conn,
        "SELECT d.name, ROUND(AVG(f.rating),1) avg_r FROM feedback f
         JOIN doctors d ON f.doctor_id=d.id
         GROUP BY f.doctor_id ORDER BY avg_r DESC LIMIT 6");
    $rating_labels = []; $rating_data = [];
    while ($r = mysqli_fetch_assoc($rating_res)) {
        $rating_labels[] = 'Dr. ' . $r['name'];
        $rating_data[]   = (float)$r['avg_r'];
    }

    // Active users per month (last 6)
    $user_monthly = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $r = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT COUNT(DISTINCT user_id) c FROM appointments WHERE DATE_FORMAT(appointment_date,'%Y-%m')='$month'"));
        $user_monthly[] = (int)$r['c'];
    }

    $total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE role='user'"))['c'];
}

// Recent appointments
if ($role === 'user') {
    $recent_query = "SELECT a.appointment_date, a.appointment_time, a.status, d.name as doctor_name, d.specialization 
                     FROM appointments a 
                     JOIN doctors d ON a.doctor_id = d.id 
                     WHERE a.user_id = $user_id 
                     ORDER BY a.appointment_date DESC LIMIT 5";
} else {
    $recent_query = "SELECT a.appointment_date, a.appointment_time, a.status, d.name as doctor_name, d.specialization, u.fullname as username 
                     FROM appointments a 
                     JOIN doctors d ON a.doctor_id = d.id 
                     JOIN users u ON a.user_id = u.id 
                     ORDER BY a.appointment_date DESC LIMIT 5";
}
$recent_result = mysqli_query($conn, $recent_query);

// ── Appointment reminder (user only) ──
$reminder = null;
if ($role === 'user') {
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $today    = date('Y-m-d');
    $rem_res  = mysqli_query($conn,
        "SELECT a.appointment_date, a.appointment_time, d.name as doctor_name, d.specialization
         FROM appointments a JOIN doctors d ON a.doctor_id=d.id
         WHERE a.user_id=$user_id AND a.status='booked'
         AND a.appointment_date IN ('$today','$tomorrow')
         ORDER BY a.appointment_date ASC LIMIT 1");
    if ($rem_res && mysqli_num_rows($rem_res) > 0) {
        $reminder = mysqli_fetch_assoc($rem_res);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard – Doctor App</title>

<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
:root {
    --blue:       #1a73e8;
    --blue-dark:  #0d47a1;
    --blue-light: #e8f0fe;
    --accent:     #ff5252;
    --green:      #00c853;
    --orange:     #ff9100;
    --purple:     #7c4dff;
    --sidebar-w:  260px;
    --card-r:     16px;
    --shadow:     0 4px 24px rgba(26,115,232,.10);
}

* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family: 'Nunito', sans-serif;
    background: #f0f4ff;
    color: #1a1a2e;
}

/* ── WRAPPER ── */
.wrapper { display:flex; min-height:100vh; }

/* ── SIDEBAR ── */
.sidebar {
    width: var(--sidebar-w);
    background: linear-gradient(175deg, #1e88e5 0%, #0d47a1 100%);
    color: #fff;
    padding: 28px 20px;
    display: flex;
    flex-direction: column;
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}

.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 36px;
    padding: 0 6px;
}
.sidebar-logo .logo-icon {
    width: 40px; height: 40px;
    background: rgba(255,255,255,.25);
    border-radius: 12px;
    display: flex; align-items:center; justify-content:center;
    font-size: 20px;
}
.sidebar-logo span {
    font-family: 'Poppins', sans-serif;
    font-weight: 700;
    font-size: 18px;
    letter-spacing: .3px;
}

.nav-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    opacity: .55;
    padding: 0 14px;
    margin: 18px 0 8px;
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 13px;
    color: rgba(255,255,255,.82);
    text-decoration: none;
    padding: 12px 14px;
    border-radius: 12px;
    margin-bottom: 4px;
    font-size: 14.5px;
    font-weight: 600;
    transition: all .25s;
    position: relative;
}
.sidebar a i { width: 20px; text-align: center; font-size: 15px; }
.sidebar a:hover,
.sidebar a.active {
    background: rgba(255,255,255,.18);
    color: #fff;
}
.sidebar a.active::before {
    content:'';
    position: absolute;
    left: 0; top: 50%;
    transform: translateY(-50%);
    width: 4px; height: 60%;
    background: #fff;
    border-radius: 0 4px 4px 0;
}

.sidebar-spacer { flex: 1; }

.sidebar .logout-link {
    background: rgba(255,82,82,.18);
    color: #ffcdd2;
}
.sidebar .logout-link:hover {
    background: rgba(255,82,82,.35);
    color: #fff;
}

/* ── MAIN ── */
.main {
    flex: 1;
    padding: 28px 32px;
    overflow-x: hidden;
}

/* ── TOPBAR ── */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 26px;
}
.topbar-left .greeting {
    font-size: 13px;
    color: #888;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.topbar-left h1 {
    font-size: 26px;
    font-weight: 800;
    color: #1a1a2e;
    margin-top: 2px;
}
.topbar-right {
    display: flex;
    align-items: center;
    gap: 14px;
}
.topbar-right .date-badge {
    background: white;
    border-radius: 10px;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 600;
    color: #555;
    box-shadow: var(--shadow);
}
.topbar-right .date-badge i { color: var(--blue); margin-right: 6px; }
.btn-logout {
    background: linear-gradient(135deg, #e53935, #ff5252);
    color: white;
    padding: 10px 20px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .3px;
    box-shadow: 0 4px 12px rgba(229,57,53,.35);
    transition: .2s;
}
.btn-logout:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(229,57,53,.45); }

/* ── HERO BANNER ── */
.hero-banner {
    background: linear-gradient(120deg, #1565c0 0%, #1a73e8 60%, #42a5f5 100%);
    border-radius: 20px;
    padding: 30px 36px;
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    overflow: hidden;
    position: relative;
    box-shadow: 0 8px 32px rgba(26,115,232,.30);
}
.hero-banner::before {
    content: '';
    position: absolute;
    right: -60px; top: -60px;
    width: 280px; height: 280px;
    background: rgba(255,255,255,.06);
    border-radius: 50%;
}
.hero-banner::after {
    content: '';
    position: absolute;
    right: 60px; bottom: -80px;
    width: 200px; height: 200px;
    background: rgba(255,255,255,.05);
    border-radius: 50%;
}
.hero-text h2 {
    font-size: 24px;
    font-weight: 800;
    color: #fff;
    margin-bottom: 8px;
}
.hero-text p {
    font-size: 14px;
    color: rgba(255,255,255,.75);
    max-width: 340px;
    line-height: 1.6;
}
.hero-actions { display: flex; gap: 12px; margin-top: 18px; flex-wrap: wrap; }
.hero-btn {
    padding: 10px 22px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: .2s;
}
.hero-btn.primary {
    background: #fff;
    color: var(--blue);
    box-shadow: 0 4px 12px rgba(0,0,0,.15);
}
.hero-btn.primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.2); }
.hero-btn.secondary {
    background: rgba(255,255,255,.18);
    color: #fff;
    border: 1.5px solid rgba(255,255,255,.35);
}
.hero-btn.secondary:hover { background: rgba(255,255,255,.28); }
.hero-illustration {
    font-size: 80px;
    opacity: .9;
    position: relative;
    z-index: 1;
    animation: float 3s ease-in-out infinite;
}
@keyframes float {
    0%,100% { transform: translateY(0); }
    50%      { transform: translateY(-10px); }
}

/* ── STAT CARDS ── */
.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 28px;
}
@media(max-width:1100px){ .stats-row { grid-template-columns: repeat(2,1fr); } }

.stat-card {
    background: white;
    border-radius: var(--card-r);
    padding: 22px 20px;
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: .25s;
    position: relative;
    overflow: hidden;
}
.stat-card::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 3px;
}
.stat-card.blue::after   { background: var(--blue); }
.stat-card.green::after  { background: var(--green); }
.stat-card.orange::after { background: var(--orange); }
.stat-card.purple::after { background: var(--purple); }

.stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 32px rgba(26,115,232,.15); }

.stat-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}
.stat-card.blue   .stat-icon { background: #e8f0fe; color: var(--blue); }
.stat-card.green  .stat-icon { background: #e8f5e9; color: var(--green); }
.stat-card.orange .stat-icon { background: #fff3e0; color: var(--orange); }
.stat-card.purple .stat-icon { background: #ede7f6; color: var(--purple); }

.stat-info .num {
    font-size: 28px;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 4px;
}
.stat-info .lbl {
    font-size: 12.5px;
    color: #888;
    font-weight: 600;
}

/* ── QUICK ACTIONS ── */
.section-title {
    font-size: 16px;
    font-weight: 800;
    color: #1a1a2e;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.section-title::before {
    content:'';
    width: 4px; height: 18px;
    background: var(--blue);
    border-radius: 4px;
    display: block;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px,1fr));
    gap: 16px;
    margin-bottom: 28px;
}

.action-card {
    background: white;
    border-radius: var(--card-r);
    padding: 22px 20px;
    box-shadow: var(--shadow);
    text-decoration: none;
    color: inherit;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: .25s;
    border: 2px solid transparent;
}
.action-card:hover {
    transform: translateY(-4px);
    border-color: var(--blue-light);
    box-shadow: 0 8px 28px rgba(26,115,232,.14);
}
.action-icon {
    width: 48px; height: 48px;
    border-radius: 13px;
    display: flex; align-items:center; justify-content:center;
    font-size: 20px;
    flex-shrink: 0;
}
.action-icon.blue   { background: #e8f0fe; color: var(--blue); }
.action-icon.green  { background: #e8f5e9; color: #2e7d32; }
.action-icon.teal   { background: #e0f2f1; color: #00796b; }
.action-icon.pink   { background: #fce4ec; color: #c2185b; }
.action-icon.indigo { background: #e8eaf6; color: #3949ab; }

.action-info h4 { font-size: 14.5px; font-weight: 700; margin-bottom: 2px; }
.action-info p  { font-size: 12px; color: #888; }

/* ── BOTTOM GRID ── */
.bottom-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 20px;
}
@media(max-width:1100px){ .bottom-grid { grid-template-columns: 1fr; } }

/* Chart card */
.chart-card {
    background: white;
    border-radius: var(--card-r);
    padding: 24px;
    box-shadow: var(--shadow);
}
.chart-card canvas { max-height: 220px; }

/* Recent table */
.recent-card {
    background: white;
    border-radius: var(--card-r);
    padding: 24px;
    box-shadow: var(--shadow);
    overflow: hidden;
}
.recent-list { list-style: none; }
.recent-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 0;
    border-bottom: 1px solid #f0f4ff;
}
.recent-item:last-child { border-bottom: none; }
.recent-avatar {
    width: 40px; height: 40px;
    background: var(--blue-light);
    border-radius: 50%;
    display: flex; align-items:center; justify-content:center;
    font-size: 17px;
    color: var(--blue);
    flex-shrink: 0;
}
.recent-info .doc { font-size: 13.5px; font-weight: 700; }
.recent-info .spec { font-size: 11.5px; color: #888; }
.recent-meta { margin-left: auto; text-align: right; }
.recent-meta .date { font-size: 11.5px; color: #aaa; }

.badge {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: capitalize;
}
.badge.confirmed  { background: #e8f5e9; color: #2e7d32; }
.badge.pending    { background: #fff3e0; color: #e65100; }
.badge.cancelled  { background: #fce4ec; color: #b71c1c; }
.badge.completed  { background: #e3f2fd; color: #0d47a1; }

/* empty state */
.empty-state {
    text-align: center;
    padding: 30px 0;
    color: #bbb;
    font-size: 13px;
}
.empty-state i { font-size: 36px; display: block; margin-bottom: 10px; }



/* REMINDER BANNER */
.reminder-banner {
    background:linear-gradient(135deg,#fff8e1,#fffde7);
    border:1.5px solid #ffe082; border-radius:14px;
    padding:14px 20px; margin-bottom:20px;
    display:flex; align-items:center; gap:14px;
    box-shadow:0 4px 14px rgba(255,193,7,.15);
    animation:fadeUp .4s ease;
}
body.dark .reminder-banner { background:#1e293b; border-color:#f59e0b; }
.reminder-icon { font-size:28px; flex-shrink:0; }
.reminder-text .rtitle { font-size:14px; font-weight:800; color:#f59e0b; }
.reminder-text .rdesc { font-size:13px; color:#666; margin-top:2px; }
body.dark .reminder-text .rdesc { color:#94a3b8; }
.reminder-close { margin-left:auto; background:none; border:none; font-size:18px; cursor:pointer; color:#aaa; }
.reminder-close:hover { color:#555; }
.reminder-link {
    margin-left:auto; background:#f59e0b; color:#fff;
    padding:8px 16px; border-radius:10px; text-decoration:none;
    font-size:12.5px; font-weight:700; transition:.2s; flex-shrink:0;
}
.reminder-link:hover { background:#d97706; }

/* fade-in animation */
@keyframes fadeUp {
    from { opacity:0; transform:translateY(18px); }
    to   { opacity:1; transform:translateY(0); }
}
.main > * { animation: fadeUp .45s ease both; }
.topbar         { animation-delay:.05s; }
.hero-banner    { animation-delay:.10s; }
.stats-row      { animation-delay:.16s; }
.quick-actions  { animation-delay:.22s; }
.bottom-grid    { animation-delay:.28s; }
</style>
</head>

<body>
<div class="wrapper">

<!-- ═══════════ SIDEBAR ═══════════ -->
<div class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="fa-solid fa-stethoscope"></i></div>
        <span>Doctor App</span>
    </div>

    <div class="nav-label">Menu</div>
    <a href="dashboard.php" class="active"><i class="fa-solid fa-house"></i>Dashboard</a>

    <?php if ($role === 'user'): ?>
        <a href="doctors.php"><i class="fa-solid fa-user-doctor"></i>Find Doctors</a>
        <a href="book.php"><i class="fa-solid fa-calendar-plus"></i>Book Appointment</a>
        <a href="my_appointments.php"><i class="fa-solid fa-list-check"></i>My Appointments</a>
        <div class="nav-label">More</div>
        <a href="feedback.php"><i class="fa-solid fa-star"></i>Feedback</a>
        <a href="chat.php"><i class="fa-solid fa-robot"></i>AI Assistant</a>
        <a href="profile.php"><i class="fa-solid fa-circle-user"></i>Profile</a>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
        <a href="admin_add_doctor.php"><i class="fa-solid fa-user-plus"></i>Manage Doctors</a>
        <a href="admin_bookings.php"><i class="fa-solid fa-table-list"></i>All Appointments</a>
        <div class="nav-label">More</div>
        <a href="admin_feedback.php"><i class="fa-solid fa-comments"></i>Feedback</a>
        <a href="chat.php"><i class="fa-solid fa-robot"></i>AI Assistant</a>
        <a href="profile.php"><i class="fa-solid fa-circle-user"></i>Profile</a>
    <?php endif; ?>

    <div class="sidebar-spacer"></div>
    <a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i>Logout</a>
</div>

<!-- ═══════════ MAIN ═══════════ -->
<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left">
            <div class="greeting">Good <?php
                $h = (int)date('H');
                echo $h < 12 ? 'Morning' : ($h < 17 ? 'Afternoon' : 'Evening');
            ?></div>
            <h1><?php echo htmlspecialchars($name); ?> 👋</h1>
        </div>
        <div class="topbar-right">
            <div class="date-badge">
                <i class="fa-regular fa-calendar"></i>
                <?php echo date('D, d M Y'); ?>
            </div>
            <a class="btn-logout" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>

    <!-- REMINDER BANNER -->
    <?php if ($reminder): ?>
    <?php
        $is_today    = $reminder['appointment_date'] === date('Y-m-d');
        $when        = $is_today ? 'Today' : 'Tomorrow';
        $time_fmt    = date('h:i A', strtotime($reminder['appointment_time']));
    ?>
    <div class="reminder-banner" id="reminderBanner">
        <div class="reminder-icon">🔔</div>
        <div class="reminder-text">
            <div class="rtitle">Appointment <?php echo $when; ?>!</div>
            <div class="rdesc">
                Dr. <?php echo htmlspecialchars($reminder['doctor_name']); ?> ·
                <?php echo htmlspecialchars($reminder['specialization']); ?> ·
                <?php echo $time_fmt; ?>
            </div>
        </div>
        <a href="my_appointments.php" class="reminder-link"><i class="fa-solid fa-calendar-check"></i> View</a>
        <button class="reminder-close" onclick="document.getElementById('reminderBanner').style.display='none'">✕</button>
    </div>
    <?php endif; ?>

    <!-- HERO BANNER -->
    <div class="hero-banner">
        <div class="hero-text">
            <h2>Welcome back, <?php echo htmlspecialchars($name); ?>!</h2>
            <p>
                <?php if ($role === 'user'): ?>
                    Manage your health easily. Book appointments, view doctors, and give feedback — all in one place.
                <?php else: ?>
                    You have full control over the Doctor App. Monitor bookings, manage doctors, and review feedback.
                <?php endif; ?>
            </p>
            <div class="hero-actions">
                <?php if ($role === 'user'): ?>
                    <a href="book.php" class="hero-btn primary"><i class="fa-solid fa-calendar-plus"></i> Quick Book</a>
                    <a href="doctors.php" class="hero-btn secondary"><i class="fa-solid fa-user-doctor"></i> View Doctors</a>
                <?php else: ?>
                    <a href="admin_bookings.php" class="hero-btn primary"><i class="fa-solid fa-table-list"></i> All Bookings</a>
                    <a href="admin_add_doctor.php" class="hero-btn secondary"><i class="fa-solid fa-user-plus"></i> Add Doctor</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="hero-illustration">🏥</div>
    </div>

    <!-- STAT CARDS -->
    <div class="stats-row">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="stat-info">
                <div class="num"><?php echo $total_appointments; ?></div>
                <div class="lbl"><?php echo $role === 'user' ? 'My Appointments' : 'Total Appointments'; ?></div>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
            <div class="stat-info">
                <div class="num"><?php echo $upcoming_appointments; ?></div>
                <div class="lbl">Upcoming</div>
            </div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon"><i class="fa-solid fa-user-doctor"></i></div>
            <div class="stat-info">
                <div class="num"><?php echo $total_doctors; ?></div>
                <div class="lbl">Available Doctors</div>
            </div>
        </div>
        <div class="stat-card purple">
            <div class="stat-icon"><i class="fa-solid fa-star"></i></div>
            <div class="stat-info">
                <div class="num"><?php echo $total_feedback; ?></div>
                <div class="lbl"><?php echo $role === 'user' ? 'My Reviews' : 'Total Reviews'; ?></div>
            </div>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="section-title">Quick Actions</div>
    <div class="quick-actions">
        <?php if ($role === 'user'): ?>
        <a href="doctors.php" class="action-card">
            <div class="action-icon blue"><i class="fa-solid fa-user-doctor"></i></div>
            <div class="action-info"><h4>Find Doctors</h4><p>View available doctors</p></div>
        </a>
        <a href="book.php" class="action-card">
            <div class="action-icon green"><i class="fa-solid fa-calendar-plus"></i></div>
            <div class="action-info"><h4>Book Appointment</h4><p>Select date &amp; time</p></div>
        </a>
        <a href="my_appointments.php" class="action-card">
            <div class="action-icon teal"><i class="fa-solid fa-list-check"></i></div>
            <div class="action-info"><h4>My Appointments</h4><p>View &amp; cancel bookings</p></div>
        </a>
        <a href="feedback.php" class="action-card">
            <div class="action-icon pink"><i class="fa-solid fa-star"></i></div>
            <div class="action-info"><h4>Give Feedback</h4><p>Rate doctor &amp; share experience</p></div>
        </a>
        </a>
        <a href="chat.php" class="action-card">
            <div class="action-icon blue"><i class="fa-solid fa-robot"></i></div>
            <div class="action-info"><h4>AI Assistant</h4><p>Chat with MediBot</p></div>
        <a href="profile.php" class="action-card">
            <div class="action-icon indigo"><i class="fa-solid fa-circle-user"></i></div>
            <div class="action-info"><h4>Profile</h4><p>View &amp; edit your info</p></div>
        </a>
        <?php else: ?>
        <a href="admin_add_doctor.php" class="action-card">
            <div class="action-icon blue"><i class="fa-solid fa-user-plus"></i></div>
            <div class="action-info"><h4>Add Doctor</h4><p>Manage doctor details</p></div>
        </a>
        <a href="admin_bookings.php" class="action-card">
            <div class="action-icon green"><i class="fa-solid fa-table-list"></i></div>
            <div class="action-info"><h4>All Appointments</h4><p>Monitor all bookings</p></div>
        </a>
        <a href="admin_feedback.php" class="action-card">
            <div class="action-icon pink"><i class="fa-solid fa-comments"></i></div>
            <div class="action-info"><h4>View Feedback</h4><p>Check user reviews</p></div>
        </a>
        <?php endif; ?>
    </div>

    <!-- ══════════ USER: simple bottom grid ══════════ -->
    <?php if ($role === 'user'): ?>
    <div class="bottom-grid">
        <div class="chart-card">
            <div class="section-title">My Appointment Activity (Last 6 Months)</div>
            <canvas id="apptChart"></canvas>
        </div>
        <div class="recent-card">
            <div class="section-title">Recent Appointments</div>
            <?php if ($recent_result && mysqli_num_rows($recent_result) > 0): ?>
            <ul class="recent-list">
                <?php while ($row = mysqli_fetch_assoc($recent_result)): ?>
                <li class="recent-item">
                    <div class="recent-avatar"><i class="fa-solid fa-stethoscope"></i></div>
                    <div class="recent-info">
                        <div class="doc">Dr. <?php echo htmlspecialchars($row['doctor_name']); ?></div>
                        <div class="spec"><?php echo htmlspecialchars($row['specialization']); ?></div>
                    </div>
                    <div class="recent-meta">
                        <span class="badge <?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span>
                        <div class="date"><?php echo date('d M', strtotime($row['appointment_date'])); ?></div>
                    </div>
                </li>
                <?php endwhile; ?>
            </ul>
            <?php else: ?>
            <div class="empty-state"><i class="fa-regular fa-calendar-xmark"></i>No appointments yet</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══════════ ADMIN: full analytics dashboard ══════════ -->
    <?php if ($role === 'admin'): ?>

    <!-- ROW 1: Line chart (monthly) + Doughnut (status) -->
    <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;margin-bottom:20px;">

        <div class="chart-card">
            <div class="section-title">Monthly Appointments (Last 6 Months)</div>
            <canvas id="apptChart" style="max-height:220px;"></canvas>
        </div>

        <div class="chart-card" style="display:flex;flex-direction:column;">
            <div class="section-title">Appointment Status</div>
            <div style="flex:1;display:flex;align-items:center;justify-content:center;">
                <canvas id="statusChart" style="max-height:200px;max-width:200px;"></canvas>
            </div>
            <div style="display:flex;gap:10px;justify-content:center;margin-top:12px;flex-wrap:wrap;">
                <span style="font-size:12px;font-weight:700;color:#1a73e8;"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#1a73e8;margin-right:4px;"></span>Booked</span>
                <span style="font-size:12px;font-weight:700;color:#00c853;"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#00c853;margin-right:4px;"></span>Completed</span>
                <span style="font-size:12px;font-weight:700;color:#e53935;"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#e53935;margin-right:4px;"></span>Cancelled</span>
            </div>
        </div>
    </div>

    <!-- ROW 2: Daily bar chart + Doctor ratings -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

        <div class="chart-card">
            <div class="section-title">Daily Bookings (Last 14 Days)</div>
            <canvas id="dailyChart" style="max-height:200px;"></canvas>
        </div>

        <div class="chart-card">
            <div class="section-title">Doctor Ratings (Avg)</div>
            <canvas id="ratingChart" style="max-height:200px;"></canvas>
        </div>

    </div>

    <!-- ROW 3: Busiest doctors bar + Recent table -->
    <div style="display:grid;grid-template-columns:1fr 360px;gap:20px;margin-bottom:20px;">

        <div class="chart-card">
            <div class="section-title">Top 5 Busiest Doctors</div>
            <canvas id="topDocChart" style="max-height:220px;"></canvas>
        </div>

        <div class="recent-card">
            <div class="section-title">Recent Bookings</div>
            <?php if ($recent_result && mysqli_num_rows($recent_result) > 0): ?>
            <ul class="recent-list">
                <?php while ($row = mysqli_fetch_assoc($recent_result)): ?>
                <li class="recent-item">
                    <div class="recent-avatar"><i class="fa-solid fa-stethoscope"></i></div>
                    <div class="recent-info">
                        <div class="doc">Dr. <?php echo htmlspecialchars($row['doctor_name']); ?></div>
                        <div class="spec"><?php echo htmlspecialchars($row['specialization']); ?> · <?php echo htmlspecialchars($row['username']); ?></div>
                    </div>
                    <div class="recent-meta">
                        <span class="badge <?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span>
                        <div class="date"><?php echo date('d M', strtotime($row['appointment_date'])); ?></div>
                    </div>
                </li>
                <?php endwhile; ?>
            </ul>
            <?php else: ?>
            <div class="empty-state"><i class="fa-regular fa-calendar-xmark"></i>No appointments yet</div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ROW 4: Active users per month -->
    <div style="margin-bottom:20px;">
        <div class="chart-card">
            <div class="section-title">Active Users Per Month (Last 6 Months)</div>
            <canvas id="userChart" style="max-height:180px;"></canvas>
        </div>
    </div>

    <?php endif; ?>

</div><!-- /main -->
</div><!-- /wrapper -->

<script>
// ══ Helper ══
function makeGradient(ctx, color1, color2) {
    const g = ctx.createLinearGradient(0,0,0,220);
    g.addColorStop(0, color1); g.addColorStop(1, color2); return g;
}
const chartDefaults = {
    responsive: true,
    plugins: {
        legend: { display: false },
        tooltip: { backgroundColor:'#1a1a2e', padding:10,
            titleFont:{family:'Nunito',weight:'700',size:13},
            bodyFont:{family:'Nunito',size:12} }
    },
    scales: {
        x: { grid:{display:false}, ticks:{font:{family:'Nunito',weight:'600',size:11},color:'#aaa'} },
        y: { beginAtZero:true, grid:{color:'#f0f4ff'}, ticks:{font:{family:'Nunito',weight:'600',size:11},color:'#aaa',precision:0} }
    }
};

<?php if ($role === 'user'): ?>
// ── USER: monthly line chart ──
(function(){
    const ctx = document.getElementById('apptChart').getContext('2d');
    new Chart(ctx, {
        type:'line',
        data:{
            labels: <?php echo json_encode($monthly_labels); ?>,
            datasets:[{
                label:'Appointments',
                data: <?php echo json_encode($monthly_data); ?>,
                borderColor:'#1a73e8',
                backgroundColor: makeGradient(ctx,'rgba(26,115,232,.28)','rgba(26,115,232,0)'),
                borderWidth:2.5, pointBackgroundColor:'#fff', pointBorderColor:'#1a73e8',
                pointBorderWidth:2.5, pointRadius:5, tension:0.45, fill:true
            }]
        },
        options: chartDefaults
    });
})();
<?php endif; ?>

<?php if ($role === 'admin'): ?>
// ── ADMIN CHARTS ──

// 1. Monthly appointments line
(function(){
    const ctx = document.getElementById('apptChart').getContext('2d');
    new Chart(ctx, {
        type:'line',
        data:{
            labels: <?php echo json_encode($monthly_labels); ?>,
            datasets:[{
                label:'Appointments',
                data: <?php echo json_encode($monthly_data); ?>,
                borderColor:'#1a73e8',
                backgroundColor: makeGradient(ctx,'rgba(26,115,232,.25)','rgba(26,115,232,0)'),
                borderWidth:2.5, pointBackgroundColor:'#fff', pointBorderColor:'#1a73e8',
                pointBorderWidth:2.5, pointRadius:5, tension:0.45, fill:true
            }]
        },
        options:{...chartDefaults}
    });
})();

// 2. Status doughnut
(function(){
    const ctx = document.getElementById('statusChart').getContext('2d');
    new Chart(ctx, {
        type:'doughnut',
        data:{
            labels:['Booked','Completed','Cancelled'],
            datasets:[{
                data:[<?php echo $status_data['booked']??0; ?>, <?php echo $status_data['completed']??0; ?>, <?php echo $status_data['cancelled']??0; ?>],
                backgroundColor:['#1a73e8','#00c853','#e53935'],
                borderWidth:0, hoverOffset:6
            }]
        },
        options:{
            responsive:true, cutout:'72%',
            plugins:{
                legend:{display:false},
                tooltip:{backgroundColor:'#1a1a2e',padding:10,
                    titleFont:{family:'Nunito',weight:'700',size:13},
                    bodyFont:{family:'Nunito',size:12}}
            }
        }
    });
})();

// 3. Daily bar chart
(function(){
    const ctx = document.getElementById('dailyChart').getContext('2d');
    new Chart(ctx, {
        type:'bar',
        data:{
            labels: <?php echo json_encode($daily_labels); ?>,
            datasets:[{
                label:'Bookings',
                data: <?php echo json_encode($daily_data); ?>,
                backgroundColor:'rgba(26,115,232,.75)',
                borderRadius:6, borderSkipped:false
            }]
        },
        options:{...chartDefaults}
    });
})();

// 4. Doctor avg rating horizontal bar
(function(){
    const ctx = document.getElementById('ratingChart').getContext('2d');
    new Chart(ctx, {
        type:'bar',
        data:{
            labels: <?php echo json_encode($rating_labels); ?>,
            datasets:[{
                label:'Avg Rating',
                data: <?php echo json_encode($rating_data); ?>,
                backgroundColor:['#ffc107','#ff9100','#00c853','#1a73e8','#7c4dff','#e53935'],
                borderRadius:6, borderSkipped:false
            }]
        },
        options:{
            indexAxis:'y',
            responsive:true,
            plugins:{
                legend:{display:false},
                tooltip:{backgroundColor:'#1a1a2e',padding:10,
                    titleFont:{family:'Nunito',weight:'700',size:13},
                    bodyFont:{family:'Nunito',size:12}}
            },
            scales:{
                x:{min:0, max:5, grid:{color:'#f0f4ff'},
                   ticks:{font:{family:'Nunito',weight:'600',size:11},color:'#aaa'}},
                y:{grid:{display:false},
                   ticks:{font:{family:'Nunito',weight:'600',size:11},color:'#555'}}
            }
        }
    });
})();

// 5. Top busiest doctors bar
(function(){
    const ctx = document.getElementById('topDocChart').getContext('2d');
    new Chart(ctx, {
        type:'bar',
        data:{
            labels: <?php echo json_encode($top_doc_labels); ?>,
            datasets:[{
                label:'Appointments',
                data: <?php echo json_encode($top_doc_data); ?>,
                backgroundColor: makeGradient(ctx,'rgba(124,77,255,.8)','rgba(26,115,232,.6)'),
                borderRadius:8, borderSkipped:false
            }]
        },
        options:{...chartDefaults}
    });
})();

// 6. Active users per month line
(function(){
    const ctx = document.getElementById('userChart').getContext('2d');
    new Chart(ctx, {
        type:'line',
        data:{
            labels: <?php echo json_encode($monthly_labels); ?>,
            datasets:[{
                label:'Active Users',
                data: <?php echo json_encode($user_monthly); ?>,
                borderColor:'#00c853',
                backgroundColor: makeGradient(ctx,'rgba(0,200,83,.20)','rgba(0,200,83,0)'),
                borderWidth:2.5, pointBackgroundColor:'#fff', pointBorderColor:'#00c853',
                pointBorderWidth:2.5, pointRadius:5, tension:0.45, fill:true
            }]
        },
        options:{...chartDefaults}
    });
})();

<?php endif; ?>
</script>

</body>
</html>
