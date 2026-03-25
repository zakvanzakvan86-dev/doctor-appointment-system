<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// This logic is now handled directly inside feedback.php
// This file is kept for backward compatibility — redirect to feedback.php
header("Location: feedback.php");
exit();
?>
