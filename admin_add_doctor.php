<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php"); exit;
}

$success = '';
$error   = '';

// ── DELETE DOCTOR ──
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    // Get photo to delete file
    $dr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo FROM doctors WHERE id=$del_id"));
    if ($dr && !empty($dr['photo']) && file_exists('doctors/' . $dr['photo'])) {
        unlink('doctors/' . $dr['photo']);
    }
    // Delete related records
    mysqli_query($conn, "DELETE FROM feedback WHERE doctor_id=$del_id");
    mysqli_query($conn, "DELETE FROM appointments WHERE doctor_id=$del_id");
    mysqli_query($conn, "DELETE FROM doctors WHERE id=$del_id");
    $success = "Doctor removed successfully.";
}

// ── EDIT DOCTOR (fetch for form) ──
$edit_doctor = null;
if (isset($_GET['edit'])) {
    $edit_id     = (int)$_GET['edit'];
    $edit_res    = mysqli_query($conn, "SELECT * FROM doctors WHERE id=$edit_id");
    $edit_doctor = mysqli_fetch_assoc($edit_res);
}

// ── UPDATE DOCTOR ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_doctor'])) {
    $id   = (int)$_POST['doctor_id'];
    $name = trim(mysqli_real_escape_string($conn, $_POST['name']));
    $spec = trim(mysqli_real_escape_string($conn, $_POST['specialization']));
    $fee  = (int)$_POST['consultation_fee'];

    $photo_sql = '';
    if (!empty($_FILES['photo']['name'])) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext     = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $_FILES['photo']['size'] <= 2*1024*1024) {
            if (!is_dir('doctors/')) mkdir('doctors/', 0755, true);
            // Delete old photo
            $old = mysqli_fetch_assoc(mysqli_query($conn,"SELECT photo FROM doctors WHERE id=$id"));
            if ($old && !empty($old['photo']) && file_exists('doctors/'.$old['photo'])) unlink('doctors/'.$old['photo']);
            $fname = time() . '_' . $id . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], 'doctors/' . $fname);
            $photo_sql = ", photo='$fname'";
        } else {
            $error = "Invalid image. Use JPG/PNG/WEBP under 2MB.";
        }
    }

    if (empty($error)) {
        mysqli_query($conn, "UPDATE doctors SET name='$name', specialization='$spec', consultation_fee=$fee $photo_sql WHERE id=$id");
        $success = "Doctor updated successfully!";
        $edit_doctor = null;
    }
}

// ── ADD DOCTOR ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_doctor'])) {
    $name = trim(mysqli_real_escape_string($conn, $_POST['name']));
    $spec = trim(mysqli_real_escape_string($conn, $_POST['specialization']));
    $fee  = (int)$_POST['consultation_fee'];

    if (empty($name) || empty($spec) || $fee <= 0) {
        $error = "Please fill all fields correctly.";
    } else {
        $photoName = '';
        if (!empty($_FILES['photo']['name'])) {
            $allowed = ['jpg','jpeg','png','gif','webp'];
            $ext     = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = "Only JPG, PNG, WEBP images allowed.";
            } elseif ($_FILES['photo']['size'] > 2*1024*1024) {
                $error = "Image must be under 2MB.";
            } else {
                if (!is_dir('doctors/')) mkdir('doctors/', 0755, true);
                $photoName = time() . '_' . preg_replace('/\s+/', '_', $name) . '.' . $ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], 'doctors/' . $photoName);
            }
        }

        if (empty($error)) {
            $sql = "INSERT INTO doctors (name, specialization, consultation_fee, photo)
                    VALUES ('$name', '$spec', $fee, '$photoName')";
            if (mysqli_query($conn, $sql)) {
                $success = "Dr. $name added successfully!";
            } else {
                $error = "Database error: " . mysqli_error($conn);
            }
        }
    }
}

