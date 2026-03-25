<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];

// ── Stats ──
$total_feedback  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM feedback"))['c'];
$avg_rating      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT ROUND(AVG(rating),1) avg FROM feedback"))['avg'] ?? 0;
$five_stars      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM feedback WHERE rating=5"))['c'];
$this_month      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM feedback WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())"))['c'];

// ── Filters ──
$where   = "WHERE 1=1";
$f_rating = isset($_GET['rating']) && $_GET['rating'] !== '' ? (int)$_GET['rating'] : '';
$f_doctor = isset($_GET['doctor']) && $_GET['doctor'] !== '' ? (int)$_GET['doctor'] : '';
$f_search = isset($_GET['search']) ? trim(mysqli_real_escape_string($conn, $_GET['search'])) : '';

if ($f_rating !== '') $where .= " AND f.rating = $f_rating";
if ($f_doctor !== '') $where .= " AND f.doctor_id = $f_doctor";
if ($f_search !== '') $where .= " AND (u.fullname LIKE '%$f_search%' OR d.name LIKE '%$f_search%' OR f.message LIKE '%$f_search%')";

// ── Main query ──
$query = "
    SELECT f.*, u.fullname, d.name AS doctor_name, d.specialization, d.photo
    FROM feedback f
    JOIN users u ON f.user_id = u.id
    JOIN doctors d ON f.doctor_id = d.id
    $where
    ORDER BY f.created_at DESC
";
$result = mysqli_query($conn, $query);

// ── Doctors for filter dropdown ──
$doctors_list = mysqli_query($conn, "SELECT id, name FROM doctors ORDER BY name");

// ── Rating distribution ──
$dist = [];
for ($i = 1; $i <= 5; $i++) {
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM feedback WHERE rating=$i"));
    $dist[$i] = (int)$r['c'];
}
$max_dist = max($dist) ?: 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Feedback Dashboard – Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
    --blue:#1a73e8;--blue-dark:#0d47a1;--blue-light:#e8f0fe;
    --green:#00c853;--orange:#ff9100;--purple:#7c4dff;--red:#e53935;
    --shadow:0 4px 24px rgba(26,115,232,.10);--card-r:16px;--sidebar-w:260px;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Nunito',sans-serif;background:#f0f4ff;color:#1a1a2e;}
.wrapper{display:flex;min-height:100vh;}

