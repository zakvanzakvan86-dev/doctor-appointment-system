<?php
session_start();
require "db.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/PHPMailer/Exception.php";
require __DIR__ . "/PHPMailer/PHPMailer.php";
require __DIR__ . "/PHPMailer/SMTP.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.php");
    exit;
}

$name     = trim($_POST['name']);
$email    = trim($_POST['email']);
$password = $_POST['password'];

/* ✅ EMAIL EXISTS CHECK */
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {

    // 🔥 CLEAR OLD OTP SESSIONS
    unset($_SESSION['otp']);
    unset($_SESSION['reg_name']);
    unset($_SESSION['reg_email']);
    unset($_SESSION['reg_pass']);

    $_SESSION['email_error'] = "exists";
    $stmt->close();

    header("Location: register.php");
    exit;
}
$stmt->close();

/* ✅ CREATE OTP */
$otp = random_int(1000, 9999);

$_SESSION['otp']       = (string)$otp;
$_SESSION['reg_name']  = $name;
$_SESSION['reg_email'] = $email;
$_SESSION['reg_pass']  = password_hash($password, PASSWORD_DEFAULT);

/* ✅ SEND MAIL */
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = "smtp.gmail.com";
    $mail->SMTPAuth   = true;
    $mail->Username   = "zakvanzakvan86@gmail.com";
    $mail->Password   = "vgdskolzqmns yzck";
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom("zakvanzakvan86@gmail.com", "Doctor App");
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = "Doctor App OTP";
    $mail->Body    = "<h2>Your OTP is <b>$otp</b></h2>";

    $mail->send();
    header("Location: register.php");
    exit;

} catch (Exception $e) {
    die("Mail error");
}
