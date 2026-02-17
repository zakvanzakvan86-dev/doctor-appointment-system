<?php
session_start();
require "db.php";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="style.css">

    <style>
        body {
            background: linear-gradient(to bottom right, #e3f2fd, #ede7f6);
            font-family: 'Segoe UI', sans-serif;
        }
        .navbar {
            background: #7b1fa2;
            color: white;
            padding: 18px;
            font-size: 24px;
            text-align: center;
            font-weight: bold;
        }
        .box {
            width: 420px;
            margin: 80px auto;
            background: #fff;
            padding: 30px;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        input {
            width: 100%;
            padding: 12px;
            margin-top: 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #7b1fa2;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 15px;
        }
        button:disabled {
            background: #bbb;
            cursor: not-allowed;
        }
        a { color: #7b1fa2; text-decoration: none; }

        #strength-box {
            width: 100%;
            height: 10px;
            background: #eee;
            border-radius: 6px;
            margin-top: 8px;
        }
        #strength-bar {
            height: 10px;
            width: 0%;
            border-radius: 6px;
        }
        #strength-text {
            text-align: center;
            margin-top: 6px;
            padding: 6px;
            border-radius: 6px;
            font-weight: 500;
            background: #f8d7da;
        }
        .hint {
            font-size: 13px;
            color: #555;
            margin-top: 6px;
        }
        .notice-box {
            background: #fff3cd;
            color: #856404;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 12px;
            border: 1px solid #ffeeba;
        }
        .error-box {
            display: none;
            background: #fdecea;
            color: #d32f2f;
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 14px;
        }

        /* ✅ Back button style (ONLY NEW) */
        .back-btn {
            display: block;
            text-align: center;
            margin-top: 12px;
            font-size: 14px;
        }
    </style>
</head>

<body>

<div class="navbar">Doctor App</div>

<div class="box">

<?php if (!isset($_SESSION['otp'])) { ?>

<h2 style="text-align:center;">Create Account</h2>
<p style="text-align:center;">Secure registration using email OTP</p>

<?php
if (isset($_SESSION['email_error'])) {
    echo '<div class="notice-box">
            This email is already registered.
            <a href="login.php">Login instead</a>
          </div>';
    unset($_SESSION['email_error']);
}
?>

<form action="send_otp.php" method="POST" onsubmit="return validateForm();">

    <input type="text" name="name" placeholder="Full Name" required>
    <input type="email" name="email" placeholder="Email" required>

    <input type="password" id="password" name="password"
           placeholder="Password" onkeyup="checkStrength()" required>

    <div id="strength-box">
        <div id="strength-bar"></div>
    </div>

    <div id="strength-text">Very Weak</div>

    <div class="hint">
        Hint: Use upper & lower case letters, numbers, and symbols like ! ? $ % ^ & *
    </div>

    <div class="error-box" id="strength-error">
        ❗ Password strength is not strong enough.
    </div>

    <input type="password" id="confirm"
           placeholder="Confirm Password" onkeyup="checkMatch()" required>

    <div class="error-box" id="match-error">
        ❗ Passwords do not match.
    </div>

    <button type="submit" id="submitBtn" disabled>Send OTP</button>
</form>

<p style="text-align:center;margin-top:15px;">
    Already have an account? <a href="login.php">Login</a>
</p>

<?php } else { ?>

<h2 style="text-align:center;">Verify OTP</h2>
<p style="text-align:center;">Enter the OTP sent to your email</p>

<form action="verify_otp.php" method="POST">
    <input type="text" name="otp" placeholder="Enter 4-digit OTP" required>
    <button type="submit">Verify OTP</button>
</form>

<!-- ✅ ONLY ADDITION -->
<a href="clear_otp.php" class="back-btn">← Back</a>

<?php } ?>

</div>

<script>
function checkStrength() {
    const pwd = document.getElementById("password").value;
    const bar = document.getElementById("strength-bar");
    const text = document.getElementById("strength-text");
    let s = 0;

    if (pwd.length >= 8) s++;
    if (/[A-Z]/.test(pwd)) s++;
    if (/[a-z]/.test(pwd)) s++;
    if (/[0-9]/.test(pwd)) s++;
    if (/[^A-Za-z0-9]/.test(pwd)) s++;

    if (s <= 2) {
        bar.style.width = "25%";
        bar.style.background = "#f44336";
        text.innerText = "Very Weak";
        text.style.background = "#f8d7da";
    } else if (s == 3) {
        bar.style.width = "50%";
        bar.style.background = "#ff9800";
        text.innerText = "Weak";
        text.style.background = "#ffe0b2";
    } else if (s == 4) {
        bar.style.width = "75%";
        bar.style.background = "#fbc02d";
        text.innerText = "Good";
        text.style.background = "#fff3cd";
    } else {
        bar.style.width = "100%";
        bar.style.background = "#4caf50";
        text.innerText = "Strong";
        text.style.background = "#d4edda";
    }
    validateForm();
}

function checkMatch() {
    const pwd = document.getElementById("password").value;
    const c = document.getElementById("confirm").value;
    document.getElementById("match-error").style.display =
        pwd && c && pwd !== c ? "block" : "none";
    validateForm();
}

function validateForm() {
    const strength = document.getElementById("strength-text").innerText;
    const pwd = document.getElementById("password").value;
    const c = document.getElementById("confirm").value;

    const ok = (strength === "Good" || strength === "Strong") && pwd === c;

    document.getElementById("strength-error").style.display =
        ok ? "none" : "block";

    document.getElementById("submitBtn").disabled = !ok;
    return ok;
}
</script>

</body>
</html>
