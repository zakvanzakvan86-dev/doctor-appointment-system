<?php
session_start();
require "db.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Register – Doctor App</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Nunito',sans-serif;min-height:100vh;display:flex;background:#f0f4ff;}

/* LEFT PANEL */
.left-panel{
    width:42%;background:linear-gradient(145deg,#1565c0 0%,#1a73e8 55%,#42a5f5 100%);
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    padding:48px 40px;position:relative;overflow:hidden;
}
.left-panel::before{content:'';position:absolute;top:-80px;right:-80px;width:280px;height:280px;background:rgba(255,255,255,.07);border-radius:50%;}
.left-panel::after{content:'';position:absolute;bottom:-90px;left:-50px;width:240px;height:240px;background:rgba(255,255,255,.05);border-radius:50%;}
.brand{display:flex;align-items:center;gap:14px;margin-bottom:40px;position:relative;z-index:1;}
.brand-icon{width:50px;height:50px;background:rgba(255,255,255,.2);border-radius:15px;display:flex;align-items:center;justify-content:center;font-size:22px;border:2px solid rgba(255,255,255,.3);}
.brand-name{font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:#fff;}
.illustration{font-size:90px;margin-bottom:24px;position:relative;z-index:1;animation:float 3s ease-in-out infinite;}
@keyframes float{0%,100%{transform:translateY(0);}50%{transform:translateY(-10px);}}
.left-text{text-align:center;position:relative;z-index:1;}
.left-text h2{font-size:24px;font-weight:800;color:#fff;margin-bottom:10px;}
.left-text p{font-size:14px;color:rgba(255,255,255,.78);line-height:1.7;}
.steps{margin-top:28px;display:flex;flex-direction:column;gap:12px;width:100%;max-width:320px;position:relative;z-index:1;}
.step{display:flex;align-items:center;gap:12px;}
.step-num{width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,.2);color:#fff;font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1.5px solid rgba(255,255,255,.3);}
.step span{font-size:13.5px;color:rgba(255,255,255,.88);font-weight:600;}

/* RIGHT PANEL */
.right-panel{width:58%;display:flex;align-items:center;justify-content:center;padding:40px;background:#fff;overflow-y:auto;}
.reg-box{width:100%;max-width:480px;}
.reg-box h1{font-size:26px;font-weight:800;color:#1a1a2e;margin-bottom:4px;}
.reg-box .subtitle{font-size:14px;color:#888;margin-bottom:28px;}

.notice-box{background:#fff8e1;color:#856404;border:1.5px solid #ffe082;border-radius:10px;padding:11px 14px;font-size:13px;font-weight:600;margin-bottom:18px;display:flex;align-items:center;gap:8px;}
.notice-box a{color:#1a73e8;font-weight:700;}

/* OTP SCREEN */
.otp-screen{text-align:center;padding:10px 0;}
.otp-icon{font-size:56px;margin-bottom:16px;}
.otp-screen h2{font-size:22px;font-weight:800;margin-bottom:8px;}
.otp-screen p{font-size:14px;color:#888;margin-bottom:24px;line-height:1.6;}

.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.form-group{margin-bottom:16px;}
.form-group label{display:block;font-size:11.5px;font-weight:700;color:#555;margin-bottom:7px;text-transform:uppercase;letter-spacing:.5px;}
.input-wrap{position:relative;}
.input-wrap i.icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#aaa;font-size:14px;}
.input-wrap input{
    width:100%;padding:12px 14px 12px 40px;
    border:2px solid #e8edf5;border-radius:11px;
    font-family:'Nunito',sans-serif;font-size:14px;color:#1a1a2e;
    outline:none;transition:.2s;background:#f8faff;
}
.input-wrap input:focus{border-color:#1a73e8;background:#fff;box-shadow:0 0 0 3px rgba(26,115,232,.08);}
.input-wrap input.otp-input{text-align:center;font-size:20px;font-weight:800;letter-spacing:8px;padding:14px 20px;}
.toggle-pwd{position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;font-size:14px;border:none;background:none;}

/* STRENGTH */
.strength-wrap{margin-top:8px;}
.strength-track{height:6px;background:#eee;border-radius:6px;overflow:hidden;}
.strength-fill{height:100%;width:0%;border-radius:6px;transition:width .3s,background .3s;}
.strength-label{font-size:12px;font-weight:700;margin-top:5px;text-align:right;}

/* HINT PILLS */
.pwd-hints{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;}
.hint-pill{font-size:11px;padding:3px 9px;border-radius:20px;font-weight:700;background:#f0f4ff;color:#aaa;transition:.2s;}
.hint-pill.ok{background:#e8f5e9;color:#2e7d32;}

.match-msg{font-size:12.5px;font-weight:700;margin-top:6px;}
.match-msg.ok{color:#00c853;}
.match-msg.no{color:#e53935;}

.btn-submit{
    width:100%;padding:14px;border-radius:12px;border:none;
    background:linear-gradient(135deg,#1a73e8,#1565c0);color:#fff;
    font-family:'Nunito',sans-serif;font-size:14.5px;font-weight:800;
    cursor:pointer;transition:.2s;box-shadow:0 4px 16px rgba(26,115,232,.35);
    display:flex;align-items:center;justify-content:center;gap:10px;margin-top:6px;
}
.btn-submit:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 6px 22px rgba(26,115,232,.45);}
.btn-submit:disabled{background:#c5d8f8;box-shadow:none;cursor:not-allowed;}

.login-link{text-align:center;margin-top:20px;font-size:14px;color:#888;}
.login-link a{color:#1a73e8;font-weight:800;text-decoration:none;}
.login-link a:hover{text-decoration:underline;}
.back-link{display:block;text-align:center;margin-top:14px;font-size:13.5px;color:#1a73e8;font-weight:700;text-decoration:none;}
.back-link:hover{text-decoration:underline;}

@media(max-width:768px){
    body{flex-direction:column;}
    .left-panel,.right-panel{width:100%;padding:28px 20px;}
    .left-panel{min-height:auto;}.steps{display:none;}
    .form-row{grid-template-columns:1fr;}
}
</style>
</head>
<body>

<!-- LEFT -->
<div class="left-panel">
    <div class="brand">
        <div class="brand-icon"><i class="fa-solid fa-stethoscope"></i></div>
        <span class="brand-name">Doctor App</span>
    </div>
    <div class="illustration">👨‍⚕️</div>
    <div class="left-text">
        <h2>Join Doctor App Today</h2>
        <p>Create your account and start managing your health appointments easily.</p>
    </div>
    <div class="steps">
        <div class="step"><div class="step-num">1</div><span>Fill in your details</span></div>
        <div class="step"><div class="step-num">2</div><span>Verify your email with OTP</span></div>
        <div class="step"><div class="step-num">3</div><span>Access your dashboard</span></div>
    </div>
</div>

<!-- RIGHT -->
<div class="right-panel">
    <div class="reg-box">

    <?php if (!isset($_SESSION['otp'])): ?>

        <h1>Create Account 🎉</h1>
        <p class="subtitle">Register with email OTP verification</p>

        <?php if (isset($_SESSION['email_error'])): ?>
        <div class="notice-box">
            <i class="fa-solid fa-circle-exclamation"></i>
            This email is already registered. <a href="login.php">Login instead →</a>
        </div>
        <?php unset($_SESSION['email_error']); endif; ?>

        <form action="send_otp.php" method="POST" onsubmit="return validateForm()">
            <div class="form-group">
                <label>Full Name</label>
                <div class="input-wrap">
                    <i class="icon fa-solid fa-user"></i>
                    <input type="text" name="name" placeholder="Your full name" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <div class="input-wrap">
                    <i class="icon fa-regular fa-envelope"></i>
                    <input type="email" name="email" placeholder="you@example.com" required>
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap">
                    <i class="icon fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password"
                           placeholder="Create a strong password"
                           oninput="checkStrength()" required>
                    <button type="button" class="toggle-pwd" onclick="togglePwd('password',this)">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
                <div class="strength-wrap">
                    <div class="strength-track"><div class="strength-fill" id="strengthFill"></div></div>
                    <div class="strength-label" id="strengthLabel" style="color:#aaa;">Enter password</div>
                </div>
                <div class="pwd-hints">
                    <span class="hint-pill" id="h-len">8+ chars</span>
                    <span class="hint-pill" id="h-upper">Uppercase</span>
                    <span class="hint-pill" id="h-lower">Lowercase</span>
                    <span class="hint-pill" id="h-num">Number</span>
                    <span class="hint-pill" id="h-sym">Symbol</span>
                </div>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <div class="input-wrap">
                    <i class="icon fa-solid fa-lock"></i>
                    <input type="password" id="confirm" placeholder="Repeat your password"
                           oninput="checkMatch()" required>
                    <button type="button" class="toggle-pwd" onclick="togglePwd('confirm',this)">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
                <div class="match-msg" id="matchMsg"></div>
            </div>

            <button type="submit" id="submitBtn" class="btn-submit" disabled>
                <i class="fa-solid fa-paper-plane"></i> Send OTP to Email
            </button>
        </form>

        <div class="login-link">Already have an account? <a href="login.php">Sign In</a></div>

    <?php else: ?>

        <!-- OTP SCREEN -->
        <div class="otp-screen">
            <div class="otp-icon">📧</div>
            <h2>Check Your Email</h2>
            <p>We sent a 4-digit OTP to your email.<br>Enter it below to complete registration.</p>
        </div>

        <form action="verify_otp.php" method="POST">
            <div class="form-group">
                <label>Enter OTP</label>
                <div class="input-wrap">
                    <i class="icon fa-solid fa-key"></i>
                    <input type="text" name="otp" class="otp-input"
                           placeholder="_ _ _ _" maxlength="4"
                           pattern="\d{4}" required autofocus>
                </div>
            </div>
            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-circle-check"></i> Verify & Create Account
            </button>
        </form>
        <a href="clear_otp.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Go back & re-enter details</a>

    <?php endif; ?>

    </div>
</div>

<script>
function togglePwd(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-regular fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa-regular fa-eye';
    }
}

let strengthScore = 0;

function checkStrength() {
    const pwd  = document.getElementById('password').value;
    const fill = document.getElementById('strengthFill');
    const lbl  = document.getElementById('strengthLabel');

    const checks = {
        len:   pwd.length >= 8,
        upper: /[A-Z]/.test(pwd),
        lower: /[a-z]/.test(pwd),
        num:   /[0-9]/.test(pwd),
        sym:   /[^A-Za-z0-9]/.test(pwd),
    };

    // Update hint pills
    document.getElementById('h-len').classList.toggle('ok',   checks.len);
    document.getElementById('h-upper').classList.toggle('ok', checks.upper);
    document.getElementById('h-lower').classList.toggle('ok', checks.lower);
    document.getElementById('h-num').classList.toggle('ok',   checks.num);
    document.getElementById('h-sym').classList.toggle('ok',   checks.sym);

    strengthScore = Object.values(checks).filter(Boolean).length;
    const pct = (strengthScore / 5) * 100;

    let color, label;
    if (strengthScore <= 2)      { color='#e53935'; label='Weak'; }
    else if (strengthScore === 3){ color='#ff9100'; label='Fair'; }
    else if (strengthScore === 4){ color='#ffc107'; label='Good'; }
    else                          { color='#00c853'; label='Strong 💪'; }

    fill.style.width      = pct + '%';
    fill.style.background = color;
    lbl.style.color       = color;
    lbl.textContent       = label;

    checkMatch();
    updateSubmit();
}

function checkMatch() {
    const pwd = document.getElementById('password').value;
    const con = document.getElementById('confirm').value;
    const msg = document.getElementById('matchMsg');
    if (!con) { msg.textContent = ''; return; }
    if (pwd === con) {
        msg.textContent = '✓ Passwords match';
        msg.className   = 'match-msg ok';
    } else {
        msg.textContent = '✗ Passwords do not match';
        msg.className   = 'match-msg no';
    }
    updateSubmit();
}

function updateSubmit() {
    const pwd = document.getElementById('password').value;
    const con = document.getElementById('confirm').value;
    const ok  = strengthScore >= 4 && pwd === con && con.length > 0;
    document.getElementById('submitBtn').disabled = !ok;
}

function validateForm() {
    const pwd = document.getElementById('password').value;
    const con = document.getElementById('confirm').value;
    if (strengthScore < 4) { alert('Please use a stronger password.'); return false; }
    if (pwd !== con)        { alert('Passwords do not match.'); return false; }
    return true;
}
</script>
</body>
</html>