/* SIDEBAR */
.sidebar{width:var(--sidebar-w);background:linear-gradient(175deg,#1e88e5 0%,#0d47a1 100%);color:#fff;padding:28px 20px;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto;}
.sidebar-logo{display:flex;align-items:center;gap:10px;margin-bottom:36px;padding:0 6px;}
.sidebar-logo .logo-icon{width:40px;height:40px;background:rgba(255,255,255,.25);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;}
.sidebar-logo span{font-family:'Poppins',sans-serif;font-weight:700;font-size:18px;}
.nav-label{font-size:10px;text-transform:uppercase;letter-spacing:1.5px;opacity:.55;padding:0 14px;margin:18px 0 8px;}
.sidebar a{display:flex;align-items:center;gap:13px;color:rgba(255,255,255,.82);text-decoration:none;padding:12px 14px;border-radius:12px;margin-bottom:4px;font-size:14.5px;font-weight:600;transition:.25s;position:relative;}
.sidebar a i{width:20px;text-align:center;font-size:15px;}
.sidebar a:hover,.sidebar a.active{background:rgba(255,255,255,.18);color:#fff;}
.sidebar a.active::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:4px;height:60%;background:#fff;border-radius:0 4px 4px 0;}
.sidebar-spacer{flex:1;}
.sidebar .logout-link{background:rgba(255,82,82,.18);color:#ffcdd2;}
.sidebar .logout-link:hover{background:rgba(255,82,82,.35);color:#fff;}

/* MAIN */
.main{flex:1;padding:28px 32px;overflow-x:hidden;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;}
.topbar h1{font-size:24px;font-weight:800;}
.back-btn{background:white;color:var(--blue);border:2px solid var(--blue-light);padding:9px 18px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:700;transition:.2s;}
.back-btn:hover{background:var(--blue-light);}

/* STAT CARDS */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:24px;}
@media(max-width:1100px){.stats-row{grid-template-columns:repeat(2,1fr);}}
.stat-card{background:white;border-radius:var(--card-r);padding:20px;box-shadow:var(--shadow);display:flex;align-items:center;gap:16px;position:relative;overflow:hidden;}
.stat-card::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;}
.stat-card.blue::after{background:var(--blue);}
.stat-card.green::after{background:var(--green);}
.stat-card.orange::after{background:var(--orange);}
.stat-card.purple::after{background:var(--purple);}
.stat-icon{width:50px;height:50px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
.stat-card.blue   .stat-icon{background:#e8f0fe;color:var(--blue);}
.stat-card.green  .stat-icon{background:#e8f5e9;color:var(--green);}
.stat-card.orange .stat-icon{background:#fff3e0;color:var(--orange);}
.stat-card.purple .stat-icon{background:#ede7f6;color:var(--purple);}
.stat-info .num{font-size:26px;font-weight:800;line-height:1;}
.stat-info .lbl{font-size:12px;color:#888;font-weight:600;margin-top:3px;}

/* CONTENT GRID */
.content-grid{display:grid;grid-template-columns:1fr 300px;gap:20px;margin-bottom:24px;}
@media(max-width:1100px){.content-grid{grid-template-columns:1fr;}}

.card{background:white;border-radius:var(--card-r);box-shadow:var(--shadow);overflow:hidden;}
.card-header{padding:16px 22px;border-bottom:1px solid #f0f4ff;font-size:15px;font-weight:800;display:flex;align-items:center;gap:8px;color:#1a1a2e;}
.card-header i{color:var(--blue);}
.card-body{padding:20px;}

/* FILTERS */
.filters{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;}
.filter-input{
    padding:10px 14px;border:2px solid #e8edf5;border-radius:10px;
    font-family:'Nunito',sans-serif;font-size:13.5px;color:#1a1a2e;
    outline:none;transition:.2s;background:white;
}
.filter-input:focus{border-color:var(--blue);}
.filter-input[type="text"]{flex:1;min-width:180px;}
select.filter-input{cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%231a73e8' d='M6 8L1 3h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;padding-right:32px;}
.btn-filter{padding:10px 18px;border-radius:10px;border:none;background:var(--blue);color:#fff;font-family:'Nunito',sans-serif;font-size:13.5px;font-weight:700;cursor:pointer;transition:.2s;display:flex;align-items:center;gap:6px;}
.btn-filter:hover{background:var(--blue-dark);}
.btn-clear{padding:10px 16px;border-radius:10px;border:2px solid #e8edf5;background:white;color:#888;font-family:'Nunito',sans-serif;font-size:13.5px;font-weight:700;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:6px;}
.btn-clear:hover{border-color:#ccc;color:#555;}

/* TABLE */
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
thead th{background:#f0f4ff;padding:12px 14px;font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#666;font-weight:700;text-align:left;white-space:nowrap;}
thead th:first-child{border-radius:10px 0 0 10px;}
thead th:last-child{border-radius:0 10px 10px 0;}
tbody td{padding:14px;font-size:13.5px;border-bottom:1px solid #f0f4ff;vertical-align:middle;}
tbody tr:last-child td{border-bottom:none;}
tbody tr:hover td{background:#f8faff;}

/* USER CELL */
.user-cell{display:flex;align-items:center;gap:10px;}
.user-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#1a73e8,#42a5f5);display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;font-weight:700;flex-shrink:0;}

/* DOCTOR CELL */
.doc-cell{display:flex;align-items:center;gap:10px;}
.doc-thumb{width:36px;height:36px;border-radius:50%;object-fit:cover;background:linear-gradient(135deg,#7c4dff,#b39ddb);display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;flex-shrink:0;overflow:hidden;}
.doc-thumb img{width:100%;height:100%;object-fit:cover;}

/* STARS */
.stars{display:flex;gap:2px;}
.stars i.filled{color:#ffc107;font-size:13px;}
.stars i.empty{color:#ddd;font-size:13px;}
.rating-num{font-size:12px;color:#aaa;margin-left:4px;font-weight:700;}

/* MESSAGE CELL */
.msg-text{max-width:240px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#444;}

/* DATE */
.date-cell{font-size:12.5px;color:#aaa;white-space:nowrap;}

/* RATING DISTRIBUTION */
.dist-row{display:flex;align-items:center;gap:10px;margin-bottom:12px;}
.dist-label{width:60px;font-size:12.5px;font-weight:700;color:#555;text-align:right;flex-shrink:0;}
.dist-bar-wrap{flex:1;background:#f0f4ff;border-radius:20px;height:10px;overflow:hidden;}
.dist-bar{height:100%;border-radius:20px;background:linear-gradient(90deg,#ffc107,#ff9100);transition:width .6s ease;}
.dist-count{width:28px;font-size:12px;color:#aaa;font-weight:700;}
.dist-stars{display:flex;gap:1px;}
.dist-stars i{font-size:10px;color:#ffc107;}

/* TOP DOCTORS */
.top-doc-item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f0f4ff;}
.top-doc-item:last-child{border-bottom:none;}
.top-rank{width:24px;height:24px;border-radius:50%;background:var(--blue-light);color:var(--blue);font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.top-rank.gold{background:#fff8e1;color:#f9a825;}
.top-rank.silver{background:#f5f5f5;color:#757575;}
.top-rank.bronze{background:#fbe9e7;color:#bf360c;}
.top-doc-name{font-size:13.5px;font-weight:700;flex:1;}
.top-doc-rating{font-size:13px;font-weight:800;color:#ffc107;}

.empty-state{text-align:center;padding:48px;color:#bbb;}
.empty-state i{font-size:44px;display:block;margin-bottom:12px;color:#d0d8f0;}

.results-info{font-size:13px;color:#888;margin-bottom:14px;font-weight:600;}
.results-info span{color:var(--blue);font-weight:800;}

@keyframes fadeUp{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}
.main > *{animation:fadeUp .4s ease both;}
.topbar{animation-delay:.05s;}.stats-row{animation-delay:.10s;}
.content-grid{animation-delay:.15s;}.table-card{animation-delay:.20s;}
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
    <a href="admin_add_doctor.php"><i class="fa-solid fa-user-plus"></i>Add Doctor</a>
    <a href="admin_bookings.php"><i class="fa-solid fa-table-list"></i>All Appointments</a>
    <div class="nav-label">More</div>
    <a href="admin_feedback.php" class="active"><i class="fa-solid fa-comments"></i>Feedback</a>
    <a href="profile.php"><i class="fa-solid fa-circle-user"></i>Profile</a>
    <div class="sidebar-spacer"></div>
    <a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i>Logout</a>
</div>

<!-- MAIN -->
<div class="main">

    <div class="topbar">
        <h1><i class="fa-solid fa-comments" style="color:var(--blue);margin-right:8px;"></i>Feedback Dashboard</h1>
        <a href="dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    </div>

    <!-- STAT CARDS -->
    <div class="stats-row">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fa-solid fa-comments"></i></div>
            <div class="stat-info">
                <div class="num"><?php echo $total_feedback; ?></div>
                <div class="lbl">Total Reviews</div>
            </div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon"><i class="fa-solid fa-star"></i></div>
            <div class="stat-info">
                <div class="num"><?php echo $avg_rating ?: '—'; ?></div>
                <div class="lbl">Average Rating</div>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="fa-solid fa-star-half-stroke"></i></div>
            <div class="stat-info">
                <div class="num"><?php echo $five_stars; ?></div>
                <div class="lbl">5-Star Reviews</div>
            </div>
        </div>
        <div class="stat-card purple">
            <div class="stat-icon"><i class="fa-solid fa-calendar-day"></i></div>
            <div class="stat-info">
                <div class="num"><?php echo $this_month; ?></div>
                <div class="lbl">This Month</div>
            </div>
        </div>
    </div>

    <!-- SIDE GRID: Distribution + Top Doctors -->
    <div class="content-grid">

        <!-- Rating Distribution -->
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-chart-bar"></i> Rating Distribution</div>
            <div class="card-body">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <div class="dist-row">
                    <div class="dist-label">
                        <div class="dist-stars">
                            <?php for ($s = 1; $s <= $i; $s++): ?><i class="fa-solid fa-star"></i><?php endfor; ?>
                        </div>
                    </div>
                    <div class="dist-bar-wrap">
                        <div class="dist-bar" style="width:<?php echo $max_dist > 0 ? round($dist[$i]/$max_dist*100) : 0; ?>%"></div>
                    </div>
                    <div class="dist-count"><?php echo $dist[$i]; ?></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Top Rated Doctors -->
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-trophy"></i> Top Rated Doctors</div>
            <div class="card-body" style="padding:10px 20px;">
                <?php
                $top = mysqli_query($conn,
                    "SELECT d.name, ROUND(AVG(f.rating),1) avg_r, COUNT(f.id) cnt
                     FROM feedback f JOIN doctors d ON f.doctor_id=d.id
                     GROUP BY f.doctor_id ORDER BY avg_r DESC, cnt DESC LIMIT 5"
                );
                $rank = 1;
                if ($top && mysqli_num_rows($top) > 0):
                    while ($t = mysqli_fetch_assoc($top)):
                        $cls = $rank === 1 ? 'gold' : ($rank === 2 ? 'silver' : ($rank === 3 ? 'bronze' : ''));
                ?>
                <div class="top-doc-item">
                    <div class="top-rank <?php echo $cls; ?>"><?php echo $rank; ?></div>
                    <div class="top-doc-name">Dr. <?php echo htmlspecialchars($t['name']); ?><br>
                        <span style="font-size:11px;color:#aaa;font-weight:600;"><?php echo $t['cnt']; ?> review<?php echo $t['cnt']!=1?'s':''; ?></span>
                    </div>
                    <div class="top-doc-rating"><i class="fa-solid fa-star" style="font-size:12px;"></i> <?php echo $t['avg_r']; ?></div>
                </div>
                <?php $rank++; endwhile; else: ?>
                <div class="empty-state" style="padding:20px;">
                    <i class="fa-regular fa-star" style="font-size:28px;"></i><br>No data yet
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- FULL FEEDBACK TABLE -->
    <div class="card table-card">
        <div class="card-header"><i class="fa-solid fa-list"></i> All Feedback
            <span style="margin-left:auto;font-size:12px;color:#aaa;font-weight:600;">
                <?php echo mysqli_num_rows($result); ?> result<?php echo mysqli_num_rows($result)!=1?'s':''; ?>
            </span>
        </div>
        <div class="card-body">

            <!-- Filters -->
            <form method="GET">
                <div class="filters">
                    <input type="text" name="search" class="filter-input"
                           placeholder="🔍  Search user, doctor or message..."
                           value="<?php echo htmlspecialchars($f_search); ?>">

                    <select name="doctor" class="filter-input">
                        <option value="">All Doctors</option>
                        <?php
                        mysqli_data_seek($doctors_list, 0);
                        while ($d = mysqli_fetch_assoc($doctors_list)):
                        ?>
                        <option value="<?php echo $d['id']; ?>"
                            <?php echo $f_doctor == $d['id'] ? 'selected' : ''; ?>>
                            Dr. <?php echo htmlspecialchars($d['name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>

                    <select name="rating" class="filter-input" style="width:150px;">
                        <option value="">All Ratings</option>
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?php echo $i; ?>" <?php echo $f_rating===$i?'selected':''; ?>>
                            <?php echo str_repeat('★',$i) . str_repeat('☆',5-$i); ?> (<?php echo $i; ?>)
                        </option>
                        <?php endfor; ?>
                    </select>

                    <button type="submit" class="btn-filter">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <a href="admin_feedback.php" class="btn-clear">
                        <i class="fa-solid fa-xmark"></i> Clear
                    </a>
                </div>
            </form>

            <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Doctor</th>
                            <th>Rating</th>
                            <th>Message</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1; mysqli_data_seek($result, 0); while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td style="color:#bbb;font-weight:700;"><?php echo $i++; ?></td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($row['fullname'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;font-size:13.5px;"><?php echo htmlspecialchars($row['fullname']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="doc-cell">
                                    <div class="doc-thumb">
                                        <?php
                                        $ph = !empty($row['photo']) ? 'doctors/' . $row['photo'] : '';
                                        if ($ph && file_exists($ph)):
                                        ?><img src="<?php echo htmlspecialchars($ph); ?>" alt=""><?php
                                        else: ?><i class="fa-solid fa-user-doctor" style="font-size:14px;"></i><?php endif; ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;font-size:13.5px;">Dr. <?php echo htmlspecialchars($row['doctor_name']); ?></div>
                                        <div style="font-size:11.5px;color:#888;"><?php echo htmlspecialchars($row['specialization']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <div class="stars">
                                        <?php for ($s=1;$s<=5;$s++): ?>
                                        <i class="fa-solid fa-star <?php echo $s<=$row['rating']?'filled':'empty'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="rating-num"><?php echo $row['rating']; ?>/5</span>
                                </div>
                            </td>
                            <td>
                                <div class="msg-text" title="<?php echo htmlspecialchars($row['message']); ?>">
                                    <?php echo htmlspecialchars($row['message']); ?>
                                </div>
                            </td>
                            <td class="date-cell">
                                <?php echo date('d M Y', strtotime($row['created_at'])); ?><br>
                                <span style="color:#ccc;"><?php echo date('h:i A', strtotime($row['created_at'])); ?></span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fa-regular fa-comments"></i>
                No feedback found<?php echo ($f_search||$f_rating||$f_doctor) ? ' for these filters.' : ' yet.'; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>

</div><!-- /main -->
</div><!-- /wrapper -->
</body>
</html>
