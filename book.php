<?php
session_start();
require "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php"); exit;
}

$user_id         = $_SESSION["user_id"];
$role            = $_SESSION['role'];
$name            = $_SESSION['username'];
$selected_doctor = $_GET['doctor_id'] ?? '';
$doctors         = mysqli_query($conn, "SELECT * FROM doctors ORDER BY name");
$success         = "";
$error           = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $doctor_id = (int)$_POST["doctor_id"];
    $date      = mysqli_real_escape_string($conn, $_POST["appointment_date"]);
    $time      = mysqli_real_escape_string($conn, $_POST["appointment_time"]);
    $message   = mysqli_real_escape_string($conn, trim($_POST["message"]));

    if ($date < date("Y-m-d")) {
        $error = "You cannot book an appointment in the past.";
    } else {
        $check = mysqli_query($conn,
            "SELECT id FROM appointments
             WHERE doctor_id='$doctor_id'
             AND appointment_date='$date'
             AND appointment_time='$time'
             AND status='booked'"
        );
        if (mysqli_num_rows($check) > 0) {
            $error = "This time slot is already booked. Please choose another time.";
        } else {
            mysqli_query($conn,
                "INSERT INTO appointments (user_id, doctor_id, appointment_date, appointment_time, message)
                 VALUES ('$user_id','$doctor_id','$date','$time','$message')"
            );
            $success = "Appointment booked successfully! 🎉";
        }
    }
}

