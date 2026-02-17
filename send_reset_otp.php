<?php
session_start();
require "db.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/PHPMailer/Exception.php";
require __DIR__ . "/PHPMailer/PHPMailer.php";
require __DIR__ . "/PHPMailer/SMTP.php";

$email = $_POST['email'];

$check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
if (mysqli_num_rows($check) != 1) {
    echo "Email not registered <a href='forgot_password.php'>Try again</a>";
    exit;
}

$otp = rand(1000, 9999);
$_SESSION['reset_otp'] = $otp;
$_SESSION['reset_email'] = $email;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->Username = "zakvanzakvan86@gmail.com";
    $mail->Password = "vgdskolzqmns yzck";
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom("zakvanzakvan86@gmail.com", "Doctor App");
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = "Password Reset OTP";
    $mail->Body = "<h2>Your OTP is <b>$otp</b></h2>";

    $mail->send();
    header("Location: reset_password.php");
    exit;

} catch (Exception $e) {
    echo "OTP not sent";
}
