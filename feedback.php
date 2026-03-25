<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];
$success = '';
$error   = '';

// Check if user already gave feedback for selected doctor
$already_submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = (int)$_POST['doctor_id'];
    $message   = trim(mysqli_real_escape_string($conn, $_POST['message']));
    $rating    = (int)$_POST['rating'];

    if (!$doctor_id) {
        $error = "Please select a doctor.";
    } elseif (empty($message)) {
        $error = "Please write a message.";
    } elseif ($rating < 1 || $rating > 5) {
        $error = "Please select a rating.";
    } else {
        // Check duplicate
        $chk = mysqli_query($conn, "SELECT id FROM feedback WHERE user_id=$user_id AND doctor_id=$doctor_id");
        if (mysqli_num_rows($chk) > 0) {
            $error = "You have already submitted feedback for this doctor.";
        } else {
            $stmt = $conn->prepare("INSERT INTO feedback (user_id, doctor_id, message, rating) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisi", $user_id, $doctor_id, $message, $rating);
            if ($stmt->execute()) {
                $success = "Feedback submitted successfully! Thank you.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}

// Fetch doctors
$doctors = mysqli_query($conn, "SELECT * FROM doctors ORDER BY name");

// Fetch user's past feedback
$my_feedback = mysqli_query($conn,
    "SELECT f.*, d.name as doctor_name, d.specialization, d.photo
     FROM feedback f
     JOIN doctors d ON f.doctor_id = d.id
     WHERE f.user_id = $user_id
     ORDER BY f.created_at DESC"
);

// IDs of doctors already reviewed
$reviewed_ids = [];
$tmp = mysqli_query($conn, "SELECT doctor_id FROM feedback WHERE user_id=$user_id");
while ($r = mysqli_fetch_assoc($tmp)) $reviewed_ids[] = $r['doctor_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Feedback – Doctor App</title>
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
.alert.error  {background:#fce4ec;color:#b71c1c;border:1.5px solid #ef9a9a;}

.page-grid{display:grid;grid-template-columns:420px 1fr;gap:22px;align-items:start;}
@media(max-width:1050px){.page-grid{grid-template-columns:1fr;}}

.card{background:white;border-radius:var(--card-r);box-shadow:var(--shadow);overflow:hidden;}
.card-header{padding:18px 22px;border-bottom:1px solid #f0f4ff;font-size:15px;font-weight:800;display:flex;align-items:center;gap:8px;color:#1a1a2e;}
.card-header i{color:var(--blue);}
.card-body{padding:24px;}

/* FORM */
.form-group{margin-bottom:20px;}
.form-group label{display:block;font-size:12.5px;font-weight:700;color:#555;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px;}

.doctor-select{
    width:100%;padding:12px 14px;border:2px solid #e8edf5;border-radius:10px;
    font-family:'Nunito',sans-serif;font-size:14px;color:#1a1a2e;
    outline:none;transition:.2s;background:white;cursor:pointer;
    appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%231a73e8' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right 14px center;
}
.doctor-select:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(26,115,232,.10);}

/* STAR RATING */
.star-rating{display:flex;flex-direction:row-reverse;justify-content:flex-end;gap:6px;margin-top:4px;}
.star-rating input{display:none;}
.star-rating label{
    font-size:34px;color:#ddd;cursor:pointer;
    transition:color .15s,transform .15s;
    line-height:1;
}
.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label{color:#ffc107;}
.star-rating label:hover{transform:scale(1.2);}

.rating-text{font-size:12.5px;color:#aaa;margin-top:6px;font-weight:600;min-height:18px;}

textarea{
    width:100%;padding:13px 14px;border:2px solid #e8edf5;border-radius:10px;
    font-family:'Nunito',sans-serif;font-size:14px;color:#1a1a2e;
    outline:none;transition:.2s;resize:vertical;min-height:120px;
}
textarea:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(26,115,232,.10);}

.char-count{font-size:11.5px;color:#aaa;text-align:right;margin-top:4px;}

.btn-submit{
    width:100%;padding:14px;border-radius:12px;border:none;
    background:linear-gradient(135deg,#1a73e8,#1565c0);
    color:#fff;font-family:'Nunito',sans-serif;font-size:15px;font-weight:800;
    cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;gap:10px;
    box-shadow:0 4px 14px rgba(26,115,232,.35);
}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(26,115,232,.45);}

/* SECTION TITLE */
.section-title{font-size:15px;font-weight:800;color:#1a1a2e;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.section-title::before{content:'';width:4px;height:18px;background:var(--blue);border-radius:4px;display:block;}

/* FEEDBACK CARDS */
.feedback-list{display:flex;flex-direction:column;gap:16px;}
.fb-card{
    background:#f8faff;border-radius:14px;padding:18px;
    border:1.5px solid #e8edf5;transition:.2s;
}
.fb-card:hover{border-color:var(--blue-light);box-shadow:0 4px 16px rgba(26,115,232,.08);}
.fb-top{display:flex;align-items:center;gap:14px;margin-bottom:12px;}
.doc-avatar{
    width:48px;height:48px;border-radius:50%;object-fit:cover;
    background:linear-gradient(135deg,#1a73e8,#42a5f5);
    display:flex;align-items:center;justify-content:center;
    color:#fff;font-size:18px;flex-shrink:0;
    border:2px solid var(--blue-light);
}
.doc-avatar img{width:100%;height:100%;border-radius:50%;object-fit:cover;}
.doc-info .name{font-size:14.5px;font-weight:800;}
.doc-info .spec{font-size:12px;color:#888;}
.fb-stars{margin-left:auto;display:flex;gap:2px;}
.fb-stars i{font-size:14px;}
.fb-stars i.filled{color:#ffc107;}
.fb-stars i.empty{color:#ddd;}
.fb-message{font-size:13.5px;color:#444;line-height:1.6;background:white;padding:12px;border-radius:10px;border:1px solid #eef2ff;}
.fb-date{font-size:11.5px;color:#bbb;margin-top:8px;text-align:right;}

.empty-state{text-align:center;padding:40px;color:#bbb;}
.empty-state i{font-size:40px;display:block;margin-bottom:10px;color:#d0d8f0;}

/* Doctor card preview */
.doctor-preview{
    display:none;background:var(--blue-light);border-radius:12px;
    padding:14px;margin-bottom:20px;
    display:flex;align-items:center;gap:12px;
    border:1.5px solid #c5d8f8;
}
.doctor-preview.hidden{display:none;}
.preview-avatar{
    width:44px;height:44px;border-radius:50%;
    background:linear-gradient(135deg,#1a73e8,#42a5f5);
    display:flex;align-items:center;justify-content:center;
    color:#fff;font-size:18px;flex-shrink:0;
    overflow:hidden;
}
.preview-avatar img{width:100%;height:100%;object-fit:cover;}
.preview-info .pname{font-size:14px;font-weight:800;color:var(--blue-dark);}
.preview-info .pspec{font-size:12px;color:#555;}
.already-badge{
    margin-left:auto;background:#fce4ec;color:#c62828;
    padding:4px 10px;border-radius:20px;font-size:11.5px;font-weight:700;
    display:none;
}

@keyframes fadeUp{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}
.main > *{animation:fadeUp .4s ease both;}
.topbar{animation-delay:.05s;} .alert{animation-delay:.08s;}
.page-grid{animation-delay:.12s;}
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
        <a href="feedback.php" class="active"><i class="fa-solid fa-star"></i>Feedback</a>
        <a href="profile.php"><i class="fa-solid fa-circle-user"></i>Profile</a>
    <?php endif; ?>
    <div class="sidebar-spacer"></div>
    <a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i>Logout</a>
</div>

<!-- MAIN -->
<div class="main">

    <div class="topbar">
        <h1><i class="fa-solid fa-star" style="color:#ffc107;margin-right:8px;"></i>Give Feedback</h1>
        <a href="dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    </div>

    <?php if ($success): ?>
    <div class="alert success"><i class="fa-solid fa-circle-check"></i><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert error"><i class="fa-solid fa-circle-xmark"></i><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="page-grid">

        <!-- FORM -->
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-pen-to-square"></i> Write a Review</div>
            <div class="card-body">
                <form method="POST" id="feedbackForm">

                    <!-- Doctor select -->
                    <div class="form-group">
                        <label>Select Doctor</label>
                        <select class="doctor-select" name="doctor_id" id="doctorSelect"
                                onchange="updateDoctorPreview(this)" required>
                            <option value="">— Choose a doctor —</option>
                            <?php
                            mysqli_data_seek($doctors, 0);
                            while ($d = mysqli_fetch_assoc($doctors)):
                                $reviewed = in_array($d['id'], $reviewed_ids) ? 'data-reviewed="1"' : '';
                            ?>
                            <option value="<?php echo $d['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($d['name']); ?>"
                                    data-spec="<?php echo htmlspecialchars($d['specialization']); ?>"
                                    data-photo="<?php echo htmlspecialchars($d['photo'] ?? ''); ?>"
                                    <?php echo $reviewed; ?>>
                                Dr. <?php echo htmlspecialchars($d['name']); ?>
                                <?php if (in_array($d['id'], $reviewed_ids)) echo ' ✓'; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Doctor preview -->
                    <div class="doctor-preview hidden" id="doctorPreview">
                        <div class="preview-avatar" id="previewAvatar">
                            <i class="fa-solid fa-user-doctor"></i>
                        </div>
                        <div class="preview-info">
                            <div class="pname" id="previewName"></div>
                            <div class="pspec" id="previewSpec"></div>
                        </div>
                        <span class="already-badge" id="alreadyBadge">
                            <i class="fa-solid fa-circle-check"></i> Already reviewed
                        </span>
                    </div>

                    <!-- Star rating -->
                    <div class="form-group">
                        <label>Your Rating</label>
                        <div class="star-rating">
                            <input type="radio" name="rating" id="s5" value="5"><label for="s5" title="Excellent">★</label>
                            <input type="radio" name="rating" id="s4" value="4"><label for="s4" title="Good">★</label>
                            <input type="radio" name="rating" id="s3" value="3" checked><label for="s3" title="Average">★</label>
                            <input type="radio" name="rating" id="s2" value="2"><label for="s2" title="Poor">★</label>
                            <input type="radio" name="rating" id="s1" value="1"><label for="s1" title="Terrible">★</label>
                        </div>
                        <div class="rating-text" id="ratingText">Average</div>
                    </div>

                    <!-- Message -->
                    <div class="form-group">
                        <label>Your Message</label>
                        <textarea name="message" id="msgArea" placeholder="Share your experience with this doctor..."
                                  maxlength="500" oninput="updateChar()"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        <div class="char-count"><span id="charCount">0</span> / 500</div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-paper-plane"></i> Submit Feedback
                    </button>
                </form>
            </div>
        </div>

        <!-- MY PAST FEEDBACK -->
        <div>
            <div class="section-title">My Previous Reviews</div>
            <div class="feedback-list">
                <?php if (mysqli_num_rows($my_feedback) > 0):
                    while ($fb = mysqli_fetch_assoc($my_feedback)):
                        $photo = !empty($fb['photo']) ? 'doctors/' . $fb['photo'] : '';
                ?>
                <div class="fb-card">
                    <div class="fb-top">
                        <div class="doc-avatar">
                            <?php if ($photo && file_exists($photo)): ?>
                                <img src="<?php echo htmlspecialchars($photo); ?>" alt="">
                            <?php else: ?>
                                <i class="fa-solid fa-user-doctor"></i>
                            <?php endif; ?>
                        </div>
                        <div class="doc-info">
                            <div class="name">Dr. <?php echo htmlspecialchars($fb['doctor_name']); ?></div>
                            <div class="spec"><?php echo htmlspecialchars($fb['specialization']); ?></div>
                        </div>
                        <div class="fb-stars">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="fa-solid fa-star <?php echo $s <= $fb['rating'] ? 'filled' : 'empty'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="fb-message"><?php echo nl2br(htmlspecialchars($fb['message'])); ?></div>
                    <div class="fb-date"><i class="fa-regular fa-clock"></i>
                        <?php echo date('d M Y, h:i A', strtotime($fb['created_at'])); ?>
                    </div>
                </div>
                <?php endwhile; else: ?>
                <div class="empty-state">
                    <i class="fa-regular fa-star"></i>
                    You haven't submitted any feedback yet.<br>
                    <small>Your reviews help others choose the right doctor.</small>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
</div>

<script>
const ratingLabels = {1:'Terrible 😞',2:'Poor 😕',3:'Average 😐',4:'Good 😊',5:'Excellent 🌟'};

// Update rating label text
document.querySelectorAll('.star-rating input').forEach(input => {
    input.addEventListener('change', () => {
        document.getElementById('ratingText').textContent = ratingLabels[input.value] || '';
    });
});

// Set default label on load (rating=3 is checked)
document.getElementById('ratingText').textContent = ratingLabels[3];

// Char count
function updateChar() {
    const len = document.getElementById('msgArea').value.length;
    document.getElementById('charCount').textContent = len;
}

// Doctor preview
const reviewedIds = <?php echo json_encode($reviewed_ids); ?>;

function updateDoctorPreview(sel) {
    const opt     = sel.options[sel.selectedIndex];
    const preview = document.getElementById('doctorPreview');
    const badge   = document.getElementById('alreadyBadge');

    if (!opt.value) {
        preview.classList.add('hidden');
        return;
    }

    document.getElementById('previewName').textContent = 'Dr. ' + opt.dataset.name;
    document.getElementById('previewSpec').textContent = opt.dataset.spec;

    const avatarEl = document.getElementById('previewAvatar');
    const photo = opt.dataset.photo;
    if (photo) {
        avatarEl.innerHTML = `<img src="doctors/${photo}" alt="" onerror="this.parentElement.innerHTML='<i class=\\'fa-solid fa-user-doctor\\'></i>'">`;
    } else {
        avatarEl.innerHTML = '<i class="fa-solid fa-user-doctor"></i>';
    }

    if (reviewedIds.includes(parseInt(opt.value))) {
        badge.style.display = 'inline-block';
    } else {
        badge.style.display = 'none';
    }

    preview.classList.remove('hidden');
}

// Init char count
updateChar();
</script>
</body>
</html>