// Fetch selected doctor info for preview
$sel_doc = null;
if ($selected_doctor) {
    $dr = mysqli_query($conn, "SELECT * FROM doctors WHERE id=$selected_doctor");
    $sel_doc = mysqli_fetch_assoc($dr);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Book Appointment – Doctor App</title>
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

/* ALERT */
.alert{padding:14px 18px;border-radius:12px;margin-bottom:22px;font-size:13.5px;font-weight:600;display:flex;align-items:center;gap:10px;}
.alert.success{background:#e8f5e9;color:#2e7d32;border:1.5px solid #a5d6a7;}
.alert.error  {background:#fce4ec;color:#b71c1c;border:1.5px solid #ef9a9a;}

/* PAGE GRID */
.page-grid{display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;}
@media(max-width:1050px){.page-grid{grid-template-columns:1fr;}}

/* FORM CARD */
.card{background:white;border-radius:var(--card-r);box-shadow:var(--shadow);overflow:hidden;}
.card-header{padding:18px 24px;border-bottom:1px solid #f0f4ff;font-size:15px;font-weight:800;display:flex;align-items:center;gap:8px;}
.card-header i{color:var(--blue);}
.card-body{padding:24px;}

.form-group{margin-bottom:20px;}
.form-group label{display:block;font-size:12px;font-weight:700;color:#555;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px;}

.form-select,.form-input,.form-textarea{
    width:100%;padding:13px 16px;
    border:2px solid #e8edf5;border-radius:11px;
    font-family:'Nunito',sans-serif;font-size:14px;color:#1a1a2e;
    outline:none;transition:.2s;background:#f8faff;
}
.form-select:focus,.form-input:focus,.form-textarea:focus{
    border-color:var(--blue);background:#fff;box-shadow:0 0 0 3px rgba(26,115,232,.08);
}
.form-select{cursor:pointer;appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%231a73e8' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right 14px center;
    background-color:#f8faff;padding-right:36px;
}
.form-textarea{resize:vertical;min-height:90px;line-height:1.6;}

/* DOCTOR PREVIEW CARD */
.doc-preview{
    background:var(--blue-light);border-radius:12px;padding:14px 16px;
    margin-bottom:20px;display:none;align-items:center;gap:14px;
    border:1.5px solid #c5d8f8;
}
.doc-preview.show{display:flex;}
.prev-photo{width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg,#1a73e8,#42a5f5);display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;flex-shrink:0;overflow:hidden;}
.prev-photo img{width:100%;height:100%;object-fit:cover;}
.prev-name{font-size:14px;font-weight:800;color:#0d47a1;}
.prev-spec{font-size:12px;color:#555;margin-top:2px;}
.prev-fee{margin-left:auto;background:#1a73e8;color:#fff;padding:5px 12px;border-radius:20px;font-size:12.5px;font-weight:800;flex-shrink:0;}

/* TIME SLOTS */
.slots-wrap{display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,1fr));gap:8px;margin-top:4px;}
.slot-btn{
    padding:9px 6px;border-radius:10px;border:2px solid #e8edf5;
    background:white;color:#555;font-family:'Nunito',sans-serif;
    font-size:13px;font-weight:700;cursor:pointer;transition:.2s;text-align:center;
}
.slot-btn:hover:not(.booked){border-color:var(--blue);color:var(--blue);background:var(--blue-light);}
.slot-btn.selected{background:var(--blue);color:#fff;border-color:var(--blue);}
.slot-btn.booked{background:#f5f5f5;color:#ccc;cursor:not-allowed;border-color:#eee;text-decoration:line-through;}
.slots-loading{text-align:center;color:#aaa;padding:16px;font-size:13px;}
#timeHidden{display:none;}

.btn-book{
    width:100%;padding:14px;border-radius:12px;border:none;
    background:linear-gradient(135deg,#1a73e8,#1565c0);color:#fff;
    font-family:'Nunito',sans-serif;font-size:15px;font-weight:800;
    cursor:pointer;transition:.2s;box-shadow:0 4px 16px rgba(26,115,232,.35);
    display:flex;align-items:center;justify-content:center;gap:10px;
}
.btn-book:hover{transform:translateY(-2px);box-shadow:0 6px 22px rgba(26,115,232,.45);}
.btn-book:disabled{background:#c5d8f8;box-shadow:none;transform:none;cursor:not-allowed;}

/* SIDE INFO CARDS */
.info-card{background:white;border-radius:var(--card-r);box-shadow:var(--shadow);padding:20px;margin-bottom:16px;}
.info-card h4{font-size:14px;font-weight:800;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.info-card h4 i{color:var(--blue);}
.info-step{display:flex;align-items:flex-start;gap:12px;margin-bottom:12px;}
.info-step:last-child{margin-bottom:0;}
.step-num{width:26px;height:26px;border-radius:50%;background:var(--blue-light);color:var(--blue);font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;}
.step-text{font-size:13px;color:#555;line-height:1.5;}
.step-text strong{color:#1a1a2e;display:block;font-size:13.5px;}

.note-box{background:#fff8e1;border:1.5px solid #ffe082;border-radius:12px;padding:14px 16px;}
.note-box p{font-size:13px;color:#856404;display:flex;align-items:flex-start;gap:8px;line-height:1.6;}
.note-box p i{flex-shrink:0;margin-top:2px;color:#f59e0b;}

@keyframes fadeUp{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}
.main>*{animation:fadeUp .4s ease both;}
.topbar{animation-delay:.04s;}.page-grid{animation-delay:.08s;}
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
    <a href="book.php" class="active"><i class="fa-solid fa-calendar-plus"></i>Book Appointment</a>
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
        <h1><i class="fa-solid fa-calendar-plus" style="color:var(--blue);margin-right:8px;"></i>Book Appointment</h1>
        <a href="dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    </div>

    <?php if ($success): ?>
    <div class="alert success"><i class="fa-solid fa-circle-check"></i><?php echo $success; ?>
        <a href="my_appointments.php" style="margin-left:auto;color:#2e7d32;font-weight:800;">View Appointments →</a>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert error"><i class="fa-solid fa-circle-xmark"></i><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="page-grid">

        <!-- BOOKING FORM -->
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-calendar-plus"></i> New Appointment</div>
            <div class="card-body">
                <form method="POST" id="bookForm">

                    <!-- Doctor preview -->
                    <div class="doc-preview" id="docPreview">
                        <div class="prev-photo" id="prevPhoto"><i class="fa-solid fa-user-doctor"></i></div>
                        <div>
                            <div class="prev-name" id="prevName"></div>
                            <div class="prev-spec" id="prevSpec"></div>
                        </div>
                        <div class="prev-fee" id="prevFee"></div>
                    </div>

                    <!-- Doctor select -->
                    <div class="form-group">
                        <label><i class="fa-solid fa-user-doctor" style="color:var(--blue);margin-right:4px;"></i> Choose Doctor</label>
                        <select name="doctor_id" id="doctor" class="form-select"
                                onchange="onDoctorChange()" required>
                            <option value="">— Select a doctor —</option>
                            <?php
                            // Build doctor data for JS
                            $doc_data = [];
                            mysqli_data_seek($doctors, 0);
                            while ($d = mysqli_fetch_assoc($doctors)):
                                $doc_data[$d['id']] = [
                                    'name' => $d['name'],
                                    'spec' => $d['specialization'],
                                    'fee'  => $d['consultation_fee'],
                                    'photo'=> $d['photo'] ?? ''
                                ];
                            ?>
                            <option value="<?php echo $d['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($d['name']); ?>"
                                    data-spec="<?php echo htmlspecialchars($d['specialization']); ?>"
                                    data-fee="<?php echo $d['consultation_fee']; ?>"
                                    data-photo="<?php echo htmlspecialchars($d['photo'] ?? ''); ?>"
                                    <?php echo $selected_doctor == $d['id'] ? 'selected' : ''; ?>>
                                Dr. <?php echo htmlspecialchars($d['name']); ?> — <?php echo htmlspecialchars($d['specialization']); ?> — ₹<?php echo $d['consultation_fee']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Date -->
                    <div class="form-group">
                        <label><i class="fa-regular fa-calendar" style="color:var(--blue);margin-right:4px;"></i> Appointment Date</label>
                        <input type="date" name="appointment_date" id="date"
                               class="form-input"
                               min="<?php echo date('Y-m-d'); ?>"
                               onchange="loadSlots()" required>
                    </div>

                    <!-- Time slots -->
                    <div class="form-group">
                        <label><i class="fa-regular fa-clock" style="color:var(--blue);margin-right:4px;"></i> Available Time Slots</label>
                        <input type="hidden" name="appointment_time" id="timeHidden" required>
                        <div id="slotsContainer">
                            <div class="slots-loading">Select a doctor and date to see available slots</div>
                        </div>
                    </div>

                    <!-- Message -->
                    <div class="form-group">
                        <label><i class="fa-regular fa-message" style="color:var(--blue);margin-right:4px;"></i> Reason / Message <span style="color:#aaa;font-size:11px;text-transform:none;letter-spacing:0;">(optional)</span></label>
                        <textarea name="message" class="form-textarea"
                                  placeholder="Describe your symptoms or reason for visit..."></textarea>
                    </div>

                    <button type="submit" class="btn-book" id="bookBtn">
                        <i class="fa-solid fa-calendar-check"></i> Confirm Booking
                    </button>
                </form>
            </div>
        </div>

        <!-- RIGHT SIDE -->
        <div>
            <!-- How it works -->
            <div class="info-card">
                <h4><i class="fa-solid fa-circle-info"></i> How to Book</h4>
                <div class="info-step">
                    <div class="step-num">1</div>
                    <div class="step-text"><strong>Choose a Doctor</strong>Select your preferred specialist</div>
                </div>
                <div class="info-step">
                    <div class="step-num">2</div>
                    <div class="step-text"><strong>Pick a Date</strong>Choose any available date</div>
                </div>
                <div class="info-step">
                    <div class="step-num">3</div>
                    <div class="step-text"><strong>Select Time Slot</strong>Pick from available slots</div>
                </div>
                <div class="info-step">
                    <div class="step-num">4</div>
                    <div class="step-text"><strong>Confirm Booking</strong>Review and confirm your appointment</div>
                </div>
            </div>

            <!-- Note -->
            <div class="note-box">
                <p><i class="fa-solid fa-circle-info"></i>
                Consultation fee is paid at the hospital reception. Online booking is free of charge.</p>
            </div>

            <!-- Quick links -->
            <div class="info-card" style="margin-top:16px;">
                <h4><i class="fa-solid fa-bolt"></i> Quick Links</h4>
                <a href="doctors.php" style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;background:#f0f4ff;text-decoration:none;color:#1a1a2e;font-weight:700;font-size:13.5px;margin-bottom:8px;transition:.2s;" onmouseover="this.style.background='#e8f0fe'" onmouseout="this.style.background='#f0f4ff'">
                    <i class="fa-solid fa-user-doctor" style="color:var(--blue);width:18px;text-align:center;"></i> Browse All Doctors
                </a>
                <a href="my_appointments.php" style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;background:#f0f4ff;text-decoration:none;color:#1a1a2e;font-weight:700;font-size:13.5px;transition:.2s;" onmouseover="this.style.background='#e8f0fe'" onmouseout="this.style.background='#f0f4ff'">
                    <i class="fa-solid fa-list-check" style="color:var(--blue);width:18px;text-align:center;"></i> My Appointments
                </a>
            </div>
        </div>

    </div>
</div>
</div>

<script>
// Doctor data from PHP
const docData = <?php echo json_encode($doc_data); ?>;

function onDoctorChange() {
    const sel   = document.getElementById('doctor');
    const opt   = sel.options[sel.selectedIndex];
    const prev  = document.getElementById('docPreview');

    if (!opt.value) { prev.classList.remove('show'); return; }

    document.getElementById('prevName').textContent = 'Dr. ' + opt.dataset.name;
    document.getElementById('prevSpec').textContent = opt.dataset.spec;
    document.getElementById('prevFee').textContent  = '₹' + opt.dataset.fee;

    const photo = opt.dataset.photo;
    const av    = document.getElementById('prevPhoto');
    if (photo) {
        av.innerHTML = `<img src="doctors/${photo}" alt="" onerror="this.parentElement.innerHTML='<i class=\\'fa-solid fa-user-doctor\\'></i>'">`;
    } else {
        av.innerHTML = '<i class="fa-solid fa-user-doctor"></i>';
    }
    prev.classList.add('show');
    loadSlots();
}

function loadSlots() {
    const doctor = document.getElementById('doctor').value;
    const date   = document.getElementById('date').value;
    const cont   = document.getElementById('slotsContainer');

    // Clear selection
    document.getElementById('timeHidden').value = '';

    if (!doctor || !date) {
        cont.innerHTML = '<div class="slots-loading">Select a doctor and date to see available slots</div>';
        return;
    }

    cont.innerHTML = '<div class="slots-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading slots...</div>';

    fetch(`fetch_slots.php?doctor_id=${doctor}&date=${date}`)
        .then(r => r.json())
        .then(booked => {
            // Generate all slots 09:00 – 17:30
            const slots = [];
            for (let h = 9; h <= 17; h++) {
                slots.push(pad(h) + ':00');
                slots.push(pad(h) + ':30');
            }

            let html = '<div class="slots-wrap">';
            slots.forEach(slot => {
                const isBooked = booked.includes(slot);
                html += `<button type="button" class="slot-btn${isBooked?' booked':''}"
                    ${isBooked?'disabled':''} onclick="selectSlot(this,'${slot}')">
                    ${formatTime(slot)}
                </button>`;
            });
            html += '</div>';
            cont.innerHTML = html;
        })
        .catch(() => {
            cont.innerHTML = '<div class="slots-loading" style="color:#e53935;">Failed to load slots. Try again.</div>';
        });
}

function selectSlot(btn, time) {
    document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('timeHidden').value = time;
}

function pad(n) { return n < 10 ? '0'+n : ''+n; }

function formatTime(t) {
    const [h, m] = t.split(':').map(Number);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const hr   = h % 12 || 12;
    return `${hr}:${pad(m)} ${ampm}`;
}

// Init if doctor pre-selected
window.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('doctor');
    if (sel.value) onDoctorChange();
});
</script>
</body>
</html>
