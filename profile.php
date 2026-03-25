<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];
$success = '';
$error   = '';

// Fetch current user
$res  = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($res);

// ── Change password ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current  = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm  = $_POST['confirm_password'];

    if (!password_verify($current, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new_pass) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new_pass !== $confirm) {
        $error = "New passwords do not match.";
    } else {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password='$hashed' WHERE id=$user_id");
        $success = "Password changed successfully!";
    }
}

// ── Update name only (email is read-only) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim(mysqli_real_escape_string($conn, $_POST['fullname']));
    mysqli_query($conn, "UPDATE users SET fullname='$fullname' WHERE id=$user_id");
    $_SESSION['username'] = $fullname;
    $success = "Profile updated!";
    $res  = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
    $user = mysqli_fetch_assoc($res);
}

$total_appts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM appointments WHERE user_id=$user_id"))['c'];
$upcoming    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM appointments WHERE user_id=$user_id AND appointment_date >= CURDATE() AND status='booked'"))['c'];
$completed   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM appointments WHERE user_id=$user_id AND status='completed'"))['c'];

// localStorage key unique per user
$ls_key = "profile_pic_user_" . $user_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Profile – Doctor App</title>
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
.sidebar{
    width:var(--sidebar-w);background:linear-gradient(175deg,#1e88e5 0%,#0d47a1 100%);
    color:#fff;padding:28px 20px;display:flex;flex-direction:column;
    position:sticky;top:0;height:100vh;overflow-y:auto;
}
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
.main{flex:1;padding:28px 32px;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;}
.topbar h1{font-size:24px;font-weight:800;}
.back-btn{background:white;color:var(--blue);border:2px solid var(--blue-light);padding:9px 18px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:700;transition:.2s;}
.back-btn:hover{background:var(--blue-light);}

.alert{padding:13px 18px;border-radius:12px;margin-bottom:22px;font-size:13.5px;font-weight:600;display:flex;align-items:center;gap:10px;}
.alert.success{background:#e8f5e9;color:#2e7d32;border:1.5px solid #a5d6a7;}
.alert.error{background:#fce4ec;color:#b71c1c;border:1.5px solid #ef9a9a;}

.profile-grid{display:grid;grid-template-columns:310px 1fr;gap:22px;align-items:start;}
@media(max-width:1000px){.profile-grid{grid-template-columns:1fr;}}

.card{background:white;border-radius:var(--card-r);box-shadow:var(--shadow);overflow:hidden;}
.card-header{padding:18px 22px;border-bottom:1px solid #f0f4ff;font-size:15px;font-weight:800;display:flex;align-items:center;gap:8px;color:#1a1a2e;}
.card-header i{color:var(--blue);}
.card-body{padding:22px;}

/* AVATAR */
.avatar-section{text-align:center;padding:30px 22px 24px;}
.avatar-wrap{position:relative;display:inline-block;margin-bottom:16px;}
.avatar-img{width:110px;height:110px;border-radius:50%;object-fit:cover;border:4px solid var(--blue-light);box-shadow:0 6px 20px rgba(26,115,232,.2);}
.avatar-placeholder{width:110px;height:110px;border-radius:50%;background:linear-gradient(135deg,#1a73e8,#42a5f5);display:flex;align-items:center;justify-content:center;font-size:44px;color:#fff;border:4px solid var(--blue-light);box-shadow:0 6px 20px rgba(26,115,232,.2);}
.avatar-edit-btn{position:absolute;bottom:4px;right:4px;width:32px;height:32px;background:var(--blue);color:#fff;border-radius:50%;border:none;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(26,115,232,.4);transition:.2s;}
.avatar-edit-btn:hover{background:var(--blue-dark);transform:scale(1.1);}
.remove-pic-btn{margin-top:8px;background:none;border:none;color:#e53935;font-size:12px;font-weight:700;cursor:pointer;font-family:'Nunito',sans-serif;display:none;}
.remove-pic-btn:hover{text-decoration:underline;}
.avatar-section h3{font-size:18px;font-weight:800;margin-bottom:4px;}
.role-badge{display:inline-block;padding:3px 12px;border-radius:20px;font-size:11.5px;font-weight:700;background:var(--blue-light);color:var(--blue);text-transform:capitalize;}

.mini-stats{display:flex;gap:12px;margin-top:18px;justify-content:center;}
.mini-stat{flex:1;background:#f0f4ff;border-radius:12px;padding:12px 8px;text-align:center;}
.mini-stat .num{font-size:22px;font-weight:800;color:var(--blue);}
.mini-stat .lbl{font-size:11px;color:#888;font-weight:600;margin-top:2px;}

/* FORM */
.form-group{margin-bottom:18px;}
.form-group label{display:block;font-size:12.5px;font-weight:700;color:#555;margin-bottom:7px;text-transform:uppercase;letter-spacing:.5px;}
.form-group input{width:100%;padding:12px 14px;border:2px solid #e8edf5;border-radius:10px;font-family:'Nunito',sans-serif;font-size:14px;color:#1a1a2e;transition:.2s;outline:none;}
.form-group input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(26,115,232,.10);}

.btn{padding:12px 24px;border-radius:10px;border:none;font-family:'Nunito',sans-serif;font-size:13.5px;font-weight:700;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:8px;}
.btn-primary{background:linear-gradient(135deg,#1a73e8,#1565c0);color:#fff;box-shadow:0 4px 14px rgba(26,115,232,.35);}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(26,115,232,.45);}
.btn-danger{background:linear-gradient(135deg,#e53935,#c62828);color:#fff;box-shadow:0 4px 14px rgba(229,57,53,.3);}
.btn-danger:hover{transform:translateY(-2px);}

.strength-bar{height:4px;border-radius:4px;margin-top:6px;background:#eee;overflow:hidden;}
.strength-fill{height:100%;border-radius:4px;transition:width .3s,background .3s;width:0%;}

/* BOOKING TABLE */
.booking-table{width:100%;border-collapse:collapse;}
.booking-table th{background:#f0f4ff;padding:11px 14px;font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#666;font-weight:700;text-align:left;}
.booking-table th:first-child{border-radius:10px 0 0 10px;}
.booking-table th:last-child{border-radius:0 10px 10px 0;}
.booking-table td{padding:13px 14px;font-size:13.5px;border-bottom:1px solid #f0f4ff;}
.booking-table tr:last-child td{border-bottom:none;}
.booking-table tr:hover td{background:#f8faff;}
.badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:11.5px;font-weight:700;text-transform:capitalize;}
.badge.booked{background:#e8f0fe;color:#1a73e8;}
.badge.completed{background:#e8f5e9;color:#2e7d32;}
.badge.cancelled{background:#fce4ec;color:#b71c1c;}
.empty-state{text-align:center;padding:36px;color:#bbb;}
.empty-state i{font-size:40px;display:block;margin-bottom:10px;}

.local-note{font-size:11.5px;color:#aaa;margin-top:8px;font-style:italic;}

@keyframes fadeUp{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}
.main > *{animation:fadeUp .4s ease both;}
.topbar{animation-delay:.05s;} .alert{animation-delay:.08s;}
.profile-grid{animation-delay:.12s;} .history-card{animation-delay:.18s;}
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
    <?php if ($role === 'user'): ?>
        <a href="doctors.php"><i class="fa-solid fa-user-doctor"></i>Find Doctors</a>
        <a href="book.php"><i class="fa-solid fa-calendar-plus"></i>Book Appointment</a>
        <a href="my_appointments.php"><i class="fa-solid fa-list-check"></i>My Appointments</a>
        <div class="nav-label">More</div>
        <a href="feedback.php"><i class="fa-solid fa-star"></i>Feedback</a>
        <a href="profile.php" class="active"><i class="fa-solid fa-circle-user"></i>Profile</a>
    <?php endif; ?>
    <?php if ($role === 'admin'): ?>
        <a href="admin_add_doctor.php"><i class="fa-solid fa-user-plus"></i>Add Doctor</a>
        <a href="admin_bookings.php"><i class="fa-solid fa-table-list"></i>All Appointments</a>
        <div class="nav-label">More</div>
        <a href="admin_feedback.php"><i class="fa-solid fa-comments"></i>Feedback</a>
        <a href="profile.php" class="active"><i class="fa-solid fa-circle-user"></i>Profile</a>
    <?php endif; ?>
    <div class="sidebar-spacer"></div>
    <a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i>Logout</a>
</div>

<!-- MAIN -->
<div class="main">

    <div class="topbar">
        <h1><i class="fa-solid fa-circle-user" style="color:var(--blue);margin-right:8px;"></i>My Profile</h1>
        <a href="dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <?php if ($success): ?>
    <div class="alert success"><i class="fa-solid fa-circle-check"></i><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert error"><i class="fa-solid fa-circle-xmark"></i><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="profile-grid">

        <!-- LEFT -->
        <div style="display:flex;flex-direction:column;gap:20px;">

            <!-- Avatar card -->
            <div class="card">
                <div class="avatar-section">
                    <div class="avatar-wrap">
                        <!-- Placeholder shown when no pic -->
                        <div class="avatar-placeholder" id="avatarPlaceholder">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <!-- Actual image shown after pick -->
                        <img src="" class="avatar-img" id="avatarImg" style="display:none;">

                        <button type="button" class="avatar-edit-btn"
                                onclick="document.getElementById('picInput').click()"
                                title="Change photo">
                            <i class="fa-solid fa-camera"></i>
                        </button>
                        <!-- Hidden file input — only reads locally, sends nothing -->
                        <input type="file" id="picInput" accept="image/*"
                               style="display:none;" onchange="loadLocalPic(this)">
                    </div>

                    <h3 id="displayName"><?php echo htmlspecialchars($user['fullname']); ?></h3>
                    <span class="role-badge"><?php echo $user['role']; ?></span>
                    <div style="margin-top:8px;font-size:13px;color:#888;">
                        <i class="fa-regular fa-envelope" style="margin-right:5px;"></i>
                        <?php echo htmlspecialchars($user['email']); ?>
                    </div>

                    <button class="remove-pic-btn" id="removePicBtn" onclick="removePic()">
                        <i class="fa-solid fa-trash-can"></i> Remove photo
                    </button>
                    <div class="local-note">📱 Photo is saved on this device only</div>

                    <div class="mini-stats">
                        <div class="mini-stat">
                            <div class="num"><?php echo $total_appts; ?></div>
                            <div class="lbl">Total</div>
                        </div>
                        <div class="mini-stat">
                            <div class="num" style="color:#00c853;"><?php echo $upcoming; ?></div>
                            <div class="lbl">Upcoming</div>
                        </div>
                        <div class="mini-stat">
                            <div class="num" style="color:#7c4dff;"><?php echo $completed; ?></div>
                            <div class="lbl">Done</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-lock"></i> Change Password</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" placeholder="Enter current password" required>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" placeholder="Min. 6 characters" required
                                   oninput="checkStrength(this.value)">
                            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" placeholder="Repeat new password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-danger"
                                style="width:100%;justify-content:center;">
                            <i class="fa-solid fa-key"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>

        </div><!-- /left -->

        <!-- RIGHT: Edit Info -->
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-pen-to-square"></i> Edit Profile Info</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="fullname"
                               value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background:#f5f7fb;color:#aaa;cursor:not-allowed;">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" value="<?php echo ucfirst($user['role']); ?>"
                               disabled style="background:#f5f7fb;color:#aaa;cursor:not-allowed;">
                    </div>
                    <button type="submit" class="btn btn-primary"
                            style="width:100%;justify-content:center;margin-top:6px;">
                        <i class="fa-solid fa-floppy-disk"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>

    </div><!-- /profile-grid -->

</div><!-- /main -->
</div><!-- /wrapper -->

<script>
const LS_KEY = <?php echo json_encode($ls_key); ?>;

// On page load — restore photo from localStorage
window.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem(LS_KEY);
    if (saved) showPic(saved);
});

// User picks a file — read it locally, store in localStorage
function loadLocalPic(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        localStorage.setItem(LS_KEY, e.target.result);
        showPic(e.target.result);
    };
    reader.readAsDataURL(input.files[0]);
}

function showPic(dataUrl) {
    const img  = document.getElementById('avatarImg');
    const ph   = document.getElementById('avatarPlaceholder');
    const rmBtn = document.getElementById('removePicBtn');
    img.src            = dataUrl;
    img.style.display  = 'block';
    ph.style.display   = 'none';
    rmBtn.style.display = 'inline-block';
}

function removePic() {
    localStorage.removeItem(LS_KEY);
    const img   = document.getElementById('avatarImg');
    const ph    = document.getElementById('avatarPlaceholder');
    const rmBtn = document.getElementById('removePicBtn');
    img.src           = '';
    img.style.display = 'none';
    ph.style.display  = 'flex';
    rmBtn.style.display = 'none';
    document.getElementById('picInput').value = '';
}

// Password strength meter
function checkStrength(val) {
    const fill = document.getElementById('strengthFill');
    let s = 0;
    if (val.length >= 6)          s++;
    if (val.length >= 10)         s++;
    if (/[A-Z]/.test(val))        s++;
    if (/[0-9]/.test(val))        s++;
    if (/[^A-Za-z0-9]/.test(val)) s++;
    fill.style.width      = (s / 5 * 100) + '%';
    fill.style.background = s <= 1 ? '#e53935' : s <= 3 ? '#ff9100' : '#00c853';
}
</script>
</body>
</html>
