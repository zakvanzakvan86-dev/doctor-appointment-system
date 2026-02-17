<?php
session_start();

/* Clear only registration related session */
unset($_SESSION['otp']);
unset($_SESSION['reg_name']);
unset($_SESSION['reg_email']);
unset($_SESSION['reg_pass']);

header("Location: register.php");
exit;
