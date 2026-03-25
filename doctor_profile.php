<?php
session_start();
require "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$role    = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$doctor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$doctor_id) { header("Location: doctors.php"); exit; }

// Fetch doctor
$res    = mysqli_query($conn, "SELECT * FROM doctors WHERE id=$doctor_id");
$doctor = mysqli_fetch_assoc($res);
if (!$doctor) { header("Location: doctors.php"); exit; }

// Stats
$total_appts   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM appointments WHERE doctor_id=$doctor_id"))['c'];
$avg_rating    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT ROUND(AVG(rating),1) avg FROM feedback WHERE doctor_id=$doctor_id"))['avg'] ?? 0;
$total_reviews = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM feedback WHERE doctor_id=$doctor_id"))['c'];
$five_star     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM feedback WHERE doctor_id=$doctor_id AND rating=5"))['c'];

// Reviews
$reviews = mysqli_query($conn,
    "SELECT f.*, u.fullname FROM feedback f
     JOIN users u ON f.user_id=u.id
     WHERE f.doctor_id=$doctor_id
     ORDER BY f.created_at DESC LIMIT 8"
);

// Rating distribution
$dist = [];
for ($i=1;$i<=5;$i++) {
    $r = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM feedback WHERE doctor_id=$doctor_id AND rating=$i"));
    $dist[$i] = (int)$r['c'];
}
$max_dist = max($dist) ?: 1;

// Already reviewed?
$already_reviewed = false;
$chk = mysqli_query($conn,"SELECT id FROM feedback WHERE user_id=$user_id AND doctor_id=$doctor_id");
if ($chk && mysqli_num_rows($chk) > 0) $already_reviewed = true;

