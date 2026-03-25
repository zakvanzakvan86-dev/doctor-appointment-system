<?php
session_start();
require "db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: dashboard.php"); exit;
}

$success = '';

// ── Handle status update ──
if (isset($_POST['action'], $_POST['appointment_id'])) {
    $id     = (int)$_POST['appointment_id'];
    $action = $_POST['action'];
    if ($action === 'complete') {
        mysqli_query($conn, "UPDATE appointments SET status='completed' WHERE id=$id");
        $success = "Appointment marked as completed.";
    }
    if ($action === 'cancel') {
        mysqli_query($conn, "UPDATE appointments SET status='cancelled' WHERE id=$id");
        $success = "Appointment cancelled.";
    }
}

// ── Filters ──
$f_status = isset($_GET['status']) && $_GET['status'] !== '' ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$f_doctor = isset($_GET['doctor']) && $_GET['doctor'] !== '' ? (int)$_GET['doctor'] : '';
$f_date   = isset($_GET['date'])   && $_GET['date']   !== '' ? mysqli_real_escape_string($conn, $_GET['date']) : '';
$f_search = isset($_GET['search']) ? trim(mysqli_real_escape_string($conn, $_GET['search'])) : '';

$where = "WHERE 1=1";
if ($f_status !== '') $where .= " AND a.status='$f_status'";
if ($f_doctor !== '') $where .= " AND a.doctor_id=$f_doctor";
if ($f_date   !== '') $where .= " AND a.appointment_date='$f_date'";
if ($f_search !== '') $where .= " AND (u.fullname LIKE '%$f_search%' OR d.name LIKE '%$f_search%' OR a.message LIKE '%$f_search%')";

// ── Main query ──
$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.message, a.status, a.cancel_reason,
               u.fullname AS user_name, d.name AS doctor_name, d.specialization, d.photo
        FROM appointments a
        JOIN users u ON a.user_id = u.id
        JOIN doctors d ON a.doctor_id = d.id
        $where
        ORDER BY a.appointment_date ASC, a.appointment_time ASC";
$result = mysqli_query($conn, $sql);
$total  = mysqli_num_rows($result);

// ── Stats ──
$stat_booked    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM appointments WHERE status='booked'"))['c'];
$stat_completed = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM appointments WHERE status='completed'"))['c'];
$stat_cancelled = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM appointments WHERE status='cancelled'"))['c'];
$stat_today     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM appointments WHERE appointment_date=CURDATE() AND status='booked'"))['c'];

