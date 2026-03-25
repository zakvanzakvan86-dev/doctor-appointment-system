<?php
session_start();
require "db.php";

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];
    $sql      = "SELECT * FROM users WHERE email='".mysqli_real_escape_string($conn,$email)."'";
    $result   = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($password, $row["password"])) {
            $_SESSION["user"]     = $row["email"];
            $_SESSION["username"] = $row["fullname"];
            $_SESSION["user_id"]  = $row["id"];
            $_SESSION["role"]     = $row["role"];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Login – Doctor App</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Nunito',sans-serif;min-height:100vh;display:flex;background:#f0f4ff;}

/* LEFT PANEL */
.left-panel{
    width:55%;background:linear-gradient(145deg,#1565c0 0%,#1a73e8 50%,#42a5f5 100%);
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    padding:48px;position:relative;overflow:hidden;
}
.left-panel::before{content:'';position:absolute;top:-80px;right:-80px;width:300px;height:300px;background:rgba(255,255,255,.07);border-radius:50%;}
.left-panel::after{content:'';position:absolute;bottom:-100px;left:-60px;width:280px;height:280px;background:rgba(255,255,255,.05);border-radius:50%;}

.brand{display:flex;align-items:center;gap:14px;margin-bottom:48px;position:relative;z-index:1;}
.brand-icon{width:52px;height:52px;background:rgba(255,255,255,.2);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:24px;border:2px solid rgba(255,255,255,.3);}
.brand-name{font-family:'Poppins',sans-serif;font-size:24px;font-weight:700;color:#fff;letter-spacing:.3px;}

.illustration{font-size:110px;margin-bottom:32px;position:relative;z-index:1;animation:float 3s ease-in-out infinite;}
@keyframes float{0%,100%{transform:translateY(0);}50%{transform:translateY(-12px);}}

.left-text{text-align:center;position:relative;z-index:1;}
.left-text h2{font-size:28px;font-weight:800;color:#fff;margin-bottom:10px;}
.left-text p{font-size:15px;color:rgba(255,255,255,.78);line-height:1.7;max-width:340px;}

.features{display:flex;flex-direction:column;gap:12px;margin-top:28px;position:relative;z-index:1;width:100%;max-width:360px;}
.feat-item{display:flex;align-items:center;gap:12px;background:rgba(255,255,255,.12);border-radius:12px;padding:12px 16px;border:1px solid rgba(255,255,255,.18);}
.feat-item i{font-size:16px;color:#fff;width:20px;text-align:center;}
.feat-item span{font-size:13.5px;color:rgba(255,255,255,.9);font-weight:600;}

/* RIGHT PANEL */
.right-panel{width:45%;display:flex;align-items:center;justify-content:center;padding:48px 40px;background:#fff;}

.login-box{width:100%;max-width:400px;}
.login-box h1{font-size:28px;font-weight:800;color:#1a1a2e;margin-bottom:6px;}
.login-box .subtitle{font-size:14px;color:#888;margin-bottom:32px;}

.form-group{margin-bottom:18px;}
.form-group label{display:block;font-size:12px;font-weight:700;color:#555;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px;}
.input-wrap{position:relative;}
.input-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#aaa;font-size:15px;}
.input-wrap input{
    width:100%;padding:13px 14px 13px 42px;
    border:2px solid #e8edf5;border-radius:12px;
    font-family:'Nunito',sans-serif;font-size:14px;color:#1a1a2e;
    outline:none;transition:.2s;background:#f8faff;
}
.input-wrap input:focus{border-color:#1a73e8;background:#fff;box-shadow:0 0 0 3px rgba(26,115,232,.08);}
.toggle-pwd{position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#aaa;font-size:15px;border:none;background:none;}
.toggle-pwd:hover{color:#1a73e8;}

.error-box{background:#fce4ec;color:#c62828;border:1.5px solid #ef9a9a;border-radius:10px;padding:11px 14px;font-size:13.5px;font-weight:600;margin-bottom:18px;display:flex;align-items:center;gap:8px;}

.btn-login{
    width:100%;padding:14px;border-radius:12px;border:none;
    background:linear-gradient(135deg,#1a73e8,#1565c0);color:#fff;
    font-family:'Nunito',sans-serif;font-size:15px;font-weight:800;
    cursor:pointer;transition:.2s;
    box-shadow:0 4px 16px rgba(26,115,232,.35);
    display:flex;align-items:center;justify-content:center;gap:10px;
}
.btn-login:hover{transform:translateY(-2px);box-shadow:0 6px 22px rgba(26,115,232,.45);}

.divider{display:flex;align-items:center;gap:12px;margin:20px 0;color:#ccc;font-size:13px;}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#e8edf5;}

.links{display:flex;justify-content:space-between;margin-top:20px;}
.links a{font-size:13.5px;color:#1a73e8;text-decoration:none;font-weight:700;}
.links a:hover{text-decoration:underline;}

.register-link{text-align:center;margin-top:24px;font-size:14px;color:#888;}
.register-link a{color:#1a73e8;font-weight:800;text-decoration:none;}
.register-link a:hover{text-decoration:underline;}

@media(max-width:768px){
    body{flex-direction:column;}
    .left-panel{width:100%;padding:32px 24px;min-height:auto;}
    .illustration{font-size:70px;margin-bottom:20px;}
    .features{display:none;}
    .right-panel{width:100%;padding:32px 24px;}
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
    <div class="illustration">🏥</div>
    <div class="left-text">
        <h2>Your Health, Our Priority</h2>
        <p>Book appointments, consult doctors, and manage your health — all in one place.</p>
    </div>
    <div class="features">
        <div class="feat-item"><i class="fa-solid fa-calendar-check"></i><span>Easy appointment booking</span></div>
        <div class="feat-item"><i class="fa-solid fa-user-doctor"></i><span>14+ specialist doctors</span></div>
        <div class="feat-item"><i class="fa-solid fa-robot"></i><span>AI health assistant</span></div>
        <div class="feat-item"><i class="fa-solid fa-shield-halved"></i><span>Secure OTP verification</span></div>
    </div>
</div>

<!-- RIGHT -->
<div class="right-panel">
    <div class="login-box">
        <h1>Welcome Back 👋</h1>
        <p class="subtitle">Sign in to your Doctor App account</p>

        <?php if ($error): ?>
        <div class="error-box"><i class="fa-solid fa-circle-xmark"></i><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <div class="input-wrap">
                    <i class="fa-regular fa-envelope"></i>
                    <input type="email" name="email" placeholder="you@example.com"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" id="loginPwd" placeholder="Enter your password" required>
                    <button type="button" class="toggle-pwd" onclick="togglePwd('loginPwd',this)">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In
            </button>
        </form>

        <div class="links">
            <a href="forgot_password.php"><i class="fa-solid fa-key"></i> Forgot Password?</a>
        </div>

        <div class="divider">or</div>

        <div class="register-link">
            Don't have an account? <a href="register.php">Create Account</a>
        </div>
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
</script>
</body>
</html>