// Photo path
$photo = !empty($doctor['photo']) ? 'doctors/' . $doctor['photo'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dr. <?php echo htmlspecialchars($doctor['name']); ?> – Doctor App</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
    --blue:#1a73e8;--blue-dark:#0d47a1;--blue-light:#e8f0fe;
    --green:#00c853;--orange:#ff9100;--purple:#7c4dff;
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
.topbar h1{font-size:22px;font-weight:800;}
.back-btn{background:white;color:var(--blue);border:2px solid var(--blue-light);padding:9px 18px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:700;transition:.2s;}
.back-btn:hover{background:var(--blue-light);}

/* PROFILE HERO */
.profile-hero{
    background:linear-gradient(120deg,#1565c0,#1a73e8,#42a5f5);
    border-radius:20px;padding:32px;margin-bottom:24px;
    display:flex;align-items:center;gap:28px;
    box-shadow:0 8px 32px rgba(26,115,232,.25);
    position:relative;overflow:hidden;
}
.profile-hero::before{content:'';position:absolute;right:-40px;top:-40px;width:200px;height:200px;background:rgba(255,255,255,.06);border-radius:50%;}
.profile-hero::after{content:'';position:absolute;right:60px;bottom:-60px;width:150px;height:150px;background:rgba(255,255,255,.04);border-radius:50%;}
.hero-photo{
    width:120px;height:120px;border-radius:50%;object-fit:cover;
    border:4px solid rgba(255,255,255,.4);
    box-shadow:0 8px 24px rgba(0,0,0,.2);flex-shrink:0;
    background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;
    font-size:48px;position:relative;z-index:1;
}
.hero-photo img{width:100%;height:100%;border-radius:50%;object-fit:cover;}
.hero-info{position:relative;z-index:1;}
.hero-info h2{font-size:26px;font-weight:800;color:#fff;margin-bottom:4px;}
.hero-info .spec{font-size:15px;color:rgba(255,255,255,.8);margin-bottom:12px;}
.hero-info .fee-big{
    display:inline-flex;align-items:center;gap:8px;
    background:rgba(255,255,255,.2);color:#fff;
    padding:8px 18px;border-radius:20px;font-size:15px;font-weight:800;
    border:1.5px solid rgba(255,255,255,.3);
}
.hero-actions{margin-left:auto;position:relative;z-index:1;}
.btn-book-big{
    display:block;padding:14px 28px;border-radius:14px;
    background:#fff;color:var(--blue);
    text-decoration:none;font-size:15px;font-weight:800;
    box-shadow:0 6px 20px rgba(0,0,0,.15);transition:.2s;text-align:center;
}
.btn-book-big:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(0,0,0,.2);}
.btn-feedback{
    display:block;margin-top:10px;padding:10px 28px;border-radius:14px;
    background:rgba(255,255,255,.18);color:#fff;border:1.5px solid rgba(255,255,255,.35);
    text-decoration:none;font-size:13px;font-weight:700;text-align:center;transition:.2s;
}
.btn-feedback:hover{background:rgba(255,255,255,.28);}
.already-tag{font-size:11.5px;color:rgba(255,255,255,.6);text-align:center;margin-top:6px;}

/* STATS ROW */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;}
@media(max-width:900px){.stats-row{grid-template-columns:repeat(2,1fr);}}
.stat-card{background:white;border-radius:var(--card-r);padding:18px 16px;box-shadow:var(--shadow);display:flex;align-items:center;gap:14px;position:relative;overflow:hidden;}
.stat-card::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;}
.stat-card.blue::after{background:var(--blue);}
.stat-card.orange::after{background:var(--orange);}
.stat-card.green::after{background:var(--green);}
.stat-card.purple::after{background:var(--purple);}
.stat-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:19px;flex-shrink:0;}
.stat-card.blue   .stat-icon{background:#e8f0fe;color:var(--blue);}
.stat-card.orange .stat-icon{background:#fff3e0;color:var(--orange);}
.stat-card.green  .stat-icon{background:#e8f5e9;color:var(--green);}
.stat-card.purple .stat-icon{background:#ede7f6;color:var(--purple);}
.stat-num{font-size:24px;font-weight:800;line-height:1;}
.stat-lbl{font-size:12px;color:#888;font-weight:600;margin-top:3px;}

/* CONTENT GRID */
.content-grid{display:grid;grid-template-columns:1fr 320px;gap:20px;}
@media(max-width:1050px){.content-grid{grid-template-columns:1fr;}}

.card{background:white;border-radius:var(--card-r);box-shadow:var(--shadow);overflow:hidden;}
.card-header{padding:16px 22px;border-bottom:1px solid #f0f4ff;font-size:15px;font-weight:800;display:flex;align-items:center;gap:8px;}
.card-header i{color:var(--blue);}
.card-body{padding:20px;}

/* REVIEWS */
.review-item{padding:14px 0;border-bottom:1px solid #f0f4ff;}
.review-item:last-child{border-bottom:none;}
.rev-top{display:flex;align-items:center;gap:10px;margin-bottom:8px;}
.rev-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#1a73e8,#42a5f5);display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;font-weight:700;flex-shrink:0;}
.rev-name{font-size:13.5px;font-weight:700;}
.rev-date{font-size:11.5px;color:#aaa;margin-top:1px;}
.stars{display:flex;gap:2px;margin-left:auto;}
.stars i.f{color:#ffc107;font-size:13px;}
.stars i.e{color:#ddd;font-size:13px;}
.rev-msg{font-size:13.5px;color:#555;line-height:1.6;background:#f8faff;padding:10px 12px;border-radius:10px;}

/* RATING SIDEBAR */
.big-rating{text-align:center;padding:20px 0 14px;}
.big-rating .num{font-size:56px;font-weight:800;color:#1a1a2e;line-height:1;}
.big-rating .stars-big{display:flex;gap:4px;justify-content:center;margin:6px 0;}
.big-rating .stars-big i{font-size:22px;color:#ffc107;}
.big-rating .stars-big i.e{color:#ddd;}
.big-rating .sub{font-size:12.5px;color:#aaa;font-weight:600;}
.dist-row{display:flex;align-items:center;gap:8px;margin-bottom:10px;}
.dist-lbl{width:14px;font-size:12px;font-weight:700;color:#555;text-align:right;flex-shrink:0;}
.dist-bar-wrap{flex:1;background:#f0f4ff;border-radius:20px;height:8px;overflow:hidden;}
.dist-bar{height:100%;border-radius:20px;background:linear-gradient(90deg,#ffc107,#ff9100);}
.dist-cnt{width:22px;font-size:11.5px;color:#aaa;font-weight:700;}

.empty-rev{text-align:center;padding:36px;color:#bbb;}
.empty-rev i{font-size:36px;display:block;margin-bottom:10px;color:#d0d8f0;}


body.dark .review-item{border-color:#2d3748;}

@keyframes fadeUp{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}
.main>*{animation:fadeUp .4s ease both;}
.topbar{animation-delay:.04s;}.profile-hero{animation-delay:.08s;}
.stats-row{animation-delay:.12s;}.content-grid{animation-delay:.16s;}
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
        <h1><i class="fa-solid fa-user-doctor" style="color:var(--blue);margin-right:8px;"></i>Doctor Profile</h1>
        <a href="doctors.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Doctors</a>
    </div>

    <!-- HERO -->
    <div class="profile-hero">
        <div class="hero-photo">
            <?php if ($photo && file_exists($photo)): ?>
                <img src="<?php echo htmlspecialchars($photo); ?>" alt="">
            <?php else: ?>
                👨‍⚕️
            <?php endif; ?>
        </div>
        <div class="hero-info">
            <h2>Dr. <?php echo htmlspecialchars($doctor['name']); ?></h2>
            <div class="spec"><i class="fa-solid fa-stethoscope" style="margin-right:6px;"></i><?php echo htmlspecialchars($doctor['specialization']); ?></div>
            <div class="fee-big">
                <i class="fa-solid fa-indian-rupee-sign"></i>
                <?php echo $doctor['consultation_fee']; ?> Consultation Fee
            </div>
        </div>
        <div class="hero-actions">
            <a href="book.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn-book-big">
                <i class="fa-solid fa-calendar-plus"></i> Book Appointment
            </a>
            <?php if (!$already_reviewed): ?>
            <a href="feedback.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn-feedback">
                <i class="fa-solid fa-star"></i> Leave a Review
            </a>
            <?php else: ?>
            <div class="already-tag"><i class="fa-solid fa-circle-check"></i> You reviewed this doctor</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats-row">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
            <div><div class="stat-num"><?php echo $total_appts; ?></div><div class="stat-lbl">Total Appointments</div></div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon"><i class="fa-solid fa-star"></i></div>
            <div><div class="stat-num"><?php echo $avg_rating ?: '—'; ?></div><div class="stat-lbl">Average Rating</div></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="fa-solid fa-comments"></i></div>
            <div><div class="stat-num"><?php echo $total_reviews; ?></div><div class="stat-lbl">Total Reviews</div></div>
        </div>
        <div class="stat-card purple">
            <div class="stat-icon"><i class="fa-solid fa-award"></i></div>
            <div><div class="stat-num"><?php echo $five_star; ?></div><div class="stat-lbl">5-Star Reviews</div></div>
        </div>
    </div>

    <!-- CONTENT GRID -->
    <div class="content-grid">

        <!-- Reviews list -->
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-comments"></i> Patient Reviews
                <span style="margin-left:auto;font-size:12px;color:#aaa;font-weight:600;"><?php echo $total_reviews; ?> review<?php echo $total_reviews!=1?'s':''; ?></span>
            </div>
            <div class="card-body">
                <?php if ($reviews && mysqli_num_rows($reviews) > 0):
                    while ($rv = mysqli_fetch_assoc($reviews)): ?>
                <div class="review-item">
                    <div class="rev-top">
                        <div class="rev-avatar"><?php echo strtoupper(substr($rv['fullname'],0,1)); ?></div>
                        <div>
                            <div class="rev-name"><?php echo htmlspecialchars($rv['fullname']); ?></div>
                            <div class="rev-date"><?php echo date('d M Y', strtotime($rv['created_at'])); ?></div>
                        </div>
                        <div class="stars">
                            <?php for($s=1;$s<=5;$s++): ?>
                            <i class="fa-solid fa-star <?php echo $s<=$rv['rating']?'f':'e'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="rev-msg"><?php echo nl2br(htmlspecialchars($rv['message'])); ?></div>
                </div>
                <?php endwhile; else: ?>
                <div class="empty-rev">
                    <i class="fa-regular fa-star"></i>
                    No reviews yet for this doctor.<br>
                    <small>Be the first to leave a review!</small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rating breakdown -->
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-chart-bar"></i> Rating Breakdown</div>
                <div class="card-body">
                    <div class="big-rating">
                        <div class="num"><?php echo $avg_rating ?: '0'; ?></div>
                        <div class="stars-big">
                            <?php for($s=1;$s<=5;$s++): ?>
                            <i class="fa-solid fa-star <?php echo $s<=round($avg_rating)?'':'e'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="sub"><?php echo $total_reviews; ?> review<?php echo $total_reviews!=1?'s':''; ?></div>
                    </div>
                    <?php for($i=5;$i>=1;$i--): ?>
                    <div class="dist-row">
                        <div class="dist-lbl"><?php echo $i; ?></div>
                        <div class="dist-bar-wrap">
                            <div class="dist-bar" style="width:<?php echo $max_dist>0?round($dist[$i]/$max_dist*100):0; ?>%"></div>
                        </div>
                        <div class="dist-cnt"><?php echo $dist[$i]; ?></div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Quick book card -->
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-calendar-plus"></i> Book Now</div>
                <div class="card-body" style="text-align:center;">
                    <div style="font-size:40px;margin-bottom:10px;">📅</div>
                    <div style="font-size:13.5px;color:#888;margin-bottom:16px;line-height:1.6;">
                        Book an appointment with<br><strong>Dr. <?php echo htmlspecialchars($doctor['name']); ?></strong>
                    </div>
                    <div style="font-size:20px;font-weight:800;color:var(--blue);margin-bottom:16px;">
                        ₹<?php echo $doctor['consultation_fee']; ?>
                    </div>
                    <a href="book.php?doctor_id=<?php echo $doctor['id']; ?>"
                       style="display:block;padding:13px;border-radius:12px;background:linear-gradient(135deg,#1a73e8,#1565c0);color:#fff;text-decoration:none;font-size:14px;font-weight:800;box-shadow:0 4px 14px rgba(26,115,232,.35);transition:.2s;"
                       onmouseover="this.style.transform='translateY(-2px)'"
                       onmouseout="this.style.transform='translateY(0)'">
                        <i class="fa-solid fa-calendar-plus"></i> Book Appointment
                    </a>
                </div>
            </div>
        </div>

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