// ── Doctors for filter ──
$doctors_list = mysqli_query($conn,"SELECT id,name FROM doctors ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>All Appointments – Admin</title>
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
.main{flex:1;padding:28px 32px;overflow-x:hidden;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;}
.topbar h1{font-size:24px;font-weight:800;}
.back-btn{background:white;color:var(--blue);border:2px solid var(--blue-light);padding:9px 18px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:700;transition:.2s;}
.back-btn:hover{background:var(--blue-light);}

/* ALERT */
.alert{padding:13px 18px;border-radius:12px;margin-bottom:20px;font-size:13.5px;font-weight:600;display:flex;align-items:center;gap:10px;}
.alert.success{background:#e8f5e9;color:#2e7d32;border:1.5px solid #a5d6a7;}

/* STATS */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;}
@media(max-width:1100px){.stats-row{grid-template-columns:repeat(2,1fr);}}
.stat-card{background:white;border-radius:var(--card-r);padding:18px 16px;box-shadow:var(--shadow);display:flex;align-items:center;gap:14px;position:relative;overflow:hidden;cursor:pointer;transition:.2s;text-decoration:none;color:inherit;}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 28px rgba(26,115,232,.14);}
.stat-card::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;}
.stat-card.blue::after{background:var(--blue);}
.stat-card.green::after{background:var(--green);}
.stat-card.red::after{background:var(--red);}
.stat-card.orange::after{background:var(--orange);}
.stat-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:19px;flex-shrink:0;}
.stat-card.blue   .stat-icon{background:#e8f0fe;color:var(--blue);}
.stat-card.green  .stat-icon{background:#e8f5e9;color:var(--green);}
.stat-card.red    .stat-icon{background:#fce4ec;color:var(--red);}
.stat-card.orange .stat-icon{background:#fff3e0;color:var(--orange);}
.stat-num{font-size:26px;font-weight:800;line-height:1;}
.stat-lbl{font-size:12px;color:#888;font-weight:600;margin-top:3px;}

/* CARD */
.card{background:white;border-radius:var(--card-r);box-shadow:var(--shadow);overflow:hidden;margin-bottom:20px;}
.card-header{padding:16px 22px;border-bottom:1px solid #f0f4ff;font-size:15px;font-weight:800;display:flex;align-items:center;gap:8px;}
.card-header i{color:var(--blue);}
.card-body{padding:20px;}

/* FILTERS */
.filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;}
.filter-input{padding:10px 14px;border:2px solid #e8edf5;border-radius:10px;font-family:'Nunito',sans-serif;font-size:13.5px;color:#1a1a2e;outline:none;transition:.2s;background:white;}
.filter-input:focus{border-color:var(--blue);}
.filter-input[type="text"]{flex:1;min-width:180px;}
select.filter-input{cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%231a73e8' d='M6 8L1 3h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;padding-right:32px;}
.btn-filter{padding:10px 18px;border-radius:10px;border:none;background:var(--blue);color:#fff;font-family:'Nunito',sans-serif;font-size:13.5px;font-weight:700;cursor:pointer;transition:.2s;display:flex;align-items:center;gap:6px;}
.btn-filter:hover{background:var(--blue-dark);}
.btn-clear{padding:10px 16px;border-radius:10px;border:2px solid #e8edf5;background:white;color:#888;font-family:'Nunito',sans-serif;font-size:13.5px;font-weight:700;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:6px;}
.btn-clear:hover{border-color:#ccc;}

/* TABLE */
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
thead th{background:#f0f4ff;padding:12px 14px;font-size:11.5px;text-transform:uppercase;letter-spacing:.5px;color:#666;font-weight:700;text-align:left;white-space:nowrap;}
thead th:first-child{border-radius:10px 0 0 10px;}
thead th:last-child{border-radius:0 10px 10px 0;}
tbody td{padding:14px;font-size:13.5px;border-bottom:1px solid #f0f4ff;vertical-align:middle;}
tbody tr:last-child td{border-bottom:none;}
tbody tr:hover td{background:#f8faff;}

/* USER CELL */
.user-cell{display:flex;align-items:center;gap:10px;}
.user-av{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#7c4dff,#b39ddb);display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:800;flex-shrink:0;}

/* DOC CELL */
.doc-cell{display:flex;align-items:center;gap:10px;}
.doc-th{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#1a73e8,#42a5f5);display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;flex-shrink:0;overflow:hidden;}
.doc-th img{width:100%;height:100%;object-fit:cover;}
.doc-info .dname{font-size:13px;font-weight:700;}
.doc-info .dspec{font-size:11px;color:#888;}

/* DATE/TIME */
.date-cell{white-space:nowrap;}
.date-main{font-size:13px;font-weight:700;}
.date-sub{font-size:11.5px;color:#aaa;margin-top:2px;}

/* MESSAGE */
.msg-cell{max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#555;font-size:13px;}

/* STATUS BADGE */
.badge{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;font-size:12px;font-weight:700;text-transform:capitalize;white-space:nowrap;}
.badge.booked   {background:#e8f0fe;color:#1a73e8;}
.badge.completed{background:#e8f5e9;color:#2e7d32;}
.badge.cancelled{background:#fce4ec;color:#c62828;}
.badge::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor;display:inline-block;}

/* ACTION BUTTONS */
.action-wrap{display:flex;gap:6px;flex-wrap:wrap;}
.btn-complete{padding:7px 12px;border-radius:8px;background:#e8f5e9;color:#2e7d32;border:none;font-family:'Nunito',sans-serif;font-size:12px;font-weight:700;cursor:pointer;transition:.2s;display:flex;align-items:center;gap:5px;}
.btn-complete:hover{background:#00c853;color:#fff;}
.btn-cancel-appt{padding:7px 12px;border-radius:8px;background:#fce4ec;color:#c62828;border:none;font-family:'Nunito',sans-serif;font-size:12px;font-weight:700;cursor:pointer;transition:.2s;display:flex;align-items:center;gap:5px;}
.btn-cancel-appt:hover{background:#e53935;color:#fff;}
.done-label{font-size:12px;color:#aaa;font-style:italic;}

/* STATUS TABS */
.status-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;}
.tab{padding:8px 18px;border-radius:20px;font-size:13px;font-weight:700;text-decoration:none;border:2px solid #e8edf5;background:white;color:#555;transition:.2s;}
.tab:hover{border-color:var(--blue);color:var(--blue);}
.tab.active{background:var(--blue);color:#fff;border-color:var(--blue);}
.tab.green.active{background:var(--green);border-color:var(--green);}
.tab.red.active{background:var(--red);border-color:var(--red);}
.tab.orange.active{background:var(--orange);border-color:var(--orange);}

.results-info{font-size:13px;color:#888;font-weight:600;margin-bottom:14px;}
.results-info span{color:var(--blue);font-weight:800;}

.empty-state{text-align:center;padding:52px;color:#bbb;}
.empty-state i{font-size:48px;display:block;margin-bottom:14px;color:#d0d8f0;}

@keyframes fadeUp{from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:translateY(0);}}
.main>*{animation:fadeUp .4s ease both;}
.topbar{animation-delay:.04s;}.stats-row{animation-delay:.08s;}.card{animation-delay:.12s;}
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
    <a href="admin_add_doctor.php"><i class="fa-solid fa-user-plus"></i>Manage Doctors</a>
    <a href="admin_bookings.php" class="active"><i class="fa-solid fa-table-list"></i>All Appointments</a>
    <div class="nav-label">More</div>
    <a href="admin_feedback.php"><i class="fa-solid fa-comments"></i>Feedback</a>
    <a href="chat.php"><i class="fa-solid fa-robot"></i>AI Assistant</a>
    <a href="profile.php"><i class="fa-solid fa-circle-user"></i>Profile</a>
    <div class="sidebar-spacer"></div>
    <a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i>Logout</a>
</div>

<!-- MAIN -->
<div class="main">

    <div class="topbar">
        <h1><i class="fa-solid fa-table-list" style="color:var(--blue);margin-right:8px;"></i>All Appointments</h1>
        <a href="dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    </div>

    <?php if ($success): ?>
    <div class="alert success"><i class="fa-solid fa-circle-check"></i><?php echo $success; ?></div>
    <?php endif; ?>

    <!-- STAT CARDS (clickable filters) -->
    <div class="stats-row">
        <a href="admin_bookings.php" class="stat-card blue">
            <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
            <div><div class="stat-num"><?php echo $stat_booked; ?></div><div class="stat-lbl">Active Bookings</div></div>
        </a>
        <a href="admin_bookings.php?status=completed" class="stat-card green">
            <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
            <div><div class="stat-num"><?php echo $stat_completed; ?></div><div class="stat-lbl">Completed</div></div>
        </a>
        <a href="admin_bookings.php?status=cancelled" class="stat-card red">
            <div class="stat-icon"><i class="fa-solid fa-circle-xmark"></i></div>
            <div><div class="stat-num"><?php echo $stat_cancelled; ?></div><div class="stat-lbl">Cancelled</div></div>
        </a>
        <a href="admin_bookings.php?date=<?php echo date('Y-m-d'); ?>" class="stat-card orange">
            <div class="stat-icon"><i class="fa-solid fa-calendar-day"></i></div>
            <div><div class="stat-num"><?php echo $stat_today; ?></div><div class="stat-lbl">Today's Bookings</div></div>
        </a>
    </div>

    <!-- TABLE CARD -->
    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-list"></i> Appointments
            <span style="margin-left:auto;font-size:12px;color:#aaa;font-weight:600;"><?php echo $total; ?> result<?php echo $total!=1?'s':''; ?></span>
        </div>
        <div class="card-body">

            <!-- STATUS TABS -->
            <div class="status-tabs">
                <a href="admin_bookings.php" class="tab <?php echo !$f_status?'active':''; ?>">All</a>
                <a href="?status=booked"    class="tab <?php echo $f_status==='booked'?'active':''; ?>"><i class="fa-solid fa-clock"></i> Booked</a>
                <a href="?status=completed" class="tab green <?php echo $f_status==='completed'?'active':''; ?>"><i class="fa-solid fa-circle-check"></i> Completed</a>
                <a href="?status=cancelled" class="tab red <?php echo $f_status==='cancelled'?'active':''; ?>"><i class="fa-solid fa-circle-xmark"></i> Cancelled</a>
            </div>

            <!-- FILTERS -->
            <form method="GET">
                <?php if ($f_status): ?><input type="hidden" name="status" value="<?php echo htmlspecialchars($f_status); ?>"><?php endif; ?>
                <div class="filters">
                    <input type="text" name="search" class="filter-input"
                           placeholder="🔍 Search patient, doctor or message..."
                           value="<?php echo htmlspecialchars($f_search); ?>">

                    <select name="doctor" class="filter-input" style="min-width:180px;">
                        <option value="">All Doctors</option>
                        <?php while ($d=mysqli_fetch_assoc($doctors_list)): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo $f_doctor==$d['id']?'selected':''; ?>>
                            Dr. <?php echo htmlspecialchars($d['name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>

                    <input type="date" name="date" class="filter-input"
                           value="<?php echo htmlspecialchars($f_date); ?>">

                    <button type="submit" class="btn-filter">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <a href="admin_bookings.php" class="btn-clear">
                        <i class="fa-solid fa-xmark"></i> Clear
                    </a>
                </div>
            </form>

            <?php if ($total > 0): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date & Time</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $i=1; while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td style="color:#bbb;font-weight:700;"><?php echo $i++; ?></td>

                        <td>
                            <div class="user-cell">
                                <div class="user-av"><?php echo strtoupper(substr($row['user_name'],0,1)); ?></div>
                                <div style="font-size:13.5px;font-weight:700;"><?php echo htmlspecialchars($row['user_name']); ?></div>
                            </div>
                        </td>

                        <td>
                            <div class="doc-cell">
                                <div class="doc-th">
                                    <?php
                                    $ph = !empty($row['photo']) ? 'doctors/'.$row['photo'] : '';
                                    if ($ph && file_exists($ph)):
                                    ?><img src="<?php echo htmlspecialchars($ph); ?>" alt=""><?php
                                    else: ?><i class="fa-solid fa-user-doctor" style="font-size:13px;"></i><?php endif; ?>
                                </div>
                                <div class="doc-info">
                                    <div class="dname">Dr. <?php echo htmlspecialchars($row['doctor_name']); ?></div>
                                    <div class="dspec"><?php echo htmlspecialchars($row['specialization']); ?></div>
                                </div>
                            </div>
                        </td>

                        <td class="date-cell">
                            <div class="date-main"><?php echo date('d M Y', strtotime($row['appointment_date'])); ?></div>
                            <div class="date-sub"><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></div>
                        </td>

                        <td>
                            <div class="msg-cell" title="<?php echo htmlspecialchars($row['message']); ?>">
                                <?php echo $row['message'] ? htmlspecialchars($row['message']) : '<span style="color:#ccc;">—</span>'; ?>
                            </div>
                        </td>

                        <td>
                            <span class="badge <?php echo $row['status']; ?>"><?php echo $row['status']; ?></span>
                        </td>

                        <td>
                            <?php if ($row['status'] === 'booked'): ?>
                            <div class="action-wrap">
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                                    <button class="btn-complete" name="action" value="complete"
                                            onclick="return confirm('Mark as completed?')">
                                        <i class="fa-solid fa-circle-check"></i> Complete
                                    </button>
                                </form>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                                    <button class="btn-cancel-appt" name="action" value="cancel"
                                            onclick="return confirm('Cancel this appointment?')">
                                        <i class="fa-solid fa-circle-xmark"></i> Cancel
                                    </button>
                                </form>
                            </div>
                            <?php elseif ($row['status'] === 'completed'): ?>
                                <span class="done-label"><i class="fa-solid fa-circle-check" style="color:#00c853;"></i> Done</span>
                            <?php else: ?>
                                <span class="done-label"><i class="fa-solid fa-circle-xmark" style="color:#e53935;"></i> Cancelled</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fa-regular fa-calendar-xmark"></i>
                No appointments found<?php echo ($f_search||$f_status||$f_doctor||$f_date) ? ' for these filters.' : '.'; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>

</div>
</div>
</body>
</html>