// ── Fetch all doctors ──
$doctors = mysqli_query($conn, "SELECT d.*, 
    (SELECT COUNT(*) FROM appointments WHERE doctor_id=d.id) as appt_count,
    (SELECT ROUND(AVG(rating),1) FROM feedback WHERE doctor_id=d.id) as avg_rating
    FROM doctors d ORDER BY d.name");

$total_doctors = mysqli_num_rows($doctors);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Manage Doctors – Admin</title>
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

/* ALERTS */
.alert{padding:13px 18px;border-radius:12px;margin-bottom:22px;font-size:13.5px;font-weight:600;display:flex;align-items:center;gap:10px;}
.alert.success{background:#e8f5e9;color:#2e7d32;border:1.5px solid #a5d6a7;}
.alert.error  {background:#fce4ec;color:#b71c1c;border:1.5px solid #ef9a9a;}

/* STATS */
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;}
.stat-card{background:white;border-radius:var(--card-r);padding:18px;box-shadow:var(--shadow);display:flex;align-items:center;gap:14px;position:relative;overflow:hidden;}
.stat-card::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;}
.stat-card.blue::after{background:var(--blue);}
.stat-card.green::after{background:var(--green);}
.stat-card.orange::after{background:var(--orange);}
.stat-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:19px;flex-shrink:0;}
.stat-card.blue   .stat-icon{background:#e8f0fe;color:var(--blue);}
.stat-card.green  .stat-icon{background:#e8f5e9;color:var(--green);}
.stat-card.orange .stat-icon{background:#fff3e0;color:var(--orange);}
.stat-num{font-size:26px;font-weight:800;}
.stat-lbl{font-size:12px;color:#888;font-weight:600;margin-top:2px;}

/* MAIN GRID */
.page-grid{display:grid;grid-template-columns:380px 1fr;gap:22px;align-items:start;}
@media(max-width:1100px){.page-grid{grid-template-columns:1fr;}}

/* FORM CARD */
.card{background:white;border-radius:var(--card-r);box-shadow:var(--shadow);overflow:hidden;}
.card-header{padding:18px 22px;border-bottom:1px solid #f0f4ff;font-size:15px;font-weight:800;display:flex;align-items:center;gap:8px;}
.card-header i{color:var(--blue);}
.card-header.edit-mode{background:linear-gradient(135deg,#fff3e0,#fff8e1);border-bottom-color:#ffe082;}
.card-header.edit-mode i{color:var(--orange);}
.card-body{padding:22px;}

.form-group{margin-bottom:16px;}
.form-group label{display:block;font-size:12px;font-weight:700;color:#555;margin-bottom:7px;text-transform:uppercase;letter-spacing:.5px;}
.form-group input{width:100%;padding:12px 14px;border:2px solid #e8edf5;border-radius:10px;font-family:'Nunito',sans-serif;font-size:14px;color:#1a1a2e;outline:none;transition:.2s;}
.form-group input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(26,115,232,.08);}

/* PHOTO UPLOAD */
.photo-upload-label{display:block;border:2px dashed #c5d8f8;border-radius:12px;padding:18px;text-align:center;cursor:pointer;transition:.2s;background:#f8faff;}
.photo-upload-label:hover{border-color:var(--blue);background:var(--blue-light);}
.photo-upload-label i{font-size:26px;color:var(--blue);display:block;margin-bottom:6px;}
.photo-upload-label p{font-size:12.5px;color:#888;}
.photo-preview-wrap{display:none;margin-top:10px;text-align:center;}
.photo-preview-wrap img{max-height:100px;border-radius:10px;border:2px solid var(--blue-light);}

/* CURRENT PHOTO in edit */
.current-photo{display:flex;align-items:center;gap:10px;background:#f0f4ff;border-radius:10px;padding:10px;margin-bottom:12px;}
.current-photo img{width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid var(--blue-light);}
.current-photo span{font-size:12.5px;color:#555;font-weight:600;}

.btn{padding:13px 20px;border-radius:10px;border:none;font-family:'Nunito',sans-serif;font-size:13.5px;font-weight:700;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;gap:8px;width:100%;justify-content:center;}
.btn-primary{background:linear-gradient(135deg,#1a73e8,#1565c0);color:#fff;box-shadow:0 4px 14px rgba(26,115,232,.35);}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(26,115,232,.4);}
.btn-warning{background:linear-gradient(135deg,#ff9100,#f57c00);color:#fff;box-shadow:0 4px 14px rgba(255,145,0,.3);}
.btn-warning:hover{transform:translateY(-2px);}
.btn-cancel{margin-top:10px;background:#f5f7fb;color:#888;box-shadow:none;}
.btn-cancel:hover{background:#e8edf5;color:#555;transform:none;}

/* DOCTOR TABLE */
.section-title{font-size:15px;font-weight:800;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.section-title::before{content:'';width:4px;height:18px;background:var(--blue);border-radius:4px;display:block;}

.search-wrap{position:relative;margin-bottom:16px;}
.search-wrap input{width:100%;padding:11px 14px 11px 40px;border:2px solid #e8edf5;border-radius:10px;font-family:'Nunito',sans-serif;font-size:13.5px;outline:none;transition:.2s;}
.search-wrap input:focus{border-color:var(--blue);}
.search-wrap i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#aaa;font-size:14px;}

.doctor-table{width:100%;border-collapse:collapse;}
.doctor-table th{background:#f0f4ff;padding:11px 14px;font-size:11.5px;text-transform:uppercase;letter-spacing:.5px;color:#666;font-weight:700;text-align:left;}
.doctor-table th:first-child{border-radius:10px 0 0 10px;}
.doctor-table th:last-child{border-radius:0 10px 10px 0;}
.doctor-table td{padding:12px 14px;border-bottom:1px solid #f0f4ff;vertical-align:middle;}
.doctor-table tr:last-child td{border-bottom:none;}
.doctor-table tr:hover td{background:#f8faff;}

/* DOC ROW */
.doc-avatar-cell{display:flex;align-items:center;gap:12px;}
.doc-thumb{width:42px;height:42px;border-radius:50%;object-fit:cover;border:2px solid var(--blue-light);flex-shrink:0;background:linear-gradient(135deg,#e8f0fe,#c5d8f8);display:flex;align-items:center;justify-content:center;font-size:18px;overflow:hidden;}
.doc-thumb img{width:100%;height:100%;object-fit:cover;}
.doc-name-cell .dname{font-size:13.5px;font-weight:800;}
.doc-name-cell .dspec{font-size:11.5px;color:#888;}

.fee-pill{background:#e8f5e9;color:#2e7d32;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:700;}
.rating-cell{display:flex;align-items:center;gap:4px;font-size:12.5px;font-weight:700;color:#f59e0b;}
.rating-cell i{font-size:11px;}
.appt-pill{background:#e8f0fe;color:#1a73e8;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:700;}

/* ACTION BUTTONS */
.action-btns{display:flex;gap:8px;}
.btn-edit-sm{padding:7px 13px;border-radius:8px;background:#fff3e0;color:#f57c00;border:none;font-family:'Nunito',sans-serif;font-size:12px;font-weight:700;cursor:pointer;transition:.2s;text-decoration:none;display:flex;align-items:center;gap:5px;}
.btn-edit-sm:hover{background:#ff9100;color:#fff;}
.btn-del-sm{padding:7px 13px;border-radius:8px;background:#fce4ec;color:#c62828;border:none;font-family:'Nunito',sans-serif;font-size:12px;font-weight:700;cursor:pointer;transition:.2s;display:flex;align-items:center;gap:5px;}
.btn-del-sm:hover{background:#e53935;color:#fff;}

/* DELETE CONFIRM MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;backdrop-filter:blur(4px);}
.modal-overlay.show{display:flex;}
.modal{background:white;border-radius:20px;padding:32px;max-width:420px;width:90%;text-align:center;animation:popIn .3s ease;}
@keyframes popIn{from{opacity:0;transform:scale(.9);}to{opacity:1;transform:scale(1);}}
.modal-icon{font-size:52px;margin-bottom:14px;}
.modal h3{font-size:18px;font-weight:800;margin-bottom:8px;}
.modal p{font-size:13.5px;color:#888;margin-bottom:22px;line-height:1.6;}
.modal-btns{display:flex;gap:12px;justify-content:center;}
.modal-btns .btn{width:auto;padding:11px 24px;}

.empty-state{text-align:center;padding:48px;color:#bbb;}
.empty-state i{font-size:44px;display:block;margin-bottom:12px;color:#d0d8f0;}

@keyframes fadeUp{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}
.main>*{animation:fadeUp .4s ease both;}
.topbar{animation-delay:.04s;}.stats-row{animation-delay:.08s;}.page-grid{animation-delay:.12s;}
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
    <a href="admin_add_doctor.php" class="active"><i class="fa-solid fa-user-plus"></i>Manage Doctors</a>
    <a href="admin_bookings.php"><i class="fa-solid fa-table-list"></i>All Appointments</a>
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
        <h1><i class="fa-solid fa-user-doctor" style="color:var(--blue);margin-right:8px;"></i>Manage Doctors</h1>
        <a href="dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    </div>

    <?php if ($success): ?>
    <div class="alert success"><i class="fa-solid fa-circle-check"></i><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert error"><i class="fa-solid fa-circle-xmark"></i><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- STATS -->
    <?php
    $total_appts_all = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM appointments"))['c'];
    $avg_fee = mysqli_fetch_assoc(mysqli_query($conn,"SELECT ROUND(AVG(consultation_fee),0) avg FROM doctors"))['avg'] ?? 0;
    ?>
    <div class="stats-row">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fa-solid fa-user-doctor"></i></div>
            <div><div class="stat-num"><?php echo $total_doctors; ?></div><div class="stat-lbl">Total Doctors</div></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
            <div><div class="stat-num"><?php echo $total_appts_all; ?></div><div class="stat-lbl">Total Appointments</div></div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
            <div><div class="stat-num">₹<?php echo $avg_fee; ?></div><div class="stat-lbl">Avg Consultation Fee</div></div>
        </div>
    </div>

    <div class="page-grid">

        <!-- ADD / EDIT FORM -->
        <div class="card">
            <div class="card-header <?php echo $edit_doctor ? 'edit-mode' : ''; ?>">
                <i class="fa-solid <?php echo $edit_doctor ? 'fa-pen-to-square' : 'fa-user-plus'; ?>"></i>
                <?php echo $edit_doctor ? 'Edit Doctor' : 'Add New Doctor'; ?>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="doctorForm">
                    <?php if ($edit_doctor): ?>
                        <input type="hidden" name="doctor_id" value="<?php echo $edit_doctor['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" placeholder="e.g. Rajesh Kumar"
                               value="<?php echo $edit_doctor ? htmlspecialchars($edit_doctor['name']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Specialization</label>
                        <input type="text" name="specialization" placeholder="e.g. Cardiologist"
                               value="<?php echo $edit_doctor ? htmlspecialchars($edit_doctor['specialization']) : ''; ?>"
                               id="specInput" required>
                        <!-- Quick spec chips -->
                        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;">
                            <?php
                            $common_specs = ['Cardiologist','Dermatologist','Neurologist','Orthopedist','Pediatrician','Gynecologist','Dentist','General Physician','ENT Specialist','Psychiatrist'];
                            foreach ($common_specs as $sp):
                            ?>
                            <button type="button" onclick="document.getElementById('specInput').value='<?php echo $sp; ?>'"
                                    style="padding:4px 10px;border-radius:20px;border:1.5px solid #c5d8f8;background:white;color:#1a73e8;font-size:11.5px;font-weight:700;cursor:pointer;font-family:'Nunito',sans-serif;transition:.15s;"
                                    onmouseover="this.style.background='#e8f0fe'"
                                    onmouseout="this.style.background='white'">
                                <?php echo $sp; ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Consultation Fee (₹)</label>
                        <input type="number" name="consultation_fee" placeholder="e.g. 500" min="0"
                               value="<?php echo $edit_doctor ? $edit_doctor['consultation_fee'] : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Doctor Photo</label>
                        <?php if ($edit_doctor && !empty($edit_doctor['photo'])): ?>
                        <div class="current-photo">
                            <img src="doctors/<?php echo htmlspecialchars($edit_doctor['photo']); ?>" alt="">
                            <span>Current photo — upload new to replace</span>
                        </div>
                        <?php endif; ?>
                        <label class="photo-upload-label" for="photoInput">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <p>Click to upload photo<br><small>JPG, PNG, WEBP · Max 2MB</small></p>
                            <input type="file" id="photoInput" name="photo" accept="image/*"
                                   style="display:none;" onchange="previewPhoto(this)">
                        </label>
                        <div class="photo-preview-wrap" id="previewWrap">
                            <img id="previewImg" src="" alt="Preview">
                        </div>
                    </div>

                    <?php if ($edit_doctor): ?>
                    <button type="submit" name="update_doctor" class="btn btn-warning">
                        <i class="fa-solid fa-floppy-disk"></i> Update Doctor
                    </button>
                    <a href="admin_add_doctor.php" class="btn btn-cancel">
                        <i class="fa-solid fa-xmark"></i> Cancel Edit
                    </a>
                    <?php else: ?>
                    <button type="submit" name="add_doctor" class="btn btn-primary">
                        <i class="fa-solid fa-user-plus"></i> Add Doctor
                    </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- DOCTOR LIST -->
        <div class="card">
            <div class="card-header">
                <i class="fa-solid fa-list"></i> All Doctors
                <span style="margin-left:auto;font-size:12px;color:#aaa;font-weight:600;"><?php echo $total_doctors; ?> total</span>
            </div>
            <div class="card-body">
                <div class="search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Search doctors..." oninput="filterDoctors()">
                </div>

                <?php if ($total_doctors > 0): ?>
                <div style="overflow-x:auto;">
                <table class="doctor-table" id="doctorTable">
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Fee</th>
                            <th>Rating</th>
                            <th>Appts</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="doctorTbody">
                    <?php mysqli_data_seek($doctors,0); while ($d = mysqli_fetch_assoc($doctors)): ?>
                    <tr data-name="<?php echo strtolower($d['name'].' '.$d['specialization']); ?>">
                        <td>
                            <div class="doc-avatar-cell">
                                <div class="doc-thumb">
                                    <?php
                                    $ph = !empty($d['photo']) ? 'doctors/'.$d['photo'] : '';
                                    if ($ph && file_exists($ph)):
                                    ?><img src="<?php echo htmlspecialchars($ph); ?>" alt=""><?php
                                    else: ?>👨‍⚕️<?php endif; ?>
                                </div>
                                <div class="doc-name-cell">
                                    <div class="dname">Dr. <?php echo htmlspecialchars($d['name']); ?></div>
                                    <div class="dspec"><?php echo htmlspecialchars($d['specialization']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="fee-pill">₹<?php echo $d['consultation_fee']; ?></span></td>
                        <td>
                            <div class="rating-cell">
                                <i class="fa-solid fa-star"></i>
                                <?php echo $d['avg_rating'] ?? '—'; ?>
                            </div>
                        </td>
                        <td><span class="appt-pill"><?php echo $d['appt_count']; ?></span></td>
                        <td>
                            <div class="action-btns">
                                <a href="?edit=<?php echo $d['id']; ?>" class="btn-edit-sm">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </a>
                                <button class="btn-del-sm"
                                        onclick="confirmDelete(<?php echo $d['id']; ?>, 'Dr. <?php echo htmlspecialchars(addslashes($d['name'])); ?>')">
                                    <i class="fa-solid fa-trash"></i> Remove
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-user-doctor"></i>
                    No doctors added yet.<br>
                    <small>Use the form to add your first doctor.</small>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <div class="modal-icon">⚠️</div>
        <h3>Remove Doctor?</h3>
        <p id="modalText">Are you sure you want to remove this doctor?<br>
        <strong>This will also delete all their appointments and feedback.</strong></p>
        <div class="modal-btns">
            <button class="btn btn-cancel" style="width:auto;padding:11px 24px;" onclick="closeModal()">
                <i class="fa-solid fa-xmark"></i> Cancel
            </button>
            <a href="#" id="confirmDeleteBtn" class="btn btn-primary"
               style="background:linear-gradient(135deg,#e53935,#c62828);box-shadow:0 4px 14px rgba(229,57,53,.3);">
                <i class="fa-solid fa-trash"></i> Yes, Remove
            </a>
        </div>
    </div>
</div>

<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('previewWrap').style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function confirmDelete(id, name) {
    document.getElementById('modalText').innerHTML =
        `Are you sure you want to remove <strong>${name}</strong>?<br><br>
        <span style="color:#e53935;font-size:13px;">⚠️ This will also delete all their appointments and feedback.</span>`;
    document.getElementById('confirmDeleteBtn').href = `admin_add_doctor.php?delete=${id}`;
    document.getElementById('deleteModal').classList.add('show');
}

function closeModal() {
    document.getElementById('deleteModal').classList.remove('show');
}

// Close modal on overlay click
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Live search
function filterDoctors() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#doctorTbody tr').forEach(row => {
        row.style.display = row.dataset.name.includes(q) ? '' : 'none';
    });
}

// Auto-scroll to form if editing
<?php if ($edit_doctor): ?>
document.querySelector('.page-grid').scrollIntoView({behavior:'smooth', block:'start'});
<?php endif; ?>
</script>
</body>
</html>
